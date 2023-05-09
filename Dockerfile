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
RUN apt update && apt upgrade && apt install zlib1g libsodium-dev
RUN docker-php-ext-install mysqli pdo_mysql sockets sodium

EXPOSE 80
EXPOSE 8080
