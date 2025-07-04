<?php defined('_DOIT') or die('Restricted access');

// Include Router class
require_once(_DEFAULT . '/Router.php');

/**
 * Routes for Credit Page
 * 
 * Simple routing system for the redesigned credit page
 */

// Credit page - NEW DESIGN
Router::get('/{lang}/credit', function($lang) {
    include(_SITE_PAGE . '/new_pages/credit.php');
}, 'credit');

/**
 * Helper functions for credit routes
 */

/**
 * Get current language from route
 */
function getCurrentLanguage() {
    $params = Router::getParameters();
    return $params[0] ?? 'ro';
}

/**
 * Generate localized URL for credit page
 */
function creditRoute($lang = null) {
    if ($lang === null) {
        $lang = $_COOKIE['lang'] ?? 'ro';
    }
    
    return Router::route('credit', ['lang' => $lang]);
}

/**
 * Check if current route is credit page
 */
function isCreditPage() {
    $currentRoute = Router::getCurrentRoute();
    return $currentRoute && $currentRoute['name'] === 'credit';
}
