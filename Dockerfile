FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    default-mysql-client \
    sendmail \
    libonig-dev \
    libxml2-dev \
    netcat-traditional \
    dos2unix \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Copy wait-for-it script
COPY scripts/wait-for-it.sh /usr/local/bin/
RUN dos2unix /usr/local/bin/wait-for-it.sh && chmod +x /usr/local/bin/wait-for-it.sh

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        mbstring \
        xml \
        zip \
        gd \
        pdo \
        pdo_mysql \
        mysqli

# Enable Apache modules
RUN a2enmod rewrite headers

# Set up Apache configuration
RUN echo "<Directory /var/www/html>" > /etc/apache2/conf-available/ejawatan.conf \
    && echo "    AllowOverride All" >> /etc/apache2/conf-available/ejawatan.conf \
    && echo "    Require all granted" >> /etc/apache2/conf-available/ejawatan.conf \
    && echo "</Directory>" >> /etc/apache2/conf-available/ejawatan.conf \
    && a2enconf ejawatan

# Download PHPMailer
RUN mkdir -p /var/www/html/admin/PHPMailer \
    && curl -L https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip -o /tmp/PHPMailer.zip \
    && unzip /tmp/PHPMailer.zip -d /tmp/ \
    && cp -r /tmp/PHPMailer-master/src /var/www/html/admin/PHPMailer/ \
    && rm -rf /tmp/PHPMailer*

# Create necessary directories and set permissions
RUN mkdir -p /var/www/html/uploads \
    && mkdir -p /var/www/html/admin/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/admin/logs

# Copy application files
COPY . /var/www/html/

# Copy database initialization script
COPY scripts/init-db.php /usr/local/bin/init-db.php
RUN chmod +x /usr/local/bin/init-db.php

# Copy application entrypoint
COPY scripts/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh
RUN chmod +x /usr/local/bin/app-entrypoint.sh

# Set proper ownership after copying
RUN chown -R www-data:www-data /var/www/html

# Configure PHP settings
RUN echo "file_uploads = On" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1
