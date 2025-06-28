<?php defined( '_DOIT' ) or die( 'Restricted access' );

$count_limit = $isMobile=='1' ? 11 : 23;

$query_args = array();
$sql = 'SELECT * FROM '.$prefx.'_team';
$sql .= ' ORDER BY `id` DESC';
//$sql .= ' LIMIT '.($count_limit+1);

$count = 0;

echo '<section class="catalog_page" moreCarText="'.$lang_more.'">';

$pdo = $db->prepare($sql);
$pdo->execute();

echo '
<div id="add_new" class="car_box tyres" title="'.$adm_lang['add'].'"> <div></div> </div>

<style>
	.car_box:hover > .adm_menu > .edit, .car_box:hover > .adm_menu > .hide {height:50%; background-size:auto 15%; float:left;}
	.car_box:hover > .adm_menu > .erase {float:right;}
</style>
';

foreach ($pdo as $row){

	$c_id = $row['id'];
	$c_fullname = $row['fullname'];
	$c_work_post = $row['work_post'];
	$c_telephone = $row['telephone'];
	$c_photo_path = $row['photo_path'];


	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_team_photo WHERE `item_id`=:id');
	$pdo->execute(array( 'id' => $c_id ));

	foreach ($pdo as $row2){
		$c_main_photo = $row2['name'];
	;}
	
	echo '
	
	<div class="car_box tyres" title="'.$c_fullname.'" idz="'.$c_id.'">
		<div class="adm_menu" idz="'.$c_id.'">
			<div class="edit action_button" title="'.$adm_lang['edit'].'"> <div></div> </div>
			<div class="erase action_button" title="'.$adm_lang['delete'].'"> <div></div> </div>';
		echo '
		</div>
		<div class="team img_container">
			<div class="info">
				<div class="id" title="id">'.$c_id.'</div>
				<div class="author" title="author">'.$c_author.'</div>
			</div>
			<img src="/'._TEAM_IMG.'/'.$c_photo_path.'/'.$c_id.'/med/'.$c_main_photo.'.jpg" alt="'.$c_fullname.'" />
		</div>

		<div class="team_info">
			'.$lang_fullname.': '.$c_fullname.'<br />
			'.$lang_work_post.': '.$c_work_post.'<br />
			'.$lang_telephone.': '.$c_telephone.'<br />
		</div>
	</div>
	';
	
	$count++;
	//if($count==$count_limit){break;}
}

//if ($count==$count_limit){ echo '<div id="more_cars">'.$lang_more.'</div>';}

echo '
</section>
<div id="car_countz" count="'.($count_limit+1).'" pos="'.$c_id.'"></div>
';

?>