<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'FP';

$rtrn = '
<style>
	.logo {float:left; height:10mm; width:auto; position:absolute;}
	.ttl {text-align:center; padding:15mm 0 0; clear:both; font-size:1.4rem; font-weight:bold;}
	.cont {margin:0;}
	.txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	
	.base {font-family:"def_l"; color:#000; font-size:.9rem;}
	.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
	.head > .ttl {text-align:center; font-size:1.1rem; font-weight:bold; margin-top:5mm;}
	.head > .sub-ttl {text-align:center; font-size:1rem; margin-top:2mm;}
	
	.info-section {margin-top:8mm;}
	.info-row {display:flex; margin:3mm 0; font-size:0.95rem;}
	.info-label {width:50%; font-weight:bold;}
	.info-value {width:50%; border-bottom:1px solid #000; padding-left:2mm;}
	
	.def_fnt {font-family:"def" !important;} 
	
	.pg:not(.x2) p {margin:1mm 0; line-height:4.5mm;}
	
	.sign-section {margin-top:15mm; display:flex; justify-content:space-between;}
	.sign-block {width:45%; text-align:center;}
	.sign-line {border-top:1px solid #000; margin-top:15mm; padding-top:2mm;}
</style>';

$rtrn .= '
<div id="p_cont" class="base">
	<div class="pg bg">
		<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
		<div class="head">
			<div class="nr">Nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>
			<div class="ttl">FOAIE DE PARCURS PENTRU</div>
			<div class="sub-ttl">AUTOCAMIOANE</div>
		</div>
		
		<div class="info-section">
			<div class="info-row">
				<div class="info-label">Data:</div>
				<div class="info-value">'.$zdate.'</div>
			</div>
			<div class="info-row">
				<div class="info-label">Numele și prenumele șoferului:</div>
				<div class="info-value">'.(isset($_POST['sofer']) ? htmlspecialchars($_POST['sofer']) : '').'</div>
			</div>
			<div class="info-row">
				<div class="info-label">Marca autovehiculului, remorcii:</div>
				<div class="info-value">'.(isset($_POST['autovehicul']) ? htmlspecialchars($_POST['autovehicul']) : '').'</div>
			</div>
		</div>
		
		<div class="sign-section">
			<div class="sign-block">
				<div class="sign-line">Semnătura șoferului</div>
			</div>
			<div class="sign-block">
				<div class="sign-line">Semnătura responsabilului</div>
			</div>
		</div>
		
	</div>
</div>';

echo $rtrn;
?>
