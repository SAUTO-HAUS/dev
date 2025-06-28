<?php defined( '_DOIT' ) or die( 'Restricted access' ); 

$valutes = ['EUR','USD','RUB','RON','UAH'];

$val = 0;
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_info WHERE `name`=:name');
$pdo->execute([ 'name' => 'bnm' ]);
foreach ($pdo as $r){$val = $r['value'] * 1;}

if ($val > 0 ){
	$zdate = date('d.m.Y', strtotime('-'.$val.' day', strtotime(date('d.m.Y'))));
	
	$cursXML = new SimpleXMLElement('https://bnm.md/ru/official_exchange_rates?get_xml=1&date='.$zdate, NULL, TRUE);
	
	$pdo = $db->prepare('INSERT INTO '.$prefx.'_bnm (`date`, `EUR`, `USD`, `RUB`, `RON`, `UAH`) VALUES (:date, :eur, :usd, :rub, :ron, :uah)');
	$pdo->execute([
		'date' => date('Y-m-d', strtotime($zdate)),
		'eur' => $cursXML->Valute[0]->Value,
		'usd' => $cursXML->Valute[1]->Value,
		'rub' => $cursXML->Valute[2]->Value,
		'ron' => $cursXML->Valute[3]->Value,
		'uah' => $cursXML->Valute[4]->Value 
	]);
	
	$pdo = $db->prepare('UPDATE '.$prefx.'_info SET `value`=:val WHERE `name`=:name');
	$pdo->execute([ 'val'=>$val-1, 'name'=>'bnm' ]);
}
?>