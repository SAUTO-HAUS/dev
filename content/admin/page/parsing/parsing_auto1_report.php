<?php
// Auto1 vehicle report: "Condition" (damage diagram + list) and equipment.
//
// Auto1 returns damages as stable translation KEYS, not text:
//   {subSectionValue: "damage-body-left",
//    parts: [{partKey: "global.damages.front-left-door",
//             descriptionKey: "global.damages.dent",
//             severities: ["global.damages.severities.less-than-5-cm"],
//             photos: [...]}]}
// and Auto1 publishes its own RO/RU bundles, so every label here is THEIR official
// wording — no hand-written dictionary and no AI (same public-safe policy as the
// OpenLane report). console/auto1_taxonomy_dump.php refreshes the dictionary
// weekly into Adapters/auto1_damage_dict.json.
//
// Equipment: only the GROUP names are translatable ("Heated seats" → "Incalzire
// scaune"). The per-item descriptions come as free text in the car's own country
// language (French on a FR car), so they are dropped rather than shown untranslated.

if (!function_exists('parsing_auto1_dict')) {
// Load the key → {en,ro,ru} dictionary once per request.
function parsing_auto1_dict(): array {
    static $dict = null;
    if ($dict !== null) return $dict;
    $file = __DIR__ . '/../../../../App/Services/Parsing/Adapters/auto1_damage_dict.json';
    $dict = [];
    if (is_file($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data) && !empty($data['keys'])) $dict = $data['keys'];
    }
    return $dict;
}
}

if (!function_exists('parsing_auto1_ucfirst')) {
function parsing_auto1_ucfirst(string $s): string {
    if ($s === '') return $s;
    $first = mb_substr($s, 0, 1, 'UTF-8');
    $rest  = mb_substr($s, 1, null, 'UTF-8');
    if ($rest !== '' && preg_match('/^\p{Lu}/u', $rest)) return $s;
    return mb_strtoupper($first, 'UTF-8') . $rest;
}
}

if (!function_exists('parsing_auto1_tr')) {
// Translate one Auto1 key. Falls back: asked lang → EN → the key's own tail
// (CamelCase/dashes split into words) so nothing ever renders as a raw key.
function parsing_auto1_tr(?string $key, string $lang = 'ro'): string {
    $key = trim((string)$key);
    if ($key === '' || $key === 'global.damages.null') return '';
    $dict = parsing_auto1_dict();
    $entry = $dict[$key] ?? null;
    if ($entry) {
        foreach ([$lang, 'en'] as $l) {
            if (!empty($entry[$l])) return parsing_auto1_ucfirst($entry[$l]);
        }
    }
    // Unknown key → humanise its last segment ("movableBody-frontDoor" → "Front door").
    $tail = $key;
    foreach (['global.damages.', 'global.equipment_groups.', 'global.car_details.'] as $p) {
        if (strpos($tail, $p) === 0) { $tail = substr($tail, strlen($p)); break; }
    }
    $tail = preg_replace('/^damage-(body|interior|motor|underbody|general)-/', '', $tail);
    $tail = preg_replace('/([a-z])([A-Z])/', '$1 $2', $tail);
    $tail = str_replace(['-', '_'], ' ', $tail);
    return ucfirst(trim(preg_replace('/\s+/', ' ', $tail)));
}
}

