FROM php:8.3-fpm-bookworm

ARG TIMEZONE=UTC

# Instalar Nginx y otras dependencias
RUN apt-get update && apt-get install -y \
    nginx \
    bash \
    gnupg \
    g++ \
    procps \
    openssl \
    git \
    unzip \
    zlib1g-dev \
    libzip-dev \
    libfreetype6-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev  \
    libonig-dev \
    libxslt1-dev \
    libpq-dev \
    acl \
    supervisor \
    && echo 'alias sf="php bin/console"' >> ~/.bashrc

# Configurar PHP
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/docker-php-config.ini

RUN docker-php-ext-configure gd --with-jpeg --with-freetype 

RUN docker-php-ext-install \
    pdo pdo_mysql pdo_pgsql zip xsl gd intl opcache exif mbstring

RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/symfony

# Copiar y instalar dependencias sin ejecutar scripts
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copiar código
COPY . .

# Ahora ejecutar los scripts después de copiar todo el código
RUN composer run-script post-install-cmd

RUN chown -R www-data:www-data /var/www/symfony

# Configurar Nginx
COPY .docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY .docker/nginx/templates /etc/nginx/templates/
RUN echo "upstream php-upstream { server 127.0.0.1:9000; }" > /etc/nginx/conf.d/upstream.conf

# Configurar Supervisor para ejecutar PHP-FPM y Nginx
RUN mkdir -p /var/log/supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80 443 9000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
