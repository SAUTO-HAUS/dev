<?php defined( '_DOIT' ) or die( 'Restricted access' );
/**
 * Jurnal Consimțământ Cookie (Legea 195/2024, Cap. 3).
 *
 * Read-only register of cookie decisions, with the search/filter/sort and CSV
 * export an auditor needs. The table itself holds no personal data — see
 * sql_scripts/create_gdpr_consent_table.sql.
 */

$gdpr_tbl = $prefx.'_cookie_consent_logs';

/**
 * Page translations (ro/ru/en), same shape as b2b_admin_lang.php. Kept inline
 * because this is a single self-contained screen.
 */
$gdpr_lang_all = [
    'ro' => [
        'title'      => 'Jurnal Consimțământ Cookie',
        'sub'        => 'Registrul deciziilor vizitatorilor privind cookie-urile',
        'no_table_1' => 'Tabelul %s nu există încă.',
        'no_table_2' => 'Rulează %s pentru a-l crea.',
        'st_total'   => 'Total (filtru curent)',
        'st_accept'  => 'Acceptate total',
        'st_reject'  => 'Refuzate',
        'st_custom'  => 'Personalizate',
        'f_search'   => 'Caută ID Consimțământ (exact)',
        'f_from'     => 'De la data',
        'f_to'       => 'Până la data',
        'f_status'   => 'Status',
        'opt_all'    => 'Toate',
        'opt_accept' => 'Acceptate',
        'opt_reject' => 'Refuzate',
        'opt_custom' => 'Personalizate',
        'btn_filter' => 'Filtrează',
        'btn_reset'  => 'Resetează',
        'btn_csv'    => 'Descarcă CSV',
        'empty'      => 'Nicio înregistrare pentru filtrele selectate.',
        'c_date'     => 'Data și ora',
        'c_id'       => 'ID Consimțământ',
        'c_status'   => 'Status',
        'c_ga'       => 'Google Analytics',
        'c_mkt'      => 'Pixeli Marketing',
        'c_ver'      => 'Versiune',
        'tag_accept' => 'Acceptat total',
        'tag_reject' => 'Refuzat',
        'tag_custom' => 'Personalizat',
        'copy'       => 'Copiază ID-ul complet',
        'copied'     => 'copiat!',
        'csv_gen'    => 'Raport generat la:',
        'csv_op'     => 'Operator:',
        'csv_total'  => 'Total inregistrari in document:',
        'csv_accept' => 'Total acceptate (accept_all):',
        'csv_reject' => 'Total refuzate (reject_all):',
        'csv_custom' => 'Total personalizate (custom_selection):',
    ],
    'ru' => [
        'title'      => 'Журнал согласий на cookie',
        'sub'        => 'Реестр решений посетителей относительно cookie',
        'no_table_1' => 'Таблица %s ещё не создана.',
        'no_table_2' => 'Запустите %s, чтобы создать её.',
        'st_total'   => 'Всего (текущий фильтр)',
        'st_accept'  => 'Приняли всё',
        'st_reject'  => 'Отказались',
        'st_custom'  => 'Настроили',
        'f_search'   => 'Поиск ID согласия (точный)',
        'f_from'     => 'С даты',
        'f_to'       => 'По дату',
        'f_status'   => 'Статус',
        'opt_all'    => 'Все',
        'opt_accept' => 'Принятые',
        'opt_reject' => 'Отклонённые',
        'opt_custom' => 'Настроенные',
        'btn_filter' => 'Фильтровать',
        'btn_reset'  => 'Сбросить',
        'btn_csv'    => 'Скачать CSV',
        'empty'      => 'Нет записей по выбранным фильтрам.',
        'c_date'     => 'Дата и время',
        'c_id'       => 'ID согласия',
        'c_status'   => 'Статус',
        'c_ga'       => 'Google Analytics',
        'c_mkt'      => 'Маркетинговые пиксели',
        'c_ver'      => 'Версия',
        'tag_accept' => 'Принято всё',
        'tag_reject' => 'Отказ',
        'tag_custom' => 'Настроено',
        'copy'       => 'Скопировать полный ID',
        'copied'     => 'скопировано!',
        'csv_gen'    => 'Отчёт сформирован:',
        'csv_op'     => 'Оператор:',
        'csv_total'  => 'Всего записей в документе:',
        'csv_accept' => 'Всего принято (accept_all):',
        'csv_reject' => 'Всего отказов (reject_all):',
        'csv_custom' => 'Всего настроено (custom_selection):',
    ],
    'en' => [
        'title'      => 'Cookie Consent Log',
        'sub'        => 'Register of visitor decisions about cookies',
        'no_table_1' => 'Table %s does not exist yet.',
        'no_table_2' => 'Run %s to create it.',
        'st_total'   => 'Total (current filter)',
        'st_accept'  => 'Accepted all',
        'st_reject'  => 'Rejected',
        'st_custom'  => 'Custom',
        'f_search'   => 'Search Consent ID (exact)',
        'f_from'     => 'From date',
        'f_to'       => 'To date',
        'f_status'   => 'Status',
        'opt_all'    => 'All',
        'opt_accept' => 'Accepted',
        'opt_reject' => 'Rejected',
        'opt_custom' => 'Custom',
        'btn_filter' => 'Filter',
        'btn_reset'  => 'Reset',
        'btn_csv'    => 'Download CSV',
        'empty'      => 'No records for the selected filters.',
        'c_date'     => 'Date and time',
        'c_id'       => 'Consent ID',
        'c_status'   => 'Status',
        'c_ga'       => 'Google Analytics',
        'c_mkt'      => 'Marketing Pixels',
        'c_ver'      => 'Version',
        'tag_accept' => 'Accepted all',
        'tag_reject' => 'Rejected',
        'tag_custom' => 'Custom',
        'copy'       => 'Copy the full ID',
        'copied'     => 'copied!',
        'csv_gen'    => 'Report generated at:',
        'csv_op'     => 'Operator:',
        'csv_total'  => 'Total records in document:',
        'csv_accept' => 'Total accepted (accept_all):',
        'csv_reject' => 'Total rejected (reject_all):',
        'csv_custom' => 'Total custom (custom_selection):',
    ],
];
$gl = $gdpr_lang_all[$_COOKIE['lang'] ?? 'ro'] ?? $gdpr_lang_all['ro'];

