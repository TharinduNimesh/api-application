FROM php:8.3-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    curl \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install pdo_mysql bcmath zip pcntl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files and install dependencies
COPY composer*.json ./
COPY composer.lock ./
RUN composer install 

# Copy package files and install npm dependencies
COPY package*.json ./
RUN npm install

# Copy project files
COPY . .

# Build frontend assets
RUN npm run build

# Optimize autoload
RUN composer dump-autoload --optimize

# Create non-root user 'laravel' and change ownership of the application folder
RUN useradd --create-home --uid 1000 laravel \
    && chown -R laravel:laravel /var/www

# Set proper permissions for storage and bootstrap cache (and public if needed)
RUN chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache /var/www/public \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public

# Copy Apache virtual host configuration
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache rewrite module (requires root)
RUN a2enmod rewrite

# Switch to non-root user 'laravel'
USER laravel

EXPOSE 80

CMD ["apache2-foreground"]