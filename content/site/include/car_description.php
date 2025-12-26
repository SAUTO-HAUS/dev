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
    
    // Keywords to search for equipment section (RO, RU, EN)
    $keywords = ['Dotări', 'Комплектация', 'Equipment', '🧭 Dotări', '🧭 Комплектация', '🧭 Equipment'];
    
    // Find section by keyword in h2 or h3
    $pattern = '/<h[23][^>]*>([^<]*(?:' . implode('|', array_map('preg_quote', $keywords)) . ')[^<]*)<\/h[23]>/iu';
    
    if (preg_match($pattern, $html, $match, PREG_OFFSET_CAPTURE)) {
        $sectionStart = $match[0][1];
        
        // Find next h2 or h3 after this section
        $afterSection = substr($html, $sectionStart + strlen($match[0][0]));
        if (preg_match('/<h[23][^>]*>/i', $afterSection, $nextMatch, PREG_OFFSET_CAPTURE)) {
            $sectionEnd = $sectionStart + strlen($match[0][0]) + $nextMatch[0][1];
            $equipmentContent = substr($html, $sectionStart, $sectionEnd - $sectionStart);
        } else {
            // No next section - take until end
            $equipmentContent = substr($html, $sectionStart);
            $sectionEnd = strlen($html);
        }
        
        $result['equipment'] = trim($equipmentContent);
        $result['rest'] = trim(substr($html, 0, $sectionStart) . substr($html, $sectionEnd));
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
function getMobileAccordions($parsedHtml, $lang, $fullHtml = '') {

    $html = '<div class="mobile-accordions-wrapper">';
    
    // Full description button title
    $fullDescriptionTitle = $lang == 'ru' ? 'Полное описание' : ($lang == 'en' ? 'Full description' : 'Descriere completă');
    $closeTitle = $lang == 'ru' ? 'Закрыть' : ($lang == 'en' ? 'Close' : 'Închide');
    
    // Equipment block with full description button inside
    if (!empty($parsedHtml['equipment'])) {
        $html .= '
        <div class="car-equipment-block mobile" id="mobile-equipment-block">
            <div class="car-description-content">
                '.$parsedHtml['equipment'].'
            </div>
            
            <!-- Full description button (styled like accordion header) -->
            <div class="accordion-header" onclick="showFullDescriptionMobile()">
                <span>'.$fullDescriptionTitle.'</span>
                <span class="accordion-icon">▼</span>
            </div>
        </div>
        
        <!-- Full description block (hidden by default) -->
        <div class="car-full-description-block mobile" id="mobile-full-description" style="display: none;">
            <div class="car-description-content">
                '.$fullHtml.'
            </div>
            
            <!-- Close button (styled like accordion header) -->
            <div class="accordion-header close-btn" onclick="hideFullDescriptionMobile()">
                <span>'.$closeTitle.'</span>
                <span class="close-icon">✕</span>
            </div>
        </div>';
    } else {
        // If no equipment, show full description directly
        $html .= '
        <div class="car-full-description-block mobile" id="mobile-full-description">
            <div class="car-description-content">
                '.$fullHtml.'
            </div>
        </div>';
    }
    
    $html .= '</div>';
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
