<?php defined( '_DOIT' ) or die( 'Restricted access' );

$is_https = 1;
$is_www = 0;
$default_lang = 'ro';

//Maintenance work
$offline = 0;

//Default
$http_host = $_SERVER['HTTP_HOST'];
$domain_name = str_replace('www.', '', $http_host );
$uri = $_SERVER['REQUEST_URI'];
$user_ip = $_SERVER['REMOTE_ADDR'];

$protocol = $is_https==1 ? 'https://' : 'http://';
$site_url = $protocol.$http_host;

//MAIN
$lang_arr = ['ro', 'ru', 'en'];

$site_name = 'Sauto';

//UTC time
date_default_timezone_set('Europe/Chisinau');

//DB
$prefx = 'gh3sp';

//ADM
$admin_dir = 'adminsauto';
//menu permissions
$admin_menu = [
	'dev' => ['cars', 'tyres', 'seo', 'mail', 'slider', 'users', 'docs', 'sett'/*, 'video', 'team'*/],
	'sad' => ['cars', 'tyres', 'seo', 'mail', 'docs'],
	'adm' => ['cars', 'tyres', 'seo', 'mail', 'docs'],
	'mod' => ['cars', 'tyres'],
	'mod2' => ['cars', 'tyres'],
	'seo' => ['seo'],
	'x1'  => ['docs'],
	'x2'  => ['cars', 'docs'],
	'publisher' => ['cars', 'tyres', 'docs'],
	'publisher_limited' => ['cars', 'tyres', 'docs']
];

$admin_menu_dev1 = [
	//'dev' => [ 'cars'=>['ctlg','br_lst'], 'tyres'=>['ctlg','br_lst'], 'seo'=>['ctlg'], 'mail'=>['message', 'order', 'favorites', 'archive'], 'slider'=>['ctlg'], 'docs'=>['ctlg', 'arch'], 'users'=>['ctlg'], 'sett'=>['info','adm_usr']/*, 'settings', 'video', 'team'*/ ],
    'dev' => ['cars' => ['ctlg', 'br_lst'], 'tyres' => ['ctlg', 'br_lst'], 'seo' => ['ctlg'], 'mail' => ['message', 'order', 'favorites', 'archive'], 'docs' => ['create', 'ctlg'], 'notcrm' => ['app'], 'sett' => ['info', 'annc', 'adm_usr']],
    'sad' => ['cars' => ['ctlg'], 'tyres' => ['ctlg'], 'seo' => ['ctlg'], 'mail' => ['message', 'order', 'favorites', 'archive'], 'docs' => ['create', 'ctlg'], 'sett' => ['info']],
    'adm' => ['cars' => ['ctlg'], 'tyres' => ['ctlg'], 'seo' => ['ctlg'], 'mail' => ['message', 'order', 'favorites', 'archive'], 'docs' => ['create', 'ctlg'], 'sett' => ['info']],
    'mod' => ['cars' => ['ctlg'], 'tyres' => ['ctlg']],
    'mod2' => ['cars' => ['ctlg'], 'docs' => ['create', 'ctlg']],
    'seo' => ['seo' => ['ctlg']],
    'x1' => ['docs' => ['create', 'ctlg']],
    'x2' => ['cars' => ['ctlg'], 'docs' => ['create', 'ctlg'], 'sett' => ['info']],
    'publisher' => ['cars' => ['ctlg'], 'tyres' => ['ctlg'], 'docs' => ['create', 'ctlg']],
    'publisher_limited' => ['cars' => ['ctlg'], 'tyres' => ['ctlg'], 'docs' => ['create', 'ctlg']]
];

$hided_admin_menu = ['users', 'settings', 'video', 'team'];

$restrict_admin_menu = [
	//5  => [ 'page'=>[ 'docs'=>['ctlg'=>0] ], 'act'=>[ 'com'=>['cars'=>0] ] ],
	15 => [ 'act'=>[ 'com'=>['cars'=>0] ] ], //ciumasu
	16 => [ 'page'=>[ 'docs'=>['ctlg'=>0] ], 'act'=>[ 'com'=>['cars'=>0] ] ], //Jurgen Wundekleiner
	21 => [ 'act'=>[ 'com'=>['cars'=>0] ] ] //Tudor L.
];

//SITE
//allow urls
$menu_arr = [
    'cars'      => '1',
    'credit'    => '1',
    'services'  => '1',
    'tradein'   => '1',
    'tyres'     => '1',
    'rent'      => '0',
    'contacts'  => '1',

];
$url_arr = [
    '',
    '#',
    $admin_dir,
    'cars',
    'services',
    'tyres',
    'rent',
    'credit',
    'contacts',
    'search',
    'about',
    'privacy',
    'terms',
    'warranty',
    'dev_tools',
    'credit',
    'tradein',
    'telegram',
    'telegram_adv'
];
$sub_urls = [
    '',
    'cars',
    /*'offers'*/
];

//split urls 1
//$sp = rawurldecode( $uri );
$mp = rawurldecode( mb_strtolower( $uri ) );

//google ads checker
$gclid = ['?gclid=', '&gclid=', '?fbclid=', '&fbclid='];
foreach($gclid as $v){
	if( strpos($mp, $v) == true ){ $adw_mp = explode( $v, $mp ); break; }
	else { $adw_mp = explode( ' ', $mp ); }
}

//split urls 2
$q_mp = explode('?', $mp);
/*$t_mp = preg_split('![?/]!', $adw_mp[0]);*/ //$t_mp = explode('/', $adw_mp[0]);
$t_mp = preg_split('![/]!', $q_mp[0]);
//$tq_mp = explode('/', $q_mp[0]);
//$r_mp = explode('=', $mp);

//$url_id = (int)preg_replace('/[^0-9.]+/', '', end( explode("-", end( $t_mp ) ) ) );
$url_id = explode("-", end( $t_mp ) );
$url_id = (int)preg_replace('/[^0-9.]+/', '', end( $url_id ) );
//if ( !is_int($url_id) ){unset($url_id);}

if ( isset($t_mp[3]) ){
	$it_id = explode('-', $t_mp[3]);
	$it_id = end( $it_id );
}

$lang_mp = '';
foreach ($t_mp as $k => $v){ $lang_mp .= $k>1 ? '/'.$v : ''; }
$lang_mp .= isset($q_mp[1]) ? '?'.$q_mp[1] : '';

//$tv_mp = explode('/', $sp);
//$old_mp=preg_split('![?/-]!', $mp);

$n_row = '&#013;';

$img_frmt = (usr_agent()==='IOS'||usr_agent()==='MAC') ? '.jpg' : '.webp';

?>