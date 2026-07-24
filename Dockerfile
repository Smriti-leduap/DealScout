FROM php:8.2-apache

# Install SQLite dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y libsqlite3-dev sqlite3 && \
    docker-php-ext-install pdo pdo_sqlite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set appropriate permissions
RUN chown -R www-data:www-data /var/www/html

# Ensure uploads directory is writable (assuming it might be used)
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Expose port 80
EXPOSE 80