/** One escaped label. Guarded: an admin page must survive being included twice. */
if (!function_exists('gdpr_t')) {
function gdpr_t(string $key): string {
    global $gl;
    return htmlspecialchars($gl[$key] ?? $key, ENT_QUOTES, 'UTF-8');
}
}

// --- filters ---------------------------------------------------------------

$f_cid  = trim($_GET['cid']  ?? '');
$f_from = trim($_GET['from'] ?? '');
$f_to   = trim($_GET['to']   ?? '');
$f_st   = trim($_GET['st']   ?? '');
$page   = max(1, (int)($_GET['pg'] ?? 1));
$perPg  = 50;

$sortCol = $_GET['sort'] ?? 'action_timestamp';
$sortDir = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
// Whitelist: the column name is interpolated into SQL, so it can never come
// straight from the query string.
$sortable = ['action_timestamp', 'consent_id', 'global_status', 'tracker_analytics', 'tracker_marketing'];
if (!in_array($sortCol, $sortable, true)) { $sortCol = 'action_timestamp'; }

$where  = [];
$params = [];

if ($f_cid !== '') {
    $where[] = '`consent_id` = :cid';
    $params[':cid'] = strtolower($f_cid);
}
if ($f_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_from)) {
    $where[] = '`action_timestamp` >= :dfrom';
    $params[':dfrom'] = $f_from.' 00:00:00';
}
if ($f_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_to)) {
    $where[] = '`action_timestamp` <= :dto';
    $params[':dto'] = $f_to.' 23:59:59';
}
if (in_array($f_st, ['accept_all', 'reject_all', 'custom_selection'], true)) {
    $where[] = '`global_status` = :st';
    $params[':st'] = $f_st;
}
$whereSql = $where ? ' WHERE '.implode(' AND ', $where) : '';

