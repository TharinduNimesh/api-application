FROM php:8.3-fpm-alpine

# Add system dependencies
RUN apk add --no-cache \
    linux-headers \
    bash \
    shadow \
    git \
    gcc \
    g++ \
    make \
    autoconf \
    openssl \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql bcmath zip pcntl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Install Node.js and npm
RUN apk add --no-cache nodejs npm
RUN npm install -g npm@latest

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files
COPY composer*.json ./
COPY composer.lock ./

# Install composer dependencies
RUN composer install --no-scripts --no-autoloader

# Copy package.json files
COPY package*.json ./

# Install npm dependencies
RUN npm install

# Copy project files
COPY . .

# Generate composer autoload files
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Create system user
RUN useradd -G www-data,root -u 1000 -d /home/dev dev
RUN mkdir -p /home/dev/.composer && \
    chown -R dev:dev /home/dev

# Set proper permissions
RUN chown -R dev:dev /var/www
RUN chmod -R 755 /var/www/storage

# Switch to non-root user
USER dev

# Expose port 9000
EXPOSE 8000

# Start PHP-FPM
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]