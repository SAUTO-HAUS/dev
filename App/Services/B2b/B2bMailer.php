<?php

namespace App\Services\B2b;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Transactional emails to B2B partners. Reuses the site's PHPMailer plugin (the
 * same one the contact form uses, default mail() transport).
 *
 * Best-effort: any failure is logged and never blocks the admin action that
 * triggered it. The partner still learns the outcome by logging in.
 */
class B2bMailer
{
    /** Notify a partner that their account has been activated. */
    public static function sendActivation(array $user): bool
    {
        $to = trim((string)($user['email'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $name  = B2bAuth::displayName($user);
        $login = trim((string)($user['login'] ?? ''));
        $safe  = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        // Language = the script the partner typed their name in at registration:
        // Cyrillic ("Григоре Ботнаренко") -> Russian, Latin ("grigore botnarenco")
        // -> Romanian. The account language itself is not stored.
        $isRu    = preg_match('/\p{Cyrillic}/u', (string)($user['full_name'] ?? '')) === 1;
        $loginUrl = B2bNotifier::siteUrl() . '/' . ($isRu ? 'ru' : 'ro') . '/b2b-login';

        // The password is stored only as a secure hash, so it cannot be sent — the
        // partner logs in with the login shown here and the password they chose.
        if ($isRu) {
            $subject = 'Ваш аккаунт Sauto.md активирован';
            $body =
                '<p>Здравствуйте, ' . $safe($name) . '!</p>'
                . '<p>Ваш аккаунт на <b>Sauto.md</b> <b>активирован</b>.</p>'
                . '<p>Данные для входа:<br>Логин: <b>' . $safe($login) . '</b><br>'
                . 'Пароль: тот, который вы указали при регистрации.</p>'
                . '<p>Войти: <a href="' . $safe($loginUrl) . '">' . $safe($loginUrl) . '</a></p>';
        } else {
            $subject = 'Contul tău Sauto.md a fost activat';
            $body =
                '<p>Salut, ' . $safe($name) . '!</p>'
                . '<p>Contul tău pe <b>Sauto.md</b> a fost <b>activat</b>.</p>'
                . '<p>Date de acces:<br>Login: <b>' . $safe($login) . '</b><br>'
                . 'Parola: cea pe care ai ales-o la înregistrare.</p>'
                . '<p>Autentificare: <a href="' . $safe($loginUrl) . '">' . $safe($loginUrl) . '</a></p>';
        }

        return self::send($to, $name, $subject, $body);
    }

    private static function send(string $to, string $toName, string $subject, string $bodyHtml): bool
    {
        try {
            require_once _PLUGINS . '/PHPMailer/src/Exception.php';
            require_once _PLUGINS . '/PHPMailer/src/PHPMailer.php';
            require_once _PLUGINS . '/PHPMailer/src/SMTP.php';

            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('mesaj@sauto.md', 'Sauto.md');
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->AltBody = trim(strip_tags(str_replace(['</p>', '<hr>', '<br>', '<br/>'], "\n", $bodyHtml)));
            $mail->send();

            return true;
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_error.log', 'activation mail to=' . $to . ' err=' . $e->getMessage());
            return false;
        }
    }
}
