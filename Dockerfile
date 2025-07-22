# Utilise une image PHP officielle avec Apache
FROM php:8.2-apache

# Installe les extensions PHP requises
RUN apt-get update && apt-get install -y \
    git unzip zip libicu-dev libonig-dev libzip-dev libpq-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql zip opcache

# Active le mod_rewrite d'Apache
RUN a2enmod rewrite

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie les fichiers de l'application
COPY . /var/www/html/

# Change le dossier de travail
WORKDIR /var/www/html/

# Installe les dépendances
RUN composer install --no-dev --optimize-autoloader

# Donne les bons droits
RUN chown -R www-data:www-data /var/www/html/var

# Configure Apache pour pointer vers public/
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf
