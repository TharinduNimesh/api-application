#!/bin/sh

if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-progress --no-interaction
fi

if [ ! -f ".env" ]; then
    echo "Creating env file for env $APP_ENV"
    cp .env.example .env
else
    echo "env file exists."
fi

# Ensure storage directories have proper permissions
echo "Setting proper permissions for storage directories"
chmod -R 775 /var/www/storage/framework
chmod -R 775 /var/www/storage/framework/views
chmod -R 775 /var/www/storage/logs
chown -R www-data:www-data /var/www/storage

# Install Node dependencies if needed
if [ -f "package.json" ]; then
    echo "Installing Node.js dependencies"
    npm ci && npm run build
fi

php artisan migrate
php artisan optimize
php artisan view:cache

php-fpm -D
nginx -g "daemon off;"
