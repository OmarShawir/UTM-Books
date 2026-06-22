FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the application
COPY . /var/www/html

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Change Document Root to public folder
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Enable Apache mod_rewrite (required for Slim framework routing)
RUN a2enmod rewrite