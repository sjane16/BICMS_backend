# Use the official PHP image with Apache
FROM php:8.2-apache

# 1. Install the MySQL extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# 2. Set the working directory
WORKDIR /var/www/html

# 3. COPY your files into the container FIRST
# This moves composer.json and your code into /var/www/html
COPY . /var/www/html/

# 4. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 5. NOW run composer install (it will find the composer.json file now)
RUN composer install --no-interaction --optimize-autoloader

# 6. Set permissions for your uploads folder
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Tell the server to listen on port 80
EXPOSE 80