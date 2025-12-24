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
 * @param string $params_html - Full HTML content
 * @return string - HTML block
 */
function getDesktopDescriptionBlock($params_html) {
    if (trim($params_html) == '') {
        return '';
    }
    
    return '
    <div style="clear:both"></div>
    <div class="car-description-block desktop">
        <div class="car-description-content">
            '.$params_html.'
        </div>
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
    $fullDescriptionTitle = $lang == 'ru' ? 'Полное исчерпывающее описание' : ($lang == 'en' ? 'Full description' : 'Descriere completă');
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
