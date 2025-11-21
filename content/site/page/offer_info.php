<?php defined( '_DOIT' ) or die( 'Restricted access' );

use App\Helper\PhoneHelper;

// If this is a 404 page, show 404 content and exit
if (isset($GLOBALS['page_is_404']) && $GLOBALS['page_is_404'] === true) {
    include(_DEFAULT.'/404.php');
    exit;
}

$pdo = $db->prepare('SELECT * FROM '.$prefx.'_offer_catalog WHERE `id`=:id');
$pdo->execute(array('id' => $t_mp[3]));

$offer_found = false;
foreach($pdo as $row){
    $offer_found = true;
    $c_id = $row['id'];
	$c_type = $row['type'];
	//$c_specs = $row['specs'];
	$c_name = $row['name'];
	$c_desc = $row['description'];
	$c_price = $row['price'];
	$c_currency = $row['currency'];
	$c_visible = $row['visible'];
	$c_active = $row['active'];
}

$pdo = $db->prepare('SELECT * FROM '.$prefx.'_offer_photo WHERE `id`=:id AND main=1');
$pdo->execute(array('id' => $t_mp[3]));

foreach($pdo as $row){
	$c_photo_name = $row['name'];
}

echo '
<style>
#left_side {display:none;}
#right_side {width:100%; margin:0;}
</style>

<div id="info_container">
	<div id="info_left">
		
		<div class="main_image">
			<img class="info_main_image info_photo zoom" img_numb="1" src="/'._OFFER_IMG.'/'.$c_path.'/'.$c_id.'/med/'.$c_photo_name.'.jpg" alt="'.$c_type.'" title="'.$c_name.'" />
		</div>
		
		<div class="gallery_image">';
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_offer_photo WHERE `id`= :id');
			$pdo->execute(array( 'id' => $t_mp[3] ));
			$i=0;
			foreach ($pdo as $row){ 
				$c_photo_name = $row['name'];
				echo '
				<img class="thumb_img info_photo" img_numb="'.($i+1).'" src="/'._OFFER_IMG.'/'.$c_path.'/'.$c_id.'/med/'.$c_photo_name.'.jpg" alt="'.$c_type.'" title="'.$c_name.'" />
				';
				$i++;
			}
			
			echo '
			<div id="gallery_img_count" count="'.$i.'"></div>
			<div id="preloaded_img" class="none"></div>
		</div>
	</div>
	<div id="info_right" class="select">
		<div class="info_name"><div class="info_id"><div class="info_id_in"></div></div><span>'.strtoupper($c_name).'</span><div class="info_count">'.$i.' '.$lang_info_photo_count.'</div></div>
    
		<div class="select">
			<div class="info_desc text_div">'; 
				if($c_desc!=''){echo '<div class="info_desc_extra">'.$c_desc.'</div>';}
        echo '
        </div>
    </div>
    <div class="info_contacts">'.$lang_info_contacts.' '.PhoneHelper::formatPhone(PhoneHelper::getGeneralPhone(), 'display').'</div>
    <div class="info_price" style="text-align:center;">
        <div class="price" title="'.$lang_price.'" style="float:none;">'.$c_price.' <span>'.$info_currency[$c_currency].'</span></div>
    </div>
</div>
';

echo '
</div>
';

?>