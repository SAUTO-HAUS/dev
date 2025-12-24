<?php

/**
 * Parse HTML and extract equipment section
 * @param string $html - Full HTML content
 * @return array - ['equipment' => extracted equipment HTML, 'rest' => remaining HTML]
 */
function parseEquipmentSection($html) {
    $result = [
        'equipment' => '',
        'rest' => $html
    ];
    
    $startMarker = '<!--SECTION:equipment-->';
    $endMarker = '<!--/SECTION:equipment-->';
    
    $startPos = strpos($html, $startMarker);
    $endPos = strpos($html, $endMarker);
    
    if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
        $equipmentStart = $startPos + strlen($startMarker);
        $equipmentContent = substr($html, $equipmentStart, $endPos - $equipmentStart);
        $result['equipment'] = trim($equipmentContent);
        
        $beforeEquipment = substr($html, 0, $startPos);
        $afterEquipment = substr($html, $endPos + strlen($endMarker));
        $result['rest'] = trim($beforeEquipment . $afterEquipment);
    }
    
    return $result;
}

/**
 * Split HTML after N emoji sections
 * @param string $html - Full HTML content
 * @param int $afterEmoji - Split after this many emojis (default 4)
 * @return array - ['visible' => first part, 'hidden' => rest]
 */
function splitHtmlByEmoji($html, $afterEmoji = 4) {
    // Common section emojis used in descriptions
    $emojiPattern = '/[\x{1F300}-\x{1F9FF}]/u';
    
    preg_match_all($emojiPattern, $html, $matches, PREG_OFFSET_CAPTURE);
    
    if (count($matches[0]) <= $afterEmoji) {
        // Less than or equal to N emojis - show all
        return ['visible' => $html, 'hidden' => ''];
    }
    
    // Find position of the (N+1)th emoji to split before it
    $splitPos = $matches[0][$afterEmoji][1];
    
    $visible = substr($html, 0, $splitPos);
    $hidden = substr($html, $splitPos);
    
    return ['visible' => trim($visible), 'hidden' => trim($hidden)];
}

/**
 * Generate desktop description block HTML
 * @param string $params_html - Full HTML content
 * @param string $lang - Current language code
 * @return string - HTML block
 */
function getDesktopDescriptionBlock($params_html, $lang = 'ro') {
    if (trim($params_html) == '') {
        return '';
    }
    
    $parts = splitHtmlByEmoji($params_html, 4);
    
    // Button text in 3 languages
    $btnText = [
        'ro' => 'Vezi toată descrierea',
        'ru' => 'Показать всё описание',
        'en' => 'Show full description'
    ];
    $btnHideText = [
        'ro' => 'Ascunde',
        'ru' => 'Скрыть',
        'en' => 'Hide'
    ];
    
    $showText = isset($btnText[$lang]) ? $btnText[$lang] : $btnText['ro'];
    $hideText = isset($btnHideText[$lang]) ? $btnHideText[$lang] : $btnHideText['ro'];
    
    $html = '
    <div style="clear:both"></div>
    <div class="car-description-block desktop">
        <div class="car-description-content">
            '.$parts['visible'].'
        </div>';
    
    if (!empty($parts['hidden'])) {
        $html .= '
        <div class="car-description-hidden" style="display:none;">
            <div class="car-description-content">
                '.$parts['hidden'].'
            </div>
        </div>
        <button class="btn-show-full-description" onclick="toggleFullDescription(this)" data-show="'.$showText.'" data-hide="'.$hideText.'">
            '.$showText.'
        </button>';
    }
    
    $html .= '
    </div>';
    
    return $html;
}

/**
 * Generate mobile accordion blocks HTML
 * @param array $parsedHtml - Parsed HTML with 'equipment' and 'rest' keys
 * @param string $lang - Current language code
 * @return string - HTML accordions
 */
function getMobileAccordions($parsedHtml, $lang) {
    $html = '';
    
    // Equipment accordion (only if section exists)
    if (!empty($parsedHtml['equipment'])) {
        $html .= '
        <div class="car-accordion mobile">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <span>Комплектация</span>
                <span class="accordion-icon">▼</span>
            </div>
            <div class="accordion-content">
                <div class="car-description-content">
                    '.$parsedHtml['equipment'].'
                </div>
            </div>
        </div>';
    }
    
    // Full description accordion
    $fullDescriptionTitle = $lang == 'ru' ? 'Полное описание' : ($lang == 'en' ? 'Full description' : 'Descriere completă');
    $html .= '
    <div class="car-accordion mobile">
        <div class="accordion-header" onclick="toggleAccordion(this)">
            <span>'.$fullDescriptionTitle.'</span>
            <span class="accordion-icon">▼</span>
        </div>
        <div class="accordion-content">
            <div class="car-description-content">
                '.$parsedHtml['rest'].'
            </div>
        </div>
    </div>';
    
    return $html;
}

/**
 * Generate desktop button that scrolls to description
 * @param string $params_html - HTML content
 * @param string $buttonText - Button text
 * @return string - Button HTML
 */
function getDesktopDescriptionButton($params_html, $buttonText) {
    if (trim($params_html) == '') {
        return '';
    }
    
    return ' <div class="btn_params desktop" onclick="document.querySelector(\'.car-description-block\').scrollIntoView({behavior: \'smooth\'})"> '.$buttonText.' </div> ';
}
