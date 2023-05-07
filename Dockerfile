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

EXPOSE 80