// --- CSV export (Task 5): exports the current view, not the whole table -----

if (($_GET['export'] ?? '') === 'csv') {
    $rows = [];
    $stats = ['total' => 0, 'accept' => 0, 'reject' => 0, 'custom' => 0];
    try {
        $q = $db->prepare('SELECT * FROM '.$gdpr_tbl.$whereSql.' ORDER BY `'.$sortCol.'` '.$sortDir);
        $q->execute($params);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) { $rows = []; }

    foreach ($rows as $r) {
        $stats['total']++;
        if ($r['global_status'] === 'accept_all')            { $stats['accept']++; }
        elseif ($r['global_status'] === 'reject_all')        { $stats['reject']++; }
        else                                                 { $stats['custom']++; }
    }

    while (ob_get_level() > 0) { ob_end_clean(); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jurnal_consimtamant_cookie_'.date('Y-m-d_His').'.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads the diacritics

    $csv = $gdpr_lang_all['en'];
    fputcsv($out, [$csv['csv_gen'],    date('d.m.Y H:i')]);
    fputcsv($out, [$csv['csv_op'],     'SAUTO S.R.L., IDNO 1017600006845']);
    fputcsv($out, [$csv['csv_total'],  $stats['total']]);
    fputcsv($out, [$csv['csv_accept'], $stats['accept']]);
    fputcsv($out, [$csv['csv_reject'], $stats['reject']]);
    fputcsv($out, [$csv['csv_custom'], $stats['custom']]);
    fputcsv($out, []);
    fputcsv($out, ['Timestamp_UTC', 'Consent_ID', 'Status', 'Analytics_Granted', 'Marketing_Granted', 'Policy_Version']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['action_timestamp'],
            $r['consent_id'],
            $r['global_status'],
            $r['tracker_analytics'] ? 'true' : 'false',
            $r['tracker_marketing'] ? 'true' : 'false',
            $r['policy_version'],
        ]);
    }
    fclose($out);
    exit;
}

// --- page data -------------------------------------------------------------

$tableMissing = false;
$total = 0;
$rows  = [];
$sums  = ['accept' => 0, 'reject' => 0, 'custom' => 0];

