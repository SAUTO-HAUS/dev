<?php

namespace App\Services\B2b;

use PDO;

/**
 * Self-service "forgot password" for B2B partners.
 *
 * A one-time token is emailed to the partner; only its SHA-256 hash is stored,
 * tokens expire (TTL) and are single-use. The request step is always silent —
 * it never reveals whether an email is registered — and rate-limited per account.
 *
 * The password itself is never emailed: the link carries only the token, and the
 * partner types the new password on our reset page.
 */
class B2bPasswordReset
{
    /** Token lifetime, seconds. */
    private const TTL = 3600; // 1 hour

    /** Max reset emails per account per hour (anti-abuse). */
    private const MAX_PER_HOUR = 5;

    /**
     * Create a token and email it — but only for an existing, non-blocked
     * account. Always returns void so the caller cannot tell whether the email
     * matched an account.
     */
    public static function request(string $email, string $lang): void
    {
        $user = B2bAuth::findByEmail($email);
        if (!$user || ($user['status'] ?? '') === 'blocked') {
            return; // silent — no account enumeration
        }
        $userId = (int)$user['id'];

        try {
            $c = B2bConfig::db()->prepare(
                'SELECT COUNT(*) FROM ' . B2bConfig::table('password_resets')
                . ' WHERE b2b_user_id = :uid AND created_at > (NOW() - INTERVAL 1 HOUR)'
            );
            $c->execute([':uid' => $userId]);
            if ((int)$c->fetchColumn() >= self::MAX_PER_HOUR) {
                return; // rate-limited, still silent
            }
        } catch (\Throwable $e) {
            return;
        }

        $raw  = bin2hex(random_bytes(32)); // 64 hex chars
        $hash = hash('sha256', $raw);

        try {
            B2bConfig::db()->prepare(
                'INSERT INTO ' . B2bConfig::table('password_resets')
                . ' (b2b_user_id, token_hash, expires_at, ip_address) VALUES (:uid, :h, :exp, :ip)'
            )->execute([
                ':uid' => $userId,
                ':h'   => $hash,
                ':exp' => date('Y-m-d H:i:s', time() + self::TTL),
                ':ip'  => B2bConfig::clientIp(),
            ]);
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'reset insert err=' . $e->getMessage());
            return;
        }

        self::sendEmail($user, $raw, $lang);
    }

    /** The reset row for a still-valid, unused token, else null. */
    public static function findValid(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || !ctype_xdigit($token)) {
            return null;
        }
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('password_resets')
                . ' WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW() LIMIT 1'
            );
            $stmt->execute([':h' => hash('sha256', $token)]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Set the new password and consume the token (and any other outstanding
     * tokens for that partner). Delegates the actual change to
     * B2bAuth::setPassword, which validates length and drops every session.
     *
     * @return array{ok: bool, error?: string}
     */
    public static function complete(string $token, string $newPassword): array
    {
        $row = self::findValid($token);
        if (!$row) {
            return ['ok' => false, 'error' => B2bAuth::msg('reset_invalid')];
        }

        $set = B2bAuth::setPassword((int)$row['b2b_user_id'], $newPassword);
        if (!$set['ok']) {
            return $set; // e.g. password too short
        }

        try {
            B2bConfig::db()->prepare(
                'UPDATE ' . B2bConfig::table('password_resets')
                . ' SET used_at = NOW() WHERE b2b_user_id = :uid AND used_at IS NULL'
            )->execute([':uid' => (int)$row['b2b_user_id']]);
        } catch (\Throwable $e) {
            // The password is already changed; failing to mark the token used is
            // non-fatal (it will simply expire).
        }

        return ['ok' => true];
    }

    // ------------------------------------------------------------------- email

    private static function sendEmail(array $user, string $rawToken, string $lang): void
    {
        $lang = in_array($lang, ['ro', 'ru', 'en'], true) ? $lang : 'ro';
        $link = B2bNotifier::siteUrl() . '/' . $lang . '/b2b-reset?token=' . $rawToken;
        $name = trim((string)($user['full_name'] ?? ''));
        $m    = self::emailStrings($lang, $name, $link);

        try {
            require_once _PLUGINS . '/PHPMailer/src/Exception.php';
            require_once _PLUGINS . '/PHPMailer/src/PHPMailer.php';
            require_once _PLUGINS . '/PHPMailer/src/SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('mesaj@sauto.md', 'Sauto.md');
            $mail->addAddress((string)$user['email']);
            $mail->isHTML(true);
            $mail->Subject = $m['subject'];
            $mail->Body    = $m['body'];
            $mail->AltBody = $m['alt'];
            $mail->send();
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'reset mail err=' . $e->getMessage());
        }
    }

    /** @return array{subject: string, body: string, alt: string} */
    private static function emailStrings(string $lang, string $name, string $link): array
    {
        $esc  = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $hi   = $name !== '' ? ' ' . $esc($name) : '';
        $href = $esc($link);

        $t = [
            'ro' => [
                'subject' => 'Resetare parolă — Sauto.md',
                'intro'   => 'Salut' . $hi . ',<br><br>Ai cerut resetarea parolei pentru contul tău Sauto.md. Apasă butonul de mai jos ca să setezi o parolă nouă:',
                'btn'     => 'Setează parola nouă',
                'note'    => 'Linkul expiră în 1 oră și poate fi folosit o singură dată. Dacă nu tu ai cerut resetarea, ignoră acest email — parola rămâne neschimbată.',
                'alt'     => "Resetare parolă Sauto.md. Deschide linkul pentru a seta o parolă nouă (expiră în 1 oră):\n" . $link,
            ],
            'ru' => [
                'subject' => 'Сброс пароля — Sauto.md',
                'intro'   => 'Здравствуйте' . $hi . ',<br><br>Вы запросили сброс пароля для вашего аккаунта Sauto.md. Нажмите кнопку ниже, чтобы задать новый пароль:',
                'btn'     => 'Задать новый пароль',
                'note'    => 'Ссылка действует 1 час и может быть использована один раз. Если вы не запрашивали сброс, просто игнорируйте это письмо — пароль останется прежним.',
                'alt'     => "Сброс пароля Sauto.md. Откройте ссылку, чтобы задать новый пароль (действует 1 час):\n" . $link,
            ],
            'en' => [
                'subject' => 'Password reset — Sauto.md',
                'intro'   => 'Hello' . $hi . ',<br><br>You requested a password reset for your Sauto.md account. Click the button below to set a new password:',
                'btn'     => 'Set a new password',
                'note'    => 'The link expires in 1 hour and can be used once. If you did not request this, just ignore this email — your password stays unchanged.',
                'alt'     => "Sauto.md password reset. Open the link to set a new password (expires in 1 hour):\n" . $link,
            ],
        ][$lang];

        $body =
            '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#222;font-size:15px;line-height:1.5;">'
          . '<p>' . $t['intro'] . '</p>'
          . '<p style="text-align:center;margin:28px 0;">'
          . '<a href="' . $href . '" style="display:inline-block;background:#e2001a;color:#fff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:8px;">' . $esc($t['btn']) . '</a>'
          . '</p>'
          . '<p style="color:#666;font-size:13px;">' . $esc($t['note']) . '</p>'
          . '<p style="color:#999;font-size:12px;word-break:break-all;">' . $href . '</p>'
          . '</div>';

        return ['subject' => $t['subject'], 'body' => $body, 'alt' => $t['alt']];
    }
}
