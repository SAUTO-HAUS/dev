<?php
/**
 * SAUTO Sitemap Generator Configuration
 * Configuration file for sitemap generation scripts
 */

/**
 * Get the domain for sitemap generation
 * @return string The domain name
 */
function getSitemapDomain() {
    // Return the production domain for SAUTO
    return 'sauto.md';
}

/**
 * Get the base URL for sitemap generation
 * @return string The full base URL with protocol
 */
function getSitemapBaseUrl() {
    // Return the full base URL for SAUTO
    return 'https://sauto.md';
}

/**
 * Get sitemap configuration settings
 * @return array Configuration array
 */
function getSitemapConfig() {
    return [
        'domain' => getSitemapDomain(),
        'base_url' => getSitemapBaseUrl(),
        'max_urls_per_file' => 7000,
        'languages' => ['ro', 'ru', 'en'],
        'output_dir' => __DIR__ . '/..',
        'backup_dir' => __DIR__ . '/../sitemap_backups',
        'log_file' => 'sitemap_generation.log'
    ];
}

// Environment detection
if (!defined('SITEMAP_ENV')) {
    define('SITEMAP_ENV', 'production');
}

// Set timezone for consistent timestamps
date_default_timezone_set('Europe/Chisinau');
