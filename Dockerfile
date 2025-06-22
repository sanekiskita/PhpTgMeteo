FROM php:8.1

WORKDIR /var/www/html

# Установка необходимых расширений PHP
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libxml2-dev \
    libonig-dev \
    git \
    && docker-php-ext-install curl mbstring

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# установка зависимостей
COPY composer.json .

RUN composer install

EXPOSE 80

CMD ["php", "./src/run.php"]