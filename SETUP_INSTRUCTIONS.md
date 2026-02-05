# Setup Instructions for Authentication and Migrations

## Step 1: Install Required PHP Extensions

Run these commands to install the required PHP extensions:

```bash
sudo apt-get install -y php-xml php-mysql php8.5-mysql
```

After installation, you may need to restart PHP-FPM (if using it):
```bash
sudo systemctl restart php8.5-fpm
```

## Step 2: Run Database Migrations

Once the MySQL extension is installed, run the migrations:

```bash
cd /home/hp/code/my-laravel-app
php artisan migrate
```

This will create the following tables:
- `users` - User accounts
- `password_reset_tokens` - Password reset tokens
- `sessions` - Session storage
- `cache` - Cache table
- `jobs` - Job queue table

## Step 3: Test Authentication

After running migrations, you can:

1. **Register a new user**: Visit `http://your-domain/register`
2. **Login**: Visit `http://your-domain/login`
3. **Logout**: Use the logout route (POST to `/logout`)

## Authentication Routes

The following routes are now available:

- `GET /login` - Show login form
- `POST /login` - Handle login
- `GET /register` - Show registration form
- `POST /register` - Handle registration
- `POST /logout` - Handle logout (requires authentication)

## Files Created

- `app/Http/Controllers/AuthController.php` - Authentication controller
- `resources/views/auth/login.blade.php` - Login form
- `resources/views/auth/register.blade.php` - Registration form
- `routes/web.php` - Updated with auth routes

## Next Steps

1. Install the PHP extensions (Step 1)
2. Run migrations (Step 2)
3. Test the authentication system
4. Customize the views and controller as needed
