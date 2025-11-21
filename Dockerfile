FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create and set permissions for data files
RUN mkdir -p /var/www/html/data && \
    touch /var/www/html/data/users.json && \
    touch /var/www/html/data/error.log && \
    chmod 755 /var/www/html/data && \
    chmod 666 /var/www/html/data/users.json && \
    chmod 666 /var/www/html/data/error.log && \
    chown -R www-data:www-data /var/www/html/data

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
