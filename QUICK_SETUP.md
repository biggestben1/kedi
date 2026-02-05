# Quick Apache Setup for Laravel

Run these commands **one by one** in your terminal:

```bash
# 1. Copy the Laravel configuration to replace the default Apache site
sudo cp /home/hp/code/my-laravel-app/000-default-laravel.conf /etc/apache2/sites-available/000-default.conf

# 2. Add the domain to your hosts file
echo "127.0.0.1 my-laravel-app.test" | sudo tee -a /etc/hosts

# 3. Enable mod_rewrite (required for Laravel routing)
sudo a2enmod rewrite

# 4. Set proper permissions for Laravel storage and cache
sudo chown -R www-data:www-data /home/hp/code/my-laravel-app/storage /home/hp/code/my-laravel-app/bootstrap/cache
sudo chmod -R 775 /home/hp/code/my-laravel-app/storage /home/hp/code/my-laravel-app/bootstrap/cache

# 5. Restart Apache
sudo systemctl restart apache2
```

After running these commands, visit: **http://my-laravel-app.test/**

If you still see issues, check the Apache error log:
```bash
sudo tail -f /var/log/apache2/error.log
```
