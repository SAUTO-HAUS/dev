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
$seller_name = isset($_POST['seller_name']) ? htmlspecialchars($_POST['seller_name']) : '';
$seller_address = isset($_POST['seller_address']) ? htmlspecialchars($_POST['seller_address']) : '';
$seller_country = isset($_POST['seller_country']) ? htmlspecialchars($_POST['seller_country']) : '';
$seller_account = isset($_POST['seller_account']) ? htmlspecialchars($_POST['seller_account']) : '';
$seller_swift = isset($_POST['seller_swift']) ? htmlspecialchars($_POST['seller_swift']) : '';
$buyer_name = isset($_POST['buyer_name']) ? htmlspecialchars($_POST['buyer_name']) : '';
$buyer_address = isset($_POST['buyer_address']) ? htmlspecialchars($_POST['buyer_address']) : '';
$buyer_country = isset($_POST['buyer_country']) ? htmlspecialchars($_POST['buyer_country']) : '';
$buyer_account = isset($_POST['buyer_account']) ? htmlspecialchars($_POST['buyer_account']) : '';
$buyer_swift = isset($_POST['buyer_swift']) ? htmlspecialchars($_POST['buyer_swift']) : '';
$description = isset($_POST['description']) && $_POST['description'] ? htmlspecialchars($_POST['description']) : ($br && $mo ? strtoupper($br.' '.$mo) : '');

