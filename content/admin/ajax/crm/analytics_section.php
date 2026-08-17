<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');
require_once(_ADM_INCL.'/crm/crm_core.php');

if (!crm_can_analytics($user_role, $user_id)) {
    echo json_encode(['ok' => false]); exit;
}

$section = $_GET['section'] ?? '';
$y   = max(2020, min((int)date('Y'), (int)($_GET['y']  ?? date('Y'))));
$m   = max(1,    min(12,             (int)($_GET['m']  ?? date('n'))));
$uid = (int)($_GET['uid'] ?? 0);

$df = sprintf('%04d-%02d-01', $y, $m);
$dt = date('Y-m-t', strtotime($df));

$prev_ts = strtotime($df . ' -1 month');
$pf = date('Y-m-01', $prev_ts);
$pt = date('Y-m-t',  $prev_ts);

$month_names = ['Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie','Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'];

// Builds WHERE for crm_leads by created_at
function as_where(string $df, string $dt, int $uid, string $alias = 'l'): array {
    $w = "DATE({$alias}.created_at) BETWEEN :df AND :dt AND {$alias}.status != 'junk'";
    $p = [':df' => $df, ':dt' => $dt];
    if ($uid) { $w .= " AND {$alias}.owner_id = :uid"; $p[':uid'] = $uid; }
    return [$w, $p];
}

// Counts closed docs (same logic as /crm/closed)
function as_count_closed(PDO $db, string $prefx, string $df, string $dt, int $uid): int {
    $w = "d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0) AND d.date BETWEEN :df AND :dt";
    $p = [':df' => $df, ':dt' => $dt];
    if ($uid) { $w .= " AND d.adm = :uid"; $p[':uid'] = $uid; }
    $r = $db->prepare("SELECT COUNT(DISTINCT d.id) FROM {$prefx}_docs_ctlg d WHERE $w");
    $r->execute($p);
    return (int)$r->fetchColumn();
}

header('Content-Type: application/json');