if (!function_exists('parsing_auto1_damage_slot')) {
// Map an Auto1 part key to one of the SVG body slots the Encar/OpenLane diagram
// draws. Auto1 ships TWO key styles — short ("front-left-door") and hierarchical
// ("damage-body-left-movableBody-frontDoor") — so this reads the side/end/part
// out of the key instead of listing every one of its 637 part names.
// Returns null for anything the silhouette can't show (rims, interior, engine),
// which the caller still lists as text.
function parsing_auto1_damage_slot(string $partKey): ?string {
    $k = strtolower($partKey);
    foreach (['global.damages.'] as $p) {
        if (strpos($k, $p) === 0) { $k = substr($k, strlen($p)); break; }
    }
    // Interior/engine/underbody damage must never colour an exterior panel
    // ("...upholstery-doorInnerPannelRearLeft" mentions door+rear+left).
    if (preg_match('/^damage-(interior|motor|underbody|general)/', $k)) return null;

    $left  = strpos($k, 'left') !== false;
    $right = strpos($k, 'right') !== false;
    $rear  = (strpos($k, 'rear') !== false || strpos($k, 'back') !== false);

    if (strpos($k, 'roof') !== false)     return 'ROOF_PANEL';
    if (strpos($k, 'hood') !== false || strpos($k, 'bonnet') !== false) return 'HOOD';
    if (strpos($k, 'tailgate') !== false || strpos($k, 'trunklid') !== false
        || strpos($k, 'rearpanel') !== false) return 'TRUNK_LID';
    if (strpos($k, 'windshield') !== false) return 'HOOD';       // front glass → nose
    if (strpos($k, 'bumper') !== false)   return $rear ? 'TRUNK_LID' : 'HOOD';
    if (strpos($k, 'door') !== false) {
        if ($left)  return $rear ? 'BACK_DOOR_LEFT'  : 'FRONT_DOOR_LEFT';
        if ($right) return $rear ? 'BACK_DOOR_RIGHT' : 'FRONT_DOOR_RIGHT';
        return null;
    }
    if (strpos($k, 'fender') !== false || strpos($k, 'wing') !== false
        || strpos($k, 'quarter') !== false) {
        if ($left)  return $rear ? 'QUARTER_PANEL_LEFT'  : 'FRONT_FENDER_LEFT';
        if ($right) return $rear ? 'QUARTER_PANEL_RIGHT' : 'FRONT_FENDER_RIGHT';
        return null;
    }
    return null; // rims, mirrors, lights, glass sides…
}
}

if (!function_exists('parsing_auto1_damage_letter')) {
// Auto1 damage type → the Encar diagram's severity letter, so a panel is coloured
// with the same legend the other sources use:
//   X = replaced/broken, C = corrosion, T = dent/deformation, A = scratch/chip.
function parsing_auto1_damage_letter(string $descKey): string {
    $k = strtolower($descKey);
    if (strpos($k, 'broken') !== false || strpos($k, 'missing') !== false
        || strpos($k, 'torn') !== false) return 'X';
    if (strpos($k, 'rust') !== false || strpos($k, 'corros') !== false) return 'C';
    if (strpos($k, 'dent') !== false || strpos($k, 'deform') !== false) return 'T';
    return 'A'; // scratch, chip, stone chip, …
}
}

