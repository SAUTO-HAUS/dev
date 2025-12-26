<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/calculator/calc_translate.php';

if (!isset($user_role) || $user_role !== 'gordon') {
    echo '<span class="err">Access denied</span>';
    return;
}

$users = [];
try {
    $pdo = $db->prepare('SELECT `id`, `name` FROM '.$prefx.'_admins');
    $pdo->execute();
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        $users[$row['id']] = $row['name'];
    }
} catch (Exception $e) {

}

$usage_data = [];
try {
    $pdo = $db->prepare('
        SELECT user_id, DATE(created_at) as usage_date, TIME(created_at) as usage_time 
        FROM '.$prefx.'_calculator_usage_log 
        ORDER BY created_at DESC
    ');
    $pdo->execute();
    
    while ($row = $pdo->fetch(PDO::FETCH_ASSOC)) {
        $user_id = $row['user_id'];
        $date = $row['usage_date'];
        $time = substr($row['usage_time'], 0, 5); 
        
        if (!isset($usage_data[$user_id])) {
            $usage_data[$user_id] = [];
        }
        if (!isset($usage_data[$user_id][$date])) {
            $usage_data[$user_id][$date] = [];
        }
        $usage_data[$user_id][$date][] = $time;
    }
} catch (Exception $e) {
    
}

$rtrn = '
<style>
    #usage-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
        font-family: Arial, sans-serif;
    }
    
    #usage-container .usage-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    #usage-container .usage-header h1 {
        color: #333;
        font-size: 1.5rem;
        margin: 0;
    }
    
    #usage-container .back-btn {
        padding: 0.75rem 1.5rem;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.9rem;
        transition: background 0.3s;
    }
    
    #usage-container .back-btn:hover {
        background: #555;
    }
    
    #usage-container .user-block {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    
    #usage-container .user-header {
        background: linear-gradient(135deg, #e2001a 0%, #bf0016 100%);
        color: #fff;
        padding: 1rem 1.5rem;
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    #usage-container .user-content {
        padding: 1rem 1.5rem;
    }
    
    #usage-container .date-block {
        padding: 1rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    #usage-container .date-block:last-child {
        border-bottom: none;
    }
    
    #usage-container .date-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    #usage-container .date-header .date {
        font-weight: 600;
        color: #333;
    }
    
    #usage-container .date-header .count {
        background: #e2001a;
        color: #fff;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
    }
    
    #usage-container .times {
        color: #666;
        font-size: 0.9rem;
    }
    
    #usage-container .times span {
        display: inline-block;
        background: #f0f0f0;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        margin: 0.25rem 0.25rem 0.25rem 0;
    }
    
    #usage-container .no-data {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
</style>

<div id="usage-container">
    <div class="usage-header">
        <h1>📊 '.$t['usage_title'].'</h1>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/calc" class="back-btn">'.$t['back_to_calc'].'</a>
    </div>';

if (empty($usage_data)) {
    $rtrn .= '<div class="no-data">'.$t['no_usage_data'].'</div>';
} else {
    foreach ($usage_data as $user_id => $dates) {
        $user_name = isset($users[$user_id]) ? $users[$user_id] : 'User #'.$user_id;
        
        $rtrn .= '
        <div class="user-block">
            <div class="user-header">👤 '.$user_name.'</div>
            <div class="user-content">';
        
        foreach ($dates as $date => $times) {
            $formatted_date = date('d.m.Y', strtotime($date));
            $count = count($times);
            $usage_word = $count == 1 ? 'utilizare' : 'utilizări';
            
            $rtrn .= '
                <div class="date-block">
                    <div class="date-header">
                        <span class="date">'.$formatted_date.'</span>
                        <span class="count">'.$count.' '.$usage_word.'</span>
                    </div>
                    <div class="times">';
            
            foreach ($times as $time) {
                $rtrn .= '<span>'.$time.'</span>';
            }
            
            $rtrn .= '
                    </div>
                </div>';
        }
        
        $rtrn .= '
            </div>
        </div>';
    }
}

$rtrn .= '
</div>';

echo $rtrn;
