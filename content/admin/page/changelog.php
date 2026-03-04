<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Changelog page - only accessible by gordon (superadmin)
if (!isset($user_role) || $user_role !== 'gordon') {
    echo '<span class="err">Restricted access</span>';
    return;
}

if (isset($t_mp[4]) && $t_mp[4] == 'ctlg') {

$per_page = 50;
$page_num = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page_num - 1) * $per_page;

// Filters
$filter_car_id = isset($_GET['car_id']) ? trim($_GET['car_id']) : '';
$filter_action = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_user = isset($_GET['user']) ? trim($_GET['user']) : '';
$filter_catalog = isset($_GET['catalog']) ? trim($_GET['catalog']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where = [];
$params = [];

// Hide mileage-related field changes in UI (keep data in DB/logs)
$mileage_hidden_fields = ['mlg', 'unit', 'mileage'];
$mileage_placeholders = [];
foreach ($mileage_hidden_fields as $idx => $mileage_field) {
    $ph = 'mileage_field_' . $idx;
    $mileage_placeholders[] = ':' . $ph;
    $params[$ph] = $mileage_field;
}
$where[] = '(cl.field_name IS NULL OR cl.field_name NOT IN (' . implode(',', $mileage_placeholders) . '))';

if ($filter_car_id !== '') {
    $where[] = 'cl.car_id = :car_id';
    $params['car_id'] = (int)$filter_car_id;
}
if ($filter_action !== '') {
    $where[] = 'cl.action = :action';
    $params['action'] = $filter_action;
}
if ($filter_user !== '') {
    $where[] = 'cl.user_login LIKE :user_login';
    $params['user_login'] = '%'.$filter_user.'%';
}
if ($filter_catalog !== '') {
    $where[] = 'cl.catalog_type = :catalog_type';
    $params['catalog_type'] = $filter_catalog;
}
if ($filter_date_from !== '') {
    $where[] = 'cl.created_at >= :date_from';
    $params['date_from'] = strtotime($filter_date_from . ' 00:00:00');
}
if ($filter_date_to !== '') {
    $where[] = 'cl.created_at <= :date_to';
    $params['date_to'] = strtotime($filter_date_to . ' 23:59:59');
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$count_sql = 'SELECT COUNT(*) FROM '.$prefx.'_car_changelog cl '.$where_sql;
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));

// Get records
$sql = 'SELECT cl.*, c.br_nm, c.mo_nm FROM '.$prefx.'_car_changelog cl LEFT JOIN '.$prefx.'_car_ctlg c ON c.id = cl.car_id '.$where_sql.' ORDER BY cl.created_at DESC LIMIT '.$per_page.' OFFSET '.$offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Field name labels (human-readable)
$field_labels = [
    'photo' => 'Фото',
    'main_photo' => 'Главное фото',
    'gr' => 'Группа',
    'br' => 'Марка (код)',
    'mo' => 'Модель (код)',
    'br_nm' => 'Марка',
    'mo_nm' => 'Модель',
    'yr' => 'Год',
    'bt' => 'Кузов',
    'sts' => 'Мест',
    'mlg' => 'Пробег',
    'unit' => 'Ед. пробега',
    'vol' => 'Объём',
    'hp' => 'Мощность',
    'fl' => 'Топливо',
    'tra' => 'КПП',
    'wd' => 'Привод',
    'clr' => 'Цвет',
    'loc' => 'Филиал',
    'txt' => 'Описание',
    'vin' => 'VIN',
    'prc' => 'Цена',
    'cur' => 'Валюта',
    'soon' => 'Под заказ',
    'n_a' => 'Нет в наличии',
    'tva' => 'НДС',
    'top' => 'Топ',
    'gift' => 'Подарок',
    'import_country_id' => 'Страна импорта'
];

