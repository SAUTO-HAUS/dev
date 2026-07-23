<?php

namespace App\Services\B2b;

use PDO;

/**
 * Advance-payment proformas (spec 2.3.2 / 4.4).
 *
 * An issued document is immutable: the car data is frozen into `car_snapshot`
 * at issue time, because listings and prices change but a proforma already sent
 * to a client must not change with them.
 *
 * Access goes through a signed URL (`access_key`, compared with hash_equals) so
 * documents cannot be discovered by incrementing the id.
 */
class B2bInvoice
{
    private const MIN_ADVANCE = 1.0;

    /** Guard against absurd manually entered amounts. */
    private const MAX_ADVANCE = 500000.0;

    /** @return array{ok: bool, error?: string, invoice?: array} */
    public static function create(int $userId, int $carId, float $advance, string $currency = 'EUR'): array
    {
        $user = B2bAuth::findById($userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'Cont inexistent.'];
        }

        $car = self::loadCar($carId);
        if (!$car) {
            return ['ok' => false, 'error' => 'Mașina nu a fost găsită.'];
        }

        // Without this check the region restriction could be bypassed by issuing
        // a proforma for a car the partner is not allowed to see.
        $region = B2bRegions::regionForCar($carId);
        if ($region !== null && !B2bRegions::isAllowed($userId, $region)) {
            B2bAudit::log($userId, B2bAudit::REGION_DENIED, ['car_id' => $carId, 'region' => $region, 'via' => 'invoice'], $carId);
            return ['ok' => false, 'error' => 'Restricționat conform planului B2B.'];
        }

        $currency = strtoupper(trim($currency));
        if (!in_array($currency, ['EUR', 'MDL', 'USD'], true)) {
            $currency = 'EUR';
        }

        $advance = round($advance, 2);
        if ($advance < self::MIN_ADVANCE || $advance > self::MAX_ADVANCE) {
            return ['ok' => false, 'error' => 'Suma avansului nu este validă.'];
        }

        $db = B2bConfig::db();

        try {
            $db->beginTransaction();

            $invoiceNo = self::nextNumber($db);
            $accessKey = bin2hex(random_bytes(20)); // 40 hex

            $db->prepare(
                'INSERT INTO ' . B2bConfig::table('invoices')
                . ' (b2b_user_id, car_id, invoice_no, advance_amount, currency, access_key, car_snapshot)
                   VALUES (:uid, :car, :no, :amount, :cur, :key, :snap)'
            )->execute([
                ':uid'    => $userId,
                ':car'    => $carId,
                ':no'     => $invoiceNo,
                ':amount' => $advance,
                ':cur'    => $currency,
                ':key'    => $accessKey,
                ':snap'   => json_encode($car, JSON_UNESCAPED_UNICODE),
            ]);

            $id = (int)$db->lastInsertId();
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            B2bConfig::log('b2b_error.log', 'invoice create err=' . $e->getMessage());
            return ['ok' => false, 'error' => 'Proforma nu a putut fi generată.'];
        }

        B2bAudit::log($userId, B2bAudit::GENERATE_INVOICE, [
            'car_id'     => $carId,
            'invoice_no' => $invoiceNo,
            'amount'     => $advance,
            'currency'   => $currency,
        ], $carId);

