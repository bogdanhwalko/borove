FROM php:8.2-fpm-alpine

# System packages
RUN apk add --no-cache \
        bash \
        git \
        curl \
        zip \
        unzip \
        oniguruma-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libwebp-dev \
        mysql-client \
        netcat-openbsd \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        exif \
    && rm -rf /var/cache/apk/*

# PHP upload settings
RUN echo "upload_max_filesize = 25M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size = 200M"     >> /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Bootstrap script
COPY docker/php/init.sh /usr/local/bin/init.sh
RUN sed -i 's/\r$//' /usr/local/bin/init.sh \
 && chmod +x /usr/local/bin/init.sh

WORKDIR /var/www/html

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/init.sh"]
CMD ["php-fpm"]
