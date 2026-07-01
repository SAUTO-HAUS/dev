<?php

namespace App\Services\Parsing;

use App\Core\Container;
use PDO;
use Throwable;

/**
 * Background publish queue (sauto only).
 *
 * Clicking "Publică pe sauto" enqueues a job; a worker
 * (console/parsing_publish_worker.php) drains the queue sequentially via
 * ParsingPublisher, so the operator can leave the page (or close the browser)
 * and publishing continues server-side without freezing the UI under many
 * simultaneous heavy requests.
 *
 * Only target 'sauto' goes through here. 999/FB/Telegram publish for real ONLY
 * through the ordercars form (browser), handled by a separate client-side queue.
 *
 * Failure policy: a job that can't publish (e.g. brand/model not mapped to the
 * sauto catalog) is marked `failed` with the reason and skipped; the car stays
 * in /ctlg for manual publishing. One failure never blocks the queue.
 */
class PublishQueue
{
    private const MAX_ATTEMPTS = 1;

    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
        $this->ensureTable();
    }

    private function table(): string
    {
        return $this->prefix . '_parsing_publish_queue';
    }

    /**
     * Add a car to the queue. A finished (done/failed) job for the same
     * car+target is reset to pending so the car can be retried. Returns the
     * queue row id, or 0 if it's already pending/processing.
     */
    public function enqueue(int $parsingCarId, string $target = 'sauto'): int
    {
        if ($parsingCarId <= 0) return 0;

        $stmt = $this->db->prepare('SELECT id, status FROM '.$this->table().'
            WHERE parsing_car_id = ? AND target = ? LIMIT 1');
        $stmt->execute([$parsingCarId, $target]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if (in_array($existing['status'], ['pending', 'processing'], true)) {
                return 0;
            }
            $this->db->prepare('UPDATE '.$this->table().'
                SET status = "pending", attempts = 0, error = NULL,
                    car_ctlg_id = NULL, started_at = NULL, finished_at = NULL, created_at = NOW()
                WHERE id = ?')->execute([$existing['id']]);
            return (int)$existing['id'];
        }

        $this->db->prepare('INSERT INTO '.$this->table().'
            (parsing_car_id, target, status) VALUES (?, ?, "pending")')
            ->execute([$parsingCarId, $target]);
        return (int)$this->db->lastInsertId();
    }

    /** How long a 'processing' job may run before it's considered abandoned
     * (worker died mid-job: PHP timeout, fatal, OOM) and is reclaimed. */
    private const STUCK_PROCESSING_MINUTES = 5;

    /**
     * Atomically claim the oldest claimable job (→ processing). A job is
     * claimable if it's 'pending', OR 'processing' but stuck for too long (the
     * previous worker died before finishing it — without this, such a job is
     * lost forever, which is why some cars never published). Null if none.
     */
    private function claimNext(): ?array
    {
        $stmt = $this->db->query('SELECT id FROM '.$this->table().'
            WHERE status = "pending"
               OR (status = "processing" AND started_at < DATE_SUB(NOW(), INTERVAL '.self::STUCK_PROCESSING_MINUTES.' MINUTE))
            ORDER BY id ASC LIMIT 1');
        $id = $stmt ? (int)$stmt->fetchColumn() : 0;
        if (!$id) return null;

        // Claim only if it's still in a claimable state (guards the race).
        $upd = $this->db->prepare('UPDATE '.$this->table().'
            SET status = "processing", attempts = attempts + 1, started_at = NOW()
            WHERE id = ?
              AND (status = "pending"
                OR (status = "processing" AND started_at < DATE_SUB(NOW(), INTERVAL '.self::STUCK_PROCESSING_MINUTES.' MINUTE)))');
        $upd->execute([$id]);
        if ($upd->rowCount() === 0) {
            return $this->claimNext(); // lost the race — try the next
        }

        $row = $this->db->prepare('SELECT * FROM '.$this->table().' WHERE id = ?');
        $row->execute([$id]);
        $job = $row->fetch(PDO::FETCH_ASSOC);
        return $job ?: null;
    }

    /** Process the whole queue until empty (or maxJobs). Returns { done, failed }. */
    public function processAll(int $maxJobs = 500): array
    {
        $done = 0; $failed = 0;
        for ($i = 0; $i < $maxJobs; $i++) {
            $job = $this->claimNext();
            if (!$job) break;
            $this->runJob($job) ? $done++ : $failed++;
        }
        return ['done' => $done, 'failed' => $failed];
    }

    private function runJob(array $job): bool
    {
        $id = (int)$job['id'];
        try {
            $res = (new ParsingPublisher())->publish((int)$job['parsing_car_id'], (string)$job['target']);
            if (!empty($res['success'])) {
                $this->db->prepare('UPDATE '.$this->table().'
                    SET status = "done", error = NULL, car_ctlg_id = ?, finished_at = NOW()
                    WHERE id = ?')->execute([$res['car_ctlg_id'] ?? null, $id]);
                return true;
            }
            return $this->markFailedOrRetry($job, (string)($res['error'] ?? 'Publish failed'));
        } catch (Throwable $e) {
            return $this->markFailedOrRetry($job, $e->getMessage());
        }
    }

    private function markFailedOrRetry(array $job, string $error): bool
    {
        $id = (int)$job['id'];
        if ((int)$job['attempts'] < self::MAX_ATTEMPTS) {
            $this->db->prepare('UPDATE '.$this->table().'
                SET status = "pending", error = ?, started_at = NULL WHERE id = ?')
                ->execute([$error, $id]);
        } else {
            $this->db->prepare('UPDATE '.$this->table().'
                SET status = "failed", error = ?, finished_at = NOW() WHERE id = ?')
                ->execute([$error, $id]);
            // Flag the car so auto-publish stops retrying it forever (it stays
            // "proposed" on failure). It still shows in the failures panel; a
            // manual retry clears the flag and lets auto-publish pick it up again.
            $this->markAutoPublishFailed((int)$job['parsing_car_id']);
        }
        return false;
    }

    // Set/clear the "auto-publish gave up on this car" flag in parsing_cars, so
    // autoPublishFilterCars skips it. Column is self-created (no migration runner).
    private function markAutoPublishFailed(int $parsingCarId): void
    {
        try {
            $this->ensureAutoPublishFailedColumn();
            $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars
                SET autopublish_failed = 1 WHERE id = ?')->execute([$parsingCarId]);
        } catch (Throwable $e) { /* non-fatal */ }
    }

    public function clearAutoPublishFailed(int $parsingCarId): void
    {
        try {
            $this->ensureAutoPublishFailedColumn();
            $this->db->prepare('UPDATE '.$this->prefix.'_parsing_cars
                SET autopublish_failed = 0 WHERE id = ?')->execute([$parsingCarId]);
        } catch (Throwable $e) { /* non-fatal */ }
    }

    private function ensureAutoPublishFailedColumn(): void
    {
        static $done = false;
        if ($done) return;
        try {
            $col = $this->db->query("SHOW COLUMNS FROM {$this->prefix}_parsing_cars LIKE 'autopublish_failed'");
            if ($col && $col->rowCount() === 0) {
                $this->db->exec("ALTER TABLE {$this->prefix}_parsing_cars
                    ADD COLUMN `autopublish_failed` TINYINT(1) NOT NULL DEFAULT 0");
            }
            $done = true;
        } catch (Throwable $e) { /* best-effort */ }
    }

    /** Counts by status for the admin progress indicator. */
    public function counts(): array
    {
        $out = ['pending' => 0, 'processing' => 0, 'done' => 0, 'failed' => 0];
        $stmt = $this->db->query('SELECT status, COUNT(*) AS c FROM '.$this->table().' GROUP BY status');
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (isset($out[$r['status']])) $out[$r['status']] = (int)$r['c'];
            }
        }
        return $out;
    }

    public function statsByFilter(int $filterId): array
    {
        $out = ['today_done' => 0, 'today_failed' => 0, 'total_done' => 0, 'total_failed' => 0, 'pending' => 0];
        $sql = 'SELECT q.status,
                       SUM(q.status = "done"   AND DATE(q.finished_at) = CURDATE()) AS td,
                       SUM(q.status = "failed" AND DATE(q.finished_at) = CURDATE()) AS tf,
                       SUM(q.status = "done")   AS ad,
                       SUM(q.status = "failed") AS af,
                       SUM(q.status IN ("pending","processing")) AS pp
                FROM '.$this->table().' q
                JOIN '.$this->prefix.'_parsing_cars c ON c.id = q.parsing_car_id
                WHERE c.filter_id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$filterId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $out['today_done']   = (int)($r['td'] ?? 0);
        $out['today_failed'] = (int)($r['tf'] ?? 0);
        $out['total_done']   = (int)($r['ad'] ?? 0);
        $out['total_failed'] = (int)($r['af'] ?? 0);
        $out['pending']      = (int)($r['pp'] ?? 0);
        return $out;
    }

    // Failed jobs for ONE filter (the per-filter situation panel). Same shape as
    // failedJobs() but scoped to the filter's cars.
    public function failedJobsByFilter(int $filterId, int $limit = 100): array
    {
        $sql = 'SELECT q.id, q.parsing_car_id, q.error, q.car_ctlg_id, q.finished_at,
                       c.brand, c.model, c.year
                FROM '.$this->table().' q
                JOIN '.$this->prefix.'_parsing_cars c ON c.id = q.parsing_car_id
                WHERE q.status = "failed" AND c.filter_id = ?
                ORDER BY q.finished_at DESC, q.id DESC
                LIMIT '.(int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$filterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Failed jobs with car detail for the failures panel. */
    public function failedJobs(int $limit = 100): array
    {
        $sql = 'SELECT q.id, q.parsing_car_id, q.target, q.error, q.car_ctlg_id,
                       c.brand, c.model, c.year, c.car_ctlg_id AS car_ctlg_id_row
                FROM '.$this->table().' q
                LEFT JOIN '.$this->prefix.'_parsing_cars c ON c.id = q.parsing_car_id
                WHERE q.status = "failed"
                ORDER BY q.finished_at DESC, q.id DESC
                LIMIT '.(int)$limit;
        $stmt = $this->db->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as &$r) {
            $r['car_ctlg_id'] = $r['car_ctlg_id'] ?: $r['car_ctlg_id_row'];
            unset($r['car_ctlg_id_row']);
        }
        return $rows;
    }

    public function retry(int $jobId): bool
    {
        // Clear the car's auto-publish-failed flag so a manual retry (after the
        // operator fixed the mapping) lets auto-publish pick it up again too.
        $car = $this->db->prepare('SELECT parsing_car_id FROM '.$this->table().' WHERE id = ?');
        $car->execute([$jobId]);
        $parsingCarId = (int)$car->fetchColumn();

        $upd = $this->db->prepare('UPDATE '.$this->table().'
            SET status = "pending", attempts = 0, error = NULL,
                started_at = NULL, finished_at = NULL, created_at = NOW()
            WHERE id = ? AND status = "failed"');
        $upd->execute([$jobId]);
        $ok = $upd->rowCount() > 0;
        if ($ok && $parsingCarId > 0) $this->clearAutoPublishFailed($parsingCarId);
        return $ok;
    }

    public function dismiss(int $jobId): bool
    {
        $del = $this->db->prepare('DELETE FROM '.$this->table().' WHERE id = ? AND status = "failed"');
        $del->execute([$jobId]);
        return $del->rowCount() > 0;
    }

    /** Self-create the table (no migration runner in this project). */
    private function ensureTable(): void
    {
        try {
            $this->db->exec('CREATE TABLE IF NOT EXISTS '.$this->table().' (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `parsing_car_id` INT(11) NOT NULL,
                `target` VARCHAR(20) NOT NULL DEFAULT "sauto",
                `status` ENUM("pending","processing","done","failed") NOT NULL DEFAULT "pending",
                `attempts` INT(11) NOT NULL DEFAULT 0,
                `error` TEXT DEFAULT NULL,
                `car_ctlg_id` INT(11) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `started_at` DATETIME DEFAULT NULL,
                `finished_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_car_target` (`parsing_car_id`, `target`),
                INDEX `idx_status` (`status`),
                INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        } catch (Throwable $e) {
            // surface on first use if creation fails
        }
    }
}
