<?php defined( '_DOIT' ) or die( 'Restricted access' );
/*
//$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg');
//$pdo->execute();
$pdo = $db->prepare('
	SELECT 
	ctlg.id AS ctlg_id, ctlg.br AS ctlg_br, ctlg.mo AS ctlg_mo, 
	list.id AS list_id, list.br AS list_br, list.mo AS list_mo, list.br_nm AS list_br_nm, list.mo_nm AS list_mo_nm 
	FROM `'.$prefx.'_tyre_ctlg` AS ctlg 
	LEFT JOIN `'.$prefx.'_tyre_list` AS list ON list.`br_nm` = ctlg.`br` AND list.`mo_nm` = ctlg.`mo`
');
$pdo->execute();
	
//WHERE ref.`id` = 4

foreach ($pdo as $r){
	//echo $r['br'].' '.$r['mo'];
	
	
	//if ($r['list_br']!='' && $r['list_mo']!=''){
		//echo $r['ctlg_id'].': '.$r['ctlg_br'].' '.$r['ctlg_mo'].' === '.$r['list_br'].' '.$r['list_mo'];
	//}else {echo '______________________________________';}
	echo 'D<br/>';
	
	$pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `br`=:br , `mo`=:mo WHERE `id`=:id');
	$pdo->execute([ 'br'=>$r['list_br'], 'mo'=>$r['list_mo'], 'id' => $r['ctlg_id'] ]);	
}
*/

/*
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `br`=:br');
$pdo->execute([ 'br'=>'linglong_greenmax' ]);
foreach ($pdo as $r){
	$pdo = $db->prepare('UPDATE '.$prefx.'_tyre_ctlg SET `br`=:br, `mo`=:mo, `br_nm`=:br_nm, `mo_nm`=:mo_nm WHERE `id`=:id');
	$pdo->execute([ 'id'=>$r['id'], 'br'=>'linglong', 'mo'=>'green_max_'.$r['mo'], 'br_nm'=>'Linglong', 'mo_nm'=>'GREEN-Max '.$r['mo_nm'] ]);	
}
*/