if (!function_exists('parsing_auto1_condition_html')) {
// Build the "Condition" block: the shared SVG silhouette with damaged panels
// coloured, plus a per-section damage list. $damages is meta.damages from
// car-details-view; $accidents is meta.accidents; $paint is response.paint.
function parsing_auto1_condition_html(array $damages, array $accidents, array $paint, string $lang = 'ro'): string {
    // The diagram lives with the Encar report; include by ABSOLUTE path so this
    // also works when called from /console (relative _ADM_PAGE fails there).
    include_once __DIR__ . '/parsing_report.php';

    $T = [
        'ro' => ['dmg' => 'Daune constatate', 'no_dmg' => 'Fără daune raportate',
                 'acc_no' => 'Fără indicii de accident', 'acc_yes' => 'Accident raportat',
                 'paint' => 'Grosime vopsea', 'zone' => 'Zonă', 'part' => 'Piesă', 'type' => 'Tip'],
        'ru' => ['dmg' => 'Повреждения', 'no_dmg' => 'Повреждений не заявлено',
                 'acc_no' => 'Признаков ДТП нет', 'acc_yes' => 'Заявлено ДТП',
                 'paint' => 'Толщина краски', 'zone' => 'Зона', 'part' => 'Деталь', 'type' => 'Тип'],
        'en' => ['dmg' => 'Reported damages', 'no_dmg' => 'No damage reported',
                 'acc_no' => 'No indication of accident', 'acc_yes' => 'Accident reported',
                 'paint' => 'Paint thickness', 'zone' => 'Area', 'part' => 'Part', 'type' => 'Type'],
    ];
    $t = $T[$lang] ?? $T['ro'];

    // Feed the diagram: one entry per damaged panel, worst finding wins (the
    // diagram itself ranks them).
    $diagItems = [];
    $rows = [];
    foreach ($damages as $section) {
        $sectionKey = (string)($section['subSectionValueKey'] ?? '');
        $sectionName = parsing_auto1_tr($sectionKey, $lang)
            ?: parsing_auto1_tr('global.damages.sub_sections.' . ($section['subSectionValue'] ?? ''), $lang);
        foreach (($section['parts'] ?? []) as $p) {
            $partKey = (string)($p['partKey'] ?? '');
            $descKey = (string)($p['descriptionKey'] ?? '');
            if ($partKey === '') continue;

            $slot = parsing_auto1_damage_slot($partKey);
            if ($slot) {
                $diagItems[] = ['name' => $slot, 'result' => parsing_auto1_damage_letter($descKey)];
            }
            $sev = [];
            foreach (($p['severities'] ?? []) as $s) {
                $sv = parsing_auto1_tr((string)$s, $lang);
                if ($sv !== '') $sev[] = $sv;
            }
            $rows[] = [
                'zone' => $sectionName,
                'part' => parsing_auto1_tr($partKey, $lang),
                'type' => trim(parsing_auto1_tr($descKey, $lang) . ($sev ? ' (' . implode(', ', $sev) . ')' : '')),
            ];
        }
    }

    // The card gives its children no padding of their own — every block is an
    // ".er-section" that brings its own (same as the Encar/OpenLane reports), so
    // build these as sections instead of bare divs.
    $html = '';

    // Shared silhouette (same legend/colours as Encar & OpenLane). It already is
    // an .er-section, so it needs no wrapper.
    if (function_exists('parsing_report_body_diagram')) {
        $html .= parsing_report_body_diagram($diagItems, [], $lang, count($damages));
    }

    // Damages section: accident flag + the per-part table.
    $hasAccident = false;
    foreach ($accidents as $a) { if (!empty($a['hasAccident'])) { $hasAccident = true; break; } }

    $inner = '<div class="a1-acc ' . ($hasAccident ? 'a1-acc-bad' : 'a1-acc-ok') . '">'
        . htmlspecialchars($hasAccident ? $t['acc_yes'] : $t['acc_no']) . '</div>';

    if ($rows) {
        $inner .= '<table class="a1-dmg"><thead><tr>'
            . '<th>' . htmlspecialchars($t['zone']) . '</th>'
            . '<th>' . htmlspecialchars($t['part']) . '</th>'
            . '<th>' . htmlspecialchars($t['type']) . '</th>'
            . '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $inner .= '<tr><td>' . htmlspecialchars($r['zone']) . '</td>'
                . '<td>' . htmlspecialchars($r['part']) . '</td>'
                . '<td>' . htmlspecialchars($r['type']) . '</td></tr>';
        }
        $inner .= '</tbody></table>';
    } else {
        $inner .= '<div class="a1-nodmg">' . htmlspecialchars($t['no_dmg']) . '</div>';
    }

    // Paint thickness — Auto1's API exposes only hood + both front doors.
    $paintMap = [
        'paintHood'          => ['ro' => 'Capotă',        'ru' => 'Капот',            'en' => 'Hood'],
        'paintDoorDriver'    => ['ro' => 'Ușă șofer',     'ru' => 'Дверь водителя',   'en' => 'Driver door'],
        'paintDoorPassenger' => ['ro' => 'Ușă pasager',   'ru' => 'Дверь пассажира',  'en' => 'Passenger door'],
    ];
    $paintCells = '';
    foreach ($paintMap as $k => $labels) {
        $v = $paint[$k] ?? null;
        if ($v === null || $v === '' || !is_numeric($v)) continue;
        $paintCells .= '<span class="a1-paint-item"><b>' . htmlspecialchars($labels[$lang] ?? $labels['en'])
            . ':</b> ' . (int)$v . ' μm</span>';
    }
    if ($paintCells !== '') {
        $inner .= '<div class="a1-paint"><span class="a1-paint-ttl">'
            . htmlspecialchars($t['paint']) . '</span>' . $paintCells . '</div>';
    }

    $html .= '<div class="er-section"><h4>' . htmlspecialchars($t['dmg']) . '</h4>'
        . '<div class="a1-pad">' . $inner . '</div></div>';

    return $html;
}
}