switch ($section) {

    case 'funnel':
        [$bw, $bp] = as_where($df, $dt, $uid);
        $r = $db->prepare("SELECT
            SUM(l.status IN ('active','missed','unprocessed','processed')) AS new_cnt,
            SUM(l.status = 'transaction')                                  AS tx_cnt,
            SUM(l.status = 'closed')                                       AS cl_cnt
            FROM {$prefx}_crm_leads l WHERE $bw");
        $r->execute($bp); $f = $r->fetchObject();
        $new = (int)($f->new_cnt ?? 0);
        $tx  = (int)($f->tx_cnt  ?? 0);
        $cl  = (int)($f->cl_cnt  ?? 0);
        $tot = $new + $tx + $cl;
        echo json_encode([
            'ok'    => true,
            'label' => ($month_names[$m-1] ?? '') . ' ' . $y,
            'new'   => $new, 'tx' => $tx, 'cl' => $cl, 'total' => $tot,
            'new_pct' => $tot > 0 ? round($new/$tot*100) : 0,
            'tx_pct'  => $tot > 0 ? round($tx/$tot*100)  : 0,
            'cl_pct'  => $tot > 0 ? round($cl/$tot*100)  : 0,
        ]);
        break;

    case 'activity':
        $trend_stmt = $db->prepare("
            SELECT DATE_FORMAT(l.created_at,'%Y-%m') AS ym, COUNT(*) AS leads_cnt
            FROM {$prefx}_crm_leads l
            WHERE l.created_at >= DATE_SUB(:d1, INTERVAL 11 MONTH)
              AND l.created_at < DATE_ADD(:d2, INTERVAL 1 MONTH)
              AND l.status != 'junk'
            GROUP BY ym ORDER BY ym ASC");
        $trend_stmt->execute([':d1' => $df, ':d2' => $dt]);
        $trend_map = [];
        foreach ($trend_stmt->fetchAll(PDO::FETCH_OBJ) as $t) $trend_map[$t->ym] = (int)$t->leads_cnt;

        $closed_stmt = $db->prepare("
            SELECT DATE_FORMAT(d.date,'%Y-%m') AS ym, COUNT(*) AS cnt
            FROM {$prefx}_docs_ctlg d
            WHERE d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0)
              AND d.date >= DATE_SUB(:d1, INTERVAL 11 MONTH)
              AND d.date < DATE_ADD(:d2, INTERVAL 1 MONTH)
            GROUP BY ym ORDER BY ym ASC");
        $closed_stmt->execute([':d1' => $df, ':d2' => $dt]);
        $closed_map = [];
        foreach ($closed_stmt->fetchAll(PDO::FETCH_OBJ) as $t) $closed_map[$t->ym] = (int)$t->cnt;

        $labels = $leads = $closed = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime($df . " -$i months"));
            $labels[]  = substr($ym, 5) . '/' . substr($ym, 0, 4);
            $leads[]   = $trend_map[$ym]  ?? 0;
            $closed[]  = $closed_map[$ym] ?? 0;
        }
        echo json_encode(['ok' => true, 'label' => ($month_names[$m-1] ?? '') . ' ' . $y,
            'labels' => $labels, 'leads' => $leads, 'closed' => $closed]);
        break;

    case 'managers':
        $uid_cond = $uid ? " AND d.adm = :uid" : "";
        $params   = [':df' => $df, ':dt' => $dt];
        if ($uid) $params[':uid'] = $uid;
        $stmt = $db->prepare("
            SELECT u.name, COUNT(d.id) AS closed,
                   SUM(d.lead_id IS NOT NULL) AS closed_crm,
                   SUM(d.lead_id IS NULL) AS closed_direct
            FROM {$prefx}_docs_ctlg d
            JOIN {$prefx}_adm_usr u ON u.id = d.adm
            WHERE d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0)
              AND DATE(d.date) BETWEEN :df AND :dt $uid_cond
            GROUP BY d.adm ORDER BY closed DESC LIMIT 8");
        $stmt->execute($params);
        $rows  = $stmt->fetchAll(PDO::FETCH_OBJ);
        $total = array_sum(array_map(fn($x) => (int)$x->closed, $rows));
        echo json_encode(['ok' => true, 'label' => ($month_names[$m-1] ?? '') . ' ' . $y,
            'rows' => $rows, 'total' => $total]);
        break;

    case 'sales':
        $stmt = $db->prepare("
            SELECT MONTH(d.date) AS mo, COUNT(*) AS cnt
            FROM {$prefx}_docs_ctlg d
            WHERE d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0) AND YEAR(d.date) = :yr
            GROUP BY mo ORDER BY mo ASC");
        $stmt->execute([':yr' => $y]);
        $by_month = array_fill(1, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $s) $by_month[(int)$s->mo] = (int)$s->cnt;
        echo json_encode(['ok' => true, 'year' => $y, 'sel_month' => $m,
            'data' => array_values($by_month)]);
        break;

    case 'sources':
        [$bw, $bp] = as_where($df, $dt, $uid);
        $bw2 = str_replace([':df',':dt',':uid'], [':df2',':dt2',':uid2'], $bw);
        $bp2 = [':df2' => $df, ':dt2' => $dt];
        if ($uid) $bp2[':uid2'] = $uid;
        $stmt = $db->prepare("
            SELECT name, color, SUM(total) AS total, SUM(closed) AS closed FROM (
                SELECT COALESCE(s.name,'Necunoscută') AS name,
                       COALESCE(s.color,'#9ca3af') AS color,
                       COUNT(l.id) AS total,
                       SUM(l.status IN ('transaction','closed')) AS closed
                FROM {$prefx}_crm_leads l
                LEFT JOIN {$prefx}_crm_sources s ON s.id = l.source_id
                WHERE $bw AND l.origin != 'inbox'
                GROUP BY l.source_id
                UNION ALL
                SELECT CONCAT('Mesaj · ',
                           CASE ins.channel WHEN '999md' THEN '999' WHEN 'facebook' THEN 'Facebook' WHEN 'instagram' THEN 'Instagram' WHEN 'telegram' THEN 'Telegram' WHEN 'viber' THEN 'Viber' WHEN 'site' THEN 'Site' ELSE ins.channel END,
                           ' · ',
                           CASE ins.page_id
                               WHEN 'order_999md'      THEN 'Comanda'
                               WHEN 'korea_999md'      THEN 'Encars'
                               WHEN 'usa_999md'        THEN 'SautoSUA'
                               WHEN 'sautohaus_999md'  THEN 'SAUTO-HAUS'
                               WHEN 'regular_999md'    THEN 'Comerciale'
                               WHEN 'order_telegram'   THEN 'Comanda'
                               WHEN 'regular_telegram' THEN 'Stock'
                               WHEN '725963964220309'  THEN 'SAUTO'
                               WHEN '482777831588669'  THEN 'Piața Pruncu'
                               WHEN 'ordercars'        THEN 'Order Cars'
                               WHEN 'cars'             THEN 'Stock'
                               WHEN 'order'            THEN 'Order'
                               WHEN 'credit'           THEN 'Credit'
                               WHEN 'tradein'          THEN 'Trade-in'
                               WHEN 'sale'             THEN 'Sale'
                               ELSE ins.page_id END
                       ) AS name,
                       '#6366f1' AS color,
                       COUNT(l.id) AS total,
                       SUM(l.status IN ('transaction','closed')) AS closed
                FROM {$prefx}_crm_leads l
                JOIN {$prefx}_crm_inbox_sessions ins ON ins.id = (SELECT MIN(id) FROM {$prefx}_crm_inbox_sessions WHERE lead_id = l.id)
                WHERE $bw2 AND l.origin = 'inbox'
                GROUP BY ins.channel, ins.page_id
            ) _src
            GROUP BY name, color ORDER BY total DESC LIMIT 12");
        $stmt->execute(array_merge($bp, $bp2));
        $rows   = $stmt->fetchAll(PDO::FETCH_OBJ);
        $max    = !empty($rows) ? max(array_map(fn($x) => (int)$x->total, $rows)) : 1;
        $result = [];
        foreach ($rows as $src) {
            $result[] = [
                'name'  => $src->name,
                'color' => $src->color,
                'total' => (int)$src->total,
                'closed'=> (int)$src->closed,
                'conv'  => $src->total > 0 ? round($src->closed / $src->total * 100, 1) : 0,
                'bar'   => $max > 0 ? round($src->total / $max * 100) : 0,
            ];
        }
        echo json_encode(['ok' => true, 'label' => ($month_names[$m-1] ?? '') . ' ' . $y,
            'rows' => $result]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Unknown section']);
}
