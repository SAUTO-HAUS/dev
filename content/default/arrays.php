<?php defined( '_DOIT' ) or die( 'Restricted access' );

$file_av_ar = [
	'img'=>[
		'fldr'=>[ /*max*/'high'=>['sz'=>2000, 'ql'=>80], 'med'=>['sz'=>600,  'ql'=>80]/*, 'min'=>['sz'=>200,  'ql'=>60]*/ ],
		'frmt'=>[ 'image/jpeg'=>['jpg','webp'], 'image/jpg'=>['jpg','webp'], 'image/png'=>['png','webp'], 'image/gif'=>['gif'], 'image/webp'=>['webp'] ]
	],
	'doc'=>[
		'fldr'=>['doc'],
		'frmt'=>[ 'application/pdf'=>['pdf'] ]
	]
];

/*$sc_ar = [
	'fb'=>['name'=>'Facebook', 	'img'=>['b'=>'sc_fb.svg', 'w'=>'sc_fb_w.svg'], 'url'=>'https://www.facebook.com/sauto.md'],
	'ig'=>['name'=>'Instagram', 'img'=>['b'=>'sc_ig.svg', 'w'=>'sc_ig_w.svg'], 'url'=>'https://www.instagram.com/sauto.md/'],
	'tt'=>['name'=>'TikTok', 	'img'=>['b'=>'sc_tt.svg', 'w'=>'sc_tt_w.svg'], 'url'=>'https://www.tiktok.com/@sauto.md'],
	'tg'=>['name'=>'Telegram', 	'img'=>['b'=>'sc_tg.svg', 'w'=>'sc_tg_w.svg'], 'url'=>'https://t.me/sautohaus']
];*/

$sc_ar = [
	'fb'=>['name'=>'Facebook', 	'img'=>['b'=>'sc_fb.svg', 'w'=>'fb.png'], 'url'=>'https://www.facebook.com/sauto.md'],
	'ig'=>['name'=>'Instagram', 'img'=>['b'=>'sc_ig.svg', 'w'=>'ig.png'], 'url'=>'https://www.instagram.com/sauto.md/'],
	'tt'=>['name'=>'TikTok', 	'img'=>['b'=>'sc_tt.svg', 'w'=>'tt.png'], 'url'=>'https://www.tiktok.com/@sauto.md'],
	'tg'=>['name'=>'Telegram', 	'img'=>['b'=>'sc_tg.svg', 'w'=>'tg.png'], 'url'=>'https://t.me/sautohaus']
];

$grp_arr = [
	'o_serv'=>[
		['img'=>'car-sales-v2',		'ttl'=>$lng['p']['services']['sale']['name'], 			'txt'=>$lng['p']['services']['sale']['ttl'], 		'href'=>'sale'],
		['img'=>'invoice-v2',		'ttl'=>$lng['p']['services']['estimation']['name'], 	'txt'=>$lng['p']['services']['estimation']['ttl'], 	'href'=>'estimation'],
		['img'=>'web-v2',			'ttl'=>$lng['p']['services']['tradein']['name'],		'txt'=>$lng['p']['services']['tradein']['ttl'], 	'href'=>'tradein'],
		['img'=>'insurance-v2',		'ttl'=>$lng['p']['services']['insurance']['name'], 	'txt'=>$lng['p']['services']['insurance']['ttl'], 	'href'=>'insurance']
	]
];

$foo_arr = [
	//'vehicles'=>['sdn', 'hbk', 'unv', 'pkp', 'mbs', 'van'],
	'services'=>['sale', 'tradein', 'estimation', 'testdrive', 'insurance', 'order'],
	'information'=>['about', 'credit', 'terms', 'warranty', 'privacy', 'contacts']
];

// Phone numbers moved to PhoneReplacementService - use approved 5-group system only
// Legacy phone array kept for reference but should not be used
$phone = [
	// All phone numbers now managed through PhoneReplacementService
	// Approved numbers: +37379600747, +37379600386, +37379500735, +37379600361
];

$email = ['def'=>'info@sauto.md'];

