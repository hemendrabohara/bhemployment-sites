FROM php:8.2-apache

# Copy the site files from the subfolder to Apache root
COPY backup-7.30.2026_02-07-20_bhemploy/homedir/public_html/ /var/www/html/

# Enable mod_rewrite for .htaccess rules
RUN a2enmod rewrite

# Explicitly set DirectoryIndex so Apache knows to load index.php
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf

# Fix ownership and permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
