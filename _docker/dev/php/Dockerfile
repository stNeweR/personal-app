FROM php:8.3-fpm

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --2.7.2 \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    libpq-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libffi-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_pgsql sockets gd exif ffi \
    && pecl install redis-6.0.2 xdebug \
    && docker-php-ext-enable redis xdebug gd ffi \
    && echo 'ffi.enable=true' > /usr/local/etc/php/conf.d/ffi.ini \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd -g 1000 app && \
    useradd -u 1000 -g app -s /bin/sh -m app

RUN mkdir -p /var/www && chown -R app:app /var/www

USER app

WORKDIR /var/www
