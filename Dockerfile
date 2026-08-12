FROM php:8.2-apache

# Copy site files from homedir directly into Apache's web root
COPY backup-7.30.2026_02-07-20_bhemploy/homedir/ /var/www/html/

# Enable URL rewrite
RUN a2enmod rewrite

# Set directory index default
RUN echo "DirectoryIndex index.php index.html" >> /etc/apache2/apache2.conf

# Fix permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 80
