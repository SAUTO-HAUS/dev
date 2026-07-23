<?php

namespace App\Services\B2b;

use App\Services\Sms\SmsService;
use PDO;

/**
 * B2B authentication: registration and two-step login (password + SMS OTP).
 *
 * Security contract (spec 1.2.A + acceptance criteria):
 *   - login() never creates a session, no matter how valid the password is;
 *     it only issues the OTP and returns the user id;
 *   - the session is created exclusively in verifyOtp(), after the code checks out;
 *   - pending / blocked accounts are rejected at login, with no SMS sent.
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
        $email    = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');
        $company  = trim((string)($data['company_name'] ?? ''));
        $idno     = trim((string)($data['idno'] ?? ''));
        $repr     = trim((string)($data['representative_name'] ?? ''));
        $phoneRaw = trim((string)($data['phone_number'] ?? ''));

        if ($company === '' || mb_strlen($company) > 190) {
            return ['ok' => false, 'field' => 'company_name', 'error' => 'Denumirea companiei este obligatorie.'];
        }
        if (!preg_match('/^\d{13}$/', $idno)) {
            return ['ok' => false, 'field' => 'idno', 'error' => 'IDNO trebuie să conțină exact 13 cifre.'];
        }
        if ($repr === '' || mb_strlen($repr) > 190) {
            return ['ok' => false, 'field' => 'representative_name', 'error' => 'Numele reprezentantului este obligatoriu.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return ['ok' => false, 'field' => 'email', 'error' => 'Adresa de email nu este validă.'];
        }
        $phone = SmsService::normalize($phoneRaw);
        if ($phone === '') {
            return ['ok' => false, 'field' => 'phone_number', 'error' => 'Numărul de telefon nu este valid.'];
        }
        // Digit count for the detected country. The browser checks this too, but
        // a wrong number here means the OTP never arrives and the account is
        // unusable, so it is re-checked where it cannot be bypassed.
        $len = B2bCountries::validate($phone);
        if (!$len['ok']) {
            return ['ok' => false, 'field' => 'phone_number', 'error' => $len['error']];
        }
        if (mb_strlen($password) < 8) {
            return ['ok' => false, 'field' => 'password', 'error' => 'Parola trebuie să aibă minimum 8 caractere.'];
        }

        $db = B2bConfig::db();

        try {
            $stmt = $db->prepare('SELECT id FROM ' . B2bConfig::table('users') . ' WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn()) {
                return ['ok' => false, 'field' => 'email', 'error' => 'Există deja un cont cu această adresă de email.'];
            }

            $db->prepare(
                'INSERT INTO ' . B2bConfig::table('users')
                . ' (email, password_hash, company_name, idno, representative_name, phone_number, status)
                   VALUES (:email, :hash, :company, :idno, :repr, :phone, :status)'
            )->execute([
                ':email'   => $email,
                ':hash'    => password_hash($password, PASSWORD_DEFAULT),
                ':company' => $company,
                ':idno'    => $idno,
                ':repr'    => $repr,
                ':phone'   => $phone,
                ':status'  => self::STATUS_PENDING,
            ]);

            $userId = (int)$db->lastInsertId();
        } catch (\Throwable $e) {
            // Also covers the UNIQUE(email) race between two concurrent signups.
            B2bConfig::log('b2b_error.log', 'register err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Contul nu a putut fi creat. Încercați din nou.'];
        }

        B2bAudit::log($userId, B2bAudit::REGISTER, ['company' => $company, 'idno' => $idno]);

        return ['ok' => true, 'user_id' => $userId];
    }

    // ------------------------------------------------------------------- login

    /**
     * Step 1: verify credentials and send the OTP. Creates no session.
     *
     * @return array{ok: bool, error?: string, status?: string, user_id?: int, phone_hint?: string}
     */
    public static function login(string $email, string $password): array
    {
        $email   = mb_strtolower(trim($email));
        $generic = ['ok' => false, 'error' => 'Email sau parolă incorectă.'];

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('users') . ' WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'login err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Serviciu temporar indisponibil.'];
        }

        // Unknown account returns the same message as a wrong password, so the
        // endpoint cannot be used to enumerate registered emails.
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

        // Password is correct but the account is not approved yet: refuse, no SMS.
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

        $sent = self::issueOtp($userId, (string)$user['phone_number']);
        if (!$sent['ok']) {
            return $sent;
        }

        return [
            'ok'         => true,
            'user_id'    => $userId,
            'phone_hint' => self::maskPhone((string)$user['phone_number']),
        ];
    }

    /**
     * Issues a 6-digit OTP, stores it hashed and sends it by SMS.
     * Any previous unused code for the same user is burned first.
     *
     * @return array{ok: bool, error?: string}
     */
    public static function issueOtp(int $userId, string $phone): array
    {
        $db = B2bConfig::db();

        try {
            // Anti-flood: at most 3 codes per 5 minutes.
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM ' . B2bConfig::table('otp_codes')
                . ' WHERE b2b_user_id = :uid AND created_at > (NOW() - INTERVAL 5 MINUTE)'
            );
            $stmt->execute([':uid' => $userId]);
            if ((int)$stmt->fetchColumn() >= 3) {
                return ['ok' => false, 'error' => 'Prea multe coduri solicitate. Așteptați câteva minute.'];
            }

            $db->prepare(
                'UPDATE ' . B2bConfig::table('otp_codes') . ' SET used = 1 WHERE b2b_user_id = :uid AND used = 0'
            )->execute([':uid' => $userId]);

            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // OTP_TTL is a class constant, inlined because MySQL does not accept a
            // placeholder in the INTERVAL quantity with native prepares.
            $db->prepare(
                'INSERT INTO ' . B2bConfig::table('otp_codes')
                . ' (b2b_user_id, code_hash, expires_at, ip_address)
                   VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL ' . (int)B2bConfig::OTP_TTL . ' SECOND), :ip)'
            )->execute([
                ':uid'  => $userId,
                ':hash' => hash('sha256', $code),
                ':ip'   => B2bConfig::clientIp(),
            ]);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'issueOtp err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Codul nu a putut fi generat. Încercați din nou.'];
        }

        $minutes = (int)(B2bConfig::OTP_TTL / 60);
        $res = SmsService::send($phone, 'Codul dvs. de autentificare Sauto B2B: ' . $code . ' (valabil ' . $minutes . ' minute).');

        if (!$res['ok']) {
            return ['ok' => false, 'error' => $res['error'] ?? 'SMS-ul nu a putut fi trimis.'];
        }

        B2bAudit::log($userId, B2bAudit::OTP_SENT);
        return ['ok' => true];
    }

    public static function resendOtp(int $userId): array
    {
        $user = self::findById($userId);
        if (!$user || $user['status'] !== self::STATUS_ACTIVE) {
            return ['ok' => false, 'error' => 'Sesiune de autentificare invalidă.'];
        }
        return self::issueOtp($userId, (string)$user['phone_number']);
    }

    /**
     * Step 2: validate the OTP and open the session.
     *
     * @return array{ok: bool, error?: string, user?: array}
     */
    public static function verifyOtp(int $userId, string $code): array
    {
        $code = preg_replace('/\D+/', '', $code);
        if (strlen($code) !== 6) {
            return ['ok' => false, 'error' => 'Codul trebuie să conțină 6 cifre.'];
        }

        $user = self::findById($userId);
        if (!$user || $user['status'] !== self::STATUS_ACTIVE) {
            return ['ok' => false, 'error' => 'Sesiune de autentificare invalidă.'];
        }

        $db  = B2bConfig::db();
        $tbl = B2bConfig::table('otp_codes');

        try {
            $stmt = $db->prepare(
                'SELECT * FROM ' . $tbl . ' WHERE b2b_user_id = :uid AND used = 0 ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute([':uid' => $userId]);
            $otp = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otp) {
                B2bAudit::log($userId, B2bAudit::OTP_FAILED, ['reason' => 'no_code']);
                return ['ok' => false, 'error' => 'Nu există un cod activ. Solicitați unul nou.'];
            }

            if (strtotime((string)$otp['expires_at']) < time()) {
                $db->prepare('UPDATE ' . $tbl . ' SET used = 1 WHERE id = :id')->execute([':id' => $otp['id']]);
                B2bAudit::log($userId, B2bAudit::OTP_FAILED, ['reason' => 'expired']);
                return ['ok' => false, 'error' => 'Codul a expirat. Solicitați unul nou.'];
            }

            if ((int)$otp['attempts'] >= B2bConfig::OTP_MAX_ATTEMPTS) {
                $db->prepare('UPDATE ' . $tbl . ' SET used = 1 WHERE id = :id')->execute([':id' => $otp['id']]);
                B2bAudit::log($userId, B2bAudit::OTP_FAILED, ['reason' => 'max_attempts']);
                return ['ok' => false, 'error' => 'Prea multe încercări. Solicitați un cod nou.'];
            }

            if (!hash_equals((string)$otp['code_hash'], hash('sha256', $code))) {
                $db->prepare('UPDATE ' . $tbl . ' SET attempts = attempts + 1 WHERE id = :id')
                   ->execute([':id' => $otp['id']]);
                B2bAudit::log($userId, B2bAudit::OTP_FAILED, ['reason' => 'bad_code']);

                $left = B2bConfig::OTP_MAX_ATTEMPTS - ((int)$otp['attempts'] + 1);
                return [
                    'ok'    => false,
                    'error' => $left > 0
                        ? 'Cod incorect. Mai aveți ' . $left . ' încercări.'
                        : 'Cod incorect. Solicitați un cod nou.',
                ];
            }

            // Correct: burn it immediately so it cannot be replayed.
            $db->prepare('UPDATE ' . $tbl . ' SET used = 1 WHERE id = :id')->execute([':id' => $otp['id']]);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'verifyOtp err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Serviciu temporar indisponibil.'];
        }

        self::createSession($userId);

        try {
            $db->prepare(
                'UPDATE ' . B2bConfig::table('users')
                . ' SET last_login_at = NOW(), last_login_ip = :ip, failed_attempts = 0, locked_until = NULL
                   WHERE id = :id'
            )->execute([':ip' => B2bConfig::clientIp(), ':id' => $userId]);
        } catch (\Throwable $e) {
            // Bookkeeping only; must not fail an otherwise successful login.
        }

        B2bAudit::log($userId, B2bAudit::LOGIN);

        self::$current  = self::findById($userId);
        self::$resolved = true;

        return ['ok' => true, 'user' => self::$current];
    }

    // ----------------------------------------------------------------- session

    /** Called exclusively after a validated OTP. */
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

    /** +37360123456 -> +3736******56, enough to recognise, not enough to leak. */
    private static function maskPhone(string $phone): string
    {
        $len = strlen($phone);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($phone, 0, 5) . str_repeat('*', $len - 7) . substr($phone, -2);
    }
}
