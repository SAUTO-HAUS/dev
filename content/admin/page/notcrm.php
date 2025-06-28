<?php defined( '_DOIT' ) or die( 'Restricted access' );

$rtrn = ''/*.password_hash('', PASSWORD_DEFAULT, ['cost' => 12])*/;

//$admin_menu_dev1[$user_type]['docs']

if ( isset($t_mp[4]) ){
	if ($t_mp[4] == 'app')
	$rtrn .= '
		<style>
			#notcrm > .tbl {display:flex;}
			#notcrm > .tbl > .col {width:19%; margin:0 .2rem;}
			#notcrm > .tbl > .col > .hdr {width:100%; text-align:center;}
			#notcrm > .tbl > .col > .spc {height:1rem; margin:1rem 0;}
			#notcrm > .tbl > .col > .spc > .btn {margin:0 auto; background-color:var(--clr); color:#fff; text-align:center; cursor:pointer;}
			#notcrm > .tbl > .col > .bx {width:100%; min-height:50vh; border:1px solid #000;}
		</style>
		
		<div id="notcrm">
			<div class="tbl">
				<div class="col">
					<div class="hdr">CREAZA COMANDA</div>
					<div class="spc">
						<div class="btn">Nou</div>
					</div>
					<div class="bx"></div>
				</div>
				<div class="col">
					<div class="hdr">CREAZA OFERTA</div>
					<div class="spc"></div>
					<div class="bx"></div>
				</div>
				<div class="col">
					<div class="hdr">PROPUNERE OFERTA</div>
					<div class="spc"></div>
					<div class="bx"></div>
				</div>
				<div class="col">
					<div class="hdr">CONTRACT</div>
					<div class="spc"></div>
					<div class="bx"></div>
				</div>
				<div class="col">
					<div class="hdr">FINISH</div>
					<div class="spc"></div>
					<div class="bx"></div>
				</div>
			</div>
		</div>';
}

echo $rtrn;
?>