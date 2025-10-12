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
				<td colspan="2">
					<div style="text-align: left; margin-top: 3mm;">
						<span class="kyc-label">Conform Legii nr.158/2008 cu privire la funcția publică și statutul funcționarului public</span>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span class="kyc-sublabel">Согласно закону №158 от 04.07.2008 o государственной должности и статусе государственного служащего</span>
					</div>
					<div style="text-align: left; margin-top: 3mm;">
						<span>☐ <span class="kyc-label">Nu dețin funcție publică</span></span>
					</div>
					<div style="text-align: left; margin-top: 1mm; margin-left: 5mm;">
						<span class="kyc-sublabel">(Не выполняю государственных обязанностей)</span>
					</div>
					<div style="text-align: left; margin-top: 5mm;">
						<span class="kyc-label">În caz că dețineți o funcție publică indicați:</span>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span class="kyc-sublabel">(Если вы политически уязвимое лицо, укажите)</span>
					</div>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 33%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Deputat al Parlamentului RM</span></div>
								<div class="kyc-sublabel">(Депутат Парламента РМ)</div>
							</td>
							<td style="border: none; width: 33%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Judecător</span></div>
								<div class="kyc-sublabel">(Судья)</div>
							</td>
							<td style="border: none; width: 34%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Membru al Guvernului RM</span></div>
								<div class="kyc-sublabel">(Член правительства рм)</div>
							</td>
						</tr>
					</table>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 50%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Membru al organelor de conducere ale partidelor politice</span></div>
								<div class="kyc-sublabel">(Член руководящих органов политических партий)</div>
							</td>
							<td style="border: none; width: 50%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Primar</span></div>
								<div class="kyc-sublabel">(Мэр)</div>
							</td>
						</tr>
					</table>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 50%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Consilier al autorităților publice locale</span></div>
								<div class="kyc-sublabel">(Советник местных органов власти)</div>
							</td>
							<td style="border: none; width: 50%; text-align: center; vertical-align: top; padding: 2mm;">
								<div>☐ <span class="kyc-label">Alta (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm;"></span></span></div>
								<div class="kyc-sublabel">(Другие)(укажите)</div>
							</td>
						</tr>
					</table>
					<div style="text-align: left; margin-top: 5mm;">
						<span class="kyc-label">Afilierea (Conducător, asociat, acționar cu 25%)</span>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span class="kyc-sublabel">Аффилированность (Руководитель, участник, акционер с 25%)</span>
					</div>
					<div style="text-align: left; margin-top: 3mm;">
						<span class="kyc-label">Denumirea companiei:</span>
						<span style="margin-left: 5mm; border-bottom: 1px dotted #000; display: inline-block; width: 100mm; min-height: 5mm;">' . (isset($_POST['kyc_affiliated_company']) ? $_POST['kyc_affiliated_company'] : '') . '</span>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span class="kyc-sublabel">(Название компании)</span>
					</div>
					<hr style="border: none; border-top: 1px solid #000; margin: 5mm 0;">
					<div style="text-align: left; margin-top: 3mm;">
						<span class="kyc-label">Membrii de familie</span>
					</div>
					<div style="text-align: left; margin-top: 1mm; margin-left: 5mm;">
						<span class="kyc-sublabel">(Члены семьи)</span>
					</div>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Părinți: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 5mm;">' . (isset($_POST['kyc_parents_names']) ? $_POST['kyc_parents_names'] : '') . '</span></div>
								<div class="kyc-sublabel">(Родители) Фамилия, Имя</div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Soț/soție: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 5mm;">' . (isset($_POST['kyc_spouse_name']) ? $_POST['kyc_spouse_name'] : '') . '</span></div>
								<div class="kyc-sublabel">(Супруг/супруга) Фамилия, Имя</div>
							</td>
						</tr>
					</table>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Copiii și soțul/soția acestora: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 5mm;">' . (isset($_POST['kyc_children_names']) ? $_POST['kyc_children_names'] : '') . '</span></div>
								<div class="kyc-sublabel">(Дети и их супруги) Фамилия, Имя</div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 2mm;">
								<div class="kyc-label">Concubin/concubină: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 5mm;">' . (isset($_POST['kyc_partner_name']) ? $_POST['kyc_partner_name'] : '') . '</span></div>
								<div class="kyc-sublabel">Сожитель/сожительница Фамилия, Имя</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%;">V.</td>
				<td class="kyc-header">Scopul și natura tranzacțiilor<br/><span class="kyc-sublabel">(Цель и вид операции)</span></td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left; margin-top:0.5mm;">
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Achiziționarea unui automobil pentru uz personal</span></div>
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Achiziționarea unui automobil pentru uzul familiei / rudelor</span></div>
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Achiziționarea unui automobil pentru companie / activitate economică</span></div>
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Achiziționarea unui automobil în scop de revânzare</span></div>
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</span></div>
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Transfer de proprietate între rude (moștenire, donație etc.)</span></div>
						<div style="margin-bottom: 0.5mm;">☐ <span class="kyc-label">Altele (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 120mm; min-height: 5mm;"></span></span></div>
					</div>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%;">VI.</td>
				<td class="kyc-header">Sursa mijloacelor bănești<br/><span class="kyc-sublabel">(Источник денежных средств)</span></td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left; margin-top: 3mm; display: flex; flex-wrap: wrap; gap: 5mm;">
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Salariu</span></div>
							<div class="kyc-sublabel">(Зарплата)</div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Dividende</span></div>
							<div class="kyc-sublabel">(Дивиденды)</div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Împrumut</span></div>
							<div class="kyc-sublabel">(Ссуда)</div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Venit din activitatea de Antreprenor</span></div>
							<div class="kyc-sublabel">(Доход от предпринимательской деятельности)</div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Moștenire</span></div>
							<div class="kyc-sublabel">(Наследство)</div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Donații</span></div>
							<div class="kyc-sublabel">(Дарение)</div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Alte (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 120mm; min-height: 5mm;"></span></span></div>
							<div class="kyc-sublabel">(Другой) (укажите)</div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		
		<div style="margin-top: 10mm;">
			<div style="text-align: center; margin-bottom: 5mm;">
				<span class="kyc-label">Declarații ale clientului / Заявления клиентa:</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span class="kyc-label">1.</span> <span style="font-size: 0.9rem;">Confirm corectitudinea datelor prezentate și îmi asum obligația să comunic în scris către SAUTO SRL orice modificare referitoare la cele declarate mai sus, în termen de 5 zile lucrătoare de la data eliberării documentului confirmativ. Confirm, de asemenea, proveniența legală a mijloacelor bănești, încasate/depuse în cont, inclusiv a mijloacelor ce vor derula prin contul/conturile mele.</span>
			</div>
			<div style="text-align: justify; margin-bottom: 5mm;">
				<span class="kyc-sublabel" style="font-size: 0.85rem;">Я подтверждаю, что представленные данные верны и и я обязуюсь письменно уведомить SAUTO SRL о любых изменениях, связанных с изложенными выше, в течение 5 рабочих дней с момента выдачи подтверждающего документа. Я также подтверждаю законное происхождение средств внесенных/зачисленных на счет/счета, включая средства, которые будут проходить через мой счет/счета.</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span class="kyc-label">2.</span> <span style="font-size: 0.9rem;">Îmi exprim consimțământul expres la prelucrarea de către SAUTO SRL a datelor mele cu caracter personal care sunt prelucrate în scopuri legate de deservirea calitativă aferentă serviciilor prestate de SAUTO SRL, inclusiv alte cazuri conform legislației în vigoare. Aceste acțiuni pot fi efectuate și prin utilizarea mijloacelor informatice automatizate în conformitate cu prevederile Legii Nr. 133 din 08.07.2011 privind protecția datelor cu caracter personal.</span>
			</div>
			<div style="text-align: justify; margin-bottom: 5mm;">
				<span class="kyc-sublabel" style="font-size: 0.85rem;">Я выражаю свое явное согласие на обработку SAUTO SRL моих персональных данных, которые обрабатываются в целях, связанных с качественным обслуживанием, включая другие случаи, предусмотренные действующим законодательством. Эти действия также могут быть осуществлены с использованием автоматизированных средств ИТ в соответствии с положениями Закона №133 от 08.07.2011 о защите персональных данных.</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span class="kyc-label">3.</span> <span style="font-size: 0.9rem;">Prin prezentul, declar pe propria răspundere că sunt beneficiarul efectiv al contului/lor, implicit al mijloacelor bănești utilizate prin intermediul acestuia/acestora.</span>
			</div>
			<div style="text-align: justify; margin-bottom: 5mm;">
				<span class="kyc-sublabel" style="font-size: 0.85rem;">Настоящим я заявляю под свою ответственность, что являюсь эффективным бенефициаром счета/ов, а также средств, использованных через него/них.</span>
			</div>
		</div>
		
		<table style="width: 100%; border: none; margin-top: 5mm;">
			<tr>
				<td colspan="2" style="border: none; text-align: left; padding: 2mm;">
					<table style="width: 100%; border: none;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Data completării</div>
								<div style="border-bottom: 1px dotted #000; width: 50mm; min-height: 5mm; margin: 2mm auto;">' . (isset($_POST['kyc_completion_date']) ? $_POST['kyc_completion_date'] : date('d.m.Y')) . '</div>
								<div class="kyc-sublabel">(Дата заполнения)</div>
							</td>
							<td style="border: none; width: 50%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Semnătura clientului</div>
								<div style="border-bottom: 1px dotted #000; width: 70mm; min-height: 5mm; margin: 2mm auto;"></div>
								<div class="kyc-sublabel">(Подпись клиента)</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="border: none; text-align: left; padding: 5mm 2mm 2mm 2mm;">
					<table style="width: 100%; border: none;">
						<tr>
							<td style="border: none; width: 33%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div class="kyc-label">SAUTO SRL :</div>
							</td>
							<td style="border: none; width: 33%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Data aprobării</div>
								<div style="border-bottom: 1px dotted #000; width: 40mm; min-height: 5mm; margin: 2mm auto;">' . (isset($_POST['kyc_approval_date']) ? $_POST['kyc_approval_date'] : '') . '</div>
								<div class="kyc-sublabel">(Дата утверждения)</div>
							</td>
							<td style="border: none; width: 34%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Semnătura persoanei responsabile</div>
								<div style="border-bottom: 1px dotted #000; width: 40mm; min-height: 5mm; margin: 2mm auto;"></div>
								<div class="kyc-sublabel">(Подпись ответственного лицa)</div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		
		<div style="margin-top: 10mm; font-size: 0.8rem; text-align: justify;">
			<span style="vertical-align: super; font-size: 0.7rem;">1</span> <span style="font-weight: 600;">Persoană expusă politic</span> - persoana fizică definită conform <span style="font-weight: 600;">art. 8 din Legea nr.308/2017</span> cu privire la prevenirea și combaterea spălării banilor și finanțării terorismului.
		</div>
		
		
';

echo $kyc_pages;
?>
