FROM php:8.2-apache
COPY backup-7.30.2026_02-07-20_bhemploy/homedir/public_html/ /var/www/html/
RUN chown -R www-data:www-data /var/www/html/ && chmod -R 755 /var/www/html/
RUN a2enmod rewrite
