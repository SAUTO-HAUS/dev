<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Renders the country dialling-code picker for the B2B phone field.
 *
 * Data comes from App\Services\B2b\B2bCountries, the same list the server
 * validates against. Flags are the SVGs in media/images/flags, named by
 * lowercase ISO 3166-1 alpha-2 code.
 */

use App\Services\B2b\B2bCountries;

if (!function_exists('b2b_dial_picker')) {

    function b2b_dial_picker(string $selected = ''): string
    {
        $selected = $selected !== '' ? $selected : B2bCountries::DEFAULT_ISO;
        $cur = B2bCountries::byIso($selected) ?? B2bCountries::all()[0];

        $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        $items = '';
        foreach (B2bCountries::all() as $c) {
            // min/max travel to the browser so it can validate before submitting.
            $items .= '<li data-iso="'.$esc($c['iso']).'" data-dial="'.$esc($c['dial']).'"'
                    . ' data-min="'.(int)$c['min'].'" data-max="'.(int)$c['max'].'"'
                    . ' data-name="'.$esc($c['name']).'"'
                    . ($c['iso'] === $cur['iso'] ? ' class="is-active"' : '').'>'
                    . '<img src="/media/images/flags/'.$esc($c['iso']).'.svg" alt="" width="20" height="20" loading="lazy" />'
                    . '<span class="b2b-cc__name">'.$esc($c['name']).'</span>'
                    . '<em class="b2b-cc__dial">'.$esc($c['dial']).'</em></li>';
        }

        return '
<div class="b2b-cc" data-dial="'.$esc($cur['dial']).'" data-min="'.(int)$cur['min'].'" data-max="'.(int)$cur['max'].'" data-name="'.$esc($cur['name']).'">
    <button type="button" class="b2b-cc__btn" aria-haspopup="listbox" aria-expanded="false">
        <img class="b2b-cc__flag" src="/media/images/flags/'.$esc($cur['iso']).'.svg" alt="'.$esc($cur['name']).'" width="20" height="20" />
        <span class="b2b-cc__code">'.$esc($cur['dial']).'</span>
        <span class="b2b-cc__arr"></span>
    </button>
    <ul class="b2b-cc__list" role="listbox">'.$items.'</ul>
</div>';
    }
}
