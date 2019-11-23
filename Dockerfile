FROM php:7.3-apache
WORKDIR /var/www/html
RUN pecl install xdebug \
  && docker-php-ext-enable xdebug
RUN apt-get update \
  && apt-get install -y libmagickwand-dev --no-install-recommends \
  && pecl install imagick \
  && docker-php-ext-enable imagick
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install
