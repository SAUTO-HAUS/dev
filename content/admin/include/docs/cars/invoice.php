<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'IN';

$rtrn = '
<style>
	.date {text-align:center; padding:15mm 0 0; float:left;}
	.ttl {text-align:center; padding:15mm 0 0; clear:both; font-size:1.4rem; font-weight:bold;}
	tr > td.id {width:10%;} tr > td.nm {width:40%;} tr > td.qty {width:15%;} tr > td.unit {width:15%;} tr > td.prc {width:20%;}
	.cont {margin:0;}
	.txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	.parties {margin-top:1rem; display:flex; justify-content:space-between;}
	.party {width:48%;}
	.party h4 {margin:0.5rem 0; font-weight:bold;}
	.invoice-table {margin:2rem 0;}
	.total-row {font-weight:bold; background-color:#f0f0f0;}
</style>

<div id="p_cont" class="base">';
$br = isset($_POST['br']) ? (is_array($_POST['br']) ? ($_POST['br'][0] ?? '') : $_POST['br']) : '';
$mo = isset($_POST['mo']) ? (is_array($_POST['mo']) ? ($_POST['mo'][0] ?? '') : $_POST['mo']) : '';
$vin = isset($_POST['vin']) ? (is_array($_POST['vin']) ? ($_POST['vin'][0] ?? '') : $_POST['vin']) : '';
$currency = isset($_POST['cur']) ? $_POST['cur'] : 'MDL';
$price = isset($_POST['prc']) && is_numeric($_POST['prc']) ? floatval($_POST['prc']) : 0;

	$rtrn .= '
	<div class="pg bg">
		<div class="date">'.$zdate.'</div>
		<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
		<div class="ln"></div>
		<span class="cont">'.$zcont.'</span>
		<div class="cont"></div>
		<div class="ttl">Invoice nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>
		
		<div class="parties">
			<div class="party">
				<h4>Vânzător / Продавец:</h4>
				<div><strong>'.(isset($_POST['seller_name']) ? htmlspecialchars($_POST['seller_name']) : '').'</strong></div>
				'.(isset($_POST['seller_vat']) && $_POST['seller_vat'] ? '<div>VAT/IDNO: '.htmlspecialchars($_POST['seller_vat']).'</div>' : '').'
				'.(isset($_POST['seller_account']) && $_POST['seller_account'] ? '<div>Account: '.htmlspecialchars($_POST['seller_account']).'</div>' : '').'
				'.(isset($_POST['seller_address']) && $_POST['seller_address'] ? '<div>'.nl2br(htmlspecialchars($_POST['seller_address'])).'</div>' : '').'
			</div>
			<div class="party">
				<h4>Cumpărător / Покупатель:</h4>
				<div><strong>'.(isset($_POST['buyer_name']) ? htmlspecialchars($_POST['buyer_name']) : '').'</strong></div>
				'.(isset($_POST['buyer_vat']) && $_POST['buyer_vat'] ? '<div>VAT/IDNO: '.htmlspecialchars($_POST['buyer_vat']).'</div>' : '').'
				'.(isset($_POST['buyer_account']) && $_POST['buyer_account'] ? '<div>Account: '.htmlspecialchars($_POST['buyer_account']).'</div>' : '').'
				'.(isset($_POST['buyer_address']) && $_POST['buyer_address'] ? '<div>'.nl2br(htmlspecialchars($_POST['buyer_address'])).'</div>' : '').'
			</div>
		</div>
		
		<div class="invoice-table">
			<table>
				<tr>
					<td class="id"><strong>№</strong></td>
					<td class="nm txt_up"><strong>Denumirea produsului/serviciului<br/>Название товара/услуги</strong></td>
					<td class="qty"><strong>Cantitatea<br/>Количество</strong></td>
					<td class="unit"><strong>Preț pentru o unitate<br/>Цена за единицу</strong></td>
					<td class="prc"><strong>Preț total<br/>Общая стоимость<br/>'.$currency.'</strong></td>
				</tr>
				<tr>
					<td class="id">1</td>
					<td class="nm">
						'.(isset($_POST['description']) && $_POST['description'] ? htmlspecialchars($_POST['description']) : 'Automobil').'
						<br/>'.($br !== '' ? ucwords(strtolower(str_replace('_', ' ', $br))) : '').' '.($mo !== '' ? ucwords(str_replace('_', ' ', $mo)) : '').'
						'.($vin !== '' ? '<br/>VIN: '.$vin : '').'
					</td>
					<td class="qty">1</td>
					<td class="unit">'.number_format($price, 2, '.', ',').'</td>
					<td class="prc">'.number_format($price, 2, '.', ',').'</td>
				</tr>
				<tr class="total-row">
					<td class="id"></td>
					<td class="nm txt_up"><strong>TOTAL</strong></td>
					<td class="qty"></td>
					<td class="unit"></td>
					<td class="prc"><strong>'.number_format($price, 2, '.', ',').' '.$currency.'</strong></td>
				</tr>
			</table>
		</div>
		
		'.(isset($_POST['dealer']) && $_POST['dealer'] ? '<div style="margin-top:1rem;"><strong>Dealer:</strong> '.htmlspecialchars($_POST['dealer']).'</div>' : '').'
		
		<div class="ws"></div>
		<div class="sign">
			<div class="s1">Semnatura<div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
			<div class="s2">L. Ş.<div class="ln"></div></div>
		</div>
	</div>';

$rtrn .= '
</div>';

echo $rtrn;
?>