        return ['ok' => true, 'invoice' => self::findById($id)];
    }

    /**
     * Next number, format CPB<yy><quarter>/<seq>.
     * Runs inside the caller's transaction with FOR UPDATE; the UNIQUE index on
     * invoice_no is the final guard against a concurrent duplicate.
     */
    private static function nextNumber(PDO $db): string
    {
        $quarter = (int)ceil((int)date('m') / 3);
        $prefix  = 'CPB' . date('y') . $quarter . '/';

        $stmt = $db->prepare(
            'SELECT invoice_no FROM ' . B2bConfig::table('invoices')
            . ' WHERE invoice_no LIKE :p ORDER BY id DESC LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([':p' => $prefix . '%']);
        $last = (string)$stmt->fetchColumn();

        $n = 1;
        if ($last !== '' && strpos($last, $prefix) === 0) {
            $n = (int)substr($last, strlen($prefix)) + 1;
        }

        return $prefix . $n;
    }

    public static function findById(int $id): ?array
    {
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('invoices') . ' WHERE id = :id LIMIT 1'
            );
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Loads a proforma only if the key matches. Returns null for both a missing
     * id and a wrong key, so the two cases are indistinguishable from outside.
     */
    public static function findSigned(int $id, string $key): ?array
    {
        $invoice = self::findById($id);
        if (!$invoice) {
            return null;
        }
        if (!preg_match('/^[a-f0-9]{40}$/', $key) || !hash_equals((string)$invoice['access_key'], $key)) {
            return null;
        }
        return $invoice;
    }

    public static function publicUrl(array $invoice): string
    {
        return B2bNotifier::siteUrl() . self::path($invoice);
    }

    public static function path(array $invoice): string
    {
        $lang = $_COOKIE['lang'] ?? 'ro';
        return '/' . $lang . '/b2b/invoice/' . (int)$invoice['id']
             . '?k=' . urlencode((string)$invoice['access_key']);
    }

    /** @return array<int, array<string, mixed>> */
    public static function forUser(int $userId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit)); // inlined below: no placeholder in LIMIT
        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT * FROM ' . B2bConfig::table('invoices')
                . ' WHERE b2b_user_id = :uid ORDER BY id DESC LIMIT ' . $limit
            );
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public static function loadCar(int $carId): ?array
    {
        if ($carId <= 0) {
            return null;
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT id, br, mo, br_nm, mo_nm, yr, vin, prc, cur, vol, fl, mlg, catalog_type
                   FROM ' . B2bConfig::prefix() . '_car_ctlg
                  WHERE id = :id AND `vis` = "1" AND `act` = "1" LIMIT 1'
            );
            $stmt->execute([':id' => $carId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$row) {
            return null;
        }

        $brand = trim((string)($row['br_nm'] ?: str_replace('_', ' ', (string)$row['br'])));
        $model = trim((string)($row['mo_nm'] ?: str_replace('_', ' ', (string)$row['mo'])));
        $row['title'] = trim($brand . ' ' . $model . ' ' . (string)$row['yr']);

        return $row;
    }

    /** Frozen car data, falling back to the live catalog for legacy rows. */
    public static function carSnapshot(array $invoice): array
    {
        if (!empty($invoice['car_snapshot'])) {
            $snap = json_decode((string)$invoice['car_snapshot'], true);
            if (is_array($snap)) {
                return $snap;
            }
        }
        return self::loadCar((int)$invoice['car_id']) ?? ['id' => (int)$invoice['car_id'], 'title' => ''];
    }

    /**
     * Suggested advance for a car (spec 2.3.2: "calculated automatically from
     * configured rules, or entered manually"). The client can always overwrite
     * the value in the panel; create() re-validates whatever arrives.
     *
     * Rules, from the settings:
     *   b2b_advance_mode    fixed | percent
     *   b2b_advance_default flat amount, and the floor in percent mode
     *   b2b_advance_percent percentage of the car price
     *   b2b_advance_max     optional cap
     */
    public static function suggestedAdvance(?array $car = null): float
    {
        $flat = (float)B2bConfig::get('b2b_advance_default', '1000');

        if (B2bConfig::get('b2b_advance_mode', 'fixed') !== 'percent') {
            return round($flat, 2);
        }

        // `prc` is the public landed price from the catalog row. On the car page the
        // B2B total is computed later than this panel, so the percentage is taken
        // from the list price, not from the discounted B2B total.
        $price = (float)($car['prc'] ?? 0);
        if ($price <= 0) {
            return round($flat, 2); // no usable price: fall back to the flat amount
        }

        $percent = (float)B2bConfig::get('b2b_advance_percent', '10');
        $amount  = $price * ($percent / 100);

        // The flat amount acts as the floor so a cheap car still carries a
        // meaningful advance.
        if ($amount < $flat) {
            $amount = $flat;
        }

        $max = (float)B2bConfig::get('b2b_advance_max', '0');
        if ($max > 0 && $amount > $max) {
            $amount = $max;
        }

        return round($amount, 2);
    }

    /** Kept for callers that have no car context. */
    public static function defaultAdvance(): float
    {
        return self::suggestedAdvance(null);
    }
}
