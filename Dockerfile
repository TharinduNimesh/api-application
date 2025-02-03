FROM php:8.3-alpine3.20

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader

COPY package.json package-lock.json ./

RUN npm install

COPY . .

RUN composer dump-autoload --optimize

CMD ["php", "artisan", "serve"]