<?php
/**
 * Sitemap Configuration
 * 
 * This file contains environment-specific configuration.
 * Change these values when moving between dev and production.
 */

// Environment detection
$isProduction = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.sauto.md');

// Domain configuration
if ($isProduction) {
    // Production environment
    define('SITEMAP_BASE_URL', 'https://www.sauto.md');
    define('SITEMAP_DOMAIN', 'www.sauto.md');
    define('SITEMAP_ENV', 'production');
} else {
    // Development environment
    define('SITEMAP_BASE_URL', 'https://www.testline8392.sauto.md');
    define('SITEMAP_DOMAIN', 'www.testline8392.sauto.md');
    define('SITEMAP_ENV', 'development');
}

// Common configuration
define('SITEMAP_MAX_URLS_PER_FILE', 7000);
define('SITEMAP_DEFAULT_CHANGEFREQ', 'weekly');
define('SITEMAP_DEFAULT_PRIORITY', '0.5');

// File paths
define('SITEMAP_ROOT_DIR', dirname(__DIR__));
define('SITEMAP_OUTPUT_DIR', SITEMAP_ROOT_DIR);

/**
 * Get the current base URL
 * @return string
 */
function getSitemapBaseUrl() {
    return SITEMAP_BASE_URL;
}

/**
 * Get the current domain
 * @return string
 */
function getSitemapDomain() {
    return SITEMAP_DOMAIN;
}

/**
 * Check if running in production
 * @return bool
 */
function isProductionEnvironment() {
    return SITEMAP_ENV === 'production';
}
?>