if (!function_exists('parsing_auto1_equipment_html')) {
// Equipment as a list of GROUP names (translated). The per-item descriptions Auto1
// returns are free text in the car's country language (French on a FR car), so they
// are intentionally not shown.
function parsing_auto1_equipment_html(array $groups, string $lang = 'ro', bool $withHeading = true): string {
    $titles = ['ro' => 'Dotări', 'ru' => 'Комплектация', 'en' => 'Equipment'];

    // Auto1 uses a handful of groups its OWN bundle has no entry for, which would
    // otherwise show up in English inside a Romanian list. Fill those in here.
    static $extra = [
        'exterior mirror'     => ['ro' => 'Oglinzi exterioare',   'ru' => 'Наружные зеркала',    'en' => 'Exterior mirrors'],
        'interior upholstery' => ['ro' => 'Tapițerie',            'ru' => 'Обивка салона',       'en' => 'Interior upholstery'],
        'entertainment system'=> ['ro' => 'Sistem multimedia',    'ru' => 'Мультимедиа',         'en' => 'Entertainment system'],
        'lm rims'             => ['ro' => 'Jante aliaj',          'ru' => 'Легкосплавные диски', 'en' => 'Alloy rims'],
        '360 cam'             => ['ro' => 'Cameră 360°',          'ru' => 'Камера 360°',         'en' => '360° camera'],
        'lumbar support'      => ['ro' => 'Suport lombar',        'ru' => 'Поясничная поддержка','en' => 'Lumbar support'],
        'power outlet'        => ['ro' => 'Priză 12V',            'ru' => 'Розетка 12В',         'en' => 'Power outlet'],
    ];
    // Groups that carry no information for a buyer.
    static $skip = ['cosmetic', 'unclassified', 'neclasificat', 'neclassified'];

    $names = [];
    foreach ($groups as $g) {
        $raw = trim((string)($g['group'] ?? ''));
        if ($raw === '') continue;
        $bare = preg_replace('/^global\.equipment_groups\./', '', $raw);
        $bareLc = mb_strtolower($bare, 'UTF-8');
        if (in_array($bareLc, $skip, true)) continue;

        $key = strpos($raw, 'global.') === 0 ? $raw : 'global.equipment_groups.' . $raw;
        $label = '';
        // Auto1's own wording first; our fill-ins only when it has none.
        if (isset(parsing_auto1_dict()[$key])) {
            $label = parsing_auto1_tr($key, $lang);
        } elseif (isset($extra[$bareLc])) {
            $label = $extra[$bareLc][$lang] ?? $extra[$bareLc]['en'];
        } else {
            $label = parsing_auto1_tr($key, $lang);
        }
        if ($label === '') continue;
        $lc = mb_strtolower($label, 'UTF-8');
        if (in_array($lc, $skip, true)) continue;
        $names[$lc] = $label; // de-dupe by visible label
    }
    if (!$names) return '';
    // An .er-section so it gets the card's own heading style (red bar + separator)
    // and padding, exactly like the OpenLane equipment block. $withHeading=false
    // drops the inner <h4> for the equipment-only card, whose own header already
    // reads "Dotări" (same rule the Encar report uses on the public page).
    $html = '<div class="er-section">'
        . ($withHeading ? '<h4>' . htmlspecialchars($titles[$lang] ?? $titles['ro']) . '</h4>' : '')
        . '<div class="a1-pad"><ul class="a1-equip-list">';
    foreach ($names as $n) {
        $html .= '<li>' . htmlspecialchars($n) . '</li>';
    }
    return $html . '</ul></div></div>';
}
}

