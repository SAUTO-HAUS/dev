<?php defined( '_DOIT' ) or die( 'Restricted access' );

$kyc_pages = '
<style>
	.kyc-table { width: 100%; border-collapse: collapse; margin: 0.5mm auto; }
	.kyc-table td { border: 1px solid #000; padding: 1mm; vertical-align: top; }
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
				<td class="kyc-header" style="width: 5%; padding: 0.5mm;">I.</td>
				<td class="kyc-header" style="padding: 0.5mm;">Date generale</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left; white-space: nowrap;">
						<span class="kyc-label" style="display: inline-block;">Numele, Prenumele: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 40mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_client_name']) ? ucwords(strtolower($_POST['kyc_client_name'])) : (isset($_POST['u_nm']) ? ucwords(strtolower($_POST['u_nm'])) : '')) . '</span></span>
						<span class="kyc-label" style="display: inline-block; margin-left: 5mm;">zz.ll.aaaa – nașterii: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 20mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_birth_info']) ? $_POST['kyc_birth_info'] : (isset($_POST['u_tva_dt']) ? date('d.m.Y', strtotime($_POST['u_tva_dt'])) : '')) . '</span></span>
						<span class="kyc-label" style="display: inline-block; margin-left: 2mm;">IDNP: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 30mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_idnp']) ? $_POST['kyc_idnp'] : (isset($_POST['u_cf_idno']) ? $_POST['u_cf_idno'] : '')) . '</span></span>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left; white-space: nowrap;">
						<span class="kyc-label" style="display: inline-block;">Actul de identitate:</span>
						<span style="display: inline-block; margin-left: 3mm;">☐ <span class="kyc-label">Buletin de identitate</span></span>
						<span style="display: inline-block; margin-left: 3mm;">☐ <span class="kyc-label">Permis de ședere</span></span>
						<span style="display: inline-block; margin-left: 3mm;">☐ <span class="kyc-label">Pașaport</span></span>
						<span class="kyc-label" style="display: inline-block; margin-left: 5mm;">Data eliberării: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 20mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_doc_date']) ? $_POST['kyc_doc_date'] : (isset($_POST['u_iban_dt_tk']) ? date('d.m.Y', strtotime($_POST['u_iban_dt_tk'])) : '')) . '</span></span>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<div class="kyc-label">Adresa de domiciliu: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 150mm; min-height: 5mm; margin-left: 1mm;">' . (isset($_POST['kyc_address']) ? $_POST['kyc_address'] : (isset($_POST['u_adr']) ? $_POST['u_adr'] : '')) . '</span></div>
					</div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<span class="kyc-label">Adresa de reședință:</span>
						<span style="margin-left: 5mm; border-bottom: 1px dotted #000; display: inline-block; width: 90mm; min-height: 5mm; margin-left: 1mm;"></span>
					</div>
					<div class="kyc-sublabel" style="text-align: left; margin-top: 0.5mm; font-weight: bold; color: #333;">
						(Se completează în cazul în care diferă de adresa de domiciliu)
					</div>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%; padding: 0.5mm;">II.</td>
				<td class="kyc-header" style="padding: 0.5mm;">Date de contact</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<span class="kyc-label">Tel. mobil: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 40mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_phone']) ? $_POST['kyc_phone'] : (isset($_POST['u_phn']) ? $_POST['u_phn'] : '')) . '</span></span>
						<span class="kyc-label" style="margin-left: 15mm;">E-mail: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm; margin-left: 5mm;">' . (isset($_POST['kyc_email']) ? $_POST['kyc_email'] : (isset($_POST['u_eml']) ? $_POST['u_eml'] : '')) . '</span></span>
					</div>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%; padding: 0.5mm;">III.</td>
				<td class="kyc-header" style="padding: 0.5mm;">Ocupația</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="display: flex; flex-wrap: wrap; row-gap: 1mm; column-gap: 7mm; margin-top: 0.5mm;">
						<div>☐ <span class="kyc-label">Angajat*</span></div>
						<div>☐ <span class="kyc-label">Student*</span></div>
						<div>☐ <span class="kyc-label">Antreprenor*</span></div>
						<div>☐ <span class="kyc-label">Șomer</span></div>
						<div>☐ <span class="kyc-label">Pensionar</span></div>
					</div>
					<div style="text-align: left; margin-top: 1mm;">
						<span>☐ <span class="kyc-label">Alte (indicați)</span></span>
						<span style="margin-left: 2mm; border-bottom: 1px dotted #000; display: inline-block; width: 100mm; min-height: 5mm;"></span>
					</div>
					<table style="width: 100%; border: none; margin-top: 1.5mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding-bottom: 0.5mm;">
								<div class="kyc-label">Denumirea instituției: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 55mm; min-height: 5mm;">' . (isset($_POST['kyc_institution_name']) ? $_POST['kyc_institution_name'] : '') . '</span></div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding-bottom: 0.5mm;">
								<div class="kyc-label">Funcția deținută: <span style="border-bottom: 1px dotted #000; display: inline-block; width: 55mm; min-height: 5mm;">' . (isset($_POST['kyc_position']) ? $_POST['kyc_position'] : '') . '</span></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%; padding: 0.5mm;">IV.</td>
				<td class="kyc-header" style="padding: 0.5mm;">Persoană expusă politic (Funcția publică deținută)</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<span class="kyc-label">Conform Legii nr.158/2008 cu privire la funcția publică și statutul funcționarului public</span>
					</div>
					<div style="text-align: left;">
						<span>☐ <span class="kyc-label">Nu dețin funcție publică</span></span>
					</div>
					<div style="text-align: left; margin-top:2mm;">
						<span class="kyc-label">În caz că dețineți o funcție publică indicați:</span>
					</div>
					<div style="display: flex; flex-wrap: wrap; row-gap: 1mm; column-gap: 8mm; margin-top: 1mm;">
						<div>☐ <span class="kyc-label">Deputat al Parlamentului RM</span></div>
						<div>☐ <span class="kyc-label">Judecător</span></div>
						<div>☐ <span class="kyc-label">Membru al Guvernului RM</span></div>
						<div>☐ <span class="kyc-label">Primar</span></div>
						<div>☐ <span class="kyc-label">Membru al organelor de conducere ale partidelor politice</span></div>
						<div>☐ <span class="kyc-label">Consilier al autorităților publice locale</span></div>
						<div>☐ <span class="kyc-label">Alta (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 80mm; min-height: 5mm;"></span></span></div>
					</div>
					<div style="text-align: left; margin-top: 1mm; white-space: nowrap;">
						<span class="kyc-label">Afilierea</span> <span style="font-size: 0.85em; font-weight: normal;">(Conducător, asociat, acționar cu 25%)</span>
						<span class="kyc-label" style="margin-left: 5mm;">Denumirea companiei:</span>
						<span style="margin-left: 3mm; border-bottom: 1px dotted #000; display: inline-block; width: 60mm; min-height: 5mm;">' . (isset($_POST['kyc_affiliated_company']) ? $_POST['kyc_affiliated_company'] : '') . '</span>
					</div>
					<hr style="border: none; border-top: 1px solid #000; margin: 1mm;">
					<div style="text-align: center; margin-top: 1mm;">
						<span class="kyc-label">Membrii de familie</span>
					</div>
					<table style="width: 100%; border: none; margin-top: 1mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div class="kyc-label">Părinți: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 75mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_parents_names']) ? $_POST['kyc_parents_names'] : '') . '</span></div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div class="kyc-label">Soț/soție: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_spouse_name']) ? $_POST['kyc_spouse_name'] : '') . '</span></div>
							</td>
						</tr>
					</table>
					<table style="width: 100%; border: none; margin-top: 3mm;">
						<tr>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div class="kyc-label">Copiii și soțul/soția acestora: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_children_names']) ? $_POST['kyc_children_names'] : '') . '</span></div>
							</td>
							<td style="border: none; width: 50%; vertical-align: top; padding: 1mm;">
								<div class="kyc-label">Concubin/concubină: Numele, Prenumele <span style="border-bottom: 1px dotted #000; display: inline-block; width: 65mm; min-height: 3mm; vertical-align: bottom;">' . (isset($_POST['kyc_partner_name']) ? $_POST['kyc_partner_name'] : '') . '</span></div>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%; padding: 0.5mm;">V.</td>
				<td class="kyc-header" style="padding: 0.5mm;">Scopul și natura tranzacțiilor</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left;">
						<div>☐ <span class="kyc-label">Achiziționarea unui automobil pentru uz personal</span></div>
						<div>☐ <span class="kyc-label">Achiziționarea unui automobil pentru uzul familiei / rudelor</span></div>
						<div>☐ <span class="kyc-label">Achiziționarea unui automobil pentru companie / activitate economică</span></div>
						<div>☐ <span class="kyc-label">Achiziționarea unui automobil în scop de revânzare</span></div>
						<div>☐ <span class="kyc-label">Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</span></div>
						<div>☐ <span class="kyc-label">Transfer de proprietate între rude (moștenire, donație etc.)</span></div>
						<div>☐ <span class="kyc-label">Altele (indicați) <span style="border-bottom: 1px dotted #000; display: inline-block; width: 120mm; min-height: 5mm;"></span></span></div>
					</div>
				</td>
			</tr>
			<tr>
				<td class="kyc-header" style="width: 5%; padding: 0.5mm;">VI.</td>
				<td class="kyc-header" style="padding: 0.5mm;">Sursa mijloacelor bănești</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: left; display: flex; flex-wrap: wrap; gap: 3mm;">
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Salariu</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Dividende</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Împrumut</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Venit din activitatea de Antreprenor</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Moștenire</span></div>
						</div>
						<div style="text-align: center;">
							<div>☐ <span class="kyc-label">Donații</span></div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		
		<div style="margin-top: 10mm; padding-top: 15mm;">
			<div style="text-align: center; margin-bottom: 5mm;">
				<span class="kyc-label">Declarații ale clientului:</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span class="kyc-label">1.</span> <span style="font-size: 0.9rem;">Confirm corectitudinea datelor prezentate și îmi asum obligația să comunic în scris către SAUTO SRL orice modificare referitoare la cele declarate mai sus, în termen de 5 zile lucrătoare de la data eliberării documentului confirmativ. Confirm, de asemenea, proveniența legală a mijloacelor bănești, încasate/depuse în cont, inclusiv a mijloacelor ce vor derula prin contul/conturile mele.</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span class="kyc-label">2.</span> <span style="font-size: 0.9rem;">Îmi exprim consimțământul expres la prelucrarea de către SAUTO SRL a datelor mele cu caracter personal care sunt prelucrate în scopuri legate de deservirea calitativă aferentă serviciilor prestate de SAUTO SRL, inclusiv alte cazuri conform legislației în vigoare. Aceste acțiuni pot fi efectuate și prin utilizarea mijloacelor informatice automatizate în conformitate cu prevederile Legii Nr. 133 din 08.07.2011 privind protecția datelor cu caracter personal.</span>
			</div>
			
			<div style="text-align: justify; margin-bottom: 1mm;">
				<span class="kyc-label">3.</span> <span style="font-size: 0.9rem;">Prin prezentul, declar pe propria răspundere că sunt beneficiarul efectiv al contului/lor, implicit al mijloacelor bănești utilizate prin intermediul acestuia/acestora.</span>
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
								<div class="kyc-label">SAUTO SRL :</div>
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