$serv_arr = [
	'transportation'=>['img'=>'serv_transportation'.$img_frmt, 'grp'=>'none'],

	'sale'=>['img'=>'car-sales-v2.svg', 'grp'=>'menu'],
	'estimation'=>['img'=>'invoice-v2.svg', 'grp'=>'menu'],
	'tradein'=>['img'=>'web-v2.svg', 'grp'=>'menu'],
	'insurance'=>['img'=>'insurance-v2.svg', 'grp'=>'menu'],
	'testdrive'=>['img'=>'test-drive-v2.svg', 'grp'=>'menu'],
	'order'=>['img'=>'car-v2.svg', 'grp'=>'menu'],
	
	'calc_credit'=>['img'=>'serv_calc_cred.svg', 'grp'=>'calc'],
	'calc_insurance'=>['img'=>'serv_calc_insr.svg', 'grp'=>'calc'],
	'calc_customs'=>['img'=>'serv_calc_cust.svg', 'grp'=>'calc'],
	
	'payment'=>['img'=>'edc-v2.svg', 'grp'=>'info'],
	'terms'=>['img'=>'terms-v2.svg', 'grp'=>'info']
];
$info_arr = ['about', 'privacy', 'credit', 'terms', 'warranty'];

$rent_txt_arr = [
	'autopark'=>['ttl'=>'Большой автопарк', 'txt'=>'50+ уникальных авто в аренду из нашего автопарка', 'img'=>'rent_autopark.svg'],
	'valuation'=>['ttl'=>'Оценка авто', 'txt'=>'Страхование КАСКО и ОСАГО включено', 'img'=>'rent_protection.svg'],
	'confidential'=>['ttl'=>'Полная конфиденциальнось', 'txt'=>'Полная конфиденциальнось. Не ведется видео и аудиозапись в автомобиле.', 'img'=>'rent_confidential.svg'],
	'support'=>['ttl'=>'Поддержка 24/7', 'txt'=>'Круглосуточная техническая поддержка', 'img'=>'rent_support.svg']
];

$f_it_t_arr = [
	'car'=>[
		'get'=> ['gr','brmo','yr','fl','tra','bt','wd','clr','mlg','vol','sts','prc'],
		'inpt_txt' => ['yr','prc','mlg','vol','sts'],
		'inpt_sel' => ['bt','fl','tra','wd','clr']
	],
	'tyre'=>[
		'get'=> ['w','h','d','c','ss','brmo','prc'],
		'inpt_txt' => ['prc'],
		'inpt_sel' => ['w','h','d','c','ss']
	]
	
];

$f_cr_bt_arr = [
	'sdn'=>['name'=>'sedan'],
	'suv'=>['name'=>'suv'],
	'hbk'=>['name'=>'hatchback'],
	'unv'=>['name'=>'universal'],
	'cup'=>['name'=>'coupe'],
	'crv'=>['name'=>'crossover'],
	'mnv'=>['name'=>'minivan'],
	'pkp'=>['name'=>'pickup'],
	'van'=>['name'=>'van'],
	'mbs'=>['name'=>'minibus']
];

$f_it_xtd_arr = [
	'car'=>[
		'yr'=>['t'=>strtok($lng['l']['car']['spec']['yr'], " "), 'i'=>'1', 'unit'=>''],
		'fl'=>['t'=>$lng['l']['car']['spec']['fl'], 'i'=>'0'],
		'tra'=>['t'=>$lng['l']['car']['spec']['tra'], 'i'=>'0'],
		'wd'=>['t'=>$lng['l']['car']['spec']['wd'], 'i'=>'0'],
		'clr'=>['t'=>$lng['l']['car']['spec']['clr'], 'i'=>'0'],
		'mlg'=>['t'=>$lng['l']['car']['spec']['mlg'], 'i'=>'1', 'unit'=>$lng['l']['unit']['km']],
		'vol'=>['t'=>strtok($lng['l']['car']['spec']['vol'], " "), 'i'=>'1', 'unit'=>$lng['l']['unit']['cm3']],
		'sts'=>['t'=>$lng['l']['car']['spec']['sts'], 'i'=>'1', 'unit'=>''],
		'prc'=>['t'=>$lng['w']['prc'], 'i'=>'1', 'unit'=>'€']
	],
	'tyre'=>[
		'w'=>['t'=>$lng['l']['tyre']['spec']['w'], 'i'=>'0'],
		'h'=>['t'=>$lng['l']['tyre']['spec']['h'], 'i'=>'0'],
		'd'=>['t'=>$lng['l']['tyre']['spec']['d'], 'i'=>'0'],
		'c'=>['t'=>'C', 'i'=>'0'],
		'ss'=>['t'=>$lng['l']['tyre']['spec']['ss'], 'i'=>'0'],
		'brmo'=>['t'=>'', 'i'=>'0'],
		//'br'=>['t'=>$lng['l']['tyre']['spec']['br'], 'i'=>'0'],
		//'mo'=>['t'=>$lng['l']['tyre']['spec']['mo'], 'i'=>'0'],
		'prc'=>['t'=>$lng['w']['prc'], 'i'=>'1', 'unit'=>'MDL']
	]
];

