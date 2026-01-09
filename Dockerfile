# Use the official PHP image with Apache
FROM php:8.2-apache

# 1. Install system dependencies for GD and other tools
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl

# 2. Install and configure PHP extensions (mysqli and GD)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli gd \
    && docker-php-ext-enable mysqli gd

# 3. Set the working directory
WORKDIR /var/www/html

# 4. COPY your files into the container
COPY . /var/www/html/

# 5. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 6. NOW run composer install
RUN composer install --no-interaction --optimize-autoloader

# 7. Set permissions for your uploads folder
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Tell the server to listen on port 80
EXPOSE 80