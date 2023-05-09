FROM phpmyadmin:latest
WORKDIR /var/www/html

COPY .htaccess/ .htaccess
COPY build.properties/ build.properties
COPY build.xml/ build.xml
COPY composer.json/ composer.json
COPY index.php index.php
COPY app/ app
COPY lib/ lib
COPY plugins/ plugins
COPY vendors/ vendors
COPY docker_dbconfig.php app/Config/database.php
# set permissions on app/tmp/ to 777
RUN chmod -R 777 app/tmp
# enable mod_rewrite (apache)
RUN a2enmod rewrite
RUN apt update && apt install zlib1g curl
RUN docker-php-ext-install curl gd iconv intl mbstring mysqli opcache pdo_mysql sockets sodium tidy zip

EXPOSE 80
EXPOSE 8080
