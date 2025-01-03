    FROM php:8.1-apache

    # Habilitar depuração detalhada
    RUN set -e && echo "Iniciando o processo de construção da imagem"

    # Atualização de pacotes e instalação de dependências
    RUN set -e && \
        echo "Atualizando pacotes" && apt-get update && \
        echo "Instalando dependências essenciais" && \
        apt-get install -y --no-install-recommends mariadb-client vim libzip-dev zip unzip && \
        echo "Instalando extensão ZIP para PHP" && docker-php-ext-install zip && \
        echo "Limpando cache do apt" && rm -rf /var/lib/apt/lists/*

    # Instalar extensões PHP necessárias
    RUN set -e && \
        echo "Instalando extensões PHP (mysqli, pdo, pdo_mysql)" && \
        docker-php-ext-install mysqli pdo pdo_mysql

    # Instalar o Composer
    RUN set -e && \
        echo "Baixando o Composer" && \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
        echo "Composer instalado com sucesso"

    # Copiar arquivos do projeto para o contêiner
    RUN set -e && \
        echo "Copiando arquivos do projeto para o contêiner"
    COPY . /var/www/html

    # Executar instalação de dependências do Composer
    RUN set -e && \
        echo "Executando instalação de dependências do Composer" && \
        cd /var/www/html/api/ocomon_api && \
        composer install --verbose

    # Substituir configurações pela variável de ambiente
    RUN set -e && \
        echo "Configurando variáveis de ambiente no config.inc.php" && \
        mv /var/www/html/includes/config.inc.php-dist /var/www/html/includes/config.inc.php && \
        sed -i "s/define(\"SQL_USER\", \".*\"/define(\"SQL_USER\", getenv('DB_USER')/" /var/www/html/includes/config.inc.php && \
        sed -i "s/define(\"SQL_PASSWD\", \".*\"/define(\"SQL_PASSWD\", getenv('DB_PASSWORD')/" /var/www/html/includes/config.inc.php && \
        sed -i "s/define(\"SQL_SERVER\", \".*\"/define(\"SQL_SERVER\", getenv('DB_HOST'));/" /var/www/html/includes/config.inc.php && \
        sed -i "s/define(\"SQL_DB\", \".*\"/define(\"SQL_DB\", getenv('DB_NAME')/" /var/www/html/includes/config.inc.php && \
        sed -i 's#define(\"LOG_PATH\", \".*\")#define(\"LOG_PATH\", \"/var/www/html/includes/logs/logs.txt\")#' /var/www/html/includes/config.inc.php

    # Remover scripts desnecessários do banco de dados
    RUN set -e && \
        echo "Removendo definições fixas de banco de dados nos scripts de instalação" && \
        sed -i '/CREATE DATABASE .*!32312 IF NOT EXISTS.*ocomon_6.*utf8/d' /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
        sed -i '/CREATE USER.*ocomon_6.*localhost.*senha_ocomon_mysql/d' /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
        sed -i '/GRANT SELECT .*INSERT .*UPDATE .*DELETE ON .*ocomon_6.*localhost/d' /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
        sed -i '/GRANT Drop ON .*ocomon_6.*localhost/d' /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
        sed -i '/FLUSH PRIVILEGES;/d' /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql && \
        sed -i '/USE .*ocomon_6.*;/d' /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql

    # Com o container executado, logar nele a primeira vez:
    #   docker compose up
    #   docker exec -it ocomon_container /bin/bash
    # Executar
    #   mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/install/6.x/01-DB_OCOMON_6.x-FRESH_INSTALL_STRUCTURE_AND_BASIC_DATA.sql

    # Com o container executado, logar nele caso seja uma atualização:
    #   docker compose up
    #   docker exec -it ocomon_container /bin/bash
    # Executar Atualização
    #   mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < /var/www/html/install/6.x/02-UPDATE_FROM_6.x.sql

    # Excluíndo resíduos
    #   docker stop ocomon_container
    #   docker container rm ocomon_container
    #   docker container rm mysql8_container
    #   docker volume rm ocomon5_mysql_data
        
    # Garantir que os diretórios necessários existam e tenham as permissões corretas
    RUN set -e && \
        echo "Criando diretórios necessários e ajustando permissões" && \
        mkdir -p /var/www/html/api/ocomon_api/storage && \
        chown -R www-data:www-data /var/www/html && \
        chmod -R 775 /var/www/html/api/ocomon_api/storage && \
        chmod -R 775 /var/www/html/includes/logs

    # Habilitar o módulo rewrite no Apache
    RUN set -e && \
        echo "Habilitando módulo rewrite no Apache" && \
        a2enmod rewrite

    # Expor a porta padrão
    EXPOSE 80
