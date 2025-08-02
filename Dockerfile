# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Set environment variables
ENV APACHE_DOCUMENT_ROOT /var/www/html
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_RUN_DIR /var/run/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2/apache2.pid

# Create necessary directories
RUN mkdir -p ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} ${APACHE_LOG_DIR} && \
    chown -R www-data:www-data ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} ${APACHE_LOG_DIR}

# Install system dependencies and PHP extensions
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libicu-dev \
        libonig-dev \
        libzip-dev \
        unzip \
        zip \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libwebp-dev \
        libjpeg62-turbo-dev \
        libpq-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install -j$(nproc) \
        intl \
        mbstring \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        opcache; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*; \
    a2enmod rewrite headers

    # Copy custom Apache configuration
    COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Configure Apache and SSL
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf && \
    a2enmod ssl headers rewrite && \
    mkdir -p /etc/ssl/certs /etc/ssl/private && \
    # Generate self-signed certificate
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout /etc/ssl/private/ssl-cert-snakeoil.key \
        -out /etc/ssl/certs/ssl-cert-snakeoil.pem \
        -subj "/C=US/ST=State/L=City/O=Company/CN=localhost" && \
    # Set proper permissions
    chmod 600 /etc/ssl/private/ssl-cert-snakeoil.key && \
    chmod 644 /etc/ssl/certs/ssl-cert-snakeoil.pem && \
    # Enable default site
    a2ensite 000-default && \
    # Create SSL configuration
    echo '<IfModule mod_ssl.c>\n\
    <VirtualHost _default_:443>\n        ServerAdmin webmaster@localhost\n        DocumentRoot ${APACHE_DOCUMENT_ROOT}\n        \n        ErrorLog ${APACHE_LOG_DIR}/error-ssl.log\n        CustomLog ${APACHE_LOG_DIR}/access-ssl.log combined\n        \n        SSLEngine on\n        SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem\n        SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key\n        \n        <FilesMatch "\\.(cgi|s?html?|jpe?g|png|gif|ico)$">\n            SSLOptions +StdEnvVars\n        </FilesMatch>\n        \n        <Directory ${APACHE_DOCUMENT_ROOT}>\n            Options Indexes FollowSymLinks\n            AllowOverride All\n            Require all granted\n        </Directory>\n    </VirtualHost>\n</IfModule>' > /etc/apache2/sites-available/default-ssl.conf && \
    a2ensite default-ssl

# Expose ports
EXPOSE 8080 8443

# Configure PHP
RUN { \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_vars = 3000'; \
    echo 'date.timezone = UTC'; \
    echo 'session.save_path = "/tmp"'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 1'; \
    echo 'session.use_strict_mode = 1'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Create required directories and set proper permissions before copying files
RUN mkdir -p ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads && \
    mkdir -p ${APACHE_DOCUMENT_ROOT}/uploads/{events,logos,medias,players,profiles} && \
    mkdir -p /var/log/apache2 && \
    touch /var/log/apache2/error.log && \
    touch /var/log/apache2/access.log && \
    chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT} /var/log/apache2

# Set working directory
WORKDIR ${APACHE_DOCUMENT_ROOT}

# Copy application files with correct ownership
COPY --chown=www-data:www-data . .

# Set directory and file permissions
RUN find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    chmod -R 777 /var/www/html/logs /var/www/html/uploads && \
    a2enmod rewrite && \
    a2dissite 000-default && \
    a2ensite apache-config

# Copy and set execute permission for entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 80 for HTTP
EXPOSE 80

# Use the entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
