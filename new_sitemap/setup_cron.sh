#!/bin/bash
# SAUTO Sitemap Cron Job Setup Script
# Sets up daily automatic sitemap generation

# Add cron job to run daily at 2:00 AM
CRON_JOB="0 2 * * * /usr/bin/php /path/to/sauto/new_sitemap/generate_sitemap.php >> /path/to/sauto/new_sitemap/cron.log 2>&1"

# Check if cron job already exists
if crontab -l | grep -q "generate_sitemap.php"; then
    echo "Cron job already exists"
else
    # Add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo "Cron job added successfully"
    echo "Sitemap will be generated daily at 2:00 AM"
fi

# Create log directory if it doesn't exist
mkdir -p /path/to/sauto/new_sitemap/logs

echo "Setup complete!"
echo "To verify cron job: crontab -l"
echo "To remove cron job: crontab -e"
