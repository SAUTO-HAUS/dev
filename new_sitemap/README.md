# SAUTO Sitemap Generator System

## Overview

This is an automated sitemap generation system for sauto.md that follows the official XML Sitemap Protocol specification. The system generates a sitemap index file referencing multiple sub-files with a maximum of 7000 URLs each, includes multilingual hreflang support, and provides comprehensive validation and error handling.

## File Structure

```
new_sitemap/
├── sitemap.xml                 # Main sitemap index file
├── sitemap-1.xml              # Sub-sitemap file 1
├── sitemap-2.xml              # Sub-sitemap file 2
├── sitemap-3.xml              # Sub-sitemap file 3
├── generate_sitemap.php       # Main generation script
├── validate_sitemap.php       # Validation script
├── setup_cron.sh             # Cron job setup script
├── sitemap_generation.log    # Generation log file
├── validation.log            # Validation log file
└── README.md                 # This documentation
```

## Technical Specifications

### XML Structure
- **Encoding**: UTF-8
- **MIME Type**: application/xml
- **Main Index**: `<sitemapindex>` with namespace `http://www.sitemaps.org/schemas/sitemap/0.9`
- **Sub-files**: `<urlset>` with sitemap and xhtml namespaces
- **Maximum URLs per sub-file**: 7000 (strict limit)

### URL Requirements
Each `<url>` entry contains:
- `<loc>`: Absolute HTTPS URL (mandatory)
- `<lastmod>`: Date in YYYY-MM-DD format (mandatory)
- `<changefreq>`: Update frequency based on content type
- `<priority>`: Calculated priority (0.0-1.0)
- `<xhtml:link>`: Multilingual hreflang tags for available translations

### Priority Calculation Rules

#### Cars
- **In stock, ≤30 days**: 1.0
- **In stock, 31-60 days**: 0.9
- **In stock, 61-120 days**: 0.7
- **In stock, >120 days**: 0.6
- **Out of stock**: 0.2

#### Tires
- **All tires**: 0.2-0.3

#### Static Pages
- **Useful pages**: 0.5
- **Legal pages**: 0.3

### Change Frequency Rules
- **Cars**: daily
- **Tires**: weekly
- **Static pages**: monthly

### Multilingual Support
- **Languages**: Romanian (ro), Russian (ru), English (en)
- **Implementation**: `<xhtml:link rel="alternate" hreflang="..." href="...">`
- **Rule**: Only include existing translations, no broken links

## Scripts

### 1. generate_sitemap.php
Main generation script that:
- Fetches all pages from database/API
- Filters pages (HTTP 200, not deleted, canonical URLs)
- Calculates priorities and change frequencies
- Generates mixed-content sub-files (max 7000 URLs each)
- Creates main index file
- Validates all generated files
- Logs all operations

**Usage:**
```bash
php generate_sitemap.php
```

### 2. validate_sitemap.php
Validation script that checks:
- XML structure and syntax
- Required namespaces and attributes
- URL format and uniqueness
- Date format validation
- Priority and changefreq values
- Hreflang link correctness
- File size limits (7000 URLs max)

**Usage:**
```bash
php validate_sitemap.php
```

### 3. setup_cron.sh
Sets up daily automatic generation at 2:00 AM server time.

**Usage:**
```bash
chmod +x setup_cron.sh
./setup_cron.sh
```

## Content Filtering Rules

### Included Pages
- ✅ HTTP 200 status pages
- ✅ Active/in-stock items
- ✅ Canonical URLs only
- ✅ Pages allowed for indexing
- ✅ Existing translations only

### Excluded Pages
- ❌ Deleted items
- ❌ Archived content
- ❌ Non-canonical URLs (with parameters)
- ❌ Pages blocked from indexing
- ❌ Broken or inaccessible pages
- ❌ Duplicate URLs

## Automation Features

### Daily Generation
- **Schedule**: 2:00 AM server time
- **Method**: Cron job
- **Logging**: All operations logged with timestamps
- **Error Handling**: Fallback to previous valid version on failure

### Validation
- **Automatic**: After each generation
- **Comprehensive**: XML structure, content rules, uniqueness
- **Logging**: Detailed validation results

### Error Handling
- **Graceful Degradation**: Keep previous valid files on error
- **Detailed Logging**: Timestamps, error descriptions
- **No Empty Files**: Never generate empty or invalid sitemaps

## Implementation Steps

1. **Database Integration**: Connect to actual data sources (cars, tires, static pages)
2. **URL Accessibility Check**: Implement HTTP status verification
3. **Translation Detection**: Add logic to detect available language versions
4. **Cron Job Setup**: Configure server cron job for daily execution
5. **Monitoring**: Set up log monitoring and alerts

## Testing

### Manual Testing
```bash
# Generate sitemap
php generate_sitemap.php

# Validate generated files
php validate_sitemap.php

# Check logs
tail -f sitemap_generation.log
tail -f validation.log
```

### Validation Checklist
- [ ] XML files are well-formed
- [ ] All required tags present
- [ ] URLs are unique and accessible
- [ ] Priorities within 0.0-1.0 range
- [ ] Dates in YYYY-MM-DD format
- [ ] Hreflang links are valid
- [ ] File size limits respected (≤7000 URLs)
- [ ] Mixed content structure maintained

## Production Deployment

1. **Database Connection**: Update database connection parameters
2. **File Paths**: Adjust absolute paths in scripts
3. **Cron Job**: Set up server cron job
4. **Permissions**: Ensure write permissions for log files
5. **Monitoring**: Configure log monitoring
6. **Backup**: Set up backup strategy for sitemap files

## Maintenance

### Regular Tasks
- Monitor generation logs for errors
- Verify sitemap accessibility via robots.txt
- Check Google Search Console for sitemap status
- Review and update priority calculation rules as needed

### Troubleshooting
- **Generation Fails**: Check database connectivity and permissions
- **Validation Errors**: Review XML structure and content rules
- **Missing URLs**: Verify filtering logic and data sources
- **Performance Issues**: Consider database query optimization

## Compliance

This system fully complies with:
- XML Sitemap Protocol 0.9
- Google Sitemap Guidelines
- Multilingual SEO best practices
- Web accessibility standards

## Support

For technical issues or modifications, refer to the generation and validation logs, or contact the development team with specific error messages and timestamps.
