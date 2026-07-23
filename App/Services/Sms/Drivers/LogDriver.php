<?php

namespace App\Services\Sms\Drivers;

use App\Services\B2b\B2bConfig;
use App\Services\Sms\SmsDriverInterface;

/**
 * Development driver: sends nothing.
 *
 * Writes the message to logs/b2b_sms.log and, if `b2b_sms_debug_email` is set,
 * mails it as well. Lets the whole 2FA flow be tested before a provider is
 * contracted; switching to SMS.md/Twilio is a settings change, not a code change.
 */
class LogDriver implements SmsDriverInterface
{
    public function name(): string
    {
        return 'log';
    }

    public function send(string $to, string $message): array
    {
        B2bConfig::log('b2b_sms.log', 'TO=' . $to . ' MSG=' . $message);

        $email = B2bConfig::get('b2b_sms_debug_email');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mail($email, $to, $message);
        }

        return ['ok' => true, 'id' => 'log-' . time()];
    }

    private function mail(string $email, string $to, string $message): void
    {
        try {
            $root = $_SERVER['DOCUMENT_ROOT'] ?? '.';
            require_once $root . '/plugins/PHPMailer/src/Exception.php';
            require_once $root . '/plugins/PHPMailer/src/PHPMailer.php';
            require_once $root . '/plugins/PHPMailer/src/SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('noreply@sauto.md', 'Sauto B2B');
            $mail->addAddress($email);
            $mail->Subject = '[DEV] SMS B2B pentru ' . $to;
            $mail->Body    = $message;
            $mail->send();
        } catch (\Throwable $e) {
            B2bConfig::log('b2b_sms.log', 'debug-email FAILED: ' . $e->getMessage());
        }
    }
}
