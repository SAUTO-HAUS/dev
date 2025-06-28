<?php defined( '_DOIT' ) or die( 'Restricted access' );

$pdo = $db->prepare('INSERT INTO '.$prefx.'_mail (`folder`, `date`, `name`, `phone`, `email`, `message`, `page`, `ip`) 
	VALUES (:folder, :date, :name, :phone, :email, :message, :page, :ip)');

$zPg = isset($_POST['page'])?$_POST['page']:'x';

$zNm  = !empty($_POST['name'])  ? $_POST['name'] : '-';
$zPhn = !empty($_POST['phone']) ? $_POST['phone'] : '-';
$zEml = !empty($_POST['email']) ? $_POST['email'] : '-';
$zMsg   = !empty($_POST['msg'])   ? $_POST['msg'] : '';


$pdo->execute(array(
	'folder'=>'message',
	'date'=>time(),
	'name'=>$zNm,
	'phone'=>$zPhn,
	'email'=>$zEml,
	'message'=>$zMsg,
	'page'=>$zPg,
	'ip'=>$_SERVER['REMOTE_ADDR']
));

$content = '';

//$last_id = $db->lastInsertId();
//$content = $last_id;	

try {
	//Recipients
	$mail->CharSet = 'UTF-8';
	$mail->setFrom('mesaj@sauto.md', 'Sauto.md');
	$mail->addAddress('logistica@sauto.md');
	$mail->addAddress('victorpro777@gmail.com');
	
	//Attachments
	//$mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
	//$mail->addAttachment('media/images/site/fcd.jpg', 'FCD image');    //Optional name
	
	//$inf_ar = json_decode($_POST['ar'], true);
	//$data = urlencode( json_encode($_POST['data']));
	
	//$pInfo = '<b>Personal Info</b><br/><br/>';
	//foreach($inf_ar as $inf_k => $inf_v){ $pInfo .= $inf_k.': '.(!empty($inf_v)?$inf_v:'-').'<br/>'; }
	//$pInfo .= '<br/><b>ip: '.$_SERVER['REMOTE_ADDR'].'<br/>time: '.(date('d.m.Y (H:i:s)', time())).'</b><br/>';
	//$pInfo .= $_POST['msg'].'<br/>';
	//$pInfo .= '<br/><br/><a href="'.$site_url.'/print.php?prt=zkz&qr='.$data.'" target="_blank" style="width:100%; padding:16px; background-color:#333; color:#fff; display:block; text-align:center; text-decoration:none; border-radius:5px;">Print</a>';		
	
	//Content
	$mail->isHTML(true);                                  //Set email format to HTML
	$mail->Subject = 'SAUTO.md Mesaj '.($zNm=='-'?'':'de la '.$zNm.($zPhn=='-'?'':' ['.$zPhn.']'));
	$mail->Body = 
		'<b>Mesaj de pe pagină:</b> <a href="https://www.sauto.md'.$zPg.'">'.$zPg.'</a><br/>'
		.'<b>Timp:</b> '.(date('d.m.Y (H:i:s)', time())).'<br/>'
		.'<b>ip:</b> '.$_SERVER['REMOTE_ADDR'].'<br/><br/>'
		.'<b>Mesaj:</b><br/>'.(nl2br(addslashes($zMsg))).'';
	$mail->AltBody = 
		'Mesaj de pe pagină: '.$zPg.' \r\n'
		.'Timp: '.(date('d.m.Y (H:i:s)', time())).' \r\n'
		.'ip: '.$_SERVER['REMOTE_ADDR'].' \r\n\r\n'
		.'Mesaj: \r\n'.(nl2br(addslashes($zMsg))).'';

	$mail->send();
	//$rtrn .= $lng['l']['msg']['ok'];
} catch (Exception $e) {
	//$rtrn .= $lng['l']['msg']['fail'].'. '.$lng['w']['err'].": {$mail->ErrorInfo}";
}

unset($zPg, $zNm, $zPhn, $zEml, $zMsg);
?>