// Value translation maps for coded fields
$value_maps = [
    'fl'  => $lng['l']['car']['fl'] ?? [],
    'tra' => $lng['l']['car']['tra'] ?? [],
    'wd'  => $lng['l']['car']['wd'] ?? [],
    'clr' => $lng['l']['car']['clr'] ?? [],
    'bt'  => $lng['l']['car']['bt'] ?? [],
    'gr'  => $lng['l']['car']['gr'] ?? [],
    'cur' => $lng['l']['cur'] ?? [],
    'loc' => [1 => $lng['t']['x']['address'][1] ?? 'Филиал 1', 2 => $lng['t']['x']['address'][2] ?? 'Филиал 2'],
    'soon' => ['0' => 'Нет', '1' => 'Да'],
    'n_a'  => ['0' => 'В наличии', '1' => 'Нет в наличии'],
    'tva'  => ['0' => 'Нет', '1' => 'Да'],
    'top'  => ['0' => 'Нет', '1' => 'Да'],
    'gift' => ['0' => 'Нет', '1' => 'Да']
];

function changelog_translate_value($field, $value, $value_maps) {
    if ($value === null || $value === '') return $value;
    if (isset($value_maps[$field][$value])) return $value_maps[$field][$value];
    return $value;
}

// Action labels
$action_labels = [
    'create' => ['Создание', '#28a745'],
    'edit' => ['Редактирование', '#007bff'],
    'photo_add' => ['Фото добавлено', '#17a2b8'],
    'photo_delete' => ['Фото удалено', '#dc3545'],
    'photo_main' => ['Главное фото', '#6f42c1'],
    'hide' => ['Скрыто', '#ffc107'],
    'reveal' => ['Показано', '#28a745'],
    'delete' => ['Удалено', '#dc3545'],
    'restore' => ['Восстановлено', '#28a745'],
    'erase' => ['Стёрто навсегда', '#721c24']
];

// Build current filter query string for pagination
$filter_qs = http_build_query(array_filter([
    'car_id' => $filter_car_id,
    'action' => $filter_action,
    'user' => $filter_user,
    'catalog' => $filter_catalog,
    'date_from' => $filter_date_from,
    'date_to' => $filter_date_to
], function($v) { return $v !== ''; }));

$base_url = '/'.$_COOKIE['lang'].'/'.$admin_dir.'/changelog/ctlg';
?>

