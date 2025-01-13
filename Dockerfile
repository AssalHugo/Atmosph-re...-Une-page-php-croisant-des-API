FROM php:8.3-apache

# basic update
RUN apt-get update && \
    apt-get install --yes --force-yes \
    cron openssl

# installing the docker php extensions installer
RUN curl -sSLf \
    -o /usr/local/bin/install-php-extensions \
    https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions

# installing the php extensions
RUN install-php-extensions xmlrpc xsl intl

# Copier le contenu de l'application dans le répertoire web de l'image
COPY . /var/www/html/

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html