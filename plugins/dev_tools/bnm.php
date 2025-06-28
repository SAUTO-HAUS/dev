<?php defined( '_DOIT' ) or die( 'Restricted access' ); 

$valutes = ['EUR','USD','RUB','RON','UAH'];

$pdo = $db->prepare('SELECT * FROM '.$prefx.'_exchange WHERE `date`=:date');
$pdo->execute([ 'date' => date("d.m.Y") ]);
$num_rows = $pdo->fetchColumn();

if( $num_rows==0 ){
	$cursXML = new SimpleXMLElement('https://bnm.md/ru/official_exchange_rates?get_xml=1&date='.date("d.m.Y"), NULL, TRUE);
	
	$i=0;
	foreach($valutes as $k){
		$pdo = $db->prepare('UPDATE '.$prefx.'_exchange SET `value`=:value, `date`=:date WHERE `name`=:name');
		$pdo->execute([
			'value' => $cursXML->Valute[$i]->Value,
			'name' => $k,
			'date' => date("d.m.Y")
		]);
		$i++;
	}
	
	$pdo = $db->prepare('INSERT INTO '.$prefx.'_bnm (`date`, `EUR`, `USD`, `RUB`, `RON`, `UAH`) VALUES (:date, :eur, :usd, :rub, :ron, :uah)');
	$pdo->execute([
		'date' => date('Y-m-d'),
		'eur' => $cursXML->Valute[0]->Value,
		'usd' => $cursXML->Valute[1]->Value,
		'rub' => $cursXML->Valute[2]->Value,
		'ron' => $cursXML->Valute[3]->Value,
		'uah' => $cursXML->Valute[4]->Value 
	]);
}

?>