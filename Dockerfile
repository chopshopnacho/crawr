FROM php:7.3-apache
WORKDIR /var/www/html
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN apt-get update \
  && apt-get install -y libmagickwand-dev --no-install-recommends \
  && pecl install imagick \
  && docker-php-ext-enable imagick
RUN apt-get update \
  && apt-get install -y zlib1g-dev libzip-dev \
  && docker-php-ext-install zip
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install
