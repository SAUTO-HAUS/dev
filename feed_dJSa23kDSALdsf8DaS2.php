<?php 
/*
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

require_once (_DEFAULT.'/defines.php');
require_once (_DEFAULT.'/config.php');
require (_DEFAULT.'/dbi.php');

session_start();

$lang_feed = array ( 'ro' => 'feed_ro.csv', 'ru' => 'feed_ru.csv', 'en' => 'feed_en.csv' );
$lang_subtitle = array( 'ro' => 'Vânzări auto în Moldova', 'ru' => 'Продажа Авто в Молдове', 'en' => 'Auto sales in Moldova' );

date_default_timezone_set('Europe/Kiev');

//header('Content-Encoding: UTF-8');
//header('Content-Type: text/csv; charset=UTF-8');
//header('Content-Disposition: attachment; filename="sample.csv"');
echo "\xEF\xBB\xBF";

foreach ($lang_feed as $this_lang => $csv_name){
	$data[0] = array('ID', 'Item title', 'Item Subtitle', 'Item description', 'Final URL', 'Image URL', 'Price');
	$i=1;

	$_COOKIE['lang']=$this_lang;
	include (_DEFAULT.'/language.php');

	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog WHERE `visible`=1 AND `active`=1');
	$pdo->execute();

	foreach ($pdo as $row){
		$l_fuel = $info_fuel[ $row['fuel'] ];
		$l_bodytype = $info_bodytype[ $row['bodytype'] ];
		$l_km_or_mi = $info_km_or_mi[ $row['km_or_mi'] ];
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_photo WHERE `id`=:id AND `main`="1"');
		$pdo->execute(array( 'id' => $row['id'] ));
		
		foreach ($pdo as $row2){$main_photo = $row2['name'];}
		
		if ($row['mileage']==''){$row['mileage']='0';}
		$z_price = number_format($row['price'], 2, '.', '');

		$data[$i] = array(
			$row['id'], 
			ucwords( str_replace('-', ' ', $row['brand'] ) ).' '.ucwords( str_replace('-', ' ', $row['model'] ) ),
			$lang_subtitle[ $this_lang ],
			$row['year'].','.$l_bodytype.','.$row['mileage'].' '.$l_km_or_mi.','.$l_fuel,
			'https://www.sauto.md/ru/cars/'.$row['brand'].'-'.$row['model'].'-'.$row['id'],
			'https://www.sauto.md/'._CAR_IMG.'/'.$row2['path'].'/'.$row['id'].'/med/'.$main_photo.'.jpg',
			$z_price.' EUR'
		);
		$i++;
	}

	if ( file_exists($csv_name) ) {unlink($csv_name);}

	$file = $csv_name;
	$fp = fopen($file, 'wb');
	$file="\xEF\xBB\xBF".$file;
	
	foreach ( $data as $key ) {
		fputcsv($fp, $key, ',');
	}
	fclose($fp);

}


require_once('class.phpmailer.php');

$bodytext = " www.sauto.md \n Updated at ".date('H:i:s ( d.m.Y )')." \n RU: www.sauto.md/feed_ru.csv \n RO: www.sauto.md/feed_ro.csv \n EN: www.sauto.md/feed_en.csv";

$email = new PHPMailer();
$email->From      = 'cron@sauto.md';
$email->FromName  = 'Sauto';
$email->Subject   = 'The feed was successfully updated';
$email->Body      = $bodytext;

$email->SMTPSecure = "ssl";

//$email->AddAttachment( 'feed_ru.csv' , 'feed_ru.csv' );
//$email->AddAttachment( 'feed_ro.csv' , 'feed_ro.csv' );

$email->AddAddress( 'clientsmd008@gmail.com' );
//$email->AddAddress( 'victorpro777@gmail.com' );

return $email->Send();

$db->connection = null;
*/
?>