<?php defined( '_DOIT' ) or die( 'Restricted access' );

if ($_POST['sub']=='chng_model'){

	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_cars WHERE `brand`=:brand ORDER BY `model` ASC');
	$pdo->execute(array('brand'=>$_POST['req_brand']));
	
	foreach ($pdo as $row){
		$models[] = '<option value="'.$row['model'].'">'.$row['model_name'].'</option>';
	}
}
elseif ($_POST['sub']=='submit'){
	
	$models[] = '';
	
	$z_name  = empty($_POST['name'])  ? '-' : $_POST['name'];
	$z_phone = empty($_POST['phone']) ? '-' : $_POST['phone'];
	
	$pdo = $db->prepare('INSERT INTO '.$prefx.'_mail (`folder`, `date`, `name`, `phone`, `email`, `message`, `page`, `ip`) 
	VALUES (:folder, :date, :name, :phone, :email, :message, :page, :ip)');
	$pdo->execute(array(
		'folder'=>'order',
		'date'=>time(),
		'name'=>$z_name,
		'phone'=>$z_phone,
		'email'=>$_POST['email'],
		'message'=>'
			AUTO: '.$_POST['brand'].' '.$_POST['model'].'
			FUEL: '.$_POST['fuel'].'
			YEAR: '.$_POST['year'].'
			ENGINE: '.$_POST['engine'].'
			EXTRA: '.$_POST['xtra_info'].'
		',
		'page'=>$_POST['page'],
		'ip'=>myIp()
	));
	
}

?>