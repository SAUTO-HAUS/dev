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
$sql = 'SELECT cl.* FROM '.$prefx.'_car_changelog cl '.$where_sql.' ORDER BY cl.created_at DESC LIMIT '.$per_page.' OFFSET '.$offset;
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
.changelog-val:hover { white-space:normal; word-break:break-all; }
.changelog-pager { display:flex; gap:4px; margin-top:15px; justify-content:center; }
.changelog-pager a, .changelog-pager span { display:inline-block; padding:4px 10px; border:1px solid #ddd; border-radius:3px; font-size:13px; text-decoration:none; color:#333; }
.changelog-pager span.act { background:#333; color:#fff; border-color:#333; }
.changelog-summary { font-size:12px; color:#888; margin-bottom:10px; }
.changelog-car-link { color:#007bff; text-decoration:none; font-weight:600; }
.changelog-car-link:hover { text-decoration:underline; }
</style>

<div class="changelog-wrap">
    <h2 style="margin:0 0 15px; font-size:18px;">История изменений</h2>

    <form method="get" action="<?= $base_url ?>">
        <div class="changelog-filters">
            <label>ID авто <input type="text" name="car_id" value="<?= htmlspecialchars($filter_car_id) ?>" placeholder="12305"></label>
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
            <button type="submit" class="btn-filter">Фильтр</button>
            <a href="<?= $base_url ?>" class="btn-reset">Сброс</a>
        </div>
    </form>

    <div class="changelog-summary">Всего записей: <b><?= $total ?></b> | Страница <?= $page_num ?> из <?= $total_pages ?></div>

    <table class="changelog-table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>ID авто</th>
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
                ?>
                <tr>
                    <td style="white-space:nowrap;"><?= date('d.m.Y H:i:s', $row['created_at']) ?></td>
                    <td><a class="changelog-car-link" href="/<?= $_COOKIE['lang'] ?>/<?= $row['catalog_type'] === 'ordercars' ? 'ordercars' : 'cars' ?>/<?= $row['car_id'] ?>" target="_blank"><?= $row['car_id'] ?></a></td>
                    <td><?= $cat_label ?></td>
                    <td><span class="changelog-badge" style="background:<?= $al[1] ?>"><?= $al[0] ?></span></td>
                    <td><?= $field_labels[$row['field_name']] ?? htmlspecialchars($row['field_name'] ?? '') ?></td>
                    <td><div class="changelog-val" title="<?= htmlspecialchars($row['old_value'] ?? '') ?>"><?= htmlspecialchars($row['old_value'] ?? '—') ?></div></td>
                    <td><div class="changelog-val" title="<?= htmlspecialchars($row['new_value'] ?? '') ?>"><?= htmlspecialchars($row['new_value'] ?? '—') ?></div></td>
                    <td><?= htmlspecialchars($row['user_login'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

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
