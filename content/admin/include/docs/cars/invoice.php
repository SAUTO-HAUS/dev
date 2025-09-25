<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'IN';

$rtrn = '
<style>
	body { font-family: Arial, sans-serif; font-size: 12px; }
	.invoice-header { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px; }
	.parties-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
	.party { width: 48%; }
	.party-title { font-weight: bold; margin-bottom: 10px; }
	.invoice-titles { text-align: center; margin: 30px 0; }
	.invoice-titles div { margin: 5px 0; font-weight: bold; }
	.invoice-meta { display: flex; justify-content: space-between; margin: 20px 0; }
	.invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
	.invoice-table th, .invoice-table td { border: 1px solid #000; padding: 8px; text-align: left; }
	.invoice-table th { background-color: #f0f0f0; font-weight: bold; }
	.totals-section { margin: 20px 0; }
	.totals-row { display: flex; justify-content: space-between; margin: 5px 0; }
	.legal-clauses { margin: 30px 0; }
	.legal-clauses div { margin: 10px 0; }
	.payment-terms { margin: 20px 0; }
	.signature-section { margin-top: 40px; }
</style>

<div id="p_cont" class="base">';

// Get form data
$br = isset($_POST['br']) ? (is_array($_POST['br']) ? ($_POST['br'][0] ?? '') : $_POST['br']) : '';
$mo = isset($_POST['mo']) ? (is_array($_POST['mo']) ? ($_POST['mo'][0] ?? '') : $_POST['mo']) : '';
$vin = isset($_POST['vin']) ? (is_array($_POST['vin']) ? ($_POST['vin'][0] ?? '') : $_POST['vin']) : '';
$currency = isset($_POST['cur']) ? $_POST['cur'] : 'EUR'; // Default to EUR
$price = isset($_POST['prc']) && is_numeric($_POST['prc']) ? floatval($_POST['prc']) : 0;
$seller_name = isset($_POST['seller_name']) ? htmlspecialchars($_POST['seller_name']) : 'Michiel Freek Koopman';
$seller_address = isset($_POST['seller_address']) ? htmlspecialchars($_POST['seller_address']) : 'Pimpernelweg 32 Zwolle<br>8042 MP<br>NETHERLANDS';
$seller_account = isset($_POST['seller_account']) ? htmlspecialchars($_POST['seller_account']) : 'NL40INGB0001942307<br>INGBNL2A';
$buyer_name = isset($_POST['buyer_name']) ? htmlspecialchars($_POST['buyer_name']) : 'SAUTO SRL';
$buyer_address = isset($_POST['buyer_address']) ? htmlspecialchars($_POST['buyer_address']) : 'Chisinau, Cricova<br>str Chisnaului 84 ap 39<br>Republia Moldova';
$description = isset($_POST['description']) && $_POST['description'] ? htmlspecialchars($_POST['description']) : ($br && $mo ? $br.' '.$mo : 'VW PASSAT GTE');

$rtrn .= '
<div class="pg bg">
	<div style="text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 20px;">INVOICE</div>
	
	<table style="width: 100%; margin-bottom: 30px;">
		<tr>
			<td style="width: 50%; vertical-align: top;">
				<div style="font-weight: bold;">Vinzator</div>
				<div>'.$seller_name.'</div>
				<div>'.str_replace('<br>', '<br>', $seller_address).'</div>
				<div>'.str_replace('<br>', '<br>', $seller_account).'</div>
			</td>
			<td style="width: 50%; vertical-align: top;">
				<div style="font-weight: bold;">Cumparator</div>
				<div>Kaufer:</div>
				<div>'.$buyer_name.'</div>
				<div>'.str_replace('<br>', '<br>', $buyer_address).'</div>
			</td>
		</tr>
	</table>
	
	<div style="text-align: center; margin: 30px 0;">
		<div style="font-weight: bold; margin: 5px 0;">Rechnung/Kaufvertrag</div>
		<div style="font-weight: bold; margin: 5px 0;">Facture / Invoice</div>
	</div>
	
	<table style="width: 100%; margin: 20px 0;">
		<tr>
			<td style="width: 33%;">
				<strong>număr de factură</strong><br>
				Date: '.date('n/j/Y').'
			</td>
			<td style="width: 33%;">
				<strong>număr intern</strong><br>
				Rechnunsgnummer: '.$abr.$cont_y.$cont_q.'/'.$cont_n.'
			</td>
			<td style="width: 33%;">
				Kundennummer:
			</td>
		</tr>
	</table>
	
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
		<tr style="border: 1px solid #000;">
			<th style="border: 1px solid #000; padding: 8px; text-align: left;">#</th>
			<th style="border: 1px solid #000; padding: 8px; text-align: left;">Beschreibung</th>
			<th style="border: 1px solid #000; padding: 8px; text-align: left;">Anzahl</th>
			<th style="border: 1px solid #000; padding: 8px; text-align: left;">Gesamtpreis</th>
		</tr>
		<tr>
			<td style="border: 1px solid #000; padding: 8px;">1</td>
			<td style="border: 1px solid #000; padding: 8px;">'.$description.($vin ? ' VIN: '.$vin : '').'</td>
			<td style="border: 1px solid #000; padding: 8px;">1</td>
			<td style="border: 1px solid #000; padding: 8px;">'.number_format($price, 0).'</td>
		</tr>
	</table>
	
	<div style="margin: 20px 0;">
		<div style="margin: 5px 0;"><strong>Summe der Nettobetrage</strong></div>
		<div style="margin: 5px 0;"><strong>Suma NETO &nbsp;&nbsp;&nbsp; '.number_format($price, 0).' '.$currency.'</strong></div>
		<div style="margin: 5px 0;">*zzgl. 0% Umsatzsteuer</div>
		<div style="margin: 5px 0;">TVA</div>
		<div style="margin: 5px 0;"><strong>Gesamtbetrag</strong></div>
		<div style="margin: 5px 0;"><strong>Total</strong></div>
	</div>
	
	<div style="margin: 30px 0;">
		<div style="margin: 10px 0;">*Exportgeschäft . Steuerfreie Ausfurlieferung Paragraf 4 Nr. 1a Ustg</div>
		<div style="margin: 10px 0;">Clauza de livrare fara taxa</div>
		<div style="margin: 10px 0;">Zahlunsart: Bar/TRANSFER</div>
		<div style="margin: 10px 0;">tip de plata: TRANSFER</div>
		<div style="margin: 10px 0;">Das Leistungsdatum entspricht dem Rechnungsdatum.</div>
		<div style="margin: 10px 0;">Data prestației este data facturii.</div>
	</div>
	
	<div style="margin-top: 50px;">
		<div>'.$seller_name.' &nbsp;&nbsp;&nbsp; '.str_replace('<br>', ' ', $seller_account).'</div>
		<div>'.str_replace('<br>', ' ', $seller_address).'</div>
	</div>
</div>';

$rtrn .= '
</div>';

echo $rtrn;
?>
