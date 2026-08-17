<?php
/**
 * Which table — and which column inside it — takes up the most space?
 *
 * MySQL only tracks size per TABLE, in information_schema. Per column there is
 * no bookkeeping at all, so the only way to get it is to add up the stored bytes
 * with SUM(LENGTH(col)), which reads the table. That is why this script splits in
 * two: the table list is instant and free, the column breakdown is opt-in and
 * runs over one table at a time.
 *
 *   https://www.sauto.md/console/db_column_sizes.php?token=cron2026
 *   &table=gh3sp_car_ctlg   columns of one table, biggest first
 *   &sample=200000          measure the newest N rows and scale up (big tables)
 *
 * LENGTH() counts BYTES, so a UTF-8 Cyrillic description is counted at its real
 * cost on disk, not at its character count.
 */
$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && (($_GET['token'] ?? '') !== 'cron2026')) { http_response_code(403); die('Forbidden'); }

@set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

error_reporting(E_ALL);
date_default_timezone_set('Europe/Chisinau');
@ini_set('memory_limit', '512M');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

require_once __DIR__ . '/../environment.php';
require_once('../' . _DEFAULT . '/defines.php');
require('../' . _DEFAULT . '/dbi.php');
require_once('../' . _DEFAULT . '/functions.php');

function human(float $bytes): string
{
    $u = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($u) - 1) { $bytes /= 1024; $i++; }
    return sprintf('%.1f %s', $bytes, $u[$i]);
}

/** Backtick-quote an identifier coming from information_schema. */
function ident(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

$dbName = $db->query('SELECT DATABASE()')->fetchColumn();
$table  = trim((string)($_GET['table'] ?? ''));
$sample = (int)($_GET['sample'] ?? 0);

// ─────────────────────────── table list ───────────────────────────
if ($table === '') {
    echo "Baza de date: {$dbName}\n";
    echo "Tabele dupa marime (date + indecsi). Adauga &table=NUME pentru coloane.\n\n";

    $stmt = $db->prepare(
        'SELECT TABLE_NAME,
                TABLE_ROWS,
                DATA_LENGTH,
                INDEX_LENGTH,
                DATA_LENGTH + INDEX_LENGTH AS TOTAL
           FROM information_schema.TABLES
          WHERE TABLE_SCHEMA = ?
          ORDER BY TOTAL DESC'
    );
    $stmt->execute([$dbName]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grand = 0;
    foreach ($rows as $r) { $grand += (float)$r['TOTAL']; }

    printf("%-42s %12s %12s %12s %10s %8s\n", 'TABEL', 'DATE', 'INDECSI', 'TOTAL', 'RANDURI', 'COTA');
    echo str_repeat('-', 100), "\n";
    foreach ($rows as $r) {
        $tot = (float)$r['TOTAL'];
        if ($tot < 1024 * 100) continue;   // under 100 KB is noise
        printf("%-42s %12s %12s %12s %10s %7.1f%%\n",
            $r['TABLE_NAME'],
            human((float)$r['DATA_LENGTH']),
            human((float)$r['INDEX_LENGTH']),
            human($tot),
            number_format((float)$r['TABLE_ROWS']),
            $grand > 0 ? $tot / $grand * 100 : 0);
    }
    echo str_repeat('-', 100), "\n";
    printf("%-42s %38s\n", 'TOTAL', human($grand));
    echo "\nNota: TABLE_ROWS e o estimare a InnoDB, nu un numar exact.\n";
    exit;
}

// ─────────────────────────── one table, by column ───────────────────────────
$chk = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES
                      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?');
$chk->execute([$dbName, $table]);
if (!(int)$chk->fetchColumn()) { die("Tabelul {$table} nu exista in {$dbName}.\n"); }

$cs = $db->prepare('SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
                     ORDER BY ORDINAL_POSITION');
$cs->execute([$dbName, $table]);
$cols = $cs->fetchAll(PDO::FETCH_ASSOC);
if (!$cols) { die("Tabelul {$table} nu are coloane?\n"); }

$total = (int)$db->query('SELECT COUNT(*) FROM ' . ident($table))->fetchColumn();

echo "Tabel : {$table}\n";
echo "Randuri: " . number_format($total) . "\n";

// One pass over the table, every column summed in the same scan.
$parts = [];
foreach ($cols as $c) {
    $parts[] = 'SUM(LENGTH(' . ident($c['COLUMN_NAME']) . ')) AS ' . ident($c['COLUMN_NAME']);
}

$from = ident($table);
$scanned = $total;
if ($sample > 0 && $sample < $total) {
    // Newest rows are the representative ones for a table that keeps growing.
    $pk = $db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS
                         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = "PRI"
                         ORDER BY ORDINAL_POSITION LIMIT 1');
    $pk->execute([$dbName, $table]);
    $pkCol = $pk->fetchColumn();
    $order = $pkCol ? ' ORDER BY ' . ident($pkCol) . ' DESC' : '';
    $from = '(SELECT * FROM ' . ident($table) . $order . ' LIMIT ' . (int)$sample . ') AS s';
    $scanned = $sample;
    echo "Esantion: " . number_format($sample) . " randuri, extrapolat la total\n";
}

$t0 = microtime(true);
$sums = $db->query('SELECT ' . implode(', ', $parts) . ' FROM ' . $from)->fetch(PDO::FETCH_ASSOC);
$secs = microtime(true) - $t0;

$scale = ($scanned > 0 && $scanned < $total) ? $total / $scanned : 1.0;

$out = [];
$sum = 0.0;
foreach ($cols as $c) {
    $b = (float)($sums[$c['COLUMN_NAME']] ?? 0) * $scale;
    $sum += $b;
    $out[] = ['name' => $c['COLUMN_NAME'], 'type' => $c['COLUMN_TYPE'], 'bytes' => $b];
}
usort($out, fn($a, $b) => $b['bytes'] <=> $a['bytes']);

echo "Citit in " . sprintf('%.1f', $secs) . "s\n\n";
printf("%-28s %-24s %12s %8s %12s\n", 'COLOANA', 'TIP', 'TOTAL', 'COTA', 'MEDIE/RAND');
echo str_repeat('-', 90), "\n";
foreach ($out as $o) {
    printf("%-28s %-24s %12s %7.1f%% %12s\n",
        $o['name'],
        strlen($o['type']) > 23 ? substr($o['type'], 0, 22) . '…' : $o['type'],
        human($o['bytes']),
        $sum > 0 ? $o['bytes'] / $sum * 100 : 0,
        $total > 0 ? human($o['bytes'] / $total) : '-');
}
echo str_repeat('-', 90), "\n";
printf("%-53s %12s\n", 'TOTAL (continut, fara indecsi)', human($sum));
echo "\nLENGTH() masoara continutul stocat. Diferenta fata de DATA_LENGTH din\n";
echo "lista de tabele sunt indecsii, umplutura paginilor si spatiul eliberat de\n";
echo "stergeri pe care InnoDB nu l-a returnat inca sistemului de fisiere.\n";
