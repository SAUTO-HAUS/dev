<?php defined( '_DOIT' ) or die( 'Restricted access' );

$members_count = 5;
$team_array = array();

$arr_types = array('id', 'fullname', 'telephone', 'work_post', 'photo_path');

if ($members_count > 0){ //ХИТЫ ПРОДАЖ

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_team ORDER BY RAND() LIMIT :count');
    $pdo->execute(array('count' => $members_count));
    foreach ($pdo as $row){
        foreach($arr_types as $key){ $team_array[$i][$key]=$row[$key];}
        $team_array[$i]['img_folder']= 'team';
        $members_count--;
        $i++;
    }

}


foreach($team_array as $key => $value){

    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_team_photo WHERE item_id=:id');
    $pdo->execute(array( 'id' => $value['id'] ));
    foreach ($pdo as $row){
        $c_photo_name = $row['name'];
    }

    $photo = '/'._TEAM_IMG.'/'.$value['photo_path'].'/'.$value['id'].'/med/'.$c_photo_name.'.jpg';
    echo '
    <div class="team-info-block">
        <div class="image" style="background-image: url('.$photo.');"></div>
            <div class="info">
              <span>'.$value['fullname'].'</span>
              <p>'.$lang_team_sale_manager.'</p>
            </div>
            <div class="phone">
              <a href="tel:'.$value['telephone'].'">
                <p>'.$value['telephone'].'</p>
                <span>'.$lang_contact_manager.'</span>
              </a>
            </div>
    </div>
    ';
    ;}

//echo '</div>';//закрытие последнего цикла

unset($result);
unset($result_photo);
?>