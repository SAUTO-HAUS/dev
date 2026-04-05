<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Include stock translations
require_once dirname(__FILE__) . '/stock_translations.php';

// Stock extern management - View only for director (on_order cars)
echo '<div class="page_title">' . $stock_lang['stock_extern'] . '</div>';

// Get brand distribution data with active/inactive breakdown for on_order cars
try {
    $sql = "SELECT 
        br_nm,
        mo_nm,
        id,
        yr,
        inf,
        loc,
        vis,
        vol,
        mlg,
        offer_timer_end
    FROM {$prefx}_car_ctlg 
    WHERE act = 1 AND n_a = 0 AND loc IN ('1', '2') AND catalog_type = 'on_order'
    ORDER BY br_nm ASC, mo_nm ASC, yr DESC, id DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $current_time = time();
    $brands = [];
    $totals = [
        'main_active' => 0,      // on_order with active timer
        'main_inactive' => 0,    // on_order with expired timer
        'branch_active' => 0,
        'branch_inactive' => 0,
        'total' => 0
    ];
    
    // Arrays to store car IDs by category
    $cars_all = [];
    $cars_active = [];
    $cars_inactive = [];
    
    foreach ($cars as $car) {
        $brand = $car['br_nm'];
        $model = $car['mo_nm'];
        
        // Initialize brand if not exists
        if (!isset($brands[$brand])) {
            $brands[$brand] = [
                'models' => [],
                'totals' => [
                    'main_active' => 0,
                    'main_inactive' => 0,
                    'branch_active' => 0, 
                    'branch_inactive' => 0,
                    'total' => 0
                ]
            ];
        }
        
        // Initialize model if not exists
        if (!isset($brands[$brand]['models'][$model])) {
            $brands[$brand]['models'][$model] = [
                'mo_nm' => $model,
                'cars' => [],
                'cnt_main_active' => 0,
                'cnt_main_inactive' => 0,
                'cnt_branch_active' => 0,
                'cnt_branch_inactive' => 0,
                'cnt_total' => 0
            ];
        }
        
        // Add car to model
        $brands[$brand]['models'][$model]['cars'][] = $car;
        
        // Add to all cars list
        $cars_all[] = $car['id'];
        
        // For on_order cars: count by offer timer status
        // Check if offer timer is still active or expired
        if (!empty($car['offer_timer_end']) && $car['offer_timer_end'] > $current_time) {
            // Active timer (offer timer not expired yet)
            $brands[$brand]['models'][$model]['cnt_main_active']++;
            $brands[$brand]['totals']['main_active']++;
            $totals['main_active']++;
            $cars_active[] = $car['id'];
        } else {
            // Expired timer (offer timer has expired or not set)
            $brands[$brand]['models'][$model]['cnt_main_inactive']++;
            $brands[$brand]['totals']['main_inactive']++;
            $totals['main_inactive']++;
            $cars_inactive[] = $car['id'];
        }
        
        // Total count
        $brands[$brand]['models'][$model]['cnt_total']++;
        $brands[$brand]['totals']['total']++;
        $totals['total']++;
    }
    
    // Convert models from associative to indexed array for easier iteration
    foreach ($brands as $brandName => &$brandData) {
        $brandData['models'] = array_values($brandData['models']);
    }
    
} catch (Exception $e) {
    echo '<div class="error">Ошибка при получении данных: ' . $e->getMessage() . '</div>';
    exit;
}
?>

<div class="stock-summary">
    <div class="summary-title">📊 <?= $stock_lang['stock_summary'] ?> - <?= $stock_lang['stock_extern'] ?></div>
    <div class="summary-grid">
        <div class="summary-item">
            <span class="summary-label"><?= $stock_lang['total_cars'] ?>:</span>
            <span class="summary-value summary-clickable" data-category="all" style="cursor: pointer;"><?= $totals['total'] ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label"><?= $stock_lang['extern_active'] ?>:</span>
            <span class="summary-value summary-clickable" data-category="active" style="cursor: pointer;"><?= $totals['main_active'] ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label"><?= $stock_lang['extern_inactive'] ?>:</span>
            <span class="summary-value summary-clickable" data-category="inactive" style="cursor: pointer;"><?= $totals['main_inactive'] ?></span>
        </div>
    </div>
</div>

<!-- Car list container -->
<div id="car-list-container" style="display: none; margin: 20px 0; padding: 20px; background: #f8f9fa; border: 2px solid #dc3545; border-radius: 8px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3 id="car-list-title" style="margin: 0; color: #dc3545;"></h3>
        <button id="close-car-list" style="background: #dc3545; color: white; border: none; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">✕</button>
    </div>
    <div id="car-list-content" style="max-height: 400px; overflow-y: auto;"></div>
