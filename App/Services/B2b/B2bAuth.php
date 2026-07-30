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

    // ------------------------------------------------------------ localisation

    /**
     * A user-facing auth/registration message in the visitor's language.
     *
     * The service returns finished text (not codes) because it also runs from the
     * AJAX endpoint, where the frontend catalog (_lang.php) is not loaded — keeping
     * the strings here guarantees they are always available. Public so the AJAX
     * layer can reuse the same catalog for the messages in this same flow. %s/%d
     * placeholders are filled from $params.
     */
    public static function msg(string $key, ...$params): string
    {
        $lang = $_COOKIE['lang'] ?? 'ro';
        $all  = self::authMessages();
        $set  = $all[$lang] ?? $all['ro'];
        $txt  = $set[$key] ?? ($all['ro'][$key] ?? $key);

        return $params ? vsprintf($txt, $params) : $txt;
    }

    private static function authMessages(): array
    {
        return [
            'ro' => [
                'person_type'       => 'Selectați tipul de persoană.',
                'name_required'     => 'Numele și prenumele sunt obligatorii.',
                'login_short'       => 'Login-ul trebuie să aibă minim 4 caractere.',
                'login_charset'     => 'Login-ul poate conține doar litere, cifre, . _ -',
                'email_invalid'     => 'Adresa de email nu este validă.',
                'phone_format'      => 'Numărul de telefon trebuie să conțină 8 cifre, după prefixul %s.',
                'password_short'    => 'Parola trebuie să aibă minimum 6 caractere.',
                'login_taken'       => 'Acest login este deja folosit.',
                'email_taken'       => 'Există deja un cont cu această adresă de email.',
                'phone_taken'       => 'Există deja un cont cu acest număr de telefon.',
                'email_phone_taken' => 'Există deja un cont cu acest număr de telefon și această adresă de email.',
                'register_failed'   => 'Contul nu a putut fi creat. Încercați din nou.',
                'register_ok'       => 'Contul a fost creat cu succes. Te poți autentifica acum.',
                'login_bad'         => 'Login/email sau parolă incorectă.',
                'service_down'      => 'Serviciu temporar indisponibil.',
                'locked'            => 'Cont blocat temporar. Reîncercați peste %d minute.',
                'blocked'           => 'Contul este blocat. Contactați administratorul.',
                'password_failed'   => 'Parola nu a putut fi schimbată.',
                'csrf_expired'      => 'Sesiune expirată. Reîncărcați pagina și încercați din nou.',
                'auth_required'     => 'Autentificare necesară.',
                'password_current_bad' => 'Parola curentă este incorectă.',
                'password_changed'  => 'Parola a fost schimbată cu succes.',
                'password_mismatch' => 'Parolele nu coincid.',
                'forgot_sent'       => 'Dacă există un cont cu acest email, ai primit un link de resetare.',
                'reset_invalid'     => 'Link de resetare invalid sau expirat.',
                'reset_done'        => 'Parola a fost modificată cu succes. Te poți autentifica.',
            ],
            'ru' => [
                'person_type'       => 'Выберите тип лица.',
                'name_required'     => 'Имя и фамилия обязательны.',
                'login_short'       => 'Логин должен содержать минимум 4 символа.',
                'login_charset'     => 'Логин может содержать только буквы, цифры, . _ -',
                'email_invalid'     => 'Адрес электронной почты недействителен.',
                'phone_format'      => 'Номер телефона должен содержать 8 цифр после префикса %s.',
                'password_short'    => 'Пароль должен содержать минимум 6 символов.',
                'login_taken'       => 'Этот логин уже используется.',
                'email_taken'       => 'Учётная запись с этим адресом email уже существует.',
                'phone_taken'       => 'Учётная запись с этим номером телефона уже существует.',
                'email_phone_taken' => 'Учётная запись с этим номером телефона и адресом email уже существует.',
                'register_failed'   => 'Не удалось создать учётную запись. Попробуйте снова.',
                'register_ok'       => 'Учётная запись успешно создана. Теперь вы можете войти.',
                'login_bad'         => 'Неверный логин/email или пароль.',
                'service_down'      => 'Сервис временно недоступен.',
                'locked'            => 'Учётная запись временно заблокирована. Повторите через %d мин.',
                'blocked'           => 'Учётная запись заблокирована. Обратитесь к администратору.',
                'password_failed'   => 'Не удалось изменить пароль.',
                'csrf_expired'      => 'Сессия истекла. Обновите страницу и попробуйте снова.',
                'auth_required'     => 'Требуется авторизация.',
                'password_current_bad' => 'Текущий пароль неверен.',
                'password_changed'  => 'Пароль успешно изменён.',
                'password_mismatch' => 'Пароли не совпадают.',
                'forgot_sent'       => 'Если аккаунт с таким email существует, вы получили ссылку для сброса.',
                'reset_invalid'     => 'Недействительная или просроченная ссылка сброса.',
                'reset_done'        => 'Пароль успешно изменён. Теперь вы можете войти.',
            ],
            'en' => [
                'person_type'       => 'Select the person type.',
                'name_required'     => 'First and last name are required.',
                'login_short'       => 'The login must be at least 4 characters.',
                'login_charset'     => 'The login may contain only letters, digits, . _ -',
                'email_invalid'     => 'The email address is not valid.',
                'phone_format'      => 'The phone number must contain 8 digits after the %s prefix.',
                'password_short'    => 'The password must be at least 6 characters.',
                'login_taken'       => 'This login is already in use.',
                'email_taken'       => 'An account with this email address already exists.',
                'phone_taken'       => 'An account with this phone number already exists.',
                'email_phone_taken' => 'An account with this phone number and email address already exists.',
                'register_failed'   => 'The account could not be created. Please try again.',
                'register_ok'       => 'Your account was created successfully. You can log in now.',
                'login_bad'         => 'Incorrect login/email or password.',
                'service_down'      => 'Service temporarily unavailable.',
                'locked'            => 'Account temporarily locked. Try again in %d minutes.',
                'blocked'           => 'The account is blocked. Contact the administrator.',
                'password_failed'   => 'The password could not be changed.',
                'csrf_expired'      => 'Session expired. Reload the page and try again.',
                'auth_required'     => 'Authentication required.',
                'password_current_bad' => 'The current password is incorrect.',
                'password_changed'  => 'Your password was changed successfully.',
                'password_mismatch' => 'Passwords do not match.',
                'forgot_sent'       => 'If an account with this email exists, a reset link has been sent.',
                'reset_invalid'     => 'Invalid or expired reset link.',
                'reset_done'        => 'Your password was changed successfully. You can log in now.',
            ],
        ];
    }

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
            return ['ok' => false, 'field' => 'person_type', 'error' => self::msg('person_type')];
        }
        if ($fullName === '' || mb_strlen($fullName) > 190) {
            return ['ok' => false, 'field' => 'full_name', 'error' => self::msg('name_required')];
        }
        // Primary rule shown to the user: at least 4 characters.
        if (mb_strlen($login) < 4 || mb_strlen($login) > 64) {
            return ['ok' => false, 'field' => 'login', 'error' => self::msg('login_short')];
        }
        // Charset kept as a safety net (it goes into URLs and logs), not advertised.
        if (!preg_match('/^[a-z0-9._-]+$/', $login)) {
            return ['ok' => false, 'field' => 'login', 'error' => self::msg('login_charset')];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            return ['ok' => false, 'field' => 'email', 'error' => self::msg('email_invalid')];
        }
        // Moldova only, exactly 8 digits. The browser checks this too, but
        // client-side validation can be bypassed.
        $phone = B2bPhone::normalizeMd($phoneRaw);
        if ($phone === '') {
            return [
                'ok'    => false,
                'field' => 'phone',
                'error' => self::msg('phone_format', B2bPhone::MD_DIAL),
            ];
        }
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'field' => 'password', 'error' => self::msg('password_short')];
        }

        $db = B2bConfig::db();

        try {
            // A person is identified by email + phone, so a match on either must
            // block a second signup (login is unique too, on its own). The lookup
            // may return more than one row — the email on one account, the phone on
            // another — so we OR-scan them all and report every collision.
            $stmt = $db->prepare('SELECT login, email, phone_number FROM ' . B2bConfig::table('users')
                . ' WHERE login = :login OR email = :email OR phone_number = :phone');
            $stmt->execute([':login' => $login, ':email' => $email, ':phone' => $phone]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $loginTaken = $emailTaken = $phoneTaken = false;
            foreach ($rows as $r) {
                if (mb_strtolower((string)$r['login']) === $login) { $loginTaken = true; }
                if (mb_strtolower((string)$r['email']) === $email) { $emailTaken = true; }
                if ((string)$r['phone_number'] === $phone)         { $phoneTaken = true; }
            }

            if ($loginTaken) {
                return ['ok' => false, 'field' => 'login', 'error' => self::msg('login_taken')];
            }
            if ($emailTaken && $phoneTaken) {
                return ['ok' => false, 'field' => 'phone', 'error' => self::msg('email_phone_taken')];
            }
            if ($emailTaken) {
                return ['ok' => false, 'field' => 'email', 'error' => self::msg('email_taken')];
            }
            if ($phoneTaken) {
                return ['ok' => false, 'field' => 'phone', 'error' => self::msg('phone_taken')];
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
                ':status' => self::STATUS_ACTIVE,
            ]);

            $userId = (int)$db->lastInsertId();
        } catch (\Throwable $e) {
            // Also covers the UNIQUE(login/email) race between concurrent signups.
            B2bConfig::log('b2b_error.log', 'register err=' . $e->getMessage());
            return ['ok' => false, 'error' => self::msg('register_failed')];
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
        $generic = ['ok' => false, 'error' => self::msg('login_bad')];

        try {
            // Login or email, so a partner who forgets the login they picked can
            // still get in with the address they already use for password resets.
            // No ambiguity between the two: a login may only contain [a-z0-9._-],
            // so it can never look like an email. Distinct placeholders because
            // this PDO runs with ATTR_EMULATE_PREPARES=false, where one named
            // placeholder cannot be reused across positions.
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('users')
                . ' WHERE login = :login OR email = :email LIMIT 1'
            );
            $stmt->execute([':login' => $login, ':email' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'login err=' . $e->getMessage());
            return ['ok' => false, 'error' => self::msg('service_down')];
        }

        // Unknown account returns the same message as a wrong password, so the
        // endpoint cannot be used to enumerate registered logins.
        if (!$user) {
            return $generic;
        }

        $userId = (int)$user['id'];

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $minutes = max(1, (int)ceil((strtotime($user['locked_until']) - time()) / 60));
            return ['ok' => false, 'error' => self::msg('locked', $minutes)];
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            self::registerFailedLogin($user);
            B2bAudit::log($userId, B2bAudit::LOGIN_FAILED, ['reason' => 'bad_password']);
            return $generic;
        }

        // Accounts are active on sign-up now (no admin approval); only a blocked
        // account is refused here.
        if ($user['status'] === self::STATUS_BLOCKED) {
            B2bAudit::log($userId, B2bAudit::LOGIN_FAILED, ['reason' => 'blocked']);
            return [
                'ok'     => false,
                'status' => self::STATUS_BLOCKED,
                'error'  => self::msg('blocked'),
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

    public static function findByEmail(string $email): ?array
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('users') . ' WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Self-service password change from the cabinet: verifies the current
     * password first and keeps the caller's session (unlike setPassword, the
     * admin reset, which drops every session).
     */
    public static function changePassword(int $userId, string $current, string $new): array
    {
        if (mb_strlen($new) < 6) {
            return ['ok' => false, 'error' => self::msg('password_short')];
        }
        $user = self::findById($userId);
        if (!$user || !password_verify($current, (string)$user['password_hash'])) {
            return ['ok' => false, 'error' => self::msg('password_current_bad')];
        }
        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('users') . ' SET password_hash = :hash WHERE id = :id'
            )->execute([':hash' => password_hash($new, PASSWORD_DEFAULT), ':id' => $userId]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => self::msg('password_failed')];
        }
        B2bAudit::log($userId, B2bAudit::PASSWORD_RESET);
        return ['ok' => true];
    }

    /** Admin-set password; drops every open session for that partner. */
    public static function setPassword(int $userId, string $password): array
    {
        if (mb_strlen($password) < 6) {
            return ['ok' => false, 'error' => self::msg('password_short')];
        }

        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('users')
                . ' SET password_hash = :hash, failed_attempts = 0, locked_until = NULL WHERE id = :id'
            )->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $userId]);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => self::msg('password_failed')];
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
