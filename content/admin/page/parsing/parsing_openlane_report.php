<?php
/**
 * Shared OpenLane report renderer (Condition diagram + Equipment list).
 *
 * Lives in a standalone file (like parsing_report.php for Encar) so it can be
 * used in THREE places without duplicating code:
 *   - admin AJAX modal (content/admin/ajax/parsing/ajax.php)
 *   - on publish, to bake the HTML into parsing_cars.report_data
 *   - the public product page (content/site/page/ordercars.php)
 *
 * The body diagram itself is drawn by parsing_report_body_diagram() in
 * parsing_report.php (the same SVG Encar uses), so the two sources look alike.
 */

// Map an OpenLane DamageLocationId to one of the SVG body slots used by the
// Encar-style diagram. IDs reverse-engineered by correlating the JSON
// Damage.Damages[] with the on-site damage list across several real cars.
if (!function_exists('parsing_openlane_damage_slot')) {
function parsing_openlane_damage_slot(int $locId): ?string {
    static $map = [
        1  => 'HOOD',                // Front Bumper → drawn on the nose
        2  => 'HOOD',                // Grille → front
        3  => 'HOOD',                // Hood/Bonnet
        6  => 'FRONT_FENDER_LEFT',
        8  => 'FRONT_DOOR_LEFT',
        10 => 'BACK_DOOR_LEFT',
        11 => 'QUARTER_PANEL_LEFT',
        13 => 'TRUNK_LID',           // Rear Bumper → tail
        14 => 'TRUNK_LID',           // Tailgate
        16 => 'ROOF_PANEL',
        18 => 'QUARTER_PANEL_RIGHT',
        19 => 'BACK_DOOR_RIGHT',
        21 => 'FRONT_DOOR_RIGHT',
        23 => 'FRONT_FENDER_RIGHT',
    ];
    return $map[$locId] ?? null;
}
}

// Friendly zone name for a DamageLocationId (text list / tooltip). Covers every
// id confirmed from real cars, including wheels/mirrors with no SVG slot.
if (!function_exists('parsing_openlane_damage_zone')) {
function parsing_openlane_damage_zone(int $locId, string $lang): string {
    static $zones = [
        1  => ['ro'=>'Bară față','ru'=>'Передний бампер','en'=>'Front Bumper'],
        2  => ['ro'=>'Grilă','ru'=>'Решётка','en'=>'Grille'],
        3  => ['ro'=>'Capotă','ru'=>'Капот','en'=>'Hood'],
        6  => ['ro'=>'Aripă față stânga','ru'=>'Переднее левое крыло','en'=>'Front Left Fender'],
        8  => ['ro'=>'Ușă față stânga','ru'=>'Передняя левая дверь','en'=>'Front Left Door'],
        10 => ['ro'=>'Ușă spate stânga','ru'=>'Задняя левая дверь','en'=>'Rear Left Door'],
        11 => ['ro'=>'Aripă spate stânga','ru'=>'Заднее левое крыло','en'=>'Left Quarter Panel'],
        13 => ['ro'=>'Bară spate','ru'=>'Задний бампер','en'=>'Rear Bumper'],
        14 => ['ro'=>'Haion','ru'=>'Крышка багажника','en'=>'Tailgate'],
        16 => ['ro'=>'Plafon','ru'=>'Крыша','en'=>'Roof'],
        18 => ['ro'=>'Aripă spate dreapta','ru'=>'Заднее правое крыло','en'=>'Right Quarter Panel'],
        19 => ['ro'=>'Ușă spate dreapta','ru'=>'Задняя правая дверь','en'=>'Rear Right Door'],
        21 => ['ro'=>'Ușă față dreapta','ru'=>'Передняя правая дверь','en'=>'Front Right Door'],
        23 => ['ro'=>'Aripă față dreapta','ru'=>'Переднее правое крыло','en'=>'Front Right Fender'],
        63 => ['ro'=>'Roată față stânga','ru'=>'Переднее левое колесо','en'=>'Front Left Wheel'],
        64 => ['ro'=>'Roată față dreapta','ru'=>'Переднее правое колесо','en'=>'Front Right Wheel'],
        65 => ['ro'=>'Roată spate stânga','ru'=>'Заднее левое колесо','en'=>'Rear Left Wheel'],
        66 => ['ro'=>'Roată spate dreapta','ru'=>'Заднее правое колесо','en'=>'Rear Right Wheel'],
        73 => ['ro'=>'Oglindă stânga','ru'=>'Левое зеркало','en'=>'Left mirror'],
        74 => ['ro'=>'Oglindă dreapta','ru'=>'Правое зеркало','en'=>'Right mirror'],
    ];
    return $zones[$locId][$lang] ?? ($zones[$locId]['en'] ?? ('#'.$locId));
}
}

