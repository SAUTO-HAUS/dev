# SAUTO Sitemap - Deployment Guide

## Overview
This guide explains how to easily move the sitemap system from development (`testline8392.sauto.md`) to production (`sauto.md`).

## Automatic Environment Detection

The system now uses **automatic environment detection** based on the `HTTP_HOST` server variable:

- **Production**: When `HTTP_HOST` = `www.sauto.md` → Uses production URLs
- **Development**: When `HTTP_HOST` = `www.testline8392.sauto.md` → Uses dev URLs

## Configuration File

All environment-specific settings are centralized in `config.php`:

```php
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
```

## Deployment Steps

### 1. Copy Files to Production
Simply copy the entire `new_sitemap/` directory to the production server.

### 2. No Configuration Changes Needed
The system will automatically detect the production environment and use the correct URLs.

### 3. Verify Environment Detection
After deployment, you can verify the environment detection by checking:
- `demo_client.php` - should show production domain in links
- `sitemap_test_web.php` - should validate against production URLs
- Generated sitemap files - should contain production URLs

## Files Updated for Portability

All these files now use the centralized configuration:

1. **`config.php`** - Central configuration file
2. **`generate_sitemap.php`** - Main sitemap generator
3. **`generate_sitemap_real.php`** - Real data generator
4. **`generate_sitemap_safe_real.php`** - Safe real data generator
5. **`validate_sitemap.php`** - Sitemap validator
6. **`sitemap_test_web.php`** - Web test interface
7. **`safe_sitemap_test.php`** - Safe test script
8. **`demo_validation.php`** - Demo validation script
9. **`demo_client.php`** - Demo client interface

## Testing After Deployment

1. **Access demo page**: `/new_sitemap/demo_client.php`
2. **Run tests**: `/new_sitemap/sitemap_test_web.php`
3. **Check sitemap**: `/sitemap.xml`
4. **Verify URLs**: All URLs should use production domain

## Benefits

✅ **Zero configuration changes** needed when moving to production
✅ **Automatic environment detection**
✅ **Consistent behavior** across environments
✅ **Easy rollback** - same code works in both environments
✅ **No hardcoded domains** anywhere in the code

## Troubleshooting

If the system doesn't detect the environment correctly:

1. Check `$_SERVER['HTTP_HOST']` value
2. Verify web server configuration
3. Check that `config.php` is being loaded correctly

## Manual Override (if needed)

If automatic detection doesn't work, you can manually set the environment in `config.php`:

```php
// Force production environment
$isProduction = true;

// Force development environment  
$isProduction = false;
```

## Summary

The sitemap system is now **100% portable** between development and production environments. Simply copy the files and the system will automatically adapt to the correct environment.