if (!function_exists('parsing_auto1_report_css')) {
// The report is also printed on the PUBLIC car page, which does not load the
// admin parsing.css — so ship the styles with the markup (same approach as the
// Encar/OpenLane reports). Emitted once per render; duplicate <style> blocks are
// harmless if a page ever shows two reports.
function parsing_auto1_report_css(): string {
    // Only the Auto1-specific bits live here. The card/diagram styling comes from
    // parsing_report_diagram_css() (shipped next to the diagram itself), so the
    // report looks exactly like the Encar/OpenLane ones instead of drifting.
    return '<style>'
        // Section body padding — matches .erd-wrap so every block in the card
        // lines up on the same rhythm.
        . '.er-dmg-report .a1-pad{padding:16px 18px 18px;}'
        . '.er-dmg-report .a1-acc{display:inline-block;margin:0;padding:5px 12px;border-radius:14px;font-size:.85rem;font-weight:600;}'
        . '.er-dmg-report .a1-acc-ok{background:#e7f7ef;color:#059669;}'
        . '.er-dmg-report .a1-acc-bad{background:#fdeaec;color:#a30015;}'
        . '.er-dmg-report .a1-dmg{width:100%;border-collapse:collapse;margin:14px 0 0;font-size:.85rem;}'
        . '.er-dmg-report .a1-dmg th,.er-dmg-report .a1-dmg td{padding:7px 10px;border-bottom:1px solid var(--er-line);text-align:left;vertical-align:top;}'
        . '.er-dmg-report .a1-dmg th{background:#fbfcfd;font-weight:600;color:#5a6270;white-space:nowrap;}'
        . '.er-dmg-report .a1-dmg td:first-child{color:#858c99;white-space:nowrap;}'
        . '.er-dmg-report .a1-nodmg{margin-top:12px;color:#059669;font-size:.88rem;}'
        . '.er-dmg-report .a1-paint{display:flex;flex-wrap:wrap;align-items:center;gap:8px 18px;margin:14px 0 0;padding:10px 12px;background:#f7f9fb;border-radius:8px;font-size:.85rem;}'
        . '.er-dmg-report .a1-paint-ttl{font-weight:700;color:#5a6270;}'
        . '.er-dmg-report .a1-paint-item b{font-weight:600;color:#858c99;}'
        . '.er-dmg-report .a1-equip-list{list-style:none;margin:0;padding:0;columns:2;column-gap:28px;font-size:.84rem;}'
        . '.er-dmg-report .a1-equip-list li{break-inside:avoid;position:relative;padding:3px 0 3px 22px;color:#3a4250;line-height:1.35;}'
        . '.er-dmg-report .a1-equip-list li:before{content:"\\2713";position:absolute;left:0;top:3px;width:15px;height:15px;border-radius:50%;background:#9097a3;color:#fff;font-size:.64rem;font-weight:700;display:flex;align-items:center;justify-content:center;}'
        . '@media (max-width:600px){.er-dmg-report .a1-equip-list{columns:1;}.er-dmg-report .a1-dmg{font-size:.8rem;}.er-dmg-report .a1-dmg th,.er-dmg-report .a1-dmg td{padding:6px;}.er-dmg-report .a1-pad{padding:14px;}}'
        . '</style>';
}
}

if (!function_exists('parsing_auto1_report_html')) {
// Render the report. $d is the merged payload the adapter returns:
// ['damages'=>…, 'accidents'=>…, 'paint'=>…, 'equipment'=>…].
// Dictionary-only (no AI, no live call), so it is safe on the public page.
//
// $equipmentOnly=true → EQUIPMENT ONLY, no damages/diagram/paint. That is what the
// PUBLIC car page gets (product decision: buyers see the equipment list, the damage
// report stays internal). The admin modal calls this with the default and keeps the
// whole thing. ParsingPublisher::bakeAuto1Report bakes the public variant.
function parsing_auto1_report_html(array $d, string $lang = 'ro', bool $equipmentOnly = false): string {
    $body  = $equipmentOnly ? '' : parsing_auto1_condition_html(
        $d['damages'] ?? [], $d['accidents'] ?? [], $d['paint'] ?? [], $lang
    );
    // Equipment-only: the card header already says "Dotări", so drop the inner one.
    $equip = parsing_auto1_equipment_html($d['equipment'] ?? [], $lang, !$equipmentOnly);
    if (trim($body) === '' && trim($equip) === '') return '';

    // Same card shell the OpenLane/Encar reports use, so every source renders as one
    // consistent block. Header follows the content: "Istoric" for the full report,
    // "Dotări" when it carries equipment alone.
    $head = $equipmentOnly
        ? (['ro' => 'Dotări', 'ru' => 'Комплектация', 'en' => 'Equipment'][$lang] ?? 'Dotări')
        : (['ro' => 'Istoric', 'ru' => 'История', 'en' => 'History'][$lang] ?? 'Istoric');
    // Always ship the shared block: besides the SVG rules it styles the CARD itself
    // (.er-dmg-report shell, .er-head, .er-section headings), which the
    // equipment-only variant needs just as much.
    $css = (function_exists('parsing_report_diagram_css') ? parsing_report_diagram_css() : '')
         . parsing_auto1_report_css();

    return $css
        . '<div class="encar-report er-dmg-report open">'
        . '<div class="er-head">' . htmlspecialchars($head) . '</div>'
        . '<div class="er-body"><div class="er-doc">'
        . $body . $equip
        . '</div></div></div>';
}
}
