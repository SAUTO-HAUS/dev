<?php 

define('_DOIT', 1);
define('_DEFAULT', $_SERVER["DOCUMENT_ROOT"].'/content/default');

require_once (_DEFAULT.'/defines.php');
require_once (_DEFAULT.'/functions.php');
require_once (_DEFAULT.'/config.php');
require_once (_DEFAULT.'/language.php');
require (_DEFAULT.'/dbi.php');
require_once (_DEFAULT.'/seo.php');



foreach ($lang_arr as $lang){
	
	$linked = 'root';
	$page = $site_url.'/'.$lang;
	$changefreq = 'daily';
	$priority = '0.50';
	
	echo $linked.', '.$lang.', '.$page.'<br/>'.$meta_desc['default'][$lang].'<br/>'.$changefreq.', '.$priority.'<br/><br/>';

	foreach ($menu_arr as $menu => $active){
		$page = $site_url.'/'.$lang.'/'.$menu;
		echo $linked.', '.$lang.', '.$page.'<br/>'.$meta_desc[$menu][$lang].'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
		
		switch ($menu) {
			case 'cars':
				$changefreq = 'daily';
				$priority = '0.80';
				
				$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog');
				$pdo->execute();
				foreach ($pdo as $row){
					$page = $site_url.'/'.$lang.'/car/'.$row['brand'].'-'.$row['model'].'-'.$row['id'];
					$c_desc = ' '.$row['brand_name'].' '.$row['model_name'].', '.$row['year'];
					echo $menu.', '.$lang.', '.$page.'<br/>'.$meta_desc['car'][$lang].$c_desc.'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
				}
			break;
			case 'tyres':
				$changefreq = 'weekly';
				$priority = '0.75';
				
				$pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyres');
				$pdo->execute();
				foreach ($pdo as $row){
					$page = $site_url.'/'.$lang.'/tyre/'.mb_strtolower($row['brand'], 'UTF-8').'-'.$row['width'].'-'.$row['height'].'-r'.$row['ins_diam'].'-'.$row['season'].'-'.$row['id'];
					$c_desc = ' '.$row['brand'].' '.$row['width'].'/'.$row['height'].' R'.$row['ins_diam'];
					echo $menu.', '.$lang.', '.$page.'<br/>'.$meta_desc['tyre'][$lang].$c_desc.'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
				}
			break;
			case 'video':
				$changefreq = 'monthly';
				$priority = '0.50';
				
				$pdo = $db->prepare('SELECT * FROM '.$prefx.'_video');
				$pdo->execute();
				foreach ($pdo as $row){
					$video_path = explode('?v=', $row['path']);
					$page = $site_url.'/'.$lang.'/video/'.$video_path[1];
					$c_desc = ' '.$row['name'];
					echo $menu.', '.$lang.', '.$page.'<br/>'.$meta_desc['video'][$lang].$c_desc.'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
				}
			break;
		}
		
	}
}

$lang = $_COOKIE['lang'];



/*
$sql_arr = array(
	'cars'=>array(
		'tab'=>'catalog',
		'page'=>'car',
		'page_col'=>array('brand', 'model', 'id'),
		'meta_col'=>array('brand_name'=>'0', 'model_name'=>'0', 'year'=>'0', 'bodytype'=>'1', 'fuel'=>'1', 'transmission'=>'1', 'color'=>'1'),
		'var'=>'info_',
		'sql_lang'=>false
	),
	
	'tyres'=>array(
		'tab'=>'tyres',
		'page'=>'tyre',
		'page_col'=>array('brand', 'width', 'height', 'ins_diam', 'season', 'id'),
		'meta_col'=>array('brand'=>'0', 'width'=>'0', 'height'=>'0', 'ins_diam'=>'0', 'season'=>'0'),
		'var'=>'',
		'sql_lang'=>false
	),
	'video'=>array(
		'tab'=>'video',
		'page'=>'video',
		'page_col'=>array('path'),
		'meta_col'=>array('source'=>'0', 'name'=>'0'),
		'var'=>'',
		'sql_lang'=>false
	),
	'news'=>array(
		'tab'=>'news',
		'page'=>'news',
		'page_col'=>array('group_id'),
		'meta_col'=>array('title'=>'0'),
		'var'=>'',
		'sql_lang'=>true
	)
);

$lang = $_COOKIE['lang'];

	$linked = 'root';
	$page = $site_url.'/'.$lang;
	$changefreq = 'daily';
	$priority = '0.50';
	
	echo $linked.', '.$lang.', '.$page.'<br/>'.$meta_desc['default'][$lang].'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
	
	foreach ($menu_arr as $m_name => $m_active){
		
		if ($m_active=='1'){
			$linked = 'root';
			$page = $site_url.'/'.$lang.'/'.$m_name;
			$changefreq = 'daily';
			$priority = '0.50';
			
			echo $linked.', '.$lang.', '.$page.'<br/>'.$meta_desc[$m_name][$lang].'<br/>'.mb_strtolower(${'lang_menu_'.$m_name}, 'UTF-8').'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
			
			if ( array_key_exists($m_name, $sql_arr) ){
				
				if ($sql_arr[$m_name]['tab']){
		
					$linked = $sql_arr[$m_name]['page'] ? $sql_arr[$m_name]['page'] : '';
	
					if ( in_array( $sql_arr[$m_name]['tab'] , array('catalog', 'tyres', 'video', 'news') ) ){
		
						$sql = 'SELECT * FROM '.$prefx.'_'.$sql_arr[$m_name]['tab'].' WHERE 1=1 ';
						$exec_arr = array();
		
						if ($sql_arr[$m_name]['sql_lang']===true){ $sql .= 'AND `lang`=:lang'; $exec_arr['lang']=$lang; }
		
						$pdo = $db->prepare($sql);
						$pdo->execute($exec_arr);

						foreach ($pdo as $row){
							$c_page = '';
							$i=0;
							$c_keys = '';
							
							foreach ($sql_arr[$m_name]['page_col'] as $c_name){
								if ( array_key_exists($c_name, $row) ){
									$ins_diam = $c_name == 'ins_diam' ? 'r' : '';
									$ins_diam = $c_name == 'ins_diam' ? 'r' : '';
									$c_page .= $i>0 ? '-': ''; $c_page .= $ins_diam.mb_strtolower($row[$c_name], 'UTF-8');
									$i++;
								}
							}
							
							$i=0;
							foreach ($sql_arr[$m_name]['meta_col'] as $c_name => $c_translate){
								if ( array_key_exists($c_name, $row) ){
									$c_keys .= $i>0 ? ', ': ''; $c_keys .= $c_translate==1 ? mb_strtolower( str_replace(' ', ', ', ${ $sql_arr[$m_name]['var'].$c_name }[ $row[$c_name] ]), 'UTF-8' ) : mb_strtolower( str_replace(' ', ', ', $row[$c_name]), 'UTF-8' );
									$i++;
								}
							}
			
							$page = $site_url.'/'.$lang.'/'.$sql_arr[$m_name]['page'].'/'.$c_page;
							$changefreq = 'daily';
							$priority = '0.80';
			
							echo $linked.', '.$lang.', '.$page.'<br/>'.$meta_desc[$sql_arr[$m_name]['page']][$lang].'<br/>'.$c_keys.', '.mb_strtolower(${'lang_menu_'.$m_name}, 'UTF-8').'<br/>'.$changefreq.', '.$priority.'<br/><br/>';
				
						;}
					}
					else {
						return 'Forbidden!';
					}
				}
				else {
					return 'Error!';
				}
				
			}
		}
		
	}
	




	
*/

?>