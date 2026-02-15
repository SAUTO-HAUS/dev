<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/calculator/calc_translate.php';

if (!isset($user_role) || $user_role !== 'gordon') {
    echo '<span class="err">Access denied</span>';
    return;
}

// Get selected date (default: today)
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$users = [];
try {
    $pdo = $db->prepare('SELECT `id`, `name` FROM '.$prefx.'_adm_usr');
    $pdo->execute();
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        $users[$row['id']] = $row['name'];
    }
} catch (Exception $e) {

}

// Get stats per user for selected date
$user_stats = [];
try {
    $pdo = $db->prepare('
        SELECT user_id, COUNT(*) as total_uses, MAX(created_at) as last_use
        FROM '.$prefx.'_calculator_usage_log 
        WHERE DATE(created_at) = :selected_date
        GROUP BY user_id
        ORDER BY total_uses DESC
    ');
    $pdo->execute(['selected_date' => $selected_date]);
    $user_stats = $pdo->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    
}


$rtrn = '
<style>
    #usage-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 0 2rem 2rem;
        font-family: Arial, sans-serif;
    }
    
    #usage-container .usage-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-top: 2rem;
    }
    
    #usage-container .usage-header h1 {
        color: #333;
        font-size: 1.5rem;
        margin: 0;
    }
    
    #usage-container .back-btn {
        padding: 0.5rem 1rem;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.85rem;
    }
    
    #usage-container .back-btn:hover {
        background: #555;
    }
    
    #usage-container .date-selector {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    #usage-container .date-selector input[type="date"] {
        padding: 0.5rem;
        border: 2px solid #e0e0e0;
        border-radius: 6px;
        font-size: 0.9rem;
    }
    
    #usage-container .date-selector input[type="date"]:focus {
        outline: none;
        border-color: #e2001a;
    }
    
    #usage-container .date-selector label {
        color: #666;
        font-size: 0.9rem;
    }
    
    #usage-container .users-table {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    #usage-container .users-table table {
        width: 100%;
        border-collapse: collapse;
    }
    
    #usage-container .users-table th {
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    #usage-container .users-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
    }
    
    #usage-container .users-table tr:last-child td {
        border-bottom: none;
    }
    
    #usage-container .users-table tr:hover td {
        background: #fafafa;
    }
    
    #usage-container .users-table .count-badge {
        background: #e2001a;
        color: #fff;
        padding: 0.25rem 0.6rem;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    #usage-container .no-data {
        text-align: center;
        padding: 3rem;
        color: #666;
        background: #fff;
        border-radius: 8px;
    }
    
    @media (max-width:767px), (orientation: portrait), (max-height:500px) and (orientation: landscape) {
        #usage-container {
            padding: 0 0.5rem 1rem;
        }
        
        #usage-container .usage-header {
            flex-direction: column;
            gap: 0.5rem;
            text-align: center;
            padding-top: 0.5rem;
        }
        
        #usage-container .usage-header h1 {
            font-size: 1.1rem;
        }
        
        #usage-container .back-btn {
            width: 100%;
            text-align: center;
            display: block;
        }
        
        #usage-container .date-selector {
            justify-content: center;
        }
        
        #usage-container .users-table {
            border-radius: 0;
            box-shadow: none;
        }
        
        #usage-container .users-table th,
        #usage-container .users-table td {
            padding: 0.5rem;
            font-size: 0.8rem;
        }
        
        #usage-container .no-data {
            border-radius: 0;
            padding: 2rem 1rem;
        }
    }
</style>

<div id="usage-container">
    <div class="usage-header">
        <h1>📊 '.$t['usage_title'].'</h1>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/calc" class="back-btn">'.$t['back_to_calc'].'</a>
    </div>
    
    <div class="date-selector">
        <label>'.$t['select_date'].':</label>
        <input type="date" id="usage-date" value="'.$selected_date.'" max="'.date('Y-m-d').'">
    </div>
    
    <script>
    document.getElementById("usage-date").addEventListener("change", function() {
        window.location.href = "/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/usage?date=" + this.value;
    });
    </script>
    ';

$formatted_date = date('d.m.Y', strtotime($selected_date));
if (empty($user_stats)) {
    $rtrn .= '<div class="no-data">'.$t['no_usage_data'].' ('.$formatted_date.')</div>';
} else {
    $rtrn .= '
    <div class="users-table">
        <table>
            <tr>
                <th>'.$t['user'].'</th>
                <th>'.$t['total_uses'].'</th>
                <th>'.$t['last_use'].'</th>
            </tr>';
    
    foreach ($user_stats as $stat) {
        $user_name = isset($users[$stat['user_id']]) ? $users[$stat['user_id']] : 'User #'.$stat['user_id'];
        $last_use = date('d.m.Y H:i', strtotime($stat['last_use']));
        
        $rtrn .= '
            <tr>
                <td>👤 '.$user_name.'</td>
                <td><span class="count-badge">'.$stat['total_uses'].'</span></td>
                <td>'.$last_use.'</td>
            </tr>';
    }
    
    $rtrn .= '
        </table>
    </div>';
}

$rtrn .= '
</div>';

echo $rtrn;
