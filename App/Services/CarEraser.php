<?php

namespace App\Services;

use PDO;


/**
 * Permanently erases a car and all its traces: DB rows (car_ctlg, car_pht, seo2,
 * schedule rows) and the photo folder on disk. Used by the auto-delete rule:
 * an on_order car that goes out of stock (n_a=1) — via timer expiry, a manual
 * "not available" toggle, or a parsing "sold" status — is deleted completely
 * instead of just hidden. in_stock cars are never touched by callers.
 *
 * The 999.md ad (if any) is intentionally left as-is per product decision.
 */
final class CarEraser
{
    /**
     * Erase one car completely. Returns ['ok'=>bool, 'photos'=>bool].
     * $carImg is the absolute car-image base dir (…/media/images/upload/car).
     */
    public static function erase(PDO $db, string $prefix, int $carId, string $carImg): array
    {
        // p_path is needed to locate the photo folder before the row is gone.
        $st = $db->prepare("SELECT p_path FROM {$prefix}_car_ctlg WHERE id = ?");
        $st->execute([$carId]);
        $pPath = (string)$st->fetchColumn();

        $photosDeleted = false;
        if ($pPath !== '') {
            $dir = rtrim($carImg, '/') . '/' . $pPath . '/' . $carId;
            if (is_dir($dir)) { self::rmdirRecursive($dir); $photosDeleted = true; }
            // R2 holds the same photos; leaving them there is paid-for dead weight.
            try { CarPhotoR2::deleteCar($pPath, $carId); } catch (\Throwable $e) { /* non-fatal */ }
        }

        // DB: photos, schedule rows on all channels, SEO, unlink parsing, catalog row.
        $db->prepare("DELETE FROM {$prefix}_car_pht WHERE it_id = ?")->execute([$carId]);
        foreach ([
            "{$prefix}_sauto_personal_schedules",
            "{$prefix}_scheduled_facebook_posts",
            "{$prefix}_scheduled_telegram_posts",
        ] as $t) {
            try { $db->prepare("DELETE FROM {$t} WHERE car_id = ?")->execute([$carId]); } catch (\Throwable $e) {}
        }
        try { $db->prepare("DELETE FROM {$prefix}_seo2 WHERE tp='item' AND p1='cars' AND it_id = ?")->execute([$carId]); } catch (\Throwable $e) {}
        try { $db->prepare("UPDATE {$prefix}_parsing_cars SET car_ctlg_id = NULL, status = 'rejected' WHERE car_ctlg_id = ?")->execute([$carId]); } catch (\Throwable $e) {}
        $ok = $db->prepare("DELETE FROM {$prefix}_car_ctlg WHERE id = ?")->execute([$carId]);

        return ['ok' => (bool)$ok, 'photos' => $photosDeleted];
    }

    /**
     * Erase every ON_ORDER car currently flagged out of stock (n_a=1). This is the
     * single entry point the crons/toggle call: "on_order + n_a=1 → delete". Returns
     * the number of cars erased. in_stock cars are excluded by the WHERE clause.
     */
    public static function eraseOutOfStockOnOrder(PDO $db, string $prefix, string $carImg, int $limit = 500, ?int &$photosDeleted = null): int
    {
        $rows = $db->query("SELECT id FROM {$prefix}_car_ctlg
            WHERE n_a = 1 AND catalog_type = 'on_order' LIMIT {$limit}")->fetchAll(PDO::FETCH_COLUMN);
        $n = 0;
        $photosDeleted = 0; // reported back so a silent photo-delete failure is visible
        foreach ($rows as $id) {
            $r = self::erase($db, $prefix, (int)$id, $carImg);
            if ($r['ok']) $n++;
            if ($r['photos']) $photosDeleted++;
        }
        return $n;
    }

    /** Public so other delete paths (e.g. ParsingPublisher::unpublish) reuse it
     *  instead of re-implementing it — every copy that drifts leaves orphan folders. */
    public static function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $f) {
            $p = $dir . '/' . $f;
            is_dir($p) ? self::rmdirRecursive($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
