<?php defined( '_DOIT' ) or die( 'Restricted access' );

$kyc_pages = '
	<!-- KYC PAGE 14 -->
	<div class="pg bg" style="page-break-before: always; margin-top: 3mm;">
		<div class="head">
			<div style="text-align: center; font-size: 1.2rem; font-family: \'def\';">Anexa nr. 1</div>
			<div style="text-align: center; font-size: 1.1rem; font-weight: bold; margin-bottom: 2mm;">CHESTIONAR CUNOAȘTE-ȚI CLIENTULUI PERSOANĂ FIZICĂ</div>
		</div>
		
		<table style="width: 100%; border-collapse: collapse; margin: 0.5mm auto;">
			<tr>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top; width: 5%;">I.</td>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top;">Date generale</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left; white-space: nowrap;">
						<span style="font-weight: bold; display: inline-block;">Numele, Prenumele: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 40mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_client_name']) ? ucwords(strtolower($_POST['kyc_client_name'])) : (isset($_POST['u_nm']) ? ucwords(strtolower($_POST['u_nm'])) : '')) . '</span></span>
						<span style="font-weight: bold; display: inline-block; margin-left: 5mm;">zz.ll.aaaa – nașterii: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 20mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_birth_info']) ? $_POST['kyc_birth_info'] : (isset($_POST['u_tva_dt']) ? date('d.m.Y', strtotime($_POST['u_tva_dt'])) : '')) . '</span></span>
						<span style="font-weight: bold; display: inline-block; margin-left: 2mm;">IDNP: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 30mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_idnp']) ? $_POST['kyc_idnp'] : (isset($_POST['u_cf_idno']) ? $_POST['u_cf_idno'] : '')) . '</span></span>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left; white-space: nowrap;">
						<span style="font-weight: bold; display: inline-block;">Actul de identitate:</span>
						<span style="display: inline-block; margin-left: 3mm;">' . (isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Buletin de identitate</span></span>
						<span style="display: inline-block; margin-left: 3mm;">' . (isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Permis de ședere</span></span>
						<span style="display: inline-block; margin-left: 3mm;">' . (isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Pașaport</span></span>
						<span style="font-weight: bold; display: inline-block; margin-left: 5mm;">Data eliberării: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 20mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_doc_date']) ? $_POST['kyc_doc_date'] : (isset($_POST['u_iban_dt_tk']) ? date('d.m.Y', strtotime($_POST['u_iban_dt_tk'])) : '')) . '</span></span>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left;">
						<div style="font-weight: bold;">Adresa de domiciliu: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 150mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_address']) ? $_POST['kyc_address'] : (isset($_POST['u_adr']) ? $_POST['u_adr'] : '')) . '</span></div>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left;">
						<span style="font-weight: bold;">Adresa de reședință:</span>
						<span style="margin-left: 5mm; border-bottom: 1px dotted #000; display: inline-block; width: 90mm; min-height: 5mm; margin-left: 1mm;"></span>
					</div>
					<div style="font-size: 0.8em; color: #333; font-style: italic; text-align: left; margin-top: 0.5mm; font-weight: bold;">
						(Se completează în cazul în care diferă de adresa de domiciliu)
					</div>
				</td>
			</tr>
			<tr>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top; width: 5%;">II.</td>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top;">Date de contact</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left;">
						<span style="font-weight: bold;">Tel. mobil: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 40mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_phone']) ? $_POST['kyc_phone'] : (isset($_POST['u_phn']) ? $_POST['u_phn'] : '')) . '</span></span>
						<span style="font-weight: bold; margin-left: 15mm;">E-mail: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_email']) ? $_POST['kyc_email'] : (isset($_POST['u_eml']) ? $_POST['u_eml'] : '')) . '</span></span>
					</div>
				</td>
			</tr>
			<tr>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top; width: 5%;">III.</td>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top;">Ocupația</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="display: flex; flex-wrap: wrap; row-gap: 1mm; column-gap: 7mm; margin-top: 0.5mm;">
						<div>' . (isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Angajat*</span></div>
						<div>' . (isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Student*</span></div>
						<div>' . (isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Antreprenor*</span></div>
						<div>' . (isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Șomer</span></div>
						<div>' . (isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Pensionar</span></div>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span>☐ <span style="font-weight: bold;">Alte (indicați)</span></span>
						<span style="margin-left: 2mm; border-bottom: 1px dotted #000; display: inline-block; width: 100mm; min-height: 5mm;"></span>
					</div>
					<table style="width: 100%; border: none; margin-top: 1.5mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding-bottom: 0.5mm;">
								<div style="font-weight: bold;">Denumirea instituției: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 55mm; min-height: 5mm;">' . (isset($_POST['kyc_institution_name']) ? $_POST['kyc_institution_name'] : '') . '</span></div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding-bottom: 0.5mm;">
								<div style="font-weight: bold;">Funcția deținută: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 55mm; min-height: 5mm;">' . (isset($_POST['kyc_position']) ? $_POST['kyc_position'] : '') . '</span></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top; width: 5%;">IV.</td>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top;">Persoană expusă politic (Funcția publică deținută)</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left;">
						<span style="font-weight: bold;">Conform Legii nr.158/2008 cu privire la funcția publică și statutul funcționarului public</span>
					</div>
					<div style="text-align: left;">
						<span>' . (isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Nu dețin funcție publică</span></span>
					</div>
					<div style="text-align: left; margin-top:2mm;">
						<span style="font-weight: bold;">În caz că dețineți o funcție publică indicați:</span>
					</div>
					<div style="display: flex; flex-wrap: wrap; row-gap: 1mm; column-gap: 8mm; margin-top: 1mm;">
						<div>' . (isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Deputat al Parlamentului RM</span></div>
						<div>' . (isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Judecător</span></div>
						<div>' . (isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Membru al Guvernului RM</span></div>
						<div>' . (isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Primar</span></div>
						<div>' . (isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Membru al organelor de conducere ale partidelor politice</span></div>
						<div>' . (isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Consilier al autorităților publice locale</span></div>
						<div>☐ <span style="font-weight: bold;">Alta (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 80mm; min-height: 5mm;"></span></span></div>
					</div>
					<div style="text-align: left; margin-top: 1mm; white-space: nowrap;">
						<span style="font-weight: bold;">Afilierea</span> <span style="font-size: 0.85em; font-weight: normal;">(Conducător, asociat, acționar cu 25%)</span>
						<span style="font-weight: bold; margin-left: 5mm;">Denumirea companiei:</span>
						<span style="margin-left: 3mm; border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm;">' . (isset($_POST['kyc_affiliated_company']) ? $_POST['kyc_affiliated_company'] : '') . '</span>
					</div>
					<hr style="border: none; border-top: 1px solid #000; margin: 1mm;">
					<div style="text-align: center; margin-top: 1mm;">
						<span style="font-weight: bold;">Membrii de familie</span>
					</div>
					<table style="width: 100%; border: none; margin-top: 1mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div style="font-weight: bold;">Părinți: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 75mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_parents_names']) ? $_POST['kyc_parents_names'] : '') . '</span></div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div style="font-weight: bold;">Soț/soție: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_spouse_name']) ? $_POST['kyc_spouse_name'] : '') . '</span></div>
							</td>
						</tr>
					</table>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div style="font-weight: bold;">Copiii și soțul/soția acestora: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_children_names']) ? $_POST['kyc_children_names'] : '') . '</span></div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div style="font-weight: bold;">Concubin/concubină: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_partner_name']) ? $_POST['kyc_partner_name'] : '') . '</span></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top; width: 5%;">V.</td>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top;">Scopul și natura tranzacțiilor</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left;">
						<div>' . (isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Achiziționarea unui automobil pentru uz personal</span></div>
						<div>' . (isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Achiziționarea unui automobil pentru uzul familiei / rudelor</span></div>
						<div>' . (isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Achiziționarea unui automobil pentru companie / activitate economică</span></div>
						<div>' . (isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Achiziționarea unui automobil în scop de revânzare</span></div>
						<div>' . (isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</span></div>
						<div>' . (isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? '☑' : '☐') . ' <span style="font-weight: bold;">Transfer de proprietate între rude (moștenire, donație etc.)</span></div>
						<div>☐ <span style="font-weight: bold;">Altele (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 120mm; min-height: 5mm;"></span></span></div>
					</div>
				</td>
			</tr>
			<tr>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top; width: 5%;">VI.</td>
				<td style="background-color: #f0f0f0; text-align: center; font-weight: bold; border: 1px solid #000; padding: 1mm; vertical-align: top;">Sursa mijloacelor bănești</td>
			</tr>
			<tr>
				<td colspan="2" style="border: 1px solid #000; padding: 1mm; vertical-align: top;">
					<div style="text-align: left; display: flex; flex-wrap: wrap; gap: 3mm;">
						<div style="text-align: center;">
							<div>☐ <span style="font-weight: bold;">Salariu</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span style="font-weight: bold;">Dividende</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span style="font-weight: bold;">Împrumut</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span style="font-weight: bold;">Venit din activitatea de Antreprenor</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span style="font-weight: bold;">Moștenire</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span style="font-weight: bold;">Donații</span></div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		
		<div style="margin-top: 10mm; padding-top: 15mm;">
			<div style="text-align: center; margin-bottom: 5mm;">
				<span style="font-weight: bold;">Declarații ale clientului:</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span style="font-weight: bold;">1.</span> <span style="font-size: 0.9rem;">Confirm corectitudinea datelor prezentate și îmi asum obligația să comunic în scris către SAUTO SRL orice modificare referitoare la cele declarate mai sus, în termen de 5 zile lucrătoare de la data eliberării documentului confirmativ. Confirm, de asemenea, proveniența legală a mijloacelor bănești, încasate/depuse în cont, inclusiv a mijloacelor ce vor derula prin contul/conturile mele.</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span style="font-weight: bold;">2.</span> <span style="font-size: 0.9rem;">Îmi exprim consimțământul expres la prelucrarea de către SAUTO SRL a datelor mele cu caracter personal care sunt prelucrate în scopuri legate de deservirea calitativă aferentă serviciilor prestate de SAUTO SRL, inclusiv alte cazuri conform legislației în vigoare. Aceste acțiuni pot fi efectuate și prin utilizarea mijloacelor informatice automatizate în conformitate cu prevederile Legii Nr. 133 din 08.07.2011 privind protecția datelor cu caracter personal.</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span style="font-weight: bold;">3.</span> <span style="font-size: 0.9rem;">Prin prezentul, declar pe propria răspundere că sunt beneficiarul efectiv al contului/lor, implicit al mijloacelor bănești utilizate prin intermediul acestuia/acestora.</span>
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
							</td>
							<td style="border: none; width: 50%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Semnătura clientului</div>
								<div style="border-bottom: 1px dotted #000; width: 70mm; min-height: 5mm; margin: 2mm auto;"></div>
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
								<div style="font-weight: bold;">SAUTO SRL :</div>
							</td>
							<td style="border: none; width: 33%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Data aprobării</div>
								<div style="border-bottom: 1px dotted #000; width: 40mm; min-height: 5mm; margin: 2mm auto;">' . date('d.m.Y') . '</div>
							</td>
							<td style="border: none; width: 34%; vertical-align: middle; padding: 2mm; text-align: center;">
								<div>Semnătura persoanei responsabile</div>
								<div style="border-bottom: 1px dotted #000; width: 40mm; min-height: 5mm; margin: 2mm auto;"></div>
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
