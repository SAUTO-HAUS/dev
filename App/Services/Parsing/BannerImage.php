<?php

namespace App\Services\Parsing;

/**
 * Tells a dealer banner apart from a real photo of the car.
 *
 * AutoTrader galleries are mixed: between the photos sit promo frames — financing
 * offers, "CERTIFIED", opening hours — that must never reach an sauto or 999 ad.
 *
 * The signal is what the image is MADE of, not what it weighs: a banner is drawn,
 * so a handful of flat colours cover almost the whole frame. A photograph is
 * captured, so light, texture and noise spread its pixels over thousands of
 * shades. Measured on real AutoTrader galleries the two never came close —
 * banners covered 91-97% of the frame with their top colours, real photos stayed
 * at or below 50%. The threshold sits in that empty gap.
 */
class BannerImage
{
    /**
     * Share of the frame the dominant flat colours must cover to call it a banner.
     *
     * 0.90, not 0.80. Measured on real galleries the two groups OVERLAP: a dealer who
     * cuts his cars out on white produces photos at 0.834, while a busy banner (car
     * photo pasted on a graphic background, logos, big lettering) sits at 0.60-0.70.
     * At 0.80 the filter deleted a real cover photo and kept the banner next to it.
     * This threshold only claims the frames nothing else reaches — a plain drawn
     * banner, 0.93 and up. The busy kind needs a different signal entirely.
     */
    private const FLAT_SHARE = 0.90;

    /** How many of the most frequent colours count as "the flat part". */
    private const TOP_COLOURS = 12;

    /** Analysis size — small keeps it fast; flat areas survive the downscale. */
    private const SAMPLE = 96;

    /**
     * @param string $bytes Raw image data (JPEG/PNG/WebP).
     * @return bool True when the frame looks drawn rather than photographed.
     *              Anything unreadable answers false: a publish must never lose a
     *              real photo because GD choked on it.
     */
    public static function isBanner(string $bytes): bool
    {
        return self::flatShare($bytes) >= self::FLAT_SHARE;
    }

    /**
     * The measured share itself, 0..1. Exposed so a check script can print the
     * number for a whole gallery instead of a yes/no.
     */
    public static function flatShare(string $bytes): float
    {
        if ($bytes === '' || !function_exists('imagecreatefromstring')) return 0.0;

        $img = @imagecreatefromstring($bytes);
        if (!$img) return 0.0;

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 8 || $h < 8) { imagedestroy($img); return 0.0; }

        $small = @imagecreatetruecolor(self::SAMPLE, self::SAMPLE);
        if (!$small) { imagedestroy($img); return 0.0; }
        imagecopyresampled($small, $img, 0, 0, 0, 0, self::SAMPLE, self::SAMPLE, $w, $h);
        imagedestroy($img);

        // Count colours quantised to 5 bits per channel: close shades of the same
        // flat fill land in one bucket, while a photo's gradients still scatter.
        $buckets = [];
        for ($y = 0; $y < self::SAMPLE; $y++) {
            for ($x = 0; $x < self::SAMPLE; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $key = ((($rgb >> 16) & 0xF8) << 8) | ((($rgb >> 8) & 0xF8) << 3) | (($rgb & 0xF8) >> 3);
                $buckets[$key] = ($buckets[$key] ?? 0) + 1;
            }
        }
        imagedestroy($small);

        arsort($buckets);
        $top = array_sum(array_slice($buckets, 0, self::TOP_COLOURS, true));

