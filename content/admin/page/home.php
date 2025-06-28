<?php defined( '_DOIT' ) or die( 'Restricted access' );

$rtrn = '
<script>
$(document).ready(function(){
	function sess_dur(){
		var val = unixTime() - localStorage.getItem("z_adm_sess_start")*1;
		var h = Math.floor( val / (60*60) ), m = Math.floor( (val - (h*60*60)) / 60 ), s = Math.floor( (val - (h*60*60) - (m*60) ) );
		$("#sess_dur").html( (h-10<0?"0":"")+h+":"+(m-10<0?"0":"")+m+":"+(s-10<0?"0":"")+s );
		setTimeout(function(){sess_dur();}, 1000)
	} sess_dur();
	
	function timeNow(){
		var tNow = new Date(); 
		var datetime = "" 
			+ ( tNow.getDate()-10<0?"0":"" ) + tNow.getDate() + "."
			+ ( tNow.getMonth()-10<0?"0":"" ) + (tNow.getMonth()+1)  + "."
			+ tNow.getFullYear() + " ( "
			+ ( tNow.getHours()-10<0?"0":"" ) + tNow.getHours() + ":"
			+ ( tNow.getMinutes()-10<0?"0":"" ) + tNow.getMinutes() + ":"
			+ ( tNow.getSeconds()-10<0?"0":"" ) + tNow.getSeconds() + " )";
		$("#time").html(datetime);
		setTimeout(function(){timeNow();}, 1000)
	} timeNow();
})
</script>

<div class="base_info">
	<span>User: <span id="user_name" class="v">'.$user_name.'</span></span><br/><br/>
	
	<span>Time: <span id="time" class="v">'.date('d.m.Y ( H:i:s )').'</span></span><br/><br/>
	
	<span>Current entry: <span class="v">'.date('d.m.Y ( H:i:s )', $r['this_entrance']).'</span></span><br/>
	<span>Current IP: <span class="v">'.$r['last_ip'].'</span></span><br/><br/>
	
	<span>Previous entry: <span class="v">'.date('d.m.Y ( H:i:s )', $r['last_entrance']).'</span></span><br/>
	<span>Previous IP: <span class="v">'.myIp().'</span></span><br/><br/>
	
	<span>Session duration: <span id="sess_dur" class="v">00:00:00</span></span><br/><br/>
	'.(file_exists('sitemap.xml')?'<span>Sitemap: <span class="v">'.date("d.m.Y ( H:i:s )", filemtime('sitemap.xml')).'</span></span><br/><br/>':'');
	
	$d = date('d'); $dt1 = strtotime(date($d.' F Y 00:00:00')); $dt2 = strtotime(date(($d+1).' F Y 00:00:00'));
	$pdo = $db->prepare('SELECT COUNT(*) FROM '.$prefx.'_mail WHERE `seen`=0 AND (`date` BETWEEN :v1 AND :v2)'); $pdo->execute(['v1'=>$dt1, 'v2'=>$dt2]);
	$r_qu = $pdo->fetchColumn();
	$rtrn .= '
	<span>Today\'s unread mails: <span class="v">'.$r_qu.'</span></span><br/><br/>
	
</div>';


echo $rtrn;

?>