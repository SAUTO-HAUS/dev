<?php

echo '<div id="video_content">';

$lines_length = '50';

if($_POST['Создать_Видео']){
    $video_path = $_REQUEST['new_path'];
    $video_name = $_REQUEST['new_name'];
    
    $youtube = stripos($video_path, 'youtube.com');
        
    if ($youtube !== false) {
        $video_source = 'youtube';
        $allisfine = 1;
    }
        
    if ($allisfine == 1&&$video_name!=''){
        include ('templates/sauto/php/main/dbinfo.php');
        $database->query("INSERT INTO gh3sp_video (`source`, `path`, `name`) VALUES ('$video_source','$video_path','$video_name')");
    
        $last_id = $database->insert_id;
        $database->close();
            
        echo '<p style="color:#9c0; position:absolute;">Добавлено видео <span style="color:#aaa;">'.$video_name.'</span></p>';
    }
    else {
        echo '<p style="color:#c75; position:absolute;">Некорректная ссылка на видео</p>';
    }
}


if($_POST['Принять_Видео']){
        include ('templates/sauto/php/main/dbinfo.php');
        
        $result = mysqli_query($database,"SELECT * FROM gh3sp_video ORDER BY `id` DESC");
        
        $i=0;
        $video_upd=0;
        
        while ($row = mysqli_fetch_array($result)){
            $i++;
            $video_id = $row['id'];
            $video_name = $row['name'];
            $upd_name = $_REQUEST['name_'.$i];
            
            if ($upd_name!=$video_name){
                $database->query("UPDATE gh3sp_video SET `name`='$upd_name' WHERE `id`='$video_id'");
                $video_upd++;
            ;}
        }
        unset($result);
        $database->close();
        
        if($video_upd>0){
            echo '<p style="color:#9c0; position:absolute;">Переименовано <span style="color:#aaa;">'.$video_upd.'</span> видео </p>';
        }
        else {echo '<p style="color:#aaa; position:absolute;">Нет изменений</p>';}
}


if($_POST['Удалить_Видео']){
    include ('templates/sauto/php/main/dbinfo.php');
    
    $result = mysqli_query($database,'SELECT * FROM gh3sp_video WHERE id="'.$_POST['Удалить_Видео'].'"');
    while ($row = mysqli_fetch_array($result)){
        $name  = $row['name'];
    }
    echo '<p style="color:#c75; position:absolute;">Видео <span style="color:#aaa;">'.$name.'</span> удалено</p>';
    
    $database->query("DELETE FROM gh3sp_video WHERE id='".$_POST['Удалить_Видео']."'");
    $database->close();
}

//-------------------------------------------------------------------------------------------------------------------------------------

/*
echo '
<form action="" method="post" id="video_new">
    <input class="text" type="text" name="new_name" size="'.$lines_length.'" tabindex="7" placeholder="Ссылка на видео">
    <input class="text" type="text" name="new_title" size="'.$lines_length.'" tabindex="7" placeholder="Название видео">
    <input type="submit" name="Создать_Видео" value="Создать" style="color:#fff; border:0; background:#5a5; cursor:pointer; font-size:15px;">
</form>
<div id="video_delimeter"></div>
';
*/
//-------------------------------------------------------------------------------------------------------------------------------------

$youtube_thumb_size = array('maxres', 'hq', 'mq', 'default');
$youtube_thumb_photo = array('default', '1', '2', '3');

$youtube_thumb = array(
	'default'=>array('', 'maxres'),
	'1'=>array('maxres', 'hq', 'mq'),
	'2'=>array('maxres', 'hq', 'mq'),
	'3'=>array('maxres', 'hq', 'mq')
);


echo '<div id="add_new" class="video_box" title="'.$adm_lang['add'].'"> <div></div> </div>';

$sql = 'SELECT * FROM '.$prefx.'_video ORDER BY `id` DESC';

$pdo = $db->prepare($sql);
$pdo->execute();

foreach ($pdo as $row){
    $c_id = $row['id'];
    $source = $row['source'];
    $path = $row['path'];
    $name = $row['name'];
	$photo = $row['photo'];

    //-----Screenshot----
    if ($source=='youtube'){
        $vi_array = explode("v=", $path);
        $video_screenshot = 'https://img.youtube.com/vi/'.$vi_array[1].'/'.$photo.'.jpg';
    ;}
	//------------------

    echo ' 
	<div class="video_box">
		<div class="adm_menu" idz="'.$c_id.'">
			<div class="edit action_button" title="'.$adm_lang['edit'].'"> <div></div> </div>
			<div class="delete action_button" title="'.$adm_lang['delete'].'"> <div></div> </div>
		</div>
		
		<div class="image" style="background-image:url('.$video_screenshot.');"></div>
		<div class="text">'.$name.'</div>
	</div>
    ';
}

echo '
</div>
';


?>