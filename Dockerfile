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

# Configure APT with retry and timeout settings
RUN echo 'Acquire::Retries "3";' > /etc/apt/apt.conf.d/80-retries && \
    echo 'Acquire::http::Timeout "120";' >> /etc/apt/apt.conf.d/80-retries && \
    echo 'Acquire::https::Timeout "120";' >> /etc/apt/apt.conf.d/80-retries

# Create and configure sources.list with a reliable mirror
RUN echo 'deb http://deb.debian.org/debian bookworm main contrib non-free' > /etc/apt/sources.list && \
    echo 'deb http://deb.debian.org/debian bookworm-updates main contrib non-free' >> /etc/apt/sources.list && \
    echo 'deb http://security.debian.org/debian-security bookworm-security main contrib non-free' >> /etc/apt/sources.list

# Update package lists with retry mechanism
RUN echo "Updating package lists..." && \
    for i in 1 2 3; do \
        if apt-get update; then \
            echo "Package lists updated successfully"; \
            break; \
        else \
            echo "Attempt $i/3 failed, retrying in 5 seconds..."; \
            sleep 5; \
        fi; \
    done && \
    echo "Sources list:" && \
    cat /etc/apt/sources.list

# Install system dependencies (minimal set without SSL)
RUN apt-get install -y --no-install-recommends \
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
    ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        intl \
        mbstring \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
    # Remove SSL related packages to prevent any SSL module loading
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
        openssl \
        ssl-cert \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Create necessary directories with proper permissions
RUN mkdir -p ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} ${APACHE_LOG_DIR} \
    && chown -R www-data:www-data ${APACHE_RUN_DIR} ${APACHE_LOCK_DIR} ${APACHE_LOG_DIR} \
    && chmod -R 775 ${APACHE_LOG_DIR} \
    && touch ${APACHE_LOG_DIR}/error.log ${APACHE_LOG_DIR}/access.log \
    && chown www-data:www-data ${APACHE_LOG_DIR}/*.log

# Configure Apache with proper MPM module and disable SSL completely
RUN set -e \
    # Disable unwanted modules
    && a2dismod -f mpm_event mpm_worker ssl 2>/dev/null || true \
    # Remove any existing SSL configurations
    && rm -f /etc/apache2/mods-enabled/ssl.* 2>/dev/null || true \
    && rm -f /etc/apache2/conf-enabled/ssl-* 2>/dev/null || true \
    && rm -f /etc/apache2/sites-enabled/default-ssl.conf 2>/dev/null || true \
    # Create a dummy SSL module to prevent auto-enabling
    && echo "# SSL module disabled" > /etc/apache2/mods-available/ssl.load \
    && echo "# SSL module disabled" > /etc/apache2/mods-available/ssl.conf \
    # Enable required modules
    && a2enmod mpm_prefork rewrite headers \
    # Configure Apache
    && echo "ServerName localhost" > /etc/apache2/apache2.conf \
    && echo "IncludeOptional mods-enabled/*.load" >> /etc/apache2/apache2.conf \
    && echo "IncludeOptional mods-enabled/*.conf" >> /etc/apache2/apache2.conf \
    && echo "IncludeOptional conf-enabled/*.conf" >> /etc/apache2/apache2.conf \
    && echo "IncludeOptional sites-enabled/*.conf" >> /etc/apache2/apache2.conf \
    && echo "<IfModule mpm_prefork_module>" >> /etc/apache2/apache2.conf \
    && echo "    StartServers            2" >> /etc/apache2/apache2.conf \
    && echo "    MinSpareServers         5" >> /etc/apache2/apache2.conf \
    && echo "    MaxSpareServers        10" >> /etc/apache2/apache2.conf \
    && echo "    MaxRequestWorkers      150" >> /etc/apache2/apache2.conf \
    && echo "    MaxConnectionsPerChild   0" >> /etc/apache2/apache2.conf \
    && echo "</IfModule>" >> /etc/apache2/apache2.conf \
    # Configure ports and logging
    && echo "Listen 80" > /etc/apache2/ports.conf \
    && echo "Mutex file:${APACHE_LOCK_DIR} default" > /etc/apache2/conf-available/mutex.conf \
    && echo "PidFile ${APACHE_PID_FILE}" >> /etc/apache2/apache2.conf \
    && echo "ErrorLog ${APACHE_LOG_DIR}/error.log" >> /etc/apache2/apache2.conf \
    && echo "CustomLog ${APACHE_LOG_DIR}/access.log combined" >> /etc/apache2/apache2.conf \
    # Enable configurations and set up logging
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
    echo 'session.cookie_secure = 0'; \
    echo 'session.use_strict_mode = 1'; \
} > /usr/local/etc/php/conf.d/uploads.ini

# Create directories and set permissions
RUN mkdir -p ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads \
    && mkdir -p ${APACHE_DOCUMENT_ROOT}/uploads/{events,logos,medias,players,profiles} \
    && touch /var/log/apache2/{error,access}.log \
    && chown -R www-data:www-data ${APACHE_DOCUMENT_ROOT} /var/log/apache2

# Set working directory and copy files
WORKDIR ${APACHE_DOCUMENT_ROOT}
COPY --chown=www-data:www-data . .

# Set permissions
RUN find ${APACHE_DOCUMENT_ROOT} -type d -exec chmod 755 {} \; \
    && find ${APACHE_DOCUMENT_ROOT} -type f -exec chmod 644 {} \; \
    && chmod -R 777 ${APACHE_DOCUMENT_ROOT}/logs ${APACHE_DOCUMENT_ROOT}/uploads

# Create basic .env file
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

# Final cleanup and verification
RUN set -e \
    # Create a dummy ssl module to prevent errors
    && mkdir -p /etc/apache2/mods-available/ \
    && echo "# Dummy ssl module - intentionally empty" > /etc/apache2/mods-available/ssl.load \
    && echo "# Dummy ssl config - intentionally empty" > /etc/apache2/mods-available/ssl.conf \
    # Disable SSL module
    && a2dismod -f ssl 2>/dev/null || true \
    # Remove any remaining SSL configurations
    && find /etc/apache2 -name "*ssl*" -type f -delete 2>/dev/null || true \
    # Remove any SSL related symlinks
    && find /etc/apache2/mods-enabled -name "*ssl*" -type l -delete 2>/dev/null || true \
    # Disable default SSL site if it exists
    && a2dissite -f default-ssl 2>/dev/null || true \
    # Clean up any lock files
    && rm -f /var/run/apache2/apache2.pid 2>/dev/null || true \
    && rm -f /var/run/apache2/.sock 2>/dev/null || true \
    # Ensure we're only listening on port 80
    && echo "Listen 80" > /etc/apache2/ports.conf \
    # Remove any SSL related configurations
    && find /etc/apache2/conf-available -name "*ssl*" -delete 2>/dev/null || true \
    && find /etc/apache2/conf-enabled -name "*ssl*" -delete 2>/dev/null || true

# Configure entrypoint
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]