try {
    $cq = $db->prepare('SELECT COUNT(*) FROM '.$gdpr_tbl.$whereSql);
    $cq->execute($params);
    $total = (int)$cq->fetchColumn();

    $offset = ($page - 1) * $perPg;
    $q = $db->prepare('SELECT * FROM '.$gdpr_tbl.$whereSql.'
        ORDER BY `'.$sortCol.'` '.$sortDir.' LIMIT '.$perPg.' OFFSET '.$offset);
    $q->execute($params);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    // Totals for the whole filtered set, not just the page on screen.
    $sq = $db->prepare('SELECT `global_status`, COUNT(*) c FROM '.$gdpr_tbl.$whereSql.' GROUP BY `global_status`');
    $sq->execute($params);
    foreach ($sq as $r) {
        if ($r['global_status'] === 'accept_all')     { $sums['accept'] = (int)$r['c']; }
        elseif ($r['global_status'] === 'reject_all') { $sums['reject'] = (int)$r['c']; }
        else                                          { $sums['custom'] = (int)$r['c']; }
    }
} catch (\Throwable $e) {
    $tableMissing = true;
}

$pages   = max(1, (int)ceil($total / $perPg));
$baseUrl = strtok($_SERVER['REQUEST_URI'], '?');

/** Build a URL for this page keeping the active filters. */
if (!function_exists('gdpr_url')) {
function gdpr_url(string $base, array $over = []): string {
    $q = array_merge([
        'cid'  => $_GET['cid']  ?? '',
        'from' => $_GET['from'] ?? '',
        'to'   => $_GET['to']   ?? '',
        'st'   => $_GET['st']   ?? '',
        'sort' => $_GET['sort'] ?? 'action_timestamp',
        'dir'  => $_GET['dir']  ?? 'desc',
        'pg'   => $_GET['pg']   ?? 1,
    ], $over);
    $q = array_filter($q, static fn($v) => $v !== '' && $v !== null);
    return $base.'?'.http_build_query($q);
}
}

/** Header cell that toggles the sort direction on its column. */
if (!function_exists('gdpr_sort_th')) {
function gdpr_sort_th(string $base, string $col, string $label, string $curCol, string $curDir): string {
    $dir  = ($curCol === $col && strtolower($curDir) === 'asc') ? 'desc' : 'asc';
    $mark = $curCol === $col ? (strtolower($curDir) === 'asc' ? ' ▲' : ' ▼') : '';
    return '<th><a href="'.htmlspecialchars(gdpr_url($base, ['sort'=>$col, 'dir'=>$dir, 'pg'=>1])).'">'.$label.$mark.'</a></th>';
}
}
?>

<style>
	.gdpr-wrap {font-size:.9rem;}
	.gdpr-wrap h2 {margin:0 0 .3rem; font-size:1.2rem;}
	.gdpr-wrap .sub {margin:0 0 1rem; color:#777; font-size:.82rem;}
	.gdpr-stats {display:flex; flex-flow:row wrap; gap:.6rem; margin-bottom:1rem;}
	.gdpr-stat {flex:1 1 140px; padding:.6rem .8rem; border:1px solid #e5e5e5; border-radius:.4rem; background:#fafafa;}
	.gdpr-stat b {display:block; font-size:1.3rem; line-height:1.2;}
	.gdpr-stat span {color:#777; font-size:.78rem;}
	.gdpr-stat.ok b {color:#2e7d32;} .gdpr-stat.no b {color:#c62828;} .gdpr-stat.cu b {color:#ef6c00;}
	.gdpr-filters {display:flex; flex-flow:row wrap; gap:.5rem; align-items:flex-end; margin-bottom:1rem; padding:.8rem; border:1px solid #e5e5e5; border-radius:.4rem;}
	.gdpr-filters .fld {display:flex; flex-flow:column; gap:.2rem;}
	.gdpr-filters label {font-size:.75rem; color:#777;}
	.gdpr-filters input, .gdpr-filters select {padding:.4rem .5rem; border:1px solid #ccc; border-radius:.3rem; font-size:.85rem;}
	.gdpr-filters input[name="cid"] {min-width:270px;}
	.gdpr-filters .fld {flex:0 1 auto; min-width:0;}
	.gdpr-filters input, .gdpr-filters select {max-width:100%; box-sizing:border-box;}
	.gdpr-tbl-wrap {width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;}
	.gdpr-acts {display:flex; flex-flow:row wrap; gap:.5rem; align-items:flex-end;}
	.gdpr-btn {padding:.45rem .9rem; border:none; border-radius:.3rem; background:var(--clr,#e2001a); color:#fff; font-size:.85rem; cursor:pointer; text-decoration:none; display:inline-block;}
	.gdpr-btn.ghost {background:#666;}
	.gdpr-tbl {width:100%; border-collapse:collapse;}
	.gdpr-tbl th, .gdpr-tbl td {padding:.5rem .6rem; border-bottom:1px solid #eee; text-align:left; white-space:nowrap;}
	.gdpr-tbl th {background:#f6f6f6; font-size:.78rem; text-transform:uppercase; color:#666;}
	.gdpr-tbl th a {color:#666; text-decoration:none;}
	.gdpr-tbl tr:hover td {background:#fcfcfc;}
	.gdpr-tag {display:inline-block; padding:.15rem .5rem; border-radius:.25rem; font-size:.75rem; font-weight:600;}
	.gdpr-tag.ok {background:#e8f5e9; color:#2e7d32;}
	.gdpr-tag.no {background:#ffebee; color:#c62828;}
	.gdpr-tag.cu {background:#fff3e0; color:#ef6c00;}
	.gdpr-yes {color:#2e7d32; font-weight:700;}
	.gdpr-nope {color:#c62828; font-weight:700;}
	.gdpr-cid-box {display:inline-flex; align-items:center; gap:.35rem; cursor:pointer;}
	.gdpr-cid {font-family:monospace; border-bottom:1px dashed #bbb;}
	.gdpr-cid-box:hover .gdpr-cid {color:var(--clr,#e2001a); border-bottom-color:var(--clr,#e2001a);}
	.gdpr-copy {display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; padding:0; border:1px solid #ddd; border-radius:.3rem; background:#fff; color:#777; cursor:pointer; flex:0 0 auto; transition:.15s;}
	.gdpr-cid-box:hover .gdpr-copy {border-color:var(--clr,#e2001a); color:var(--clr,#e2001a);}
	.gdpr-copy:active {transform:scale(.9);}
	.gdpr-cid-box.copied .gdpr-cid {color:#2e7d32; border-bottom-color:#2e7d32;}
	.gdpr-cid-box.copied .gdpr-copy {border-color:#2e7d32; color:#2e7d32;}
	.gdpr-pager {display:flex; gap:.3rem; flex-wrap:wrap; margin-top:1rem;}
	.gdpr-pager a, .gdpr-pager span {padding:.35rem .6rem; border:1px solid #ddd; border-radius:.3rem; text-decoration:none; color:#333; font-size:.8rem;}
	.gdpr-pager span.act {background:var(--clr,#e2001a); color:#fff; border-color:var(--clr,#e2001a);}
	.gdpr-empty {padding:2rem; text-align:center; color:#888;}

	/* --- tablet: two stat cards per row, filters keep wrapping --- */
	@media (max-width:1024px){
		.gdpr-stat {flex:1 1 calc(50% - .3rem);}
		.gdpr-filters {gap:.6rem;}
		.gdpr-filters .fld {flex:1 1 200px;}
		.gdpr-filters input[name="cid"] {min-width:0; width:100%;}
	}

	/* --- phone: everything stacks; the table stays scrollable in its box --- */
	@media (max-width:640px){
		.gdpr-wrap h2 {font-size:1.05rem;}
		.gdpr-stats {gap:.4rem;}
		.gdpr-stat {flex:1 1 calc(50% - .2rem); padding:.5rem .6rem;}
		.gdpr-stat b {font-size:1.1rem;}
		.gdpr-stat span {font-size:.72rem;}

		.gdpr-filters {flex-flow:column; align-items:stretch; padding:.7rem;}
		.gdpr-filters .fld {flex:1 1 auto; width:100%;}
		.gdpr-filters input, .gdpr-filters select {width:100%;}
		/* The three actions keep one row, each big enough to tap. */
		.gdpr-acts .gdpr-btn {flex:1 1 0; min-width:0; text-align:center; padding:.6rem .4rem; font-size:.8rem;}

		.gdpr-tbl th, .gdpr-tbl td {padding:.45rem .5rem; font-size:.8rem;}
		.gdpr-tbl th {font-size:.7rem;}
		/* Hint that the table scrolls sideways rather than being cut off. */
		.gdpr-tbl-wrap {box-shadow:inset -12px 0 12px -12px rgba(0,0,0,.25);}

		.gdpr-pager a, .gdpr-pager span {padding:.45rem .7rem;}
	}
</style>

<div class="gdpr-wrap">
	<h2><?php echo gdpr_t('title'); ?></h2>
	<p class="sub"><?php echo gdpr_t('sub'); ?></p>

<?php if ($tableMissing): ?>
	<div class="gdpr-empty">
		<?php echo sprintf(gdpr_t('no_table_1'), '<code>'.htmlspecialchars($gdpr_tbl).'</code>'); ?><br>
		<?php echo sprintf(gdpr_t('no_table_2'), '<code>sql_scripts/create_gdpr_consent_table.sql</code>'); ?>
	</div>
<?php else: ?>

	<div class="gdpr-stats">
		<div class="gdpr-stat"><b><?php echo $total; ?></b><span><?php echo gdpr_t('st_total'); ?></span></div>
		<div class="gdpr-stat ok"><b><?php echo $sums['accept']; ?></b><span><?php echo gdpr_t('st_accept'); ?></span></div>
		<div class="gdpr-stat no"><b><?php echo $sums['reject']; ?></b><span><?php echo gdpr_t('st_reject'); ?></span></div>
		<div class="gdpr-stat cu"><b><?php echo $sums['custom']; ?></b><span><?php echo gdpr_t('st_custom'); ?></span></div>
	</div>

	<form class="gdpr-filters" method="get" action="<?php echo htmlspecialchars($baseUrl); ?>">
		<div class="fld">
			<label for="g_cid"><?php echo gdpr_t('f_search'); ?></label>
			<input type="text" id="g_cid" name="cid" value="<?php echo htmlspecialchars($f_cid); ?>" placeholder="f47ac10b-58cc-4372-a567-0e02b2c3d479">
		</div>
		<div class="fld">
			<label for="g_from"><?php echo gdpr_t('f_from'); ?></label>
			<input type="date" id="g_from" name="from" value="<?php echo htmlspecialchars($f_from); ?>">
		</div>
		<div class="fld">
			<label for="g_to"><?php echo gdpr_t('f_to'); ?></label>
			<input type="date" id="g_to" name="to" value="<?php echo htmlspecialchars($f_to); ?>">
		</div>
		<div class="fld">
			<label for="g_st"><?php echo gdpr_t('f_status'); ?></label>
			<select id="g_st" name="st">
				<option value=""><?php echo gdpr_t('opt_all'); ?></option>
				<option value="accept_all"       <?php echo $f_st==='accept_all'?'selected':''; ?>><?php echo gdpr_t('opt_accept'); ?></option>
				<option value="reject_all"       <?php echo $f_st==='reject_all'?'selected':''; ?>><?php echo gdpr_t('opt_reject'); ?></option>
				<option value="custom_selection" <?php echo $f_st==='custom_selection'?'selected':''; ?>><?php echo gdpr_t('opt_custom'); ?></option>
			</select>
		</div>
		<input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortCol); ?>">
		<input type="hidden" name="dir"  value="<?php echo htmlspecialchars(strtolower($sortDir)); ?>">
		<?php // Grouped so they stay on one row when the filters stack vertically
		      // on a phone — three full-width buttons would fill the screen. ?>
		<div class="gdpr-acts">
			<button type="submit" class="gdpr-btn"><?php echo gdpr_t('btn_filter'); ?></button>
			<a class="gdpr-btn ghost" href="<?php echo htmlspecialchars($baseUrl); ?>"><?php echo gdpr_t('btn_reset'); ?></a>
			<a class="gdpr-btn" href="<?php echo htmlspecialchars(gdpr_url($baseUrl, ['export'=>'csv'])); ?>"><?php echo gdpr_t('btn_csv'); ?></a>
		</div>
	</form>

	<?php if (!$rows): ?>
		<div class="gdpr-empty"><?php echo gdpr_t('empty'); ?></div>
	<?php else: ?>
	<?php // Own scroll box: the cells are nowrap, so on a phone the table has to
	      // slide sideways inside this wrapper instead of stretching the page. ?>
	<div class="gdpr-tbl-wrap">
	<table class="gdpr-tbl">
		<thead>
			<tr>
				<?php
				echo gdpr_sort_th($baseUrl, 'action_timestamp',  gdpr_t('c_date'),   $sortCol, $sortDir);
				echo gdpr_sort_th($baseUrl, 'consent_id',        gdpr_t('c_id'),     $sortCol, $sortDir);
				echo gdpr_sort_th($baseUrl, 'global_status',     gdpr_t('c_status'), $sortCol, $sortDir);
				echo gdpr_sort_th($baseUrl, 'tracker_analytics', gdpr_t('c_ga'),     $sortCol, $sortDir);
				echo gdpr_sort_th($baseUrl, 'tracker_marketing', gdpr_t('c_mkt'),    $sortCol, $sortDir);
				?>
				<th><?php echo gdpr_t('c_ver'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($rows as $r):
			$cid   = $r['consent_id'];
			$short = strlen($cid) > 8 ? substr($cid, 0, 4).'…'.substr($cid, -4) : $cid;
			// A refusal can never show a granted tracker: the API forces both to 0
			// on reject_all, so the display simply mirrors the stored row.
			$isRej = $r['global_status'] === 'reject_all';
			$tag   = $isRej ? ['no', gdpr_t('tag_reject')]
			       : ($r['global_status']==='accept_all' ? ['ok', gdpr_t('tag_accept')] : ['cu', gdpr_t('tag_custom')]);
		?>
			<tr>
				<td><?php echo date('d.m.Y H:i', strtotime($r['action_timestamp'].' UTC')); ?></td>
				<?php // Shortened id + an explicit copy button. The whole box is
			      // clickable, but the button is what makes it obvious. ?>
			<td>
				<span class="gdpr-cid-box" data-cid="<?php echo htmlspecialchars($cid, ENT_QUOTES); ?>" title="<?php echo gdpr_t('copy'); ?>">
					<span class="gdpr-cid"><?php echo htmlspecialchars($short); ?></span>
					<button type="button" class="gdpr-copy" aria-label="<?php echo gdpr_t('copy'); ?>">
						<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
							<rect x="9" y="9" width="11" height="11" rx="2"></rect>
							<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
						</svg>
					</button>
				</span>
			</td>
				<td><span class="gdpr-tag <?php echo $tag[0]; ?>"><?php echo $tag[1]; ?></span></td>
				<td class="<?php echo $r['tracker_analytics'] ? 'gdpr-yes' : 'gdpr-nope'; ?>"><?php echo $r['tracker_analytics'] ? '✓' : '✕'; ?></td>
				<td class="<?php echo $r['tracker_marketing'] ? 'gdpr-yes' : 'gdpr-nope'; ?>"><?php echo $r['tracker_marketing'] ? '✓' : '✕'; ?></td>
				<td><?php echo htmlspecialchars($r['policy_version']); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	</div>

	<?php if ($pages > 1): ?>
	<div class="gdpr-pager">
		<?php
		$from = max(1, $page - 3); $to = min($pages, $page + 3);
		if ($from > 1) { echo '<a href="'.htmlspecialchars(gdpr_url($baseUrl, ['pg'=>1])).'">1 …</a>'; }
		for ($i = $from; $i <= $to; $i++) {
			echo $i === $page
				? '<span class="act">'.$i.'</span>'
				: '<a href="'.htmlspecialchars(gdpr_url($baseUrl, ['pg'=>$i])).'">'.$i.'</a>';
		}
		if ($to < $pages) { echo '<a href="'.htmlspecialchars(gdpr_url($baseUrl, ['pg'=>$pages])).'">… '.$pages.'</a>'; }
		?>
	</div>
	<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>
</div>

<script>
// Copy the full consent id — auditors quote it verbatim, and only the first and
// last four characters are on screen. The button or the text both work.
document.addEventListener('click', function (e) {
	var box = e.target.closest && e.target.closest('.gdpr-cid-box');
	if (!box) return;

	var cid  = box.getAttribute('data-cid');
	var text = box.querySelector('.gdpr-cid');

	var done = function () {
		if (box.dataset.busy) return;         // ignore repeat clicks mid-feedback
		box.dataset.busy = '1';
		var old = text.textContent;
		text.textContent = <?php echo json_encode($gl['copied'], JSON_UNESCAPED_UNICODE); ?>;
		box.classList.add('copied');
		setTimeout(function () {
			text.textContent = old;
			box.classList.remove('copied');
			delete box.dataset.busy;
		}, 1200);
	};

	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(cid).then(done).catch(function () {});
	} else {
		// http:// admin panels have no async clipboard; fall back to the old way.
		var ta = document.createElement('textarea');
		ta.value = cid; ta.style.position = 'absolute'; ta.style.left = '-9999px';
		document.body.appendChild(ta); ta.select();
		try { document.execCommand('copy'); done(); } catch (err) {}
		document.body.removeChild(ta);
	}
});
</script>
