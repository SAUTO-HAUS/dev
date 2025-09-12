<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Include stock translations
require_once dirname(__FILE__) . '/stock_translations.php';

// Stock management - View only for director
echo '<div class="page_title">' . $stock_lang['stock'] . '</div>';

// Get brand distribution data
try {
    $sql = "SELECT 
        br_nm,
        COUNT(*) AS cnt_total,
        SUM(CASE WHEN loc = '1' THEN 1 ELSE 0 END) AS cnt_main,
        SUM(CASE WHEN loc = '2' THEN 1 ELSE 0 END) AS cnt_pruntul
    FROM {$prefx}_car_ctlg 
    WHERE n_a = 0 AND vis = 1 AND act = 1 
    GROUP BY br_nm 
    ORDER BY cnt_total DESC, br_nm ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalCars = 0;
    $totalMain = 0;
    $totalPruntul = 0;
    foreach ($brands as $row) {
        $totalCars += (int)$row['cnt_total'];
        $totalMain += (int)$row['cnt_main'];
        $totalPruntul += (int)$row['cnt_pruntul'];
    }
    $brandCount = count($brands);
    
} catch (Exception $e) {
    echo '<div class="error">Ошибка при получении данных: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="stock-summary">
    <div class="summary-title">📊 <?= $stock_lang['stock_summary'] ?></div>
    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label"><?= $stock_lang['total_cars'] ?>:</span>
            <span class="summary-value"><?= $totalCars ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label"><?= $stock_lang['main_branch'] ?>:</span>
            <span class="summary-value"><?= $totalMain ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label"><?= $stock_lang['pruntul_branch'] ?>:</span>
            <span class="summary-value"><?= $totalPruntul ?></span>
        </div>
    </div>
</div>

<table class="stock-brands-table">
    <thead>
        <tr>
            <th><?= $stock_lang['table_brand'] ?></th>
            <th><?= $stock_lang['table_total'] ?></th>
            <th><?= $stock_lang['table_main_branch'] ?></th>
            <th><?= $stock_lang['table_pruncul_branch'] ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($brands as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['br_nm'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $row['cnt_total'] ?></td>
                <td><?= $row['cnt_main'] ?></td>
                <td><?= $row['cnt_pruntul'] ?></td>
            </tr>
        <?php endforeach; ?>
        
        <tr class="total-row">
            <td><strong><?= $stock_lang['total_brands'] ?>: <?= $brandCount ?></strong></td>
            <td><strong><?= $totalCars ?></strong></td>
            <td><strong><?= $totalMain ?></strong></td>
            <td><strong><?= $totalPruntul ?></strong></td>
        </tr>
    </tbody>
</table>


<style>
.stock-brands-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-family: Arial, sans-serif;
}

.stock-brands-table th,
.stock-brands-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

.stock-brands-table th {
    background-color: #f2f2f2;
    font-weight: bold;
    text-align: center;
}

.stock-brands-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.stock-brands-table tr:hover {
    background-color: #f5f5f5;
}

.stock-brands-table td:nth-child(2),
.stock-brands-table td:nth-child(3),
.stock-brands-table td:nth-child(4),
.stock-brands-table th:nth-child(2),
.stock-brands-table th:nth-child(3),
.stock-brands-table th:nth-child(4) {
    text-align: center;
    width: 150px;
}

.stock-brands-table .total-row {
    background-color: #e8f4f8 !important;
    font-weight: bold;
}

.stock-summary {
    margin: 20px 0;
    padding: 25px;
    background: #ffffff;
    border: 2px solid #dc3545;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.15);
}

.summary-title {
    font-size: 20px;
    font-weight: bold;
    color: #dc3545;
    margin-bottom: 20px;
    text-align: center;
    border-bottom: 2px solid #dc3545;
    padding-bottom: 10px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}

.summary-label {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.summary-value {
    font-weight: bold;
    color: #dc3545;
    font-size: 18px;
    background: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid #dc3545;
    min-width: 50px;
    text-align: center;
}

.error {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #f5c6cb;
    margin: 20px 0;
}

.page_title {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #dc3545;
    padding-bottom: 10px;
}
</style>
