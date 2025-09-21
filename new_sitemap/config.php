<?php
/**
 * Sitemap Configuration
 * 
 * This file contains environment-specific configuration.
 * Change these values when moving between dev and production.
 */

// Environment detection
$environment = null;

// 1. Allow explicit override via constant
if (defined('SITEMAP_ENV_OVERRIDE') && is_string(SITEMAP_ENV_OVERRIDE)) {
    $environment = strtolower(trim(SITEMAP_ENV_OVERRIDE));
}

// 2. Allow override via environment variable
if ($environment === null) {
    $envFromEnv = getenv('SITEMAP_ENV');
    if (is_string($envFromEnv) && $envFromEnv !== '') {
        $environment = strtolower(trim($envFromEnv));
    }
}

// 3. Allow override via server variable (useful for CLI scripts bootstrapping $_SERVER)
if ($environment === null && isset($_SERVER['SITEMAP_ENV'])) {
    $envFromServer = $_SERVER['SITEMAP_ENV'];
    if (is_string($envFromServer) && $envFromServer !== '') {
        $environment = strtolower(trim($envFromServer));
    }
}

// Fallback to host detection if no override provided
if ($environment === null) {
    $environment = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'www.sauto.md')
        ? 'production'
        : 'development';
}

// Normalise to expected values
if ($environment !== 'production') {
    $environment = 'development';
}

define('SITEMAP_ENV', $environment);

// Domain configuration
if (SITEMAP_ENV === 'production') {
    // Production environment
    define('SITEMAP_BASE_URL', 'https://www.sauto.md');
    define('SITEMAP_DOMAIN', 'www.sauto.md');
} else {
    // Development environment
    define('SITEMAP_BASE_URL', 'https://www.testline8392.sauto.md');
    define('SITEMAP_DOMAIN', 'www.testline8392.sauto.md');
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
