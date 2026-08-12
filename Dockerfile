FROM php:8.2-apache

# Copy site files from the nested folder where GitHub extracted them
COPY backup-7.30.2026_02-07-20_bhemploy/homedir/www/public_html/ /var/www/html/

# Enable URL rewrite
RUN a2enmod rewrite

# Explicitly set index loading order
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf

# Fix permissions for Apache
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
