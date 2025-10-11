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
				<table style="width: 100%; border: none;">
						<tr>
							<td style="border: none; width: 33%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Seria, numărul: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm;">' . (isset($_POST['kyc_doc_series']) ? $_POST['kyc_doc_series'] : '') . '</span></div>
								<div class="kyc-sublabel">(Серия, номер)</div>
							</td>
							<td style="border: none; width: 33%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Oficiul de care a fost eliberat <span style="border-bottom: 1px dotted #000; display: inline-block; width: 50mm; min-height: 5mm;">' . (isset($_POST['kyc_doc_office']) ? $_POST['kyc_doc_office'] : '') . '</span></div>
								<div class="kyc-sublabel">(Орган выдачи документа)</div>
							</td>
							<td style="border: none; width: 34%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Data eliberării: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 50mm; min-height: 5mm;">' . (isset($_POST['kyc_doc_date']) ? $_POST['kyc_doc_date'] : (isset($_POST['u_iban_dt_tk']) ? date('d.m.Y', strtotime($_POST['u_iban_dt_tk'])) : '')) . '</span></div>
								<div class="kyc-sublabel">(Дата выдачи)</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<table style="width: 100%; border: none;">
						<tr>
							<td style="border: none; width: 33%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">IDNP: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm;">' . (isset($_POST['kyc_idnp']) ? $_POST['kyc_idnp'] : (isset($_POST['u_cf_idno']) ? $_POST['u_cf_idno'] : '')) . '</span></div>
								<div class="kyc-sublabel">(Идентификационный номер)</div>
							</td>
							<td style="border: none; width: 33%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Termen de valabilitate: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 50mm; min-height: 5mm;">' . (isset($_POST['kyc_doc_expiry']) ? $_POST['kyc_doc_expiry'] : '') . '</span></div>
								<div class="kyc-sublabel">(Действителен до)</div>
							</td>
							<td style="border: none; width: 34%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Cetățenia: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 45mm; min-height: 5mm;">' . (isset($_POST['kyc_citizenship']) ? $_POST['kyc_citizenship'] : 'Republica Moldova') . '</span></div>
								<div class="kyc-sublabel">(Гражданство)</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<div class="kyc-label">Data și locul nașterii (țara, regiunea, localitatea): <span style="border-bottom: 1px dotted #000; display: inline-block; width: 100mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_birth_info']) ? $_POST['kyc_birth_info'] : (isset($_POST['u_tva_dt']) ? date('d.m.Y', strtotime($_POST['u_tva_dt'])) : '')) . '</span></div>
					</div>
					<div class="kyc-sublabel" style="text-align: left; margin-top: 1mm;">(Дата и место рождения)</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<div class="kyc-label">Adresa de domiciliu: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 120mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_address']) ? $_POST['kyc_address'] : (isset($_POST['u_adr']) ? $_POST['u_adr'] : '')) . '</span></div>
					</div>
					<div class="kyc-sublabel" style="text-align: left; margin-top: 1mm;">(Адрес прописки)</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<span class="kyc-label">Adresa de reședință:</span>
						<span style="margin-left: 5mm; border-bottom: 1px dotted #000; display: inline-block; width: 120mm; min-height: 5mm;">' . (isset($_POST['kyc_residence_addr']) ? $_POST['kyc_residence_addr'] : '') . '</span>
					</div>
					<div class="kyc-sublabel" style="text-align: left; margin-top: 1mm;">(Адрес проживания)</div>
					<div class="kyc-sublabel" style="text-align: left; margin-top: 2mm;">
						(Se completează în cazul în care diferă de adresa de domiciliu / Заполняется, при отличие от адреса прописки)
					</div>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%;">II.</td>
				<td class="kyc-header">Date de contact<br/><span class="kyc-sublabel">(Контактные данные)</span></td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<span class="kyc-label">Tel. mobil: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 40mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_phone']) ? $_POST['kyc_phone'] : (isset($_POST['u_phn']) ? $_POST['u_phn'] : '')) . '</span></span>
						<span class="kyc-label" style="margin-left: 15mm;">E-mail: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_email']) ? $_POST['kyc_email'] : (isset($_POST['u_eml']) ? $_POST['u_eml'] : '')) . '</span></span>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span class="kyc-sublabel">(Мобильный телефон)</span>
						<span class="kyc-sublabel" style="margin-left: 65mm;">(Электронная почта)</span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%;">III.</td>
				<td class="kyc-header">Ocupația<br/><span class="kyc-sublabel">(Занятие)</span></td>
			</tr>
			<tr>
				<td colspan="2">
					<table style="width: 100%; border: none;">
						<tr>
							<td style="border: none; width: 20%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Angajat*</span></div>
								<div class="kyc-sublabel">(Служащий*)</div>
							</td>
							<td style="border: none; width: 20%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Student*</span></div>
								<div class="kyc-sublabel">(Студент*)</div>
							</td>
							<td style="border: none; width: 20%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Antreprenor*</span></div>
								<div class="kyc-sublabel">(Предприниматель*)</div>
							</td>
							<td style="border: none; width: 20%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Șomer</span></div>
								<div class="kyc-sublabel">(Безработный)</div>
							</td>
							<td style="border: none; width: 20%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Pensionar</span></div>
								<div class="kyc-sublabel">(Пенсионер)</div>
							</td>
						</tr>
					</table>
					<div style="text-align: left; margin-top: 3mm;">
						<span>☐ <span class="kyc-label">Alte (indicați)</span></span>
						<span style="margin-left: 5mm; border-bottom: 1px dotted #000; display: inline-block; width: 100mm; min-height: 5mm;"></span>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span class="kyc-sublabel">(Другое) (укажите)</span>
					</div>
					<table style="width: 100%; border: none; margin-top: 5mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Denumirea instituției: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 70mm; min-height: 5mm;">' . (isset($_POST['kyc_institution_name']) ? $_POST['kyc_institution_name'] : '') . '</span></div>
								<div class="kyc-sublabel">(Название организаций)</div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Funcția deținută: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 80mm; min-height: 5mm;">' . (isset($_POST['kyc_position']) ? $_POST['kyc_position'] : '') . '</span></div>
								<div class="kyc-sublabel">(Должность)</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%;">IV.</td>
				<td class="kyc-header">Persoană expusă politic (Funcția publică deținută)<br/><span class="kyc-sublabel">Политически уязвимые лица/(Выполняемая государственная должность)</span></td>
			</tr>
			<tr>		
';

echo $kyc_pages;
?>
