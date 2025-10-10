<?php defined( '_DOIT' ) or die( 'Restricted access' );

// KYC pages with exact table structure from image
$kyc_pages = '
<style>
	.kyc-table { width: 100%; border-collapse: collapse; margin: 5mm 0; }
	.kyc-table td { border: 1px solid #000; padding: 3mm; vertical-align: top; }
	.kyc-header { background-color: #f0f0f0; text-align: center; font-weight: bold; }
	.kyc-label { font-weight: bold; }
	.kyc-sublabel { font-size: 0.8em; color: #666; font-style: italic; }
	.kyc-checkbox { margin-right: 5px; }
</style>
	<!-- KYC PAGE 14 -->
	<div class="pg bg">
		<div class="head">
			<div class="nr">Anexa nr. 1</div>
			<div class="ttl">CHESTIONAR CUNOAȘTE-ȚI CLIENTULUI PERSOANĂ FIZICĂ</div>
		</div>
		
		<table class="kyc-table">
			<tr>
				<td class="kyc-header" style="width: 5%;">I.</td>
				<td class="kyc-header">Date generale<br/><span class="kyc-sublabel">(Общие данные)</span></td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<div class="kyc-label">Numele, Prenumele: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 110mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_client_name']) ? strtoupper($_POST['kyc_client_name']) : (isset($_POST['u_nm']) ? strtoupper($_POST['u_nm']) : '')) . '</span></div>
					</div>
					<div class="kyc-sublabel" style="text-align: left; margin-top: 1mm;">(Фамилия, Имя)</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<table style="width: 100%; border: none;">
						<tr>
							<td style="border: none; width: 25%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Actul de identitate:</div>
								<div class="kyc-sublabel">(Документ удостоверяющий личность)</div>
							</td>
							<td style="border: none; width: 25%; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Buletin de identitate</span></div>
								<div class="kyc-sublabel">(Удостоверение личности)</div>
							</td>
							<td style="border: none; width: 25%; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Permis de ședere</span></div>
								<div class="kyc-sublabel">(Вид на жительство)</div>
							</td>
							<td style="border: none; width: 25%; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Pașaport</span></div>
								<div class="kyc-sublabel">(Паспорт)</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2">		
';

echo $kyc_pages;
?>