// Translate an OpenLane DamageKind (Scratch/Dent/Chip/Broken/Corrosion/Other).
if (!function_exists('parsing_openlane_damage_kind')) {
function parsing_openlane_damage_kind(string $kind, string $lang): string {
    static $kinds = [
        'scratch'   => ['ro'=>'Zgârietură','ru'=>'Царапина','en'=>'Scratch'],
        'dent'      => ['ro'=>'Lovitură','ru'=>'Вмятина','en'=>'Dent'],
        'chip'      => ['ro'=>'Ciobitură','ru'=>'Скол','en'=>'Chip'],
        'broken'    => ['ro'=>'Spart','ru'=>'Повреждено','en'=>'Broken'],
        'corrosion' => ['ro'=>'Coroziune','ru'=>'Коррозия','en'=>'Corrosion'],
        'other'     => ['ro'=>'Altele','ru'=>'Другое','en'=>'Other'],
    ];
    $k = mb_strtolower(trim($kind), 'UTF-8');
    return $kinds[$k][$lang] ?? ucfirst($kind);
}
}

// Build the OpenLane "Condition" block: the Encar-style SVG body diagram with
// damaged panels coloured + a special-damages table. No card header (the diagram
// carries its own "Starea caroseriei" heading).
if (!function_exists('parsing_openlane_condition_html')) {
function parsing_openlane_condition_html(array $d, string $lang): string {
    $damage  = $d['Damage'] ?? [];
    $damages = $damage['Damages'] ?? [];
    $special = $damage['SpecialDamageLabels'] ?? [];
    $comment = trim((string)($damage['DamageComment'] ?? ''));
    if (empty($damages) && empty($special) && $comment === '') return '';

    // Group damages by location → list of kinds (de-duplicated).
    $byLoc = [];
    foreach ($damages as $dm) {
        $locId = (int)($dm['DamageLocationId'] ?? 0);
        $kind  = (string)($dm['DamageKind'] ?? '');
        if ($locId === 0 || $kind === '') continue;
        $byLoc[$locId][$kind] = true;
    }

    // Worst severity per SVG slot, for the body diagram colouring.
    $sevRank = ['chip'=>1,'scratch'=>2,'dent'=>3,'corrosion'=>4,'broken'=>5,'other'=>2];
    $slotSev = [];
    foreach ($byLoc as $locId => $kinds) {
        $slot = parsing_openlane_damage_slot($locId);
        if (!$slot) continue;
        foreach ($kinds as $k => $_) {
            $r = $sevRank[mb_strtolower($k,'UTF-8')] ?? 2;
            if (!isset($slotSev[$slot]) || $r > $slotSev[$slot]) $slotSev[$slot] = $r;
        }
    }
    // Severity → the diagram's letter type (chip/scratch=A, dent=T, corrosion=C, broken=X).
    $sevToCode = [1=>'A',2=>'A',3=>'T',4=>'C',5=>'X'];
    $diagItems = [];
    foreach ($slotSev as $slot => $r) {
        $diagItems[] = ['name' => $slot, 'resultCode' => $sevToCode[$r] ?? 'A'];
    }

    // The body diagram lives in parsing_report.php (same SVG Encar uses). Use an
    // absolute path (__DIR__) so it resolves regardless of the caller's cwd — the
    // backfill script runs from /console where a relative path fails.
    if (!function_exists('parsing_report_body_diagram')) {
        include_once __DIR__ . '/parsing_report.php';
    }
    $svgSection = '';
    if (function_exists('parsing_report_body_diagram')) {
        $svgSection = parsing_report_body_diagram($diagItems, [], $lang, count($damages));
    }

    // Special damages (coded labels like "SpecDmg.AlloysScratched").
    $specMap = [
        'alloysscratched'   => ['ro'=>'Jante zgâriate','ru'=>'Диски поцарапаны','en'=>'Alloy wheels scratched'],
        'interiordamaged'   => ['ro'=>'Interior deteriorat','ru'=>'Салон повреждён','en'=>'Interior damaged'],
        'uncleaninterior'   => ['ro'=>'Interior murdar','ru'=>'Грязный салон','en'=>'Unclean interior'],
        'uncleanexterior'   => ['ro'=>'Exterior murdar','ru'=>'Грязный кузов','en'=>'Unclean exterior'],
        'windscreendamaged' => ['ro'=>'Parbriz deteriorat','ru'=>'Лобовое стекло повреждено','en'=>'Windscreen damaged'],
        'windscreenchipped' => ['ro'=>'Parbriz ciobit','ru'=>'Скол на лобовом стекле','en'=>'Windscreen chipped'],
        'tireswear'         => ['ro'=>'Anvelope uzate','ru'=>'Износ шин','en'=>'Tyres worn'],
        'tyreswear'         => ['ro'=>'Anvelope uzate','ru'=>'Износ шин','en'=>'Tyres worn'],
        'paintdamaged'      => ['ro'=>'Vopsea deteriorată','ru'=>'Повреждение ЛКП','en'=>'Paint damaged'],
        'smellofsmoke'      => ['ro'=>'Miros de fum','ru'=>'Запах табака','en'=>'Smell of smoke'],
        'warninglighton'    => ['ro'=>'Martor aprins pe bord','ru'=>'Горит индикатор','en'=>'Warning light on'],
    ];
    $specRows = '';
    if (!empty($special) && is_array($special)) {
        foreach ($special as $sp) {
            $raw = trim((string)(is_array($sp) ? ($sp['Label'] ?? $sp['Name'] ?? '') : $sp));
            if ($raw === '') continue;
            // Strip a "SpecDmg." prefix; match the dictionary by lowercase code.
            $bare = preg_replace('/^[A-Za-z]+\./', '', $raw);
            $code = mb_strtolower($bare, 'UTF-8');
            if (isset($specMap[$code])) {
                $txt = $specMap[$code][$lang] ?? $specMap[$code]['en'];
            } else {
                // Unknown code → never show "SpecDmg.Xyz". Split CamelCase into
                // readable words instead (e.g. "UncleanInterior" → "Unclean Interior").
                $txt = trim(preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $bare));
            }
            $specRows .= '<tr><td class="er-item">'.htmlspecialchars($txt).'</td>'
                       . '<td class="er-stcell"><span class="er-badge er-bad">!</span></td></tr>';
        }
    }
    $specSection = '';
    if ($specRows !== '') {
        $lblSpec = ['ro'=>'Avarii speciale','ru'=>'Особые повреждения','en'=>'Special damages'][$lang] ?? 'Avarii speciale';
        $specSection = '<div class="er-section"><h4>'.htmlspecialchars($lblSpec).'</h4>'
                     . '<table class="er-table"><tbody>'.$specRows.'</tbody></table></div>';
    }

    if ($svgSection === '' && $specSection === '') return '';

    $css = '<style>'
        . '.er-dmg-report{--er-red:#e2001a;--er-line:#eef0f3;}'
        . '.encar-report-public .encar-report{margin:0;}'
        . '.md-price-block + .encar-report-public{margin-top:-20px;}'
        . '.encar-report-public .encar-report ~ .encar-report{margin-top:12px;}'
        . '@media (max-width:768px){.md-price-mobile-only > .md-price-block{margin-bottom:0;}.md-price-block + .encar-report-public{margin-top:0;}.encar-report-public{margin:0;padding:12px 0;}}'
        . '.er-dmg-report.encar-report{background:#fff;border:1px solid #efefef;border-radius:18px;box-shadow:0 10px 30px rgba(20,20,40,.07);overflow:hidden;}'
        . '.er-dmg-report > .er-head{margin:0 !important;padding:11px 22px !important;font-size:1.05rem !important;font-weight:bold !important;color:#fff !important;text-transform:uppercase;letter-spacing:.4px;background:linear-gradient(135deg,#2b2b2b 0%,#444 100%) !important;display:flex;align-items:center;border:none;width:100%;text-align:left;}'
        . '.er-dmg-report .er-section + .er-section{border-top:6px solid #f4f6f8;}'
        . '.er-dmg-report .er-section h4{display:flex;align-items:center;gap:10px;margin:0;padding:14px 20px;font-size:1.02rem;font-weight:700;color:#1f2430;background:linear-gradient(180deg,#fbfcfd,#f4f6f8);border-bottom:1px solid var(--er-line);}'
        . '.er-dmg-report .er-section h4:before{content:"";width:5px;height:19px;border-radius:3px;background:var(--er-red);flex:none;box-shadow:0 0 0 3px rgba(226,0,26,.10);}'
        . '.er-dmg-report .erd-section h4{margin-bottom:0;}'
        . '.er-dmg-report .erd-wrap{display:flex;gap:28px;align-items:flex-start;padding:20px 18px;flex-wrap:wrap;}'
        . '.er-dmg-report .erd-col{min-width:0;}'
        . '.er-dmg-report .erd-col-car{flex:0 0 auto;}'
        . '.er-dmg-report .erd-col-legend{flex:0 1 auto;padding-right:14px;border-right:1px solid #eef0f3;}'
        . '.er-dmg-report .erd-col-full{flex:1 1 220px;min-width:190px;}'
        . '.er-dmg-report .erd-svg{width:150px;height:auto;display:block;filter:drop-shadow(0 4px 10px rgba(20,20,40,.08));}'
        . '.er-dmg-report .erd-body{fill:#f1f3f6;}'
        . '.er-dmg-report .erd-p{fill:transparent;}'
        . '.er-dmg-report .erd-seams line,.er-dmg-report .erd-seams path{fill:none;stroke:#aeb6c2;stroke-width:1.8;stroke-linecap:round;}'
        . '.er-dmg-report .erd-glass path,.er-dmg-report .erd-glass rect{fill:#cdd7e4;stroke:#aeb6c2;stroke-width:1.2;}'
        . '.er-dmg-report .erd-lamp path{fill:#dfe5ec;stroke:#aeb6c2;stroke-width:1;}.er-dmg-report .erd-lamp-rear path{fill:#f0c9cc;}'
        . '.er-dmg-report .erd-mirror path{fill:#c4ccd8;}.er-dmg-report .erd-wheel rect{fill:#2c3138;}'
        . '.er-dmg-report .erd-outline{fill:none;stroke:#7d8694;stroke-width:3;stroke-linejoin:round;}'
        . '.er-dmg-report .erd-badge circle{stroke:#fff;stroke-width:1.5;}'
        . '.er-dmg-report .erd-badge text{fill:#fff;font-size:13px;font-weight:800;text-anchor:middle;}'
        . '.er-dmg-report .erd-legend{display:flex;flex-direction:column;gap:9px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid #e8ebef;}'
        . '.er-dmg-report .erd-leg{display:inline-flex;align-items:center;gap:8px;font-size:.84rem;color:#525a67;white-space:nowrap;}'
        . '.er-dmg-report .erd-dot{width:18px;height:18px;border-radius:5px;flex:none;border:1px solid rgba(0,0,0,.10);display:inline-flex;align-items:center;justify-content:center;}'
        . '.er-dmg-report .erd-dot b{color:#fff;font-size:.66rem;font-weight:800;line-height:1;}'
        . '.er-dmg-report .erd-affected{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:9px;}'
        . '.er-dmg-report .erd-affected li{display:flex;align-items:center;gap:10px;font-size:.88rem;color:#2c333f;}'
        . '.er-dmg-report .erd-num{flex:none;width:22px;height:22px;border-radius:50%;color:#fff;font-size:.78rem;font-weight:800;display:flex;align-items:center;justify-content:center;}'
        . '.er-dmg-report .erd-clean{margin:0;padding:10px 14px;font-size:.9rem;font-weight:600;color:#444b58;background:#eef0f3;border-radius:10px;}'
        . '.er-dmg-report .erd-full{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:7px;}'
        . '.er-dmg-report .erd-full li{display:flex;justify-content:space-between;gap:8px;padding:9px 14px;font-size:.86rem;color:#2c333f;background:#f1f2f4;border-radius:10px;}'
        . '.er-dmg-report .erd-full-nm{flex:1 1 auto;min-width:0;padding-right:6px;}'
        . '.er-dmg-report .erd-full-st{flex:0 0 auto;font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;}'
        . '.er-dmg-report .erd-st-ok{color:#2c333f;}.er-dmg-report .erd-st-bad{color:#c01425;}'
        . '.er-dmg-report .er-table{width:100%;border-collapse:collapse;font-size:.93rem;}'
        . '.er-dmg-report .er-table td{padding:9px 18px;border-bottom:1px solid #f5f6f8;color:#2c333f;}'
        . '.er-dmg-report .er-table tr:last-child td{border-bottom:none;}'
        . '.er-dmg-report .er-stcell{width:120px;text-align:right;white-space:nowrap;}'
        . '.er-dmg-report .er-badge{display:inline-block;min-width:22px;padding:2px 8px;border-radius:8px;font-size:.78rem;font-weight:700;text-align:center;}'
        . '.er-dmg-report .er-badge.er-bad{background:#fdeaec;color:#c01425;}'
        . '@media (max-width:600px){.er-dmg-report .erd-wrap{justify-content:center;}.er-dmg-report .erd-col-full{flex-basis:100%;}}'
        . '</style>';

    $lblHist = ['ro'=>'Istoric','ru'=>'История','en'=>'History'][$lang] ?? 'Istoric';
    return $css.'<div class="encar-report er-dmg-report open">'
         . '<div class="er-head">'.htmlspecialchars($lblHist).'</div>'
         . '<div class="er-body"><div class="er-doc">'
         . $svgSection
         . $specSection
         . '</div></div></div>';
}
}

