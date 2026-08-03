FROM php:8.2-apache

# System packages + PHP extensions needed by the app (PostgreSQL, file uploads)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev libonig-dev unzip \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apache: serve from /public, allow .htaccess, enable rewrite/headers
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN a2enmod rewrite headers \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Uploads: PDFs can be a few MB, raise the PHP defaults
RUN { \
        echo 'upload_max_filesize=30M'; \
        echo 'post_max_size=35M'; \
        echo 'memory_limit=256M'; \
        echo 'max_execution_time=120'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY . /var/www/html

# Storage directory for uploaded PDFs — a Render Disk should be
# mounted at this path in production so files persist across deploys.
RUN mkdir -p /var/www/html/storage/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