$clr_arr = [
	'l_grn'=>'#6f8a3f 20%, #91aa3b 80%',
	'blu'=>'#0071ea 20%, #004c9d 80%',
	'brn'=>'#883c05 20%, #8a4624 80%',
	'cmn'=>'#d95151 20%, #ae4d4d 80%',
	'cml'=>'#eab434 30%, #5da832 50%, #1d586f 70%',
	'bge'=>'#d9c597 20%, #997f41 80%',
	'wht'=>'#f2f2f2 20%, #d9d9d9 80%',
	'vns'=>'#c11119 20%, #991f36 80%',
	'azr'=>'#a6e1f2 20%, #6aa9c1 80%',
	'ylw'=>'#ffda11 20%, #d5b509 80%',
	'grn'=>'#83df27 20%, #289b17 80%',
	'gld'=>'#fff011 20%, #e39c71 80%',
	'red'=>'#f00 20%, #c80e0e 80%',
	'orn'=>'#ff8111 20%, #b05528 80%',
	'pnk'=>'#df278f 20%, #9b1755 80%',
	'slv'=>'#e1e1e1 20%, #979797 80%',
	'gra'=>'#b7b7b7 20%, #868686 80%',
	'd_grn'=>'#52861e 20%, #234215 80%',
	'prp'=>'#6c1ddf 20%, #350c8e 80%',
	'blk'=>'#444 20%, #2d2d2d 80%',
	'wat'=>'#5d6064 20%, #1e3d64 80%',
	'snd'=>'#c2b280 20%, #867b57 80%'
];

$cnt_sponsor_ar = [
	['name'=>'DECOR STONE','img'=>'decorstone.png', 			'url'=>'https://decorstone.md/'],
	['name'=>'Gradina Marioarei', 	'img'=>'gradina_marioarei.png', 	'url'=>'https://gradinamarioarei.md'],
	['name'=>'Alex Garden','img'=>'alex_garden.png', 			'url'=>'https://alexgarden.md'],
	['name'=>'ALEXVET', 	'img'=>'alexvet.jpg', 				'url'=>'https://servicii-veterinare.business.site/'],
	['name'=>'NovaPorta', 	'img'=>'novaporto.png', 			'url'=>'https://novaporta.md'],
	['name'=>'Inter Cars', 'img'=>'intercars.png', 			'url'=>'https://www.intercars.md/'],
	['name'=>'Buket', 		'img'=>'buket_floral.png', 			'url'=>'https://buket.md'],
	['name'=>'Bistro', 	'img'=>'bistro_md.jpg', 			'url'=>''/*'http://bistro.md'*/],
	['name'=>'CET', 		'img'=>'centru_de_excelenta.png', 	'url'=>'https://cetauto.md'],
	['name'=>'CERBER', 	'img'=>'cerber.png', 				'url'=>'https://cerber.md'],
	['name'=>'ECO Floor', 	'img'=>'eco_floor.png', 			'url'=>'https://ecofloor.md'],
	['name'=>'LIDER', 		'img'=>'logo_esplan_lux_srl.png', 	'url'=>''/*'http://www.lider.md'*/],
	['name'=>'NOUCONST', 	'img'=>'nouconst.png', 				'url'=>'https://nouconst.md'],
	['name'=>'ESTER', 		'img'=>'real_estatelogo.png', 		'url'=>'https://www.ester.md'],
	['name'=>'SSM Extern Randis', 'img'=>'smm_randis.png', 	'url'=>'https://randis.md/servicii/'],
	['name'=>'StarNet', 	'img'=>'starnet.png', 				'url'=>'https://starnet.md'],
	['name'=>'UisPac', 	'img'=>'uispac.png', 				'url'=>'http://www.uispac.md'],
	['name'=>'WeTrade', 	'img'=>'we_trade_agro.png', 		'url'=>'http://www.wetrade.moldagro.md'],
	['name'=>'Zubcu', 		'img'=>'zubcu_energy.png', 			'url'=>'https://zubcu.md'],
	['name'=>'gEnergy', 	'img'=>'genergy.png', 				'url'=>'https://genergy.md'],
	['name'=>'ZAW energy', 'img'=>'zaw.png', 					'url'=>''/*'https://zawenergy.md'*/]
];

