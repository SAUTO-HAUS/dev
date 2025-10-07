<?php defined( '_DOIT' ) or die( 'Restricted access' );
	
$ann = array(
	/*array(
		'prc'=>$lng['w']['from'].' 17€',
		'ttl'=>$lng['t']['mini']['rent'],
		'txt'=>$lng['t']['mini']['cond'],
		'cat'=>$lng['w']['ctlg'],
		'img'=>'mini_1',
		'href'=>'/rent'
	),*/
	array(
		'prc'=>$lng['w']['credit'].' 0%',
		'ttl'=>$lng['t']['mini']['tyres'],
		'txt'=>'',
		'cat'=>$lng['w']['ctlg'],
		'img'=>'mini_2',
		'href'=>'/tyres'
	)
);

$ann_rnd = rand( 0, (count($ann)-1) );
// echo '
// <a class="mini" href="/'.$_COOKIE['lang'].$ann[$ann_rnd]['href'].'" style="background-image:url(/media/images/site/'.$ann[$ann_rnd]['img'].$img_frmt.');">
// 	<div class="grp">';
// 		foreach($ann[$ann_rnd] as $key => $val){
// 			if ( $val!='' && ($key!='href'&&$key!='img') ){
// 				echo '
// 				<div class="'.$key.'">'.$val.'</div>';
// 			}
// 		}
// 	echo '
// 	</div>
// </a>';
?>