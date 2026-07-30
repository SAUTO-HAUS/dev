<?php

namespace App\Services;

/**
 * Single door between the app and car photos in R2.
 *
 * Every path here is the usual absolute one under media/images/upload/car/; the
 * R2 key is that path with the base stripped, so it matches the public URL and
 * the Worker needs no mapping table.
 *
 * read()/exists() look at the local disk FIRST and only then at R2. That is what
 * lets the local copies be deleted whenever we like: nothing has a flag day, the
 * callers keep working either way.
 */
final class CarPhotoR2
{
    private static ?R2Client $client = null;
    private static ?string $base = null;

    /** Absolute car-image root, resolved once. */
    public static function base(): string
    {
        if (self::$base !== null) return self::$base;
        $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
        if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
            // Under CLI DOCUMENT_ROOT is an empty string, not unset — check the value.
            $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
            if ($root === '' || !is_dir($root)) $root = dirname(__DIR__, 2);
            $carImg = rtrim($root, '/') . '/' . ltrim($carImg, '/');
        }
        return self::$base = rtrim(str_replace('\\', '/', $carImg), '/');
    }

    /** Null when R2 is not configured — callers then simply stay local-only. */
    private static function r2(): ?R2Client
    {
        if (self::$client === null) self::$client = R2Client::fromEnv();
        return self::$client;
    }

    /**
     * Absolute path for any input. _CAR_IMG is a path relative to the document
     * root, so the upload scripts pass relative paths while the parsing side
     * passes absolute ones — both have to work.
     */
    public static function abs(string $path): string
    {
        $p = str_replace('\\', '/', $path);
        if ($p !== '' && ($p[0] === '/' || preg_match('#^[A-Za-z]:#', $p))) return self::normalize($p);

        $rel = ltrim(str_replace('\\', '/', defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car'), '/');
        return self::normalize(strpos($p, $rel . '/') === 0
            ? self::base() . '/' . substr($p, strlen($rel) + 1)
            : $p);
    }

    /**
     * Collapse "." and ".." without touching the filesystem — realpath() returns
     * false for a file that is no longer on disk, which is precisely the case
     * this class exists for. Callers build paths like __DIR__.'/../media/...',
     * and the leftover "console/.." made keyFor() miss the base and silently
     * return an empty key, so nothing was ever fetched from R2.
     */
    private static function normalize(string $p): string
    {
        $rooted = $p !== '' && $p[0] === '/';
        $parts = [];
        foreach (explode('/', $p) as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($parts); continue; }
            $parts[] = $seg;
        }
        return ($rooted ? '/' : '') . implode('/', $parts);
    }

    /** R2 key for a path, or '' when it falls outside the car tree. */
    public static function keyFor(string $path): string
    {
        $p = self::abs($path);
        $b = self::base() . '/';
        return strpos($p, $b) === 0 ? substr($p, strlen($b)) : '';
    }

    // ── writing ─────────────────────────────────────────────────────────────

    /** Upload one already-written local file. */
    public static function push(string $path): bool
    {
        $abs = self::abs($path);
        $r2  = self::r2();
        $key = self::keyFor($abs);
        if (!$r2 || $key === '' || !is_file($abs)) return false;
        return $r2->putFile($key, $abs, self::mime($abs));
    }

    /**
     * Upload every file under a car folder (high/, med/, doc/, index.html).
     * Called right after the resizer has written them. Returns files uploaded.
     */
    public static function pushDir(string $absDir): int
    {
        $r2 = self::r2();
        if (!$r2 || !is_dir($absDir)) return 0;

        $items = [];
        $walk = function (string $dir) use (&$walk, &$items) {
            foreach (array_diff(@scandir($dir) ?: [], ['.', '..']) as $f) {
                $p = $dir . '/' . $f;
                if (is_link($p)) continue;
                if (is_dir($p)) { $walk($p); continue; }
                $key = self::keyFor($p);
                if ($key !== '') $items[] = [$key, $p, self::mime($p)];
            }
        };
        $walk($absDir);
        if (!$items) return 0;

        $res = $r2->putFilesParallel($items, 8);
        if ($res['failed']) {
            // Loud but non-fatal: the photo is on disk and the Worker still falls
            // back to origin, so the page keeps working until this is retried.
            error_log('CarPhotoR2: ' . count($res['failed']) . ' upload(s) failed under ' . $absDir);
        }
        return $res['ok'];
    }

    // ── deleting ────────────────────────────────────────────────────────────

    /** Drop one file from R2. Mirrors unlinking it on disk. */
    public static function delete(string $path): bool
    {
        $r2  = self::r2();
        $key = self::keyFor($path);
        if (!$r2 || $key === '') return false;
        return $r2->deleteObject($key);
    }

    /** Drop a whole car folder from R2. Mirrors deleting it on disk. */
    public static function deleteCar(string $pPath, int $carId): int
    {
        $r2 = self::r2();
        if (!$r2 || $pPath === '' || $carId <= 0) return 0;
        return $r2->deletePrefix(trim($pPath, '/') . '/' . $carId . '/');
    }

    // ── reading ─────────────────────────────────────────────────────────────

    /**
     * Bytes of a photo, local first then R2. Replaces file_get_contents() at the
     * call sites that ship a photo somewhere else (999, Facebook, Telegram, the
     * AI description) — they are the only things that break once the local copies
     * go away.
     */
    public static function read(string $path): ?string
    {
        $abs = self::abs($path);
        if (is_file($abs)) {
            $b = @file_get_contents($abs);
            if ($b !== false) return $b;
        }
        $r2 = self::r2();
        $key = self::keyFor($abs);
        if (!$r2 || $key === '') return null;
        $b = $r2->getObject($key);
        return $b !== null && $b !== '' ? $b : null;
    }

    /** Same idea for the existence checks that gate the admin thumbnails. */
    public static function exists(string $path): bool
    {
        $abs = self::abs($path);
        if (is_file($abs)) return true;
        $r2 = self::r2();
        $key = self::keyFor($abs);
        return $r2 && $key !== '' && $r2->exists($key);
    }

    /**
     * A real path on disk for callers that must hand a filename to something
     * else (CURLFile uploads to 999 / Facebook / Telegram). Returns the original
     * while it still exists, otherwise pulls the object from R2 into tmp/.
     *
     * Temp copies clean themselves up at the end of the request/process, so no
     * caller has to remember — forgetting is exactly how tmp/ filled up before.
     */
    public static function localCopy(string $path): ?string
    {
        $absPath = self::abs($path);
        if (is_file($absPath)) return $absPath;
        $bytes = self::read($absPath);
        if ($bytes === null) return null;

        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($root === '' || !is_dir($root)) $root = dirname(__DIR__, 2);
        $tmpDir = rtrim($root, '/') . '/tmp';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION)) ?: 'jpg';
        $tmp = $tmpDir . '/r2_' . uniqid('', true) . '.' . $ext;
        if (@file_put_contents($tmp, $bytes) === false) return null;

        self::$temps[] = $tmp;
        if (count(self::$temps) === 1) {
            register_shutdown_function(function () {
                foreach (self::$temps as $f) @unlink($f);
                self::$temps = [];
            });
        }
        return $tmp;
    }

    /** @var string[] temp files pulled from R2, removed on shutdown */
    private static array $temps = [];

    private static function mime(string $path): string
    {
        static $types = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'webp' => 'image/webp', 'gif' => 'image/gif',
            'pdf' => 'application/pdf', 'html' => 'text/html',
        ];
        return $types[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }
}