/*
$tyres_ar = [
	'avon'=>'AVON',
	'accelera'=>'Accelera',
	'achilles'=>'Achilles',
	'aderenza'=>'Aderenza',
	'aeolus'=>'Aeolus',
	'altenzo'=>'Altenzo',
	'amberstone'=>'Amberstone',
	'america'=>'America',
	'amtel'=>'Amtel',
	'annaite'=>'Annaite',
	'arctic_claw'=>'Arctic Claw',
	'atlas'=>'Atlas',
	'atturo'=>'Atturo',
	'aufine'=>'Aufine',
	'austone'=>'Austone',
	'autoguard'=>'Autoguard',
	'bfgoodrich'=>'BFGoodrich',
	'barum'=>'Barum',
	'blackstone'=>'Blackstone',
	'brasa'=>'Brasa',
	'bridgestone'=>'Bridgestone',
	'ceat'=>'CEAT',
	'continental'=>'Continental',
	'contyre'=>'Contyre',
	'cooper'=>'Cooper',
	'cooper_chengshan'=>'Cooper Chengshan',
	'cordiant'=>'Cordiant',
	'davanti'=>'Davanti',
	'dayton'=>'Dayton',
	'dean_tires'=>'Dean Tires',
	'debica'=>'Debica',
	'dextero'=>'Dextero',
	'diplomat'=>'Diplomat',
	'dunlop'=>'Dunlop',
	'duraturn'=>'Duraturn',
	'durun'=>'Durun',
	'esa_tecar'=>'ESA-Tecar',
	'evergreen'=>'Evergreen',
	'falken'=>'Falken',
	'federal'=>'Federal',
	'fenix'=>'Fenix',
	'firestone'=>'Firestone',
	'formula'=>'Formula',
	'fulda'=>'Fulda',
	'fullrun'=>'Fullrun',
	'fullway'=>'Fullway',
	'gt_radial'=>'GT Radial',
	'general_tire'=>'General Tire',
	'gislaved'=>'Gislaved',
	'goform'=>'Goform',
	'goldway'=>'Goldway',
	'goodride'=>'Goodride',
	'goodyear'=>'Goodyear',
	'greendiamond'=>'GreenDiamond',
	'gremax'=>'Gremax',
	'haida_group'=>'Haida Group',
	'hankook'=>'Hankook',
	'hercules'=>'Hercules',
	'hifly'=>'Hifly',
	'hilo'=>'Hilo',
	'imperial'=>'Imperial',
	'infinity_tyres'=>'Infinity Tyres',
	'insa_turbo'=>'Insa Turbo',
	'ironman'=>'Ironman',
	'jinyu'=>'Jinyu',
	'joyroad'=>'Joyroad',
	'kelly'=>'Kelly',
	'kenda'=>'Kenda',
	'kinforest'=>'Kinforest',
	'kingstar'=>'KingStar',
	'kleber'=>'Kleber',
	'kormoran'=>'Kormoran',
	'kumho'=>'Kumho',
	'landsail'=>'Landsail',
	'lassa'=>'Lassa',
	'linglong'=>'LingLong',
	'long_march'=>'Long March',
	'mabor'=>'Mabor',
	'marangoni'=>'Marangoni',
	'marshal'=>'Marshal',
	'mastercraft'=>'Mastercraft',
	'matador'=>'Matador',
	'maxtrek'=>'Maxtrek',
	'maxxis'=>'Maxxis',
	'medeo'=>'Medeo',
	'metzeler'=>'Metzeler',
	'michelin'=>'Michelin',
	'milestone'=>'Milestone',
	'minerva'=>'Minerva',
	'multi_mile'=>'Multi-Mile',
	'nankang'=>'Nankang',
	'neuton'=>'Neuton',
	'nexen'=>'Nexen',
	'roadstone'=>'Roadstone',
	'nitto'=>'Nitto',
	'nokian'=>'Nokian',
	'nordman'=>'Nordman',
	'novex'=>'Novex',
	'ovation_tyres'=>'Ovation Tyres',
	'petlas'=>'Petlas',
	'pirelli'=>'Pirelli',
	'platin'=>'Platin',
	'point_s'=>'Point S',
	'premada'=>'Premada',
	'premiorri_viamaggiore'=>'Premiorri ViaMaggiore',
	'riken'=>'Riken',
	'roadcruza'=>'Roadcruza',
	'rockstone'=>'Rockstone',
	'rosava'=>'Rosava',
	'rotalla'=>'Rotalla',
	'rotex'=>'Rotex',
	'sunny'=>'SUNNY',
	'suntek'=>'SUNTEK',
	'sailun'=>'Sailun',
	'sava'=>'Sava',
	'semperit'=>'Semperit',
	'silverstone'=>'SilverStone',
	'solideal'=>'Solideal',
	'sonar'=>'Sonar',
	'sportiva'=>'Sportiva',
	'starfire'=>'Starfire',
	'starmaxx'=>'Starmaxx',
	'starperformer'=>'Starperformer',
	'sumitomo'=>'Sumitomo',
	'syron'=>'Syron',
	'telstar_tire'=>'Telstar Tire',
	'tigar'=>'Tigar',
	'torque'=>'Torque',
	'toyo'=>'Toyo',
	'tracmax'=>'Tracmax',
	'tri_ace'=>'Tri Ace',
	'triangle_group'=>'Triangle Group',
	'tunga'=>'Tunga',
	'uniroyal'=>'Uniroyal',
	'vsp'=>'VSP',
	'viatti'=>'Viatti',
	'viking'=>'Viking',
	'vredestein'=>'Vredestein',
	'wanli'=>'Wanli',
	'westlake_tyres'=>'Westlake Tyres',
	'winrun'=>'Winrun',
	'winter_tact'=>'Winter Tact',
	'yokohama'=>'Yokohama',
	'zeta'=>'ZETA',
	'zeetex'=>'Zeetex',
	'belshina'=>'Белшина',
	'uralshina'=>'Уралшина',
	'unigrip'=>'Unigrip',
	'grenlander'=>'Grenlander',
	'comforser'=>'Comforser'
];

foreach ($tyres_ar as $k => $v){
	$pdo = $db->prepare('SELECT `id` FROM '.$prefx.'_tyre_list WHERE `br_nm`=:br_nm AND `mo`=\'\'');
	$pdo->execute([ 'br_nm' => $v ]);
	if ( $pdo->fetchColumn() ){echo $v.' exist<br/>';}
	else {
		$pdo = $db->prepare('INSERT INTO '.$prefx.'_tyre_list (`br`,`br_nm`) VALUES (:br,:br_nm)'); $pdo->execute(['br'=>$k, 'br_nm'=>$v]);
	}
}
*/

?>