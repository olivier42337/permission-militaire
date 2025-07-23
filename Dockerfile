# Étape 1 : image officielle PHP avec Apache
FROM php:8.2-apache

# Étape 2 : installation des extensions nécessaires à Symfony
RUN apt-get update && apt-get install -y \
    git zip unzip libicu-dev libonig-dev libzip-dev libpq-dev libpng-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip

# Étape 3 : activer le mod rewrite d’Apache
RUN a2enmod rewrite

# Étape 4 : installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Étape 5 : copier le projet dans le conteneur
COPY . /var/www/html/

# Étape 6 : se placer dans le bon dossier
WORKDIR /var/www/html/

# Étape 7 : installer les dépendances Symfony
RUN composer install --no-dev --optimize-autoloader

# Étape 8 : configurer Apache pour pointer sur /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf
