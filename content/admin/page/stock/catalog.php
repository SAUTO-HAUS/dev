<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Stock management - View only for director
echo '<div class="page_title">Stoc Sauto - Raport Filiale</div>';

// Get stock data by locations
try {
    // Query to get car counts by branch/location and brand/model using 'loc' field
    $stock_query = "
        SELECT 
            CASE 
                WHEN c.loc = '1' THEN 'str. Calea Moşilor 11'
                WHEN c.loc = '2' THEN 'str. Pietrăriei 3'
                ELSE 'Fără Locație'
            END as location,
            c.br_nm as brand_name,
            c.mo_nm as model_name,
            COUNT(*) as car_count
        FROM gh3sp_car_ctlg c
        WHERE c.vis = '1' AND c.act = '1'
        GROUP BY 
            CASE 
                WHEN c.loc = '1' THEN 'str. Calea Moşilor 11'
                WHEN c.loc = '2' THEN 'str. Pietrăriei 3'
                ELSE 'Fără Locație'
            END, 
            c.br, c.mo
        ORDER BY location, c.br_nm, c.mo_nm
    ";
    
    $stock_stmt = $db->prepare($stock_query);
    $stock_stmt->execute();
    $stock_data = $stock_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total counts by location using 'loc' field
    $location_totals_query = "
        SELECT 
            CASE 
                WHEN loc = '1' THEN 'str. Calea Moşilor 11'
                WHEN loc = '2' THEN 'str. Pietrăriei 3'
                ELSE 'Fără Locație'
            END as location,
            COUNT(*) as total_cars
        FROM gh3sp_car_ctlg 
        WHERE vis = '1' AND act = '1'
        GROUP BY 
            CASE 
                WHEN loc = '1' THEN 'str. Calea Moşilor 11'
                WHEN loc = '2' THEN 'str. Pietrăriei 3'
                ELSE 'Fără Locație'
            END
        ORDER BY location
    ";
    
    $totals_stmt = $db->prepare($location_totals_query);
    $totals_stmt->execute();
    $location_totals = $totals_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize data by location - initialize with the 2 branches
    $locations = [
        'str. Calea Moşilor 11' => [],
        'str. Pietrăriei 3' => [],
        'Fără Locație' => []
    ];
    
    foreach ($stock_data as $row) {
        $location = $row['location'] ?: 'Fără Locație';
        if (!isset($locations[$location])) {
            $locations[$location] = [];
        }
        $locations[$location][] = $row;
    }
    
    echo '<div class="stock_container">';
    
    // Summary section
    echo '<div class="stock_summary">';
    echo '<h3>Rezumat Total</h3>';
    echo '<table class="stock_table">';
    echo '<thead><tr><th>Locație</th><th>Total Mașini</th></tr></thead>';
    echo '<tbody>';
    
    $grand_total = 0;
    foreach ($location_totals as $total) {
        $location = $total['location'] ?: 'Other';
        $count = $total['total_cars'];
        $grand_total += $count;
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($location) . '</strong></td>';
        echo '<td><strong>' . $count . '</strong></td>';
        echo '</tr>';
    }
    
    echo '<tr class="total_row">';
    echo '<td><strong>TOTAL GENERAL</strong></td>';
    echo '<td><strong>' . $grand_total . '</strong></td>';
    echo '</tr>';
    echo '</tbody></table>';
    echo '</div>';
    
    // Detailed breakdown by location
    foreach ($locations as $location_name => $cars) {
        if (empty($cars)) continue;
        
        echo '<div class="location_section">';
        echo '<h3>📍 ' . htmlspecialchars($location_name) . '</h3>';
        echo '<table class="stock_table detailed">';
        echo '<thead><tr><th>Marcă</th><th>Model</th><th>Cantitate</th></tr></thead>';
        echo '<tbody>';
        
        $location_total = 0;
        foreach ($cars as $car) {
            $location_total += $car['car_count'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($car['brand_name'] ?: 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($car['model_name'] ?: 'N/A') . '</td>';
            echo '<td>' . $car['car_count'] . '</td>';
            echo '</tr>';
        }
        
        echo '<tr class="location_total">';
        echo '<td colspan="2"><strong>Total ' . htmlspecialchars($location_name) . '</strong></td>';
        echo '<td><strong>' . $location_total . '</strong></td>';
        echo '</tr>';
        echo '</tbody></table>';
        echo '</div>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="error">Eroare la încărcarea datelor de stoc: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Add CSS styles
echo '<style>
.stock_container {
    padding: 20px;
    max-width: 1200px;
}

.stock_summary {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    border-left: 4px solid #dc3545;
}

.location_section {
    margin-bottom: 30px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.location_section h3 {
    background: #343a40;
    color: white;
    margin: 0;
    padding: 15px 20px;
    font-size: 18px;
}

.stock_table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.stock_table th {
    background: #e9ecef;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.stock_table td {
    padding: 10px 15px;
    border-bottom: 1px solid #dee2e6;
}

.stock_table tr:hover {
    background: #f8f9fa;
}

.total_row, .location_total {
    background: #ffebee !important;
    font-weight: bold;
}

.total_row td, .location_total td {
    border-top: 2px solid #f44336;
    color: #c62828;
}

.stock_table.detailed {
    margin: 0;
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
</style>';
?>