        return $top / (self::SAMPLE * self::SAMPLE);
    }

    // ── Repeat detection ────────────────────────────────────────────────────────
    //
    // The reliable signal, and a free one: a dealer's banner is the SAME picture in
    // every one of his listings, while a photo of a car exists once in the whole
    // catalogue. So an image seen on another car is an advert, whatever it looks
    // like — no threshold to guess and no way to delete a genuine photo by mistake.

    /**
     * 64-bit dHash as 16 hex chars: 9x8 grayscale, each pixel compared to its right
     * neighbour. Survives re-compression and resizing, which is what we need — the
     * same banner is re-encoded per listing and served under a different URL.
     */
    public static function fingerprint(string $bytes): ?string
    {
        if ($bytes === '' || !function_exists('imagecreatefromstring')) return null;
        $img = @imagecreatefromstring($bytes);
        if (!$img) return null;

        $small = @imagecreatetruecolor(9, 8);
        if (!$small) { imagedestroy($img); return null; }
        imagecopyresampled($small, $img, 0, 0, 0, 0, 9, 8, imagesx($img), imagesy($img));
        imagedestroy($img);

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            $prev = null;
            for ($x = 0; $x < 9; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $lum = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
                if ($prev !== null) $bits .= ($lum > $prev) ? '1' : '0';
                $prev = $lum;
            }
        }
        imagedestroy($small);

        // An almost uniform picture — a blank frame, an overexposed shot — has nearly
        // no variation between neighbouring pixels, so its fingerprint is nearly all
        // zeros and would match OTHER uniform pictures that are not the same image at
        // all. Refuse to fingerprint those: better no opinion than a wrong match that
        // deletes someone's photo. Real banners carry text and logos, so they stay
        // well inside these bounds (the plainest one measured had 16 bits set).
        $ones = substr_count($bits, '1');
        if ($ones < 6 || $ones > 58) return null;

        $hex = '';
        foreach (str_split($bits, 4) as $nib) $hex .= dechex(bindec($nib));
        return $hex;
    }

    /** Table holding "this picture was part of that listing". Created on first use. */
    public static function ensureStore(\PDO $db, string $prefix): bool
    {
        static $ready = null;
        if ($ready !== null) return $ready;
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS {$prefix}_parsing_img_hash (
                `hash` CHAR(16) NOT NULL,
                `listing_id` INT(11) NOT NULL,
                `seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`hash`, `listing_id`),
                INDEX `idx_hash` (`hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $ready = true;
        } catch (\Throwable $e) {
            $ready = false;
        }
        return $ready;
    }

    /**
     * True when this exact picture also belongs to a listing of a DIFFERENT car.
     *
     * "Different" means another brand or model, not merely another listing id: the
     * same car is sometimes imported twice, and those two listings legitimately share
     * every photo. Only an advert travels across a BMW, a Honda and a Lexus.
     */
    public static function seenOnAnotherListing(\PDO $db, string $prefix, string $hash, int $listingId): bool
    {
        if ($hash === '' || !self::ensureStore($db, $prefix)) return false;
        try {
            $st = $db->prepare("SELECT 1
                FROM {$prefix}_parsing_img_hash h
                JOIN {$prefix}_parsing_cars other ON other.id = h.listing_id
                JOIN {$prefix}_parsing_cars mine  ON mine.id = ?
                WHERE h.hash = ? AND h.listing_id <> mine.id
                  AND (COALESCE(other.brand,'') <> COALESCE(mine.brand,'')
                       OR COALESCE(other.model,'') <> COALESCE(mine.model,''))
                LIMIT 1");
            $st->execute([$listingId, $hash]);
            return $st->fetchColumn() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Record that this picture belongs to this listing. */
    public static function remember(\PDO $db, string $prefix, string $hash, int $listingId): void
    {
        if ($hash === '' || $listingId <= 0 || !self::ensureStore($db, $prefix)) return;
        try {
            $db->prepare("INSERT IGNORE INTO {$prefix}_parsing_img_hash (hash, listing_id) VALUES (?, ?)")
               ->execute([$hash, $listingId]);
        } catch (\Throwable $e) { /* a missed record only weakens the next check */ }
    }

    /**
     * Drop the banners from a list of fetched images, keeping the original keys
     * and order. Returns the list untouched when every frame looks like a banner —
     * an ad with no photos at all is worse than an ad with a banner in it.
     *
     * @param array<int,string|null> $images
     * @return array<int,string|null>
     */
    public static function filter(array $images): array
    {
        $kept = [];
        foreach ($images as $idx => $bytes) {
            if ($bytes === null || $bytes === '') continue;
            if (!self::isBanner($bytes)) $kept[$idx] = $bytes;
        }
        return empty($kept) ? $images : $kept;
    }
}
