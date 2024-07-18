FROM php:8.1 as php

RUN apt-get update -y
RUN apt-get install -y unzip libpq-dev libcurl4-gnutls-dev
RUN docker-php-ext-install pdo pdo_mysql bcmath

RUN pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && docker-php-ext-enable redis

WORKDIR /var/www

COPY . .

COPY --from=composer:2.3.5 /usr/bin/composer /usr/bin/composer

ENV PORT=8000

ENTRYPOINT ["docker/entrypoint.sh"]

# ============================================================

# Node
FROM node:18-alpine as node

WORKDIR /var/www
COPY package*.json ./

# Clean install
RUN npm cache clean --force
RUN rm -rf node_modules
RUN npm install --global cross-env
RUN npm ci

COPY . .

VOLUME /var/www/node_modules

