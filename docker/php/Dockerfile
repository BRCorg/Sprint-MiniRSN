# Image de base : PHP 8.3 avec Apache
FROM php:8.3-apache

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Installer Composer (gestionnaire de dépendances)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer le module Apache pour les URLs propres
RUN a2enmod rewrite

# Copier la configuration Apache personnalisée
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Définir le dossier de travail
WORKDIR /var/www/html

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html
