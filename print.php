<?php 
if ( $_POST ){
	if ($_POST['src']=='adm'){
		define('_DOIT', 1); define('_DEFAULT', $_SERVER['DOCUMENT_ROOT'].'/content/default');
		require_once (_DEFAULT.'/language.php');
		$z_site = 'https://www.sauto.md';
		$pCur = mb_strtoupper($_POST['cur'], "utf-8");
		$zCur = isset($_POST['cur_'.$pCur])?$lng['l']['cur'][ $pCur ]:$pCur;
		echo '
		<!DOCTYPE html>
		<html>
			<head>
				<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" id="mobile_viewport" />
				
				<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
				
				<title>Print '.$_POST['br_nm'].' '.$_POST['mo_nm'].' ['.$_POST['id'].']</title>
				<style>
					@font-face {font-family:"def"; src:url("/media/fonts/def_font.ttf") format("opentype");}
					
					body {position:relative; margin:0 auto; /*padding:20mm 10mm 20mm 30mm;*/ box-sizing:border-box; font-family:"def"; /*border:1px solid #333;*/'.(($_POST['theme']==0)?' filter:grayscale(1);':'').'}
						body.v {width:210mm; height:297mm;}
						body.h {width:297mm; height:200mm;}
					//.c {width:100%; height:100%; display:flex; flex-flow:row wrap; justify-content:center; align-items:center;}
					body .top {width:100%; min-height:15mm;}
					body .top > .adr {float:right; text-align:right;}
					
					body .bx {width:80%; margin:5rem auto 0; font-size:1.7rem; line-height:3rem; text-align:center;}
						body.h .bx {width:90%;  margin:1rem 5% 0;}
					body .bx > .ttl {color:#e2001a; font-size:1rem; text-transform:uppercase; line-height:1rem; text-align:left; border-bottom:3px solid #e2001a;}
					body .bx > .txt {padding:.5rem 2rem 0;}
						body.h .bx > .txt {width:45%; line-height:2.5rem; font-size:1.5rem;}
						body.h .bx > .txt.m {padding: 2rem 1% 0 2%; float:left;}
						body.v .bx > .txt.m {display:none;}
						body.h .bx > .txt.x {padding: 2rem 2% 0 1%; float:right;}
					
					body .bx.main {font-size:4rem; line-height:normal; width:100%;}
						body.h .bx.main {margin:0 auto;}
					body .bx.main > .spec {font-size:1.5rem; display:flex; flex-flow:row wrap; justify-content:space-evenly; margin:0 1rem; background-color:#e2001a; color:#fff;}
						body.h .bx.main > .spec {display:none;}
					body .bx.main > .spec > span {margin:0 1rem;}
					
						body.h .bx.mtds {width:50%; margin:3rem 25% 0; float:left;}
						body.h .bx.mtds > .txt {margin:0 auto; width:100%; padding:0;}
					
					body .bx.spec > .txt > .it {display:flex; flex-flow:row; justify-content:space-between; align-items:center;}
					body .bx.spec > .txt > .it > .ttl {}
					body .bx.spec > .txt > .it > .space {flex-grow:1; border-bottom:1.5px dashed #d7d7d7; height:1.2rem; line-height:1.5rem; font-size:1.2rem; text-align:right; padding:0 1rem 0 0; margin:0 .5rem;}
					body .bx.spec > .txt > .it > .val {}
					
					body .bx.prc {width:100%; text-align:right; position:absolute; bottom:0; right:0;}
						body.h .bx.prc {margin:0}
					body .bx.prc > .chng {display:block; line-height:normal; float:left;}
					body .bx.prc > .chng > .i1 {display:block; text-align:center; line-height:normal; padding-right:0rem; font-size:1rem;}
					body .bx.prc > .chng > .i2 {display:flex; align-items:center; font-size:3rem; margin-top:-.8rem;}
					body .bx.prc > .value {float:right;}
					body .bx.prc > .value > .i1 {font-size:3rem; display:block; float:left; line-height:normal;}
					body .bx.prc > .value > .i2 {color:#e2001a; font-size:7rem; display:block; float:left;}
					body .bx.prc > .value > .i2 > .cur {font-size:3rem;}
					body .bx.prc > .cnvrt {width:100%; font-size:1.5rem; color:#555; line-height:normal; margin-top:.5rem; float:right;}
					body .bx.prc > .cnvrt > .i2 {display:block; font-size:.8rem; margin-top:-.25rem;}
				</style>
				<script>
					$(document).ready(function(){
						function zCnt(){
							var zDef = 4; var zMax = 22; var zCnt = $("body > .main > .nm").html().length; var zVal = zMax / zCnt;
							if (zVal>1){zVal=1;}
							$("body > .main > .nm").css({"font-size":zDef*zVal+"rem"})
							
						}
						$("body > .main > .nm").on("DOMSubtreeModified", function(){ zCnt(); })
						zCnt();
						
						/*
						function print_popup(){window.print(); window.top.close(); return false;}
						print_popup();
						*/
					})
				</script>
			</head>
			<body class="'.$_POST['drct'].'">
				<div class="top">';
					if ($_POST['loc']==1){echo '
						<img class="logo" src="'.$z_site.'/media/images/site/v2/logo_b.svg" width="200" />
						<div class="adr">SAUTO SRL<br/>Chisinau str.Calea Mosilor 11, MD-2084</div>';
					}
				echo '
				</div>
				
				<div class="bx main">
					<span class="nm">'.$_POST['br_nm'].' '.$_POST['mo_nm'].'</span>
					<div class="spec">';
						foreach (array('vol', 'wd', 'hp', 'yr') as $v){//$_POST['wd']!='4x4'
							$zTr = isset($lng['l']['car'][$v][$_POST[$v]])?$lng['l']['car'][$v][$_POST[$v]]:$_POST[$v];
							$zX = ($v=='vol')?(number_format($zTr/1000, 1).' L'):(($v=='wd')?(($_POST[$v]!='44')?'4x2 ('.$zTr.')':'4x4'):(($v=='hp')?$zTr.' c.p.':$zTr));
							echo '<span>'.$zX.'</span>';
						}
					echo '
					</div>
				</div>
				
				<div class="bx spec">
					<div class="ttl">Date tehnice despre autoturism</div>
					<div class="txt m">';
						foreach (array('vol', 'wd', 'hp', 'yr') as $v){//$_POST['wd']!='4x4'
							if ( $_POST[$v]==''||$_POST[$v]=='0' ){continue;}
							$zTr = isset($lng['l']['car'][$v][$_POST[$v]])?$lng['l']['car'][$v][$_POST[$v]]:$_POST[$v];
							$zX = $v=='vol'?(($zTr/1000).'L'):($v=='wd'?($_POST[$v]!='4x4'?'4x2 ('.$zTr.')':$_POST[$v]):($v=='hp'?$_POST[$v].' c.p.':$zTr));
							echo '
							<div class="it">
								<span class="ttl">'.(isset($lng['l']['car']['spec'][$v])?$lng['l']['car']['spec'][$v]:$v).'</span>
								<span class="space"></span>
								<span class="val">'.$zX.'</span>
							</div>';
						}
					echo '
					</div>
					<div class="txt x">';
						foreach (array('mlg', 'cons', 'fl', 'tra', 'sts', 'tnk') as $v){
							if ( $_POST[$v]==''||$_POST[$v]=='0' ){continue;}
							$zTr = isset($lng['l']['car'][$v][$_POST[$v]])?$lng['l']['car'][$v][$_POST[$v]]:$_POST[$v];
							$zX = ($v=='mlg')?number_format($zTr, 0).' '.$_POST['unit']:($v=='cons'?$zTr.' L /100 km':($v=='tnk'?$zTr.' L':$zTr));
							echo '
							<div class="it">
								<span class="ttl">'.(isset($lng['l']['car']['spec'][$v])?$lng['l']['car']['spec'][$v]:$v).'</span>
								<span class="space"></span>
								<span class="val">'.$zX.'</span>
							</div>';
						}
					echo '
					</div>
				</div>
				
				<div class="bx mtds">
					<div class="ttl">Modalități de procurare</div>
					<div class="txt">CASH / CREDIT / TRANSFER / SCHIMB</div>
				</div>
				
				<div class="bx prc">';
					if ($_POST['exchange']>$_POST['prc'] && $_POST['prc']<999999){echo '
						<div class="chng">
							<span class="i1">SCHIMB negociabil</span>
							<span class="i2">'.(number_format($_POST['exchange'], 0)).' '.$zCur.'</span>
						</div>';
					}
					echo '
					<div class="value">
						<span class="i1">PREȚ:</span> <span class="i2">'.(number_format($_POST['prc'], 0)).'<span class="cur">'.$zCur.'</span></span>
					</div>';
					if ( isset($_POST['cur_'.$pCur]) ){echo '
						<div class="cnvrt">
							<span class="i1">VALOAREA IN LEI: ~'.(number_format($_POST['prc'] * $_POST['cur_'.$pCur], 0)).' MDL</span>
							<span class="i2">Conform BNM la ziua achitarii</span>
						</div>';
					}
				echo '
				</div>
			</body>
		</html>';
	}else{die('Restricted access');}
}else{die('Restricted access');}
?>