$seo_url_arr = [
	'base' => [
		'home' => ['sm'=>'d9'],
		'cars' => ['sm'=>'d8', 'sub'=>1],
		'services' => ['sm'=>'m5', 'sub'=>1],
		'tyres' => ['sm'=>'d8', 'sub'=>1],
		'rent' => ['sm'=>'d8'],
		'contacts' => ['sm'=>'m5'],
		
		'about' => ['sm'=>'m5'],
		'privacy' => ['sm'=>'m5'],
		'credit' => ['sm'=>'m5'],
		'terms' => ['sm'=>'m5'],
		'warranty' => ['sm'=>'m5']
	],
	'sub' => [
		'services' => [
			'transportation' => ['sm'=>'m6'],
			'sale' => ['sm'=>'m6'],
			'estimation' =>['sm'=>'m6'],
			'tradein' => ['sm'=>'m6'],
			'credit' => ['sm'=>'m6'],
			'testdrive' => ['sm'=>'m6'],
			'order' => ['sm'=>'m6'],
			'payment' => ['sm'=>'m6'],
			'terms' => ['sm'=>'m6'],
			
			'calc_credit' => ['sm'=>'m7'],
			'calc_insurance' => ['sm'=>'m7'],
			'calc_customs' => ['sm'=>'m7']
		],
		'cars' => [
			'it_act' => ['sm'=>'d10'],
			'landing' => ['sm'=>'d9'],
			'it_arh' => ['sm'=>'n0']
		],
		'tyres' => [
			'it_act' => ['sm'=>'d10'],
			'it_arh' => ['sm'=>'n0']
		]
	]
];

$o2n = [
	's'=>['bodytype'=>'bt','year'=>'yr','engine'=>'vol','fuel'=>'fl','tm'=>'tra','transmission'=>'tra','hp'=>'hp','color'=>'clr','mileage'=>'mlg','price'=>'prc','seats'=>'sts','wheel_drive'=>'wd'],
	'v'=>['sedan'=>'sdn','suv'=>'suv','hatchback'=>'hbk','universal'=>'unv','coupe'=>'cup','cabriolet'=>'cbr','combi'=>'cmb','roadster'=>'rod','crossover'=>'crv','minivan'=>'mnv','pickup'=>'pkp','van'=>'van','furgon'=>'van','minibus'=>'mbs','fridge'=>'frg','carriage'=>'crr','gasoline'=>'gsl','gasoline-methane'=>'gmn','gasoline-propane'=>'gpn','hybrid'=>'hbd','diesel'=>'dsl','tiptronic'=>'tpt','automatic'=>'atm','manual'=>'mnl','4x4'=>'44','rear'=>'re','front'=>'fr','light-green'=>'l_grn', 'blue'=>'blu', 'brown'=>'brn', 'crimson'=>'cmn', 'chameleon'=>'cml', 'beige'=>'bge', 'white'=>'wht', 'vinous'=>'vns', 'azure'=>'azr','yellow'=>'ylw', 'green'=>'grn', 'gold'=>'gld', 'red'=>'red', 'orange'=>'orn', 'pink'=>'pnk','silver'=>'slv', 'gray'=>'gra', 'dark-green'=>'d_grn', 'purple'=>'prp', 'black'=>'blk', 'wet-asphalt'=>'wap', 'sand'=>'snd']
];

?>