<style>
.changelog-wrap { padding: 10px; }
.changelog-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:15px; align-items:flex-end; }
.changelog-filters label { display:flex; flex-direction:column; font-size:11px; color:#888; }
.changelog-filters input, .changelog-filters select { padding:0 8px; border:1px solid #ccc; border-radius:4px; font-size:13px; height:32px; box-sizing:border-box; }
.changelog-filters input[type="text"] { width:100px; }
.changelog-filters input[type="date"] { width:140px; }
.changelog-filters select { width:140px; }
.changelog-filters .btn-filter { padding:0 16px; height:32px; background:#333; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:13px; box-sizing:border-box; }
.changelog-filters .btn-reset { padding:0 12px; height:32px; line-height:30px; background:#eee; color:#333; border:1px solid #ccc; border-radius:4px; cursor:pointer; font-size:13px; text-decoration:none; box-sizing:border-box; display:inline-block; }
.changelog-table { width:100%; border-collapse:collapse; font-size:13px; }
.changelog-table th { background:#f5f5f5; padding:8px 6px; text-align:left; border-bottom:2px solid #ddd; border-right:2px solid #ddd; font-size:12px; color:#666; }
.changelog-table th:last-child { border-right:none; }
.changelog-table td { padding:8px 6px; border-bottom:2px solid #ddd; border-right:2px solid #ddd; vertical-align:top; }
.changelog-table td:last-child { border-right:none; }
.changelog-table tr:hover { background:#fafafa; }
.changelog-badge { display:inline-block; padding:2px 8px; border-radius:3px; color:#fff; font-size:11px; font-weight:600; }
.changelog-val { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:12px; color:#555; }
.changelog-pager { display:flex; gap:4px; margin-top:15px; justify-content:center; }
.changelog-pager a, .changelog-pager span { display:inline-block; padding:4px 10px; border:1px solid #ddd; border-radius:3px; font-size:13px; text-decoration:none; color:#333; }
.changelog-pager span.act { background:#333; color:#fff; border-color:#333; }
.changelog-summary { font-size:12px; color:#888; margin-bottom:10px; }
.changelog-car-link { color:#007bff; text-decoration:none; font-weight:600; }
.changelog-car-link:hover { text-decoration:underline; }

/* Mobile cards */
.changelog-cards { display:none; }

@media (max-width: 768px) {
    .changelog-wrap { padding:5px; }
    .changelog-filters { flex-direction:column; gap:6px; }
    .changelog-filters label { width:100%; font-size:13px; }
    .changelog-filters input, .changelog-filters select { width:100% !important; height:38px; font-size:14px; }
    .changelog-filters .btn-filter, .changelog-filters .btn-reset { width:48%; height:38px; text-align:center; font-size:14px; }
    .changelog-filters .changelog-btns { display:flex; gap:4%; justify-content:center; width:100%; }
    .changelog-table { display:none; }
    .changelog-cards { display:block; }
    .cl-card { background:#fff; border:1px solid #ddd; border-radius:8px; margin-bottom:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.06); }
    .cl-card-head { display:flex; justify-content:space-between; align-items:center; padding:8px 10px; background:#f5f5f5; border-bottom:1px solid #eee; font-size:12px; color:#777; }
    .cl-card-head .cl-date { font-weight:600; color:#333; }
    .cl-card-head .cl-user { color:#555; }
    .cl-card-meta { display:flex; gap:6px; align-items:center; padding:6px 10px; border-bottom:1px solid #f0f0f0; font-size:13px; flex-wrap:wrap; }
    .cl-card-meta .cl-id { font-weight:700; color:#007bff; }
    .cl-card-meta .cl-cat { color:#888; font-size:11px; }
    .cl-card-body { padding:8px 10px; }
    .cl-card-field { font-weight:700; color:#333; font-size:13px; margin-bottom:4px; }
    .cl-card-diff { display:flex; align-items:flex-start; gap:6px; font-size:13px; flex-wrap:wrap; }
    .cl-card-diff .cl-old { background:#ffeaea; color:#c0392b; padding:3px 8px; border-radius:4px; word-break:break-all; max-width:100%; }
    .cl-card-diff .cl-arrow { color:#999; font-size:16px; line-height:1; flex-shrink:0; }
    .cl-card-diff .cl-new { background:#e8f5e9; color:#27ae60; padding:3px 8px; border-radius:4px; word-break:break-all; max-width:100%; }
    .changelog-pager { flex-wrap:wrap; justify-content:center; }
    .changelog-pager a, .changelog-pager span { padding:8px 14px; font-size:14px; }
    .changelog-summary { font-size:13px; }
}
</style>

<div class="changelog-wrap">
    <h2 style="margin:0 0 15px; font-size:18px;">История изменений</h2>

    <form method="get" action="<?= $base_url ?>">
        <div class="changelog-filters">
            <label>ID <input type="text" name="car_id" value="<?= htmlspecialchars($filter_car_id) ?>" placeholder="12305"></label>
            <label>Действие
                <select name="action">
                    <option value="">Все</option>
                    <?php foreach ($action_labels as $ak => $av): ?>
                        <option value="<?= $ak ?>" <?= $filter_action === $ak ? 'selected' : '' ?>><?= $av[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Пользователь <input type="text" name="user" value="<?= htmlspecialchars($filter_user) ?>" placeholder="login"></label>
            <label>Каталог
                <select name="catalog">
                    <option value="">Все</option>
                    <option value="cars" <?= $filter_catalog === 'cars' ? 'selected' : '' ?>>В наличии</option>
                    <option value="ordercars" <?= $filter_catalog === 'ordercars' ? 'selected' : '' ?>>Под заказ</option>
                </select>
            </label>
            <label>Дата от <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>"></label>
            <label>Дата до <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>"></label>
            <div class="changelog-btns">
                <button type="submit" class="btn-filter">Фильтр</button>
                <a href="<?= $base_url ?>" class="btn-reset">Сброс</a>
            </div>
        </div>
    </form>

    <div class="changelog-summary">Всего записей: <b><?= $total ?></b> | Страница <?= $page_num ?> из <?= $total_pages ?></div>

    <table class="changelog-table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Авто</th>
                <th>Каталог</th>
                <th>Действие</th>
                <th>Поле</th>
                <th>Было</th>
                <th>Стало</th>
                <th>Пользователь</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" style="text-align:center; padding:30px; color:#999;">Нет записей</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $row): 
                    $al = $action_labels[$row['action']] ?? [$row['action'], '#999'];
                    $cat_label = $row['catalog_type'] === 'ordercars' ? 'Под заказ' : 'В наличии';
                    $fn = $row['field_name'] ?? '';
                    $display_old = changelog_translate_value($fn, $row['old_value'] ?? null, $value_maps);
                    $display_new = changelog_translate_value($fn, $row['new_value'] ?? null, $value_maps);
                ?>
                <tr>
                    <td data-label="Дата" style="white-space:nowrap;"><?= date('d.m.Y H:i:s', $row['created_at']) ?></td>
                    <td data-label="Авто"><a class="changelog-car-link" href="/<?= $_COOKIE['lang'] ?>/<?= $admin_dir ?>/<?= $row['catalog_type'] === 'ordercars' ? 'ordercars' : 'cars' ?>/detail?id=<?= $row['car_id'] ?>" target="_blank"><?= $row['car_id'] ?> <?= ucwords($row['br_nm'] ?? '') ?> <?= ucwords($row['mo_nm'] ?? '') ?></a></td>
                    <td data-label="Каталог"><?= $cat_label ?></td>
                    <td data-label="Действие"><span class="changelog-badge" style="background:<?= $al[1] ?>"><?= $al[0] ?></span></td>
                    <td data-label="Поле"><?= $field_labels[$row['field_name']] ?? htmlspecialchars($row['field_name'] ?? '') ?></td>
                    <td data-label="Было"><div class="changelog-val" title="<?= htmlspecialchars($row['old_value'] ?? '') ?>"><?= htmlspecialchars($display_old ?? '—') ?></div></td>
                    <td data-label="Стало"><div class="changelog-val" title="<?= htmlspecialchars($row['new_value'] ?? '') ?>"><?= htmlspecialchars($display_new ?? '—') ?></div></td>
                    <td data-label="Пользователь"><?= htmlspecialchars($row['user_login'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Mobile cards (visible only on mobile) -->
    <div class="changelog-cards">
        <?php if (empty($rows)): ?>
            <div style="text-align:center; padding:30px; color:#999;">Нет записей</div>
        <?php else: ?>
            <?php foreach ($rows as $row):
                $al = $action_labels[$row['action']] ?? [$row['action'], '#999'];
                $cat_label = $row['catalog_type'] === 'ordercars' ? 'Под заказ' : 'В наличии';
                $fn = $row['field_name'] ?? '';
                $d_old = changelog_translate_value($fn, $row['old_value'] ?? null, $value_maps);
                $d_new = changelog_translate_value($fn, $row['new_value'] ?? null, $value_maps);
                $field_label = $field_labels[$fn] ?? $fn;
            ?>
            <div class="cl-card">
                <div class="cl-card-head">
                    <span class="cl-date"><?= date('d.m.Y H:i', $row['created_at']) ?></span>
                    <span class="cl-user"><?= htmlspecialchars($row['user_login'] ?? '') ?></span>
                </div>
                <div class="cl-card-meta">
                    <a class="cl-id" href="/<?= $_COOKIE['lang'] ?>/<?= $admin_dir ?>/<?= $row['catalog_type'] === 'ordercars' ? 'ordercars' : 'cars' ?>/detail?id=<?= $row['car_id'] ?>" target="_blank">#<?= $row['car_id'] ?> <?= ucwords($row['br_nm'] ?? '') ?> <?= ucwords($row['mo_nm'] ?? '') ?></a>
                    <span class="cl-cat"><?= $cat_label ?></span>
                    <span class="changelog-badge" style="background:<?= $al[1] ?>"><?= $al[0] ?></span>
                </div>
                <div class="cl-card-body">
                    <?php if ($field_label): ?><div class="cl-card-field"><?= $field_label ?></div><?php endif; ?>
                    <div class="cl-card-diff">
                        <span class="cl-old"><?= htmlspecialchars($d_old ?? '—') ?></span>
                        <span class="cl-arrow">&rarr;</span>
                        <span class="cl-new"><?= htmlspecialchars($d_new ?? '—') ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="changelog-pager">
        <?php for ($i = 1; $i <= $total_pages; $i++): 
            $pager_qs = $filter_qs ? $filter_qs.'&p='.$i : 'p='.$i;
        ?>
            <?php if ($i === $page_num): ?>
                <span class="act"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= $base_url ?>?<?= $pager_qs ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php
} else {
    echo '<span class="err">Check the URL</span>';
}
