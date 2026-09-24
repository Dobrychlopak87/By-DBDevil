FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql mbstring opcache \
    && a2enmod rewrite headers expires

COPY docker/apache/66600.conf /etc/apache2/conf-available/66600.conf
RUN a2enconf 66600

COPY docker/php/entrypoint.sh /usr/local/bin/66600-entrypoint
RUN chmod 0755 /usr/local/bin/66600-entrypoint \
    && mkdir -p /var/www/private /var/lib/66600/session /var/lib/66600/cache /var/log/66600 \
    && chown -R www-data:www-data /var/www/private /var/lib/66600 /var/log/66600

WORKDIR /var/www/html
ENTRYPOINT ["66600-entrypoint"]
CMD ["apache2-foreground"]
