# Use the official PHP image with Apache
FROM php:8.2-apache

# Install the MySQL extension so your PHP can talk to Aiven
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Run composer install to create the vendor folder
RUN composer install --no-interaction --optimize-autoloader

# Copy your backend files into the server's web folder
COPY . /var/www/html/

# Tell the server to listen on port 80 (standard for web)
EXPOSE 80