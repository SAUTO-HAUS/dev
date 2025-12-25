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
 * Generate desktop description block HTML
 * Uses CSS max-height for limiting visible content
 * @param string $pageType - 'stock' for cars.php, 'order' for ordercars.php
 */
function getDesktopDescriptionBlock($params_html, $lang = 'ro', $pageType = 'order') {
    if (trim($params_html) == '') {
        return '';
    }
    
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
    
    $extraClass = ($pageType == 'stock') ? ' stock' : '';
    
    return '
    <div style="clear:both"></div>
    <div class="car-description-block desktop'.$extraClass.'">
        <div class="car-description-content">
            '.$params_html.'
        </div>
        <div class="desc-gradient"></div>
        <button class="btn-show-full-description" onclick="toggleFullDescription(this)" data-show="'.$showText.'" data-hide="'.$hideText.'">
            '.$showText.'
        </button>
    </div>';
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
