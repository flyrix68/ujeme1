# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Set environment variables
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV APACHE_RUN_USER=www-data
ENV APACHE_RUN_GROUP=www-data
ENV APACHE_LOG_DIR=/var/log/apache2
ENV APACHE_RUN_DIR=/var/run/apache2
ENV APACHE_LOCK_DIR=/var/lock/apache2
ENV APACHE_PID_FILE=/var/run/apache2/apache2.pid

# Install system dependencies (minimal set without SSL)
RUN apt-get update && apt-get install -y --no-install-recommends \
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
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        intl \
        mbstring \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create necessary directories first
RUN mkdir -p ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} ${APACHE_LOG_DIR} \
    && mkdir -p /etc/apache2/conf-available /etc/apache2/conf-enabled \
    && mkdir -p /etc/apache2/mods-available /etc/apache2/mods-enabled \
    && mkdir -p /etc/apache2/sites-available /etc/apache2/sites-enabled \
    && chown -R www-data:www-data ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} ${APACHE_LOG_DIR}

# Configure Apache with minimal configuration
RUN a2enmod rewrite headers \
    && echo "ServerName localhost" > /etc/apache2/apache2.conf \
    && echo "Listen 80" > /etc/apache2/ports.conf \
    && echo "Mutex file:${APACHE_LOCK_DIR} default" > /etc/apache2/conf-available/mutex.conf \
    && echo "PidFile ${APACHE_PID_FILE}" >> /etc/apache2/apache2.conf \
    && echo "ErrorLog ${APACHE_LOG_DIR}/error.log" >> /etc/apache2/apache2.conf \
    && echo "CustomLog ${APACHE_LOG_DIR}/access.log combined" >> /etc/apache2/apache2.conf \
    && a2enconf mutex \
    && ln -sf /dev/stdout ${APACHE_LOG_DIR}/access.log \
    && ln -sf /dev/stderr ${APACHE_LOG_DIR}/error.log

# Copy custom Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Enable the default site
RUN a2ensite 000-default

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

# Create directories and set permissions
RUN mkdir -p ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads && \
    mkdir -p ${APACHE_DOCUMENT_ROOT}/uploads/{events,logos,medias,players,profiles} && \
    mkdir -p /var/log/apache2 && \
    touch /var/log/apache2/{error,access}.log && \
    chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT} /var/log/apache2

# Set working directory and copy files
WORKDIR ${APACHE_DOCUMENT_ROOT}
COPY --chown=www-data:www-data . .

# Create necessary directories if they don't exist
RUN mkdir -p ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads

# Set permissions
RUN find ${APACHE_DOCUMENT_ROOT} -type d -exec chmod 755 {} \; && \
    find ${APACHE_DOCUMENT_ROOT} -type f -exec chmod 644 {} \;

# Set special permissions for logs and uploads
RUN chmod -R 777 ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads

# Configure Apache
RUN a2enmod rewrite headers ssl && \
    a2dissite 000-default && \
    a2ensite 000-default.conf

# Create a basic .env file with required settings
RUN { \
    echo 'APP_ENV=production'; \
    echo 'APP_DEBUG=false'; \
    echo 'APP_KEY=base64:'$(openssl rand -base64 32); \
    echo 'APP_URL=http://localhost'; \
    echo 'DB_CONNECTION=mysql'; \
    echo 'DB_HOST=localhost'; \
    echo 'DB_PORT=3306'; \
    echo 'DB_DATABASE=laravel'; \
    echo 'DB_USERNAME=root'; \
    echo 'DB_PASSWORD='; \
} > /var/www/html/.env

# Copy and configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 443 8080 8443

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
