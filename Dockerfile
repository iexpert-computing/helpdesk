FROM php:8.1-apache

# Apenas o cliente MySQL
RUN apt-get update && \
    apt-get install -y --no-install-recommends mariadb-client vim libzip-dev zip unzip && \
    docker-php-ext-install zip && \
    rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /var/www/html

RUN cd /var/www/html/api/ocomon_api && \
    composer install

# Substituindo a configuração pela variável ambiente
RUN mv /var/www/html/includes/config.inc.php-dist /var/www/html/includes/config.inc.php
RUN sed -i "s/define(\"SQL_USER\", \".*\"/define(\"SQL_USER\", getenv('DB_USER')/" /var/www/html/includes/config.inc.php
RUN sed -i "s/define(\"SQL_PASSWD\", \".*\"/define(\"SQL_PASSWD\", getenv('DB_PASSWORD')/" /var/www/html/includes/config.inc.php
RUN sed -i "s/define(\"SQL_SERVER\", \".*\"/define(\"SQL_SERVER\", getenv('DB_HOST'));/" /var/www/html/includes/config.inc.php
RUN sed -i "s/define(\"SQL_DB\", \".*\"/define(\"SQL_DB\", getenv('DB_NAME')/" /var/www/html/includes/config.inc.php
RUN sed -i 's#define("LOG_PATH", ".*")#define("LOG_PATH", "/var/www/html/includes/logs/logs.txt")#' /var/www/html/includes/config.inc.php

# O ocomon usa definições do banco de forma fixa no script de instalação inicial, aqui estou removendo eles, já que o banco está criado no composer
RUN sed -i '/CREATE DATABASE .*!32312 IF NOT EXISTS.*ocomon_5.*utf8/d' /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
    sed -i '/CREATE USER.*ocomon_5.*localhost.*senha_ocomon_mysql/d' /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
    sed -i '/GRANT SELECT .*INSERT .*UPDATE .*DELETE ON .*ocomon_5.*localhost/d' /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
    sed -i '/GRANT Drop ON .*ocomon_5.*localhost/d' /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
    sed -i '/FLUSH PRIVILEGES;/d' /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
    sed -i '/USE .*ocomon_5.*;/d' /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql

# Com o container executado, logar nele a primeira vez:
#   docker compose up
#   docker exec -it ocomon_container /bin/bash
# Executar
#   mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/install/5.x/01-DB_OCOMON_5.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql
# Excluíndo resíduos
#   docker container rm ocomon_container
#   docker container rm mysql8_container
#   docker volume rm ocomon5_mysql_data

RUN mkdir -p /var/www/html/api/ocomon_api/storage
RUN chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/html/api/ocomon_api/storage && \
    chmod -R 775 /var/www/html/includes/logs

RUN a2enmod rewrite

EXPOSE 80