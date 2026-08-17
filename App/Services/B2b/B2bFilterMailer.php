<?php

namespace App\Services\B2b;

use PDO;
use Throwable;

/**
 * Email alerts for saved searches ("Filtrele mele").
 *
 * The cabinet already shows a count on the header button; this is the same
 * event pushed to the address the partner registered with, so a filter is worth
 * saving even when nobody is on the site.
 *
 * Watermark: gh3sp_b2b_saved_filters.notified_at. Cars published after it are
 * what an email is about, and it is only moved once the message actually left —
 * a mail failure means the next run tries again instead of losing the alert.
 * It is deliberately NOT last_seen_at: opening the cabinet must not cancel an
 * email that was never sent.
 *
 * One message per partner, listing every filter that caught something, because
 * five saved searches must not mean five emails in the same minute.
 *
 * Driven by console/b2b_filter_notify.php (cron).
 */
class B2bFilterMailer
{
    /** Cars counted per filter; the email shows a number, not a list. */
    private const MAX_PER_FILTER = 200;

    /**
     * When the signup consent checkbox went live (commit e60f8f86).
     *
     * Accounts older than this were never asked, so they keep receiving the
     * alerts; from here on the checkbox decides. Grandfathered in code rather
     * than by writing marketing_accepted = 1 on those rows — that column is the
     * record of what the partner answered, and it must not claim an answer
     * nobody ever gave.
     */
    private const CONSENT_ASKED_SINCE = '2026-08-11';

    /**
     * Whether this partner is due the alert emails — the PHP twin of the WHERE
     * in pendingByUser(), so the cabinet does not promise an email that will
     * never be sent (and the other way round).
     */
    public static function wants(array $user): bool
    {
        if ((int)($user['marketing_accepted'] ?? 0) === 1) {
            return true;
        }
        $since = trim((string)($user['created_at'] ?? ''));
        return $since !== '' && $since < self::CONSENT_ASKED_SINCE;
    }

    /** Kill switch: set b2b_filter_mail = 0 in gh3sp_settings to stop the emails. */
    public static function enabled(): bool
    {
        return B2bConfig::get('b2b_filter_mail', '1') !== '0';
    }

