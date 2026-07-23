<?php

namespace App\Services\B2b;

use PDO;

/**
 * B2B authentication: registration and login.
 *
 * Approval by the Super Admin is the only gate: an account starts `pending` and
 * cannot log in until an administrator activates it. There is no second factor.
 *
 * Sessions use a selector/validator pair: the cookie holds `selector.validator`
 * while the DB stores only a hash of the validator, so a leaked table dump
 * cannot be replayed as a session.
 */
class B2bAuth
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_ACTIVE  = 'active';
    private const STATUS_BLOCKED = 'blocked';

    /** Signup asks whether the partner is a legal entity or an individual. */
    public const PERSON_TYPES = ['company', 'individual'];

    /**
     * Name to show for a partner. Signup collects only full_name; company_name
     * is filled in later from the admin panel, and wins when present because
     * that is the name a proforma has to carry.
     */
    public static function displayName(array $user): string
    {
        $company = trim((string)($user['company_name'] ?? ''));
        return $company !== '' ? $company : trim((string)($user['full_name'] ?? ''));
    }

    private static ?array $current = null;
    private static bool $resolved = false;

    // ---------------------------------------------------------------- register

    /**
     * Creates an account with status `pending` (spec 2.1).
     *
     * @return array{ok: bool, error?: string, field?: string, user_id?: int}
     */
    public static function register(array $data): array
    {
        $personType = (string)($data['person_type'] ?? '');
        $login      = mb_strtolower(trim((string)($data['login'] ?? '')));
        $email      = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password   = (string)($data['password'] ?? '');
        $fullName   = trim((string)($data['full_name'] ?? ''));
        $phoneRaw   = trim((string)($data['phone_number'] ?? ''));

        if (!in_array($personType, self::PERSON_TYPES, true)) {
            return ['ok' => false, 'field' => 'person_type', 'error' => 'Selectați tipul de persoană.'];
        }
        if ($fullName === '' || mb_strlen($fullName) > 190) {
            return ['ok' => false, 'field' => 'full_name', 'error' => 'Numele și prenumele sunt obligatorii.'];
        }
        // Letters, digits, dot, dash and underscore: it goes into URLs and logs.
        if (!preg_match('/^[a-z0-9._-]{4,64}$/', $login)) {
            return ['ok' => false, 'field' => 'login', 'error' => 'Login-ul trebuie să aibă 4-64 caractere: litere, cifre, . _ -'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return ['ok' => false, 'field' => 'email', 'error' => 'Adresa de email nu este validă.'];
        }
        // Moldova only, exactly 8 digits. The browser checks this too, but
        // client-side validation can be bypassed.
        $phone = B2bPhone::normalizeMd($phoneRaw);
        if ($phone === '') {
            return [
                'ok'    => false,
                'field' => 'phone_number',
                'error' => 'Numărul de telefon trebuie să conțină 8 cifre, după prefixul '.B2bPhone::MD_DIAL.'.',
            ];
        }
        if (mb_strlen($password) < 8) {
            return ['ok' => false, 'field' => 'password', 'error' => 'Parola trebuie să aibă minimum 8 caractere.'];
        }

        $db = B2bConfig::db();

        try {
            $stmt = $db->prepare('SELECT login, email FROM ' . B2bConfig::table('users')
                . ' WHERE login = :login OR email = :email LIMIT 1');
            $stmt->execute([':login' => $login, ':email' => $email]);
            if ($taken = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return ($taken['login'] === $login)
                    ? ['ok' => false, 'field' => 'login', 'error' => 'Acest login este deja folosit.']
                    : ['ok' => false, 'field' => 'email', 'error' => 'Există deja un cont cu această adresă de email.'];
            }

            $db->prepare(
                'INSERT INTO ' . B2bConfig::table('users')
                . ' (person_type, login, email, password_hash, full_name, phone_number, status)
                   VALUES (:ptype, :login, :email, :hash, :name, :phone, :status)'
            )->execute([
                ':ptype'  => $personType,
                ':login'  => $login,
                ':email'  => $email,
                ':hash'   => password_hash($password, PASSWORD_DEFAULT),
                ':name'   => $fullName,
                ':phone'  => $phone,
                ':status' => self::STATUS_PENDING,
            ]);

            $userId = (int)$db->lastInsertId();
        } catch (\Throwable $e) {
            // Also covers the UNIQUE(login/email) race between concurrent signups.
            B2bConfig::log('b2b_error.log', 'register err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Contul nu a putut fi creat. Încercați din nou.'];
        }

        B2bAudit::log($userId, B2bAudit::REGISTER, ['login' => $login, 'person_type' => $personType]);

        return ['ok' => true, 'user_id' => $userId];
    }

    // ------------------------------------------------------------------- login

    /**
     * Verifies credentials and opens the session for an approved account.
     *
     * @return array{ok: bool, error?: string, status?: string, user_id?: int}
     */
    public static function login(string $login, string $password): array
    {
        $login   = mb_strtolower(trim($login));
        $generic = ['ok' => false, 'error' => 'Login sau parolă incorectă.'];

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('users') . ' WHERE login = :login LIMIT 1'
            );
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'login err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Serviciu temporar indisponibil.'];
        }

        // Unknown account returns the same message as a wrong password, so the
        // endpoint cannot be used to enumerate registered logins.
        if (!$user) {
            return $generic;
        }

        $userId = (int)$user['id'];

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $minutes = max(1, (int)ceil((strtotime($user['locked_until']) - time()) / 60));
            return ['ok' => false, 'error' => 'Cont blocat temporar. Reîncercați peste ' . $minutes . ' minute.'];
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            self::registerFailedLogin($user);
            B2bAudit::log($userId, B2bAudit::LOGIN_FAILED, ['reason' => 'bad_password']);
            return $generic;
        }

        // Password is correct but the account is not approved yet.
        if ($user['status'] === self::STATUS_PENDING) {
            B2bAudit::log($userId, B2bAudit::LOGIN_FAILED, ['reason' => 'pending']);
            return [
                'ok'     => false,
                'status' => self::STATUS_PENDING,
                'error'  => 'Contul este în așteptarea validării de către administrator.',
            ];
        }
        if ($user['status'] === self::STATUS_BLOCKED) {
            B2bAudit::log($userId, B2bAudit::LOGIN_FAILED, ['reason' => 'blocked']);
            return [
                'ok'     => false,
                'status' => self::STATUS_BLOCKED,
                'error'  => 'Contul este blocat. Contactați administratorul.',
            ];
        }

        self::resetFailedLogin($userId);

        // Approval by the Super Admin is the only gate, so an approved account
        // gets its session right here: no second factor to clear.
        self::createSession($userId);
        self::touchLogin($userId);
        B2bAudit::log($userId, B2bAudit::LOGIN, []);

        return ['ok' => true, 'user_id' => $userId];
    }

    // ----------------------------------------------------------------- session

    /** Opens a session for an approved account. */
    private static function createSession(int $userId): void
    {
        $selector  = bin2hex(random_bytes(16));   // 32 hex
        $validator = bin2hex(random_bytes(32));   // 64 hex
        $expires   = time() + B2bConfig::SESSION_TTL;

        try {
            B2bConfig::db()->prepare(
                'INSERT INTO ' . B2bConfig::table('sessions')
                . ' (b2b_user_id, selector, validator_hash, expires_at, ip_address, user_agent)
                   VALUES (:uid, :sel, :val, :exp, :ip, :ua)'
            )->execute([
                ':uid' => $userId,
                ':sel' => $selector,
                ':val' => hash('sha256', $validator),
                ':exp' => date('Y-m-d H:i:s', $expires),
                ':ip'  => B2bConfig::clientIp(),
                ':ua'  => B2bConfig::userAgent(),
            ]);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'createSession err=' . $e->getMessage());
            return; // no cookie without a stored session
        }

        self::setCookie($selector . '.' . $validator, $expires);
    }

    private static function setCookie(string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        setcookie(B2bConfig::COOKIE_NAME, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Keep $_COOKIE in sync so the same request already sees the new state.
        if ($expires > time()) {
            $_COOKIE[B2bConfig::COOKIE_NAME] = $value;
        } else {
            unset($_COOKIE[B2bConfig::COOKIE_NAME]);
        }
    }

    /** Authenticated partner for this request, resolved once. */
    public static function currentUser(): ?array
    {
        if (self::$resolved) {
            return self::$current;
        }
        self::$resolved = true;
        self::$current  = null;

        $raw = (string)($_COOKIE[B2bConfig::COOKIE_NAME] ?? '');
        if ($raw === '' || substr_count($raw, '.') !== 1) {
            return null;
        }

        [$selector, $validator] = explode('.', $raw, 2);
        if (!preg_match('/^[a-f0-9]{32}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $validator)) {
            return null;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT s.id AS sess_id, s.validator_hash, s.expires_at, u.*
                   FROM ' . B2bConfig::table('sessions') . ' AS s
                   JOIN ' . B2bConfig::table('users') . ' AS u ON u.id = s.b2b_user_id
                  WHERE s.selector = :sel LIMIT 1'
            );
            $stmt->execute([':sel' => $selector]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$row) {
            self::setCookie('', time() - 3600);
            return null;
        }

        if (!hash_equals((string)$row['validator_hash'], hash('sha256', $validator))) {
            // Valid selector with a wrong validator means a forged cookie.
            self::destroySession($selector);
            self::setCookie('', time() - 3600);
            B2bConfig::log('b2b_error.log', 'session validator mismatch selector=' . $selector);
            return null;
        }

        // Expired, or the account was blocked/reset while the session was open.
        if (strtotime((string)$row['expires_at']) < time() || $row['status'] !== self::STATUS_ACTIVE) {
            self::destroySession($selector);
            self::setCookie('', time() - 3600);
            return null;
        }

        unset($row['sess_id'], $row['validator_hash'], $row['expires_at'], $row['password_hash']);
        return self::$current = $row;
    }

    public static function isLoggedIn(): bool
    {
        return self::currentUser() !== null;
    }

    public static function currentUserId(): int
    {
        $u = self::currentUser();
        return $u ? (int)$u['id'] : 0;
    }

    public static function logout(): void
    {
        $userId = self::currentUserId();

        $raw = (string)($_COOKIE[B2bConfig::COOKIE_NAME] ?? '');
        if ($raw !== '' && substr_count($raw, '.') === 1) {
            self::destroySession(explode('.', $raw, 2)[0]);
        }

        self::setCookie('', time() - 3600);
        self::$current  = null;
        self::$resolved = true;

        if ($userId > 0) {
            B2bAudit::log($userId, B2bAudit::LOGOUT);
        }
    }

    private static function destroySession(string $selector): void
    {
        try {
            B2bConfig::db()->prepare(
                'DELETE FROM ' . B2bConfig::table('sessions') . ' WHERE selector = :sel'
            )->execute([':sel' => $selector]);
        } catch (\Throwable $e) {
            // ignored
        }
    }

    /** Used when an account is blocked, deleted or its password is reset. */
    public static function destroyAllSessions(int $userId): void
    {
        try {
            B2bConfig::db()->prepare(
                'DELETE FROM ' . B2bConfig::table('sessions') . ' WHERE b2b_user_id = :uid'
            )->execute([':uid' => $userId]);
        } catch (\Throwable $e) {
            // ignored
        }
    }

    // ----------------------------------------------------------------- helpers

    public static function findById(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('users') . ' WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Admin-set password; drops every open session for that partner. */
    public static function setPassword(int $userId, string $password): array
    {
        if (mb_strlen($password) < 8) {
            return ['ok' => false, 'error' => 'Parola trebuie să aibă minimum 8 caractere.'];
        }

        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('users')
                . ' SET password_hash = :hash, failed_attempts = 0, locked_until = NULL WHERE id = :id'
            )->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'Parola nu a putut fi schimbată.'];
        }

        self::destroyAllSessions($userId);
        B2bAudit::log($userId, B2bAudit::PASSWORD_RESET);

        return ['ok' => true];
    }

    private static function registerFailedLogin(array $user): void
    {
        $attempts = (int)$user['failed_attempts'] + 1;
        $lock = $attempts >= B2bConfig::LOGIN_MAX_ATTEMPTS
            ? date('Y-m-d H:i:s', time() + B2bConfig::LOGIN_LOCK_TTL)
            : null;

        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('users')
                . ' SET failed_attempts = :n, locked_until = :lock WHERE id = :id'
            )->execute([':n' => $attempts, ':lock' => $lock, ':id' => (int)$user['id']]);
        } catch (\Throwable $e) {
            // ignored
        }
    }

    private static function resetFailedLogin(int $userId): void
    {
        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('users')
                . ' SET failed_attempts = 0, locked_until = NULL WHERE id = :id'
            )->execute([':id' => $userId]);
        } catch (\Throwable $e) {
            // ignored
        }
    }

    /** Records the successful login. Bookkeeping: never fails the login itself. */
    private static function touchLogin(int $userId): void
    {
        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('users')
                . ' SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id'
            )->execute([':ip' => B2bConfig::clientIp(), ':id' => $userId]);
        } catch (\Throwable $e) {
            // ignored
        }

        self::$current  = self::findById($userId);
        self::$resolved = true;
    }
}
