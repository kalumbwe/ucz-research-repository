# Use official PHP image with Apache
FROM php:8.1-apache

# Install OpenSSL development libraries and the PDO MySQL extension
RUN apt-get update && apt-get install -y libssl-dev \
    && docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite for pretty URLs
RUN a2enmod rewrite

# Copy all project files to the web server folder
COPY . /var/www/html/

# Set proper permissions for the upload folders so Railway can save PDFs
RUN mkdir -p /var/www/html/uploads/reports /var/www/html/uploads/covers
RUN chown -R www-data:www-data /var/www/html/uploads

# Make sure ca.pem is readable by the web server
RUN chown www-data:www-data /var/www/html/ca.pem