    /**
     * Sends what is due and returns a summary for the cron log.
     *
     * @param array<string, array<string, mixed>> $labels describe() labels per
     *        email language ('ro', 'ru'), built by the caller from the site's
     *        own translations
     * @return array{users: int, filters: int, cars: int, sent: int, failed: int}
     */
    public static function run(array $labels, bool $dry = false): array
    {
        $stat = ['users' => 0, 'filters' => 0, 'cars' => 0, 'sent' => 0, 'failed' => 0];

        foreach (self::pendingByUser() as $userId => $filters) {
            $user = B2bAuth::findById($userId);
            if (!$user || !filter_var((string)($user['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $lang  = self::langFor($user);
            $lines = [];
            $ids   = [];

            foreach ($filters as $f) {
                // Never before created_at: a filter is a watch on what comes
                // next, not a search through the existing catalog.
                $since = trim((string)($f['notified_at'] ?? ''));
                if ($since === '' || $since < (string)$f['created_at']) {
                    $since = (string)$f['created_at'];
                }

                $n = count(B2bSavedFilter::matchIdsSince($f, $since, self::MAX_PER_FILTER));
                if ($n === 0) {
                    continue;
                }

                $lines[] = [
                    'what' => B2bSavedFilter::describe($f, $labels[$lang] ?? []),
                    'n'    => $n,
                ];
                $ids[] = (int)$f['id'];
                $stat['filters']++;
                $stat['cars'] += $n;
            }

            if (!$lines) {
                continue;
            }
            $stat['users']++;

            if ($dry) {
                continue; // counted, but nothing sent and no watermark moved
            }

            if (self::send($user, $lang, $lines)) {
                B2bSavedFilter::markNotified($ids);
                $stat['sent']++;
            } else {
                $stat['failed']++;
            }
        }

        return $stat;
    }

    /**
     * Filters of active partners who accepted email contact, grouped by partner.
     *
     * marketing_accepted is the signup checkbox ("...автооповещения по e-mail"):
     * unticking it means no cabinet email at all — except the password reset,
     * which is a security message rather than a notification. Accounts that
     * predate the checkbox are exempt (see CONSENT_ASKED_SINCE).
     *
     * If the column is missing (GDPR migration never run) the query throws, is
     * logged, and nothing is sent — the safe direction: no record of consent,
     * no email.
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private static function pendingByUser(): array
    {
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT f.* FROM ' . B2bConfig::table('saved_filters') . ' AS f
                   JOIN ' . B2bConfig::table('users') . ' AS u ON u.id = f.b2b_user_id
                  WHERE u.status = "active"
                    AND (u.marketing_accepted = 1 OR u.created_at < :asked)
                  ORDER BY f.b2b_user_id, f.id'
            );
            $stmt->execute([':asked' => self::CONSENT_ASKED_SINCE]);

            $out = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $out[(int)$row['b2b_user_id']][] = $row;
            }
            return $out;
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'filter mail select err=' . $e->getMessage());
            return [];
        }
    }

    /**
     * Email language, by the same convention as the password reset: the script
     * of full_name, the one name signup asks for. Not the browsing language —
     * a cron has no browser — and not company_name, which only an admin ever
     * fills in, so the partner gets every Sauto email in one language.
     */
    private static function langFor(array $user): string
    {
        $name = trim((string)($user['full_name'] ?? ''));
        return preg_match('/\p{Cyrillic}/u', $name) ? 'ru' : 'ro';
    }

    /** @param array<int, array{what: string, n: int}> $lines */
    private static function send(array $user, string $lang, array $lines): bool
    {
        $link = self::siteUrl() . '/' . $lang . '/b2b/filters';
        $mail = self::compose($lang, B2bAuth::displayName($user), $lines, $link);

        try {
            // Absolute, from this file: _PLUGINS is a path relative to the
            // docroot, and the cron does not run with that as its cwd.
            $plugins = dirname(__DIR__, 3) . '/plugins/PHPMailer/src/';
            require_once $plugins . 'Exception.php';
            require_once $plugins . 'PHPMailer.php';
            require_once $plugins . 'SMTP.php';

            $m = new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->CharSet = 'UTF-8';
            $m->setFrom('mesaj@sauto.md', 'Sauto.md');
            $m->addAddress((string)$user['email']);
            $m->isHTML(true);
            $m->Subject = $mail['subject'];
            $m->Body    = $mail['body'];
            $m->AltBody = $mail['alt'];
            $m->send();
            return true;
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'filter mail uid=' . (int)$user['id'] . ' err=' . $e->getMessage());
            return false;
        }
    }

    /** Absolute site URL; the cron has no HTTP_HOST to read. */
    private static function siteUrl(): string
    {
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        return $host !== '' ? B2bNotifier::siteUrl() : 'https://www.sauto.md';
    }

    // ------------------------------------------------------------------- text

    /**
     * @param array<int, array{what: string, n: int}> $lines
     * @return array{subject: string, body: string, alt: string}
     */
    private static function compose(string $lang, string $name, array $lines, string $link): array
    {
        $esc  = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $many = count($lines) > 1;

        $t = [
            'ro' => [
                'subject' => $many
                    ? 'Mașini noi pentru filtrele tale salvate — Sauto.md'
                    : 'Mașini noi pentru filtrul tău salvat — Sauto.md',
                'hi'    => 'Salut',
                'intro' => $many
                    ? 'Au apărut mașini noi care corespund filtrelor salvate în cabinetul tău pe Sauto.md:'
                    : 'Au apărut mașini noi care corespund filtrului salvat în cabinetul tău pe Sauto.md:',
                'btn'   => 'Vezi mașinile',
                'foot'  => 'Primești acest mesaj pentru că ai salvat filtrul în cabinet. Îl poți modifica sau șterge oricând din secțiunea „Filtrele mele".',
                'alt'   => 'Vezi mașinile în cabinet:',
            ],
            'ru' => [
                'subject' => $many
                    ? 'Новые автомобили по вашим фильтрам — Sauto.md'
                    : 'Новые автомобили по вашему фильтру — Sauto.md',
                'hi'    => 'Здравствуйте',
                'intro' => $many
                    ? 'Появились новые автомобили по фильтрам, сохранённым в вашем кабинете на Sauto.md:'
                    : 'Появились новые автомобили по фильтру, сохранённому в вашем кабинете на Sauto.md:',
                'btn'   => 'Смотреть автомобили',
                'foot'  => 'Вы получили это письмо, потому что сохранили фильтр в кабинете. Его можно изменить или удалить в разделе «Мои фильтры».',
                'alt'   => 'Посмотреть автомобили в кабинете:',
            ],
        ][$lang] ?? [];

        $hi   = $t['hi'] . ($name !== '' ? ' ' . $esc($name) : '') . ',';
        $rows = '';
        $plain = '';

        foreach ($lines as $l) {
            $count = self::countLabel($lang, $l['n']);
            $rows .= '<tr>'
                   . '<td style="padding:10px 14px;border:1px solid #eceff3;border-radius:8px 0 0 8px;color:#111827;font-weight:700;">'
                   . $esc($l['what']) . '</td>'
                   . '<td style="padding:10px 14px;border:1px solid #eceff3;border-left:0;border-radius:0 8px 8px 0;color:#e2001a;font-weight:700;white-space:nowrap;">'
                   . $esc($count) . '</td>'
                   . '</tr>';
            $plain .= '- ' . $l['what'] . ': ' . $count . "\n";
        }

        $body =
            '<div style="font-family:Arial,Helvetica,sans-serif;max-width:520px;margin:0 auto;color:#222;font-size:15px;line-height:1.5;">'
          . '<p>' . $hi . '<br><br>' . $esc($t['intro']) . '</p>'
          . '<table role="presentation" cellpadding="0" cellspacing="6" style="width:100%;border-collapse:separate;font-size:14px;">' . $rows . '</table>'
          . '<p style="text-align:center;margin:28px 0;">'
          . '<a href="' . $esc($link) . '" style="display:inline-block;background:#e2001a;color:#fff;text-decoration:none;font-weight:700;padding:13px 26px;border-radius:8px;">' . $esc($t['btn']) . '</a>'
          . '</p>'
          . '<p style="color:#6b7280;font-size:12px;line-height:1.45;">' . $esc($t['foot']) . '</p>'
          . '</div>';

        return [
            'subject' => $t['subject'],
            'body'    => $body,
            'alt'     => $t['hi'] . ($name !== '' ? ' ' . $name : '') . ",\n\n"
                       . $t['intro'] . "\n" . $plain . "\n" . $t['alt'] . "\n" . $link,
        ];
    }

    /** "3 mașini noi" / "3 новых автомобиля" — both languages count differently. */
    private static function countLabel(string $lang, int $n): string
    {
        if ($lang === 'ru') {
            $mod100 = $n % 100;
            $mod10  = $n % 10;
            if ($mod100 >= 11 && $mod100 <= 14) {
                return $n . ' новых автомобилей';
            }
            if ($mod10 === 1) {
                return $n . ' новый автомобиль';
            }
            if ($mod10 >= 2 && $mod10 <= 4) {
                return $n . ' новых автомобиля';
            }
            return $n . ' новых автомобилей';
        }

        if ($n === 1) {
            return '1 mașină nouă';
        }
        // Romanian inserts "de" from 20 upwards: "20 de mașini".
        return $n . ($n % 100 === 0 || $n % 100 >= 20 ? ' de' : '') . ' mașini noi';
    }
}