// De-duplicate equipment items by their visible label (case/accents insensitive).
if (!function_exists('parsing_equipment_dedup')) {
function parsing_equipment_dedup(array $items): array {
    $out = [];
    $seen = [];
    foreach ($items as $it) {
        $norm = mb_strtolower(trim((string)($it['label'] ?? '')), 'UTF-8');
        $norm = strtr($norm, ['ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t']);
        $norm = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $norm);
        $norm = trim(preg_replace('/\s+/u', ' ', $norm));
        if ($norm === '') continue;
        if (isset($seen[$norm])) continue;
        $seen[$norm] = true;
        $out[] = $it;
    }
    return $out;
}
}

// Render a flat equipment list as a check-mark grid (Encar look).
if (!function_exists('parsing_equipment_report_html')) {
function parsing_equipment_report_html(array $items, string $lang = 'ro'): string {
    $title = ['ro' => 'Dotări', 'ru' => 'Комплектация', 'en' => 'Equipment'][$lang] ?? 'Dotări';
    $none  = ['ro' => 'Fără dotări.', 'ru' => 'Нет опций.', 'en' => 'No equipment.'][$lang] ?? 'Fără dotări.';

    $items = parsing_equipment_dedup($items);

    $css = '<style>'
        . '.er-equip-report{--er-red:#e2001a;--er-line:#eef0f3;}'
        // Card chrome identical to Encar: rounded corners + border + shadow, clipped.
        // Margin handled by the .encar-report-public wrapper (same as Encar).
        . '.er-equip-report.encar-report{background:#fff;border:1px solid #efefef;border-radius:18px;box-shadow:0 10px 30px rgba(20,20,40,.07);overflow:hidden;}'
        // Black card header (like Encar "DOTĂRI"). !important guards it on the public page.
        . '.er-equip-report > .er-head{margin:0 !important;padding:11px 22px !important;font-size:1.05rem !important;font-weight:bold !important;color:#fff !important;text-transform:uppercase;letter-spacing:.4px;background:linear-gradient(135deg,#2b2b2b 0%,#444 100%) !important;display:flex;align-items:center;border:none;width:100%;text-align:left;}'
        . '.er-equip-report .er-body{padding:6px 10px 14px;}'
        . '.er-equip-report .er-equip-pub{padding:10px 4px 4px;column-width:330px;column-gap:28px;}'
        . '.er-equip-report .er-optlist{margin:0;padding:6px 0 0;list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:0 14px;}'
        . '.er-equip-report .er-optlist li{position:relative;padding:3px 0 3px 22px;font-size:.84rem;color:#3a4250;line-height:1.35;break-inside:avoid;}'
        . '.er-equip-report .er-optlist li:before{content:"\\2713";position:absolute;left:0;top:3px;width:15px;height:15px;border-radius:50%;background:#9097a3;color:#fff;font-size:.64rem;font-weight:700;display:flex;align-items:center;justify-content:center;}'
        . '.er-equip-report .er-nodmg{margin:0;padding:14px 18px;font-size:.9rem;color:#6b7280;}'
        . '@media (max-width:600px){.er-equip-report .er-optlist{grid-template-columns:1fr;}.er-equip-report .er-equip-pub{column-width:auto;column-count:1;}}'
        . '</style>';

    $h = $css . '<div class="encar-report er-equip-report open">';
    $h .= '<div class="er-head">' . htmlspecialchars($title) . '</div>';
    $h .= '<div class="er-body">';
    if ($items) {
        $h .= '<div class="er-equip-pub"><ul class="er-optlist">';
        foreach ($items as $it) {
            $h .= '<li>' . htmlspecialchars((string)$it['label']) . '</li>';
        }
        $h .= '</ul></div>';
    } else {
        $h .= '<p class="er-nodmg">' . htmlspecialchars($none) . '</p>';
    }
    $h .= '</div></div>';
    return $h;
}
}

