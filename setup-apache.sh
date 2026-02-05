#!/bin/bash

# Laravel Apache Setup Script
# Run this script with: sudo bash setup-apache.sh

echo "Setting up Apache for Laravel..."

# Copy virtual host configuration
cp /home/hp/code/my-laravel-app/my-laravel-app.conf /etc/apache2/sites-available/my-laravel-app.conf
echo "✓ Virtual host configuration copied"

# Add domain to hosts file if not already present
if ! grep -q "my-laravel-app.test" /etc/hosts; then
    echo "127.0.0.1 my-laravel-app.test" >> /etc/hosts
    echo "✓ Added my-laravel-app.test to /etc/hosts"
else
    echo "✓ my-laravel-app.test already in /etc/hosts"
fi

# Enable mod_rewrite
a2enmod rewrite
echo "✓ mod_rewrite enabled"

# Enable the Laravel site
a2ensite my-laravel-app.conf
echo "✓ Laravel site enabled"

# Disable default site
a2dissite 000-default.conf
echo "✓ Default site disabled"

# Set proper permissions for Laravel
chown -R www-data:www-data /home/hp/code/my-laravel-app/storage /home/hp/code/my-laravel-app/bootstrap/cache
chmod -R 775 /home/hp/code/my-laravel-app/storage /home/hp/code/my-laravel-app/bootstrap/cache
echo "✓ Permissions set for storage and cache directories"

# Restart Apache
systemctl restart apache2
echo "✓ Apache restarted"

echo ""
echo "Setup complete! Your Laravel app should now be accessible at:"
echo "http://my-laravel-app.test/"
echo ""
echo "If you still see the default page, try:"
echo "1. Clear your browser cache"
echo "2. Check Apache error logs: sudo tail -f /var/log/apache2/error.log"
