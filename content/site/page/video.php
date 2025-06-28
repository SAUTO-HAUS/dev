<?php defined( '_DOIT' ) or die( 'Restricted access' );

echo '<div class="video_page">';

/*---------------------------------PLAYER-----------------------------------*/

if ( $t_mp[2]=='video' && ( isset($t_mp[3]) && $t_mp[3]!='' ) ){
    echo '
	<div id="video_player" style="position:relative;">
		<div style="width:calc(100% - 10px); height:calc(100% - 10px); background:#000; position:absolute; z-index:1; "></div>
		<iframe src="//www.youtube.com/embed/'.$tv_mp[3].'?rel=0&autoplay=1&showinfo=0" frameborder="0" allowfullscreen></iframe>
	</div>
	'; 
;}

/*---------------------------------CATALOG-----------------------------------*/
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_video ORDER BY `id` DESC');
$pdo->execute();

$i=0;
foreach ($pdo as $row){
	$i++;  
	$id     = $row['id'];
	$source = $row['source'];
	$path   = $row['path'];
	$name   = $row['name'];
	$photo = $row['photo'];

	$vi_array = explode("v=", $path);
	$video_screenshot = 'https://img.youtube.com/vi/'.$vi_array[1].'/'.$photo.'.jpg';//mqdefault.jpg
	
	echo '
	<a class="video_a '; if(isset($tv_mp[3])&&($tv_mp[3]==$vi_array[1])){echo 'active';} echo'" href="/'.$_COOKIE['lang'].'/video/'.$vi_array[1].'">
		
		<div class="image" style="background-image:url('.$video_screenshot.');"></div>
		<div class="text">'.$name.'</div>
		
	</a>
	';
;}

echo '</div>';
?>