</div>

<table class="stock-brands-table">
    <thead>
        <tr>
            <th class="brand-column"><?= $stock_lang['table_brand'] ?></th>
            <th><?= $stock_lang['extern_active'] ?></th>
            <th><?= $stock_lang['extern_inactive'] ?></th>
            <th><?= $stock_lang['table_total'] ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($brands as $brandName => $brandData): ?>
            <tr class="brand-row" data-brand="<?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?>">
                <td class="brand-name">
                    <span class="expand-icon">▶</span>
                    <span class="brand-text"><?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><?= $brandData['totals']['main_active'] ?></td>
                <td><?= $brandData['totals']['main_inactive'] ?></td>
                <td><?= $brandData['totals']['total'] ?></td>
            </tr>
            
            <?php foreach ($brandData['models'] as $model): ?>
                <tr class="model-row" data-brand="<?= htmlspecialchars($brandName, ENT_QUOTES, 'UTF-8') ?>" style="display: none;">
                    <td class="model-name">
                        &nbsp;&nbsp;&nbsp;&nbsp;<?= htmlspecialchars($model['mo_nm'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td><?= $model['cnt_main_active'] ?></td>
                    <td><?= $model['cnt_main_inactive'] ?></td>
                    <td>
                        <?= $model['cnt_total'] ?>
                        <div class="model-links">
                            <a href="/adminsauto/ordercars/ctlg?br_search=<?= urlencode($brandName) ?>&mo_search=<?= urlencode($model['mo_nm']) ?>" 
                               class="show-all-link"><?= $stock_lang['show_all_ads'] ?></a>
                            <?php
                            // Use cars already loaded in model data
                            foreach ($model['cars'] as $car):
                                $carTitle = $car['yr'] . ' ' . $brandName . ' ' . $model['mo_nm'];
                                if (!empty($car['inf'])) {
                                    $carTitle .= ' - ' . substr(strip_tags($car['inf']), 0, 30) . '...';
                                }
                                
                                // Format engine and mileage
                                $engine = !empty($car['vol']) ? $car['vol'] : 'N/A';
                                $mileage = !empty($car['mlg']) ? number_format($car['mlg']) . ' km' : 'N/A';
                            ?>
                                <a href="/adminsauto/ordercars/detail?id=<?= $car['id'] ?>" 
                                   class="car-link" title="<?= htmlspecialchars($carTitle, ENT_QUOTES, 'UTF-8') ?>">
                                   ID <?= $car['id'] ?> - <?= $car['yr'] ?> - <?= $engine ?> - <?= $mileage ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endforeach; ?>
        
        <tr class="total-row">
            <td><strong><?= $stock_lang['total_brands'] ?>: <?= count($brands) ?></strong></td>
            <td><strong><?= $totals['main_active'] ?></strong></td>
            <td><strong><?= $totals['main_inactive'] ?></strong></td>
            <td><strong><?= $totals['total'] ?></strong></td>
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
    padding: 8px 12px;
    text-align: left;
}

.stock-brands-table th {
    background-color: #f2f2f2;
    font-weight: bold;
    text-align: center;
    font-size: 13px;
}

.stock-brands-table .brand-column {
    width: 180px;
    max-width: 180px;
}

.stock-brands-table td:nth-child(2),
.stock-brands-table td:nth-child(3),
.stock-brands-table td:nth-child(4),
.stock-brands-table td:nth-child(5),
.stock-brands-table th:nth-child(2),
.stock-brands-table th:nth-child(3),
.stock-brands-table th:nth-child(4),
.stock-brands-table th:nth-child(5) {
    text-align: center;
    width: 100px;
}

.stock-brands-table td:nth-child(6),
.stock-brands-table th:nth-child(6) {
    text-align: center;
    width: 250px;
}

.brand-row {
    background-color: #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s;
}

.brand-row:hover {
    background-color: #e9ecef;
}

.brand-name {
    font-weight: bold;
    position: relative;
}

.expand-icon {
    display: inline-block;
    margin-right: 8px;
    transition: transform 0.2s;
    color: #dc3545;
    font-size: 12px;
}

.expand-icon.expanded {
    transform: rotate(90deg);
}

.model-row {
    background-color: #ffffff;
    border-left: 3px solid #dc3545;
}

.model-row:hover {
    background-color: #f8f9fa;
}

.model-name {
    font-style: italic;
    color: #666;
}

.model-links {
    margin-top: 5px;
    font-size: 11px;
}

.show-all-link {
    display: inline-block;
    background: #dc3545;
    color: white !important;
    padding: 2px 6px;
    border-radius: 3px;
    text-decoration: none;
    margin-bottom: 3px;
    font-size: 10px;
}

.show-all-link:hover {
    background: #c82333;
}

.car-link {
    display: block;
    color: #007bff !important;
    text-decoration: none;
    padding: 1px 0;
    font-size: 16px;
}

.car-link:hover {
    color: #0056b3 !important;
    text-decoration: underline;
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
    transition: all 0.2s ease;
}

.summary-clickable:hover {
    background: #dc3545;
    color: #fff !important;
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
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

@media (max-width: 768px) {
    .stock-brands-table .brand-column {
        width: 120px;
        max-width: 120px;
    }
    
    .stock-brands-table td:nth-child(2),
    .stock-brands-table td:nth-child(3),
    .stock-brands-table td:nth-child(4),
    .stock-brands-table td:nth-child(5),
    .stock-brands-table td:nth-child(6),
    .stock-brands-table th:nth-child(2),
    .stock-brands-table th:nth-child(3),
    .stock-brands-table th:nth-child(4),
    .stock-brands-table th:nth-child(5),
    .stock-brands-table th:nth-child(6) {
        width: 60px;
        font-size: 11px;
        padding: 4px 6px;
    }
    
    .summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
}
</style>

<script>
// Car data for displaying lists
const carData = {
    all: <?= json_encode($cars_all) ?>,
    active: <?= json_encode($cars_active) ?>,
    inactive: <?= json_encode($cars_inactive) ?>
};

const lang = '<?= $_COOKIE['lang'] ?? 'ro' ?>';
const baseUrl = 'https://www.sauto.md';

document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for summary values
    const summaryClickables = document.querySelectorAll('.summary-clickable');
    const carListContainer = document.getElementById('car-list-container');
    const carListTitle = document.getElementById('car-list-title');
    const carListContent = document.getElementById('car-list-content');
    const closeButton = document.getElementById('close-car-list');
    
    summaryClickables.forEach(function(element) {
        element.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            const cars = carData[category];
            
            // Set title based on category
            let title = '';
            if (category === 'all') {
                title = lang === 'ro' ? 'Toate automobilele' : (lang === 'ru' ? 'Все автомобили' : 'All cars');
            } else if (category === 'active') {
                title = lang === 'ro' ? 'Timer activ' : (lang === 'ru' ? 'Таймер активен' : 'Timer active');
            } else if (category === 'inactive') {
                title = lang === 'ro' ? 'Timer expirat' : (lang === 'ru' ? 'Таймер истёк' : 'Timer expired');
            }
            
            carListTitle.textContent = title + ' (' + cars.length + ')';
            
            // Generate car links
            let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
            cars.forEach(function(carId) {
                const url = baseUrl + '/' + lang + '/ordercars/' + carId;
                html += '<a href="' + url + '" target="_blank" style="color: #dc3545; text-decoration: none; padding: 8px; background: white; border-radius: 4px; border: 1px solid #ddd; transition: all 0.2s;">' + url + '</a>';
            });
            html += '</div>';
            
            carListContent.innerHTML = html;
            carListContainer.style.display = 'block';
            
            // Scroll to list
            carListContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });
    
    // Close button handler
    closeButton.addEventListener('click', function() {
        carListContainer.style.display = 'none';
    });
    
    // Add hover effect to links
    document.addEventListener('mouseover', function(e) {
        if (e.target.tagName === 'A' && e.target.parentElement.parentElement === carListContent) {
            e.target.style.background = '#dc3545';
            e.target.style.color = 'white';
        }
    });
    
    document.addEventListener('mouseout', function(e) {
        if (e.target.tagName === 'A' && e.target.parentElement.parentElement === carListContent) {
            e.target.style.background = 'white';
            e.target.style.color = '#dc3545';
        }
    });
    
    // Add click handlers for brand expansion
    const brandRows = document.querySelectorAll('.brand-row');
    
    brandRows.forEach(function(row) {
        row.addEventListener('click', function() {
            const brandName = this.getAttribute('data-brand');
            const modelRows = document.querySelectorAll('.model-row[data-brand="' + brandName + '"]');
            const expandIcon = this.querySelector('.expand-icon');
            
            if (expandIcon.classList.contains('expanded')) {
                // Collapse
                modelRows.forEach(function(modelRow) {
                    modelRow.style.display = 'none';
                });
                expandIcon.classList.remove('expanded');
                expandIcon.textContent = '▶';
            } else {
                // Expand
                modelRows.forEach(function(modelRow) {
                    modelRow.style.display = 'table-row';
                });
                expandIcon.classList.add('expanded');
                expandIcon.textContent = '▼';
            }
        });
    });
});
</script>