// Full OpenLane report: Condition (damages) + Equipment, from the raw detail
// payload. Static dictionary only (no AI) — safe/fast for the public page; the
// admin caller may AI-translate the equipment list before passing $d.
if (!function_exists('parsing_openlane_report_html')) {
function parsing_openlane_report_html(array $d, string $lang = 'ro', int $carId = 0): string {
    $conditionHtml = parsing_openlane_condition_html($d, $lang);

    $items = [];
    $seen = [];
    foreach (($d['EtgOptionList'] ?? []) as $opt) {
        $name = trim((string)($opt['Name'] ?? ''));
        if ($name === '') continue;
        $name = preg_replace('/^\(Car\)\s*/i', '', $name);
        $parts = explode(' - ', $name, 2);
        $label = count($parts) === 2 ? trim($parts[1]) : trim($parts[0]);
        $label = parsing_openlane_tr_equip($label, $lang);
        $key = mb_strtolower($label, 'UTF-8');
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $items[] = ['label' => $label, 'top' => false];
    }
    usort($items, function ($a, $b) { return strcasecmp($a['label'], $b['label']); });

    return $conditionHtml . parsing_equipment_report_html($items, $lang);
}
}

// Translate an OpenLane equipment group or option (EN) to RO/RU. Unknown phrases
// stay unchanged (safe fallback). Case-insensitive on the whole phrase.
if (!function_exists('parsing_openlane_tr_equip')) {
function parsing_openlane_tr_equip(string $text, string $lang): string {
    if ($lang === 'en' || $text === '') return $text;
    static $dict = null;
    if ($dict === null) {
        $dict = [
            // Groups
            'air conditioning' => ['ro'=>'Climatizare','ru'=>'Кондиционер'],
            'doors' => ['ro'=>'Uși','ru'=>'Двери'],
            'infotainment' => ['ro'=>'Infotainment','ru'=>'Мультимедиа'],
            'lights' => ['ro'=>'Lumini','ru'=>'Освещение'],
            'mirrors / cameras' => ['ro'=>'Oglinzi / Camere','ru'=>'Зеркала / Камеры'],
            'parking aid' => ['ro'=>'Asistență parcare','ru'=>'Помощь при парковке'],
            'safety systems' => ['ro'=>'Sisteme de siguranță','ru'=>'Системы безопасности'],
            'seats' => ['ro'=>'Scaune','ru'=>'Сиденья'],
            'steering wheel' => ['ro'=>'Volan','ru'=>'Руль'],
            'wheels & transmission' => ['ro'=>'Roți și transmisie','ru'=>'Колёса и трансмиссия'],
            'windows' => ['ro'=>'Geamuri','ru'=>'Стёкла'],
            'upholstery' => ['ro'=>'Tapițerie','ru'=>'Обивка'],
            'roof' => ['ro'=>'Plafon','ru'=>'Крыша'],
            'towing equipment' => ['ro'=>'Echipament remorcare','ru'=>'Буксировка'],
            'other' => ['ro'=>'Altele','ru'=>'Прочее'],
            // Options — air conditioning
            'automatic climate control' => ['ro'=>'Climatizare automată','ru'=>'Климат-контроль'],
            'automatic climate control, 2 zones' => ['ro'=>'Climatizare automată, 2 zone','ru'=>'Климат-контроль, 2 зоны'],
            'manual climate control' => ['ro'=>'Climatizare manuală','ru'=>'Ручной климат'],
            'automatic - 2 zones' => ['ro'=>'Automată - 2 zone','ru'=>'Автоматический - 2 зоны'],
            // Doors
            'keyless central door lock' => ['ro'=>'Închidere centralizată keyless','ru'=>'Бесключевой центральный замок'],
            'keyless engine start' => ['ro'=>'Pornire fără cheie','ru'=>'Запуск без ключа'],
            // Infotainment
            'bluetooth' => ['ro'=>'Bluetooth','ru'=>'Bluetooth'],
            'cd player' => ['ro'=>'CD player','ru'=>'CD-проигрыватель'],
            'mp3' => ['ro'=>'MP3','ru'=>'MP3'],
            'navigation system' => ['ro'=>'Sistem de navigație','ru'=>'Навигация'],
            'on board computer' => ['ro'=>'Computer de bord','ru'=>'Бортовой компьютер'],
            'premium sound system' => ['ro'=>'Sistem audio premium','ru'=>'Премиум аудиосистема'],
            'heads-up display' => ['ro'=>'Head-up display','ru'=>'Проекционный дисплей'],
            'digital cockpit' => ['ro'=>'Bord digital','ru'=>'Цифровая панель'],
            // Lights
            'headlights' => ['ro'=>'Faruri','ru'=>'Фары'],
            'daytime running lights' => ['ro'=>'Lumini de zi','ru'=>'Дневные ходовые огни'],
            'fog lights' => ['ro'=>'Proiectoare ceață','ru'=>'Противотуманные фары'],
            'headlight washer system' => ['ro'=>'Spălare faruri','ru'=>'Омыватель фар'],
            'headlights - xenon' => ['ro'=>'Faruri - Xenon','ru'=>'Фары - Ксенон'],
            'headlights - xenon headlights' => ['ro'=>'Faruri - Xenon','ru'=>'Фары - Ксенон'],
            'headlights - led headlights' => ['ro'=>'Faruri - LED','ru'=>'Фары - LED'],
            'headlights - led' => ['ro'=>'Faruri - LED','ru'=>'Фары - LED'],
            'rear led dimming lights' => ['ro'=>'Stopuri LED','ru'=>'Задние LED-фонари'],
            'light sensor' => ['ro'=>'Senzor lumină','ru'=>'Датчик света'],
            // Mirrors / cameras
            'electrically adjustable mirrors' => ['ro'=>'Oglinzi reglabile electric','ru'=>'Электрорегулировка зеркал'],
            'electrical adjustable mirrors' => ['ro'=>'Oglinzi reglabile electric','ru'=>'Электрорегулировка зеркал'],
            'electrically folding mirrors' => ['ro'=>'Oglinzi rabatabile electric','ru'=>'Электроскладывание зеркал'],
            'folding mirrors electric' => ['ro'=>'Oglinzi rabatabile electric','ru'=>'Электроскладывание зеркал'],
            'electrically heated mirrors' => ['ro'=>'Oglinzi încălzite','ru'=>'Обогрев зеркал'],
            'heated mirrors' => ['ro'=>'Oglinzi încălzite','ru'=>'Обогрев зеркал'],
            'rearview camera' => ['ro'=>'Cameră marșarier','ru'=>'Камера заднего вида'],
            // Parking aid
            'front sensors' => ['ro'=>'Senzori față','ru'=>'Передние датчики'],
            'rear sensors' => ['ro'=>'Senzori spate','ru'=>'Задние датчики'],
            'parking assistant' => ['ro'=>'Asistent parcare','ru'=>'Ассистент парковки'],
            // Safety
            'abs' => ['ro'=>'ABS','ru'=>'ABS'],
            'airbags' => ['ro'=>'Airbaguri','ru'=>'Подушки безопасности'],
            'cruise control' => ['ro'=>'Cruise control','ru'=>'Круиз-контроль'],
            'esp' => ['ro'=>'ESP','ru'=>'ESP'],
            'emergency brake assistant' => ['ro'=>'Asistent frânare urgență','ru'=>'Ассистент экстренного торможения'],
            'lane control assistant' => ['ro'=>'Asistent menținere bandă','ru'=>'Ассистент полосы движения'],
            'rain sensor' => ['ro'=>'Senzor ploaie','ru'=>'Датчик дождя'],
            'alarm system' => ['ro'=>'Sistem alarmă','ru'=>'Сигнализация'],
            // Seats
            'isofix' => ['ro'=>'Isofix','ru'=>'Isofix'],
            'sport' => ['ro'=>'Sport','ru'=>'Спорт'],
            'comfort' => ['ro'=>'Confort','ru'=>'Комфорт'],
            'heated front' => ['ro'=>'Încălzite față','ru'=>'Подогрев передних'],
            'ventilated front' => ['ro'=>'Ventilație față','ru'=>'Вентиляция передних'],
            'electrical adjustable driver' => ['ro'=>'Reglare electrică șofer','ru'=>'Электрорегулировка водителя'],
            'electrical adjustable passenger' => ['ro'=>'Reglare electrică pasager','ru'=>'Электрорегулировка пассажира'],
            'electrical memory' => ['ro'=>'Memorie electrică','ru'=>'Электропамять'],
            'upholstery - leather' => ['ro'=>'Tapițerie - Piele','ru'=>'Обивка - Кожа'],
            'upholstery - artificial leather' => ['ro'=>'Tapițerie - Piele ecologică','ru'=>'Обивка - Эко-кожа'],
            'upholstery - cloth' => ['ro'=>'Tapițerie - Textil','ru'=>'Обивка - Ткань'],
            // Steering wheel
            'leather' => ['ro'=>'Piele','ru'=>'Кожа'],
            'heated' => ['ro'=>'Încălzit','ru'=>'С подогревом'],
            'manually adjust' => ['ro'=>'Reglaj manual','ru'=>'Ручная регулировка'],
            'multifunctional' => ['ro'=>'Multifuncțional','ru'=>'Многофункциональный'],
            'shift paddles' => ['ro'=>'Padele schimbător','ru'=>'Подрулевые лепестки'],
            // Wheels & transmission
            'alloy wheels' => ['ro'=>'Jante aliaj','ru'=>'Литые диски'],
            'tire pressure monitoring system' => ['ro'=>'Monitorizare presiune anvelope','ru'=>'Контроль давления в шинах'],
            '4 wheel drive' => ['ro'=>'Tracțiune integrală','ru'=>'Полный привод'],
            'all wheel drive' => ['ro'=>'Tracțiune integrală','ru'=>'Полный привод'],
            // Windows
            'electric front' => ['ro'=>'Electrice față','ru'=>'Электрост. передние'],
            'electric rear' => ['ro'=>'Electrice spate','ru'=>'Электрост. задние'],
            'tinted rear' => ['ro'=>'Geamuri fumurii spate','ru'=>'Тонировка задних'],
            // Other
            'armrest' => ['ro'=>'Cotieră','ru'=>'Подлокотник'],
            'metallic paint' => ['ro'=>'Vopsea metalizată','ru'=>'Металлик'],
            'power steering' => ['ro'=>'Servodirecție','ru'=>'Гидроусилитель руля'],
            'start & stop system' => ['ro'=>'Sistem Start & Stop','ru'=>'Система Start & Stop'],
            'panoramic' => ['ro'=>'Panoramic','ru'=>'Панорамная'],
            'sunroof electric opening' => ['ro'=>'Trapă electrică','ru'=>'Электролюк'],
            'towing hook' => ['ro'=>'Cârlig remorcare','ru'=>'Фаркоп'],
        ];
    }
    $key = mb_strtolower(trim($text), 'UTF-8');
    if (isset($dict[$key][$lang])) return $dict[$key][$lang];
    if (preg_match('/^alloy wheels\s*-\s*(.+)$/i', $text, $m)) {
        $size = trim($m[1]);
        $size = ['ro'=>str_ireplace('or less', 'sau mai mici', $size),
                 'ru'=>str_ireplace('or less', 'или меньше', $size)][$lang] ?? $size;
        $base = ['ro'=>'Jante aliaj','ru'=>'Литые диски'][$lang] ?? 'Alloy wheels';
        return $base . ' - ' . $size;
    }
    return $text;
}
}