$rtrn .= '
<div class="pg bg" style="font-size: 16px; line-height: 1.3; padding: 15px;">
	<div style="text-align: left; font-size: 36px; font-weight: bold; margin-bottom: 15px;">INVOICE</div>
	
	<div style="display: flex; margin-bottom: 15px;">
		<div style="width: 50%; padding-right: 15px;">
		<div style="font-size: 14px; color: #666; margin-bottom: 8px;">Vinzator:</div>
			<div style="font-weight: bold; font-size: 22px; margin-bottom: 3px;">Verkäufer:</div>
			
			<div style="line-height: 1.4;">
				<div>'.$seller_name.'</div>
				<div>'.str_replace('<br>', '<br>', $seller_address).'</div>
				'.($seller_country ? '<div><strong>'.$seller_country.'</strong></div>' : '').'
				<div>'.str_replace('<br>', '<br>', $seller_account).'</div>
				'.($seller_swift ? '<div>'.$seller_swift.'</div>' : '').'
			</div>
		</div>
		<div style="width: 50%; padding-left: 15px;">
		<div style="font-size: 14px; color: #666; margin-bottom: 8px;">Cumparator:</div>
			<div style="font-weight: bold; font-size: 22px; margin-bottom: 3px;">Käufer:</div>
			
			<div style="line-height: 1.4;">
				<div>'.$buyer_name.'</div>
				<div>'.str_replace('<br>', '<br>', $buyer_address).'</div>
				'.($buyer_country ? '<div><strong>'.$buyer_country.'</strong></div>' : '').'
				<div>'.str_replace('<br>', '<br>', $buyer_account).'</div>
				'.($buyer_swift ? '<div>'.$buyer_swift.'</div>' : '').'
			</div>
		</div>
	</div>
	
	<div style="text-align: center; margin: 20px 0;">
		<div style="font-weight: bold; font-size: 26px; margin: 5px 0;">Rechnung/Kaufvertrag</div>
		<div style="font-weight: bold; font-size: 20px; margin: 5px 0;">Facture / Invoice</div>
	</div>
	
	<table style="width: 100%; margin: 15px 0; border-collapse: collapse; border: 2px solid #000;">
		<tr>
			<td style="width: 33%; padding: 12px; border: 1px solid #000; font-size: 17px; font-weight: bold;">
				Rechnungsnummer<br>
				<span style="font-size: 12px; font-weight: normal;">Număr de factură</span>
			</td>
			<td style="width: 33%; padding: 12px; border: 1px solid #000; font-size: 17px; font-weight: bold;">
				Interne Nummer<br>
				<span style="font-size: 12px; font-weight: normal;">Număr intern</span>
			</td>
			<td style="width: 33%; padding: 12px; border: 1px solid #000; font-size: 17px; font-weight: bold;">
				Kundennummer<br>
				<span style="font-size: 12px; font-weight: normal;">Numărul clientului</span>
			</td>
		</tr>
		<tr>
			<td style="padding: 12px; border: 1px solid #000; font-size: 15px;">
				<strong>Date:</strong> '.date('n/j/Y').'
			</td>
			<td style="padding: 12px; border: 1px solid #000; font-size: 15px;">
				<strong>'.$abr.$cont_y.$cont_q.'/'.$cont_n.'</strong>
			</td>
			<td style="padding: 12px; border: 1px solid #000; font-size: 15px;">
				
			</td>
		</tr>
	</table>
	
	<table style="width: 100%; border-collapse: collapse; margin: 20px 0; border: 2px solid #000;">
		<thead>
			<tr>
				<th style="border: 1px solid #000; padding: 15px; text-align: left; font-size: 17px; font-weight: bold;">#</th>
				<th style="border: 1px solid #000; padding: 15px; text-align: left; font-size: 17px; font-weight: bold;">Beschreibung<br><span style="font-size: 12px; font-weight: normal;">Descriere</span></th>
				<th style="border: 1px solid #000; padding: 15px; text-align: left; font-size: 17px; font-weight: bold;">Anzahl<br><span style="font-size: 12px; font-weight: normal;">Cantitate</span></th>
				<th style="border: 1px solid #000; padding: 15px; text-align: left; font-size: 17px; font-weight: bold;">Gesamtpreis<br><span style="font-size: 12px; font-weight: normal;">Preț total</span></th>
			</tr>
		</thead>
		<tbody>
			'.($description || $price > 0 ? '
			<tr>
				<td style="border: 1px solid #000; padding: 15px; font-size: 17px; font-weight: bold;">1</td>
				<td style="border: 1px solid #000; padding: 15px; font-size: 17px;">'.$description.($vin ? '<br><em>VIN: '.$vin.'</em>' : '').'</td>
				<td style="border: 1px solid #000; padding: 15px; font-size: 17px; text-align: center; font-weight: bold;">1</td>
				<td style="border: 1px solid #000; padding: 15px; font-size: 20px; text-align: right; font-weight: bold;">'.number_format($price, 0).' '.$currency.'</td>
			</tr>' : '').'
		</tbody>
	</table>
	
	<div style="margin: 20px 0; padding: 20px; border: 2px solid #000;">
		<div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 20px;"><span><strong>Summe der Nettobetrage</strong></span><span></span></div>
		<div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 12px;"><span><strong>Suma NETO</strong></span><span></span></div>
		<div style="display: flex; justify-content: space-between; margin: 5px 0; font-size: 20px;"><span>*zzgl. 0% Umsatzsteuer</span><span></span></div>
		<div style="display: flex; justify-content: space-between; margin: 5px 0; font-size: 12px;"><span>TVA</span><span></span></div>
		<hr style="border: none; height: 2px; background: #000; margin: 15px 0;">
		<div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 20px;"><span><strong>Gesamtbetrag</strong></span><span></span></div>
		<div style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 20px;"><span><strong>Total</strong></span><span><strong>'.number_format($price, 0).' '.$currency.'</strong></span></div>
	</div>
	
	<div style="margin: 25px 0; padding: 18px; border: 1px solid #000; font-size: 15px; line-height: 1.5;">
		<div style="margin: 8px 0;"><strong>*Exportgeschäft . Steuerfreie Ausfurlieferung Paragraf 4 Nr. 1a Ustg</strong></div>
		<div style="margin: 8px 0;"><em>Clauza de livrare fara taxa</em></div>
		<div style="margin: 12px 0;"><strong>Zahlunsart:</strong> Bar/TRANSFER</div>
		<div style="margin: 8px 0;"><em>tip de plata: TRANSFER</em></div>
		<div style="margin: 12px 0;">Das Leistungsdatum entspricht dem Rechnungsdatum.</div>
		<div style="margin: 8px 0;"><em>Data prestației este data facturii.</em></div>
	</div>
	
	<div style="margin-top: 40px; padding: 20px; border-top: 2px solid #000;">
		<div style="display: flex; justify-content: space-between; align-items: flex-start;">
			<div style="font-size: 16px; flex: 1;">
				'.str_replace('<br>', '<br>', $seller_address).'
			</div>
			<div style="font-size: 18px; text-align: right;">
				<strong>'.$seller_name.'</strong><br>
				'.str_replace('<br>', '<br>', $seller_account).'
			</div>
		</div>
	</div>
</div>';

$rtrn .= '
</div>';

echo $rtrn;
?>
