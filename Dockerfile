FROM php:8.2-cli
RUN docker-php-ext-install pdo pdo_mysql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader
CMD php -S 0.0.0.0:$PORT
