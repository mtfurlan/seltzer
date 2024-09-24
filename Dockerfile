FROM php:7.4-fpm AS dev
RUN docker-php-ext-install mysqli
RUN docker-php-ext-configure calendar && docker-php-ext-install calendar

FROM dev AS prod
COPY crm/ /var/www/html

VOLUME /var/www/html/files
# TODO: send mail via smtp configured by env vars
