FROM debian:bookworm

# Install required packages
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    && rm -rf /var/lib/apt/lists/*

# Add PHP repository
RUN curl -sSLo /usr/share/keyrings/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb [signed-by=/usr/share/keyrings/php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# Install Apache, PHP, and required extensions
RUN apt-get update && apt-get install -y \
    apache2 \
    libapache2-mod-php8.2 \
    php8.2 \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-pdo \
    php8.2-opcache \
    php8.2-mbstring \
    php8.2-xml \
    php8.2-curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && phpenmod pdo_mysql

# Configure Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy Apache configuration
COPY apache-config-new.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default

# Set working directory
WORKDIR /var/www/html

# Copy .env file
COPY .env /var/www/html/.env
RUN chown www-data:www-data /var/www/html/.env

# PHP configuration
RUN mkdir -p /etc/php/8.2/apache2/conf.d

# Create PHP configuration file with essential settings
RUN { \
    echo 'display_errors = On'; \
    echo 'error_log = /var/log/php_errors.log'; \
    echo 'log_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'display_startup_errors = On'; \
    echo 'date.timezone = UTC'; \
    echo 'memory_limit = 256M'; \
    echo 'upload_max_filesize = 64M'; \
    echo 'post_max_size = 64M'; \
    echo 'max_execution_time = 300'; \
    echo 'session.save_handler = files'; \
    echo 'session.save_path = "/var/lib/php/sessions"'; \
    echo 'variables_order = "EGPCS"'; \
    echo 'auto_prepend_file = /var/www/html/set_env.php'; \
} > /etc/php/8.2/apache2/conf.d/99-custom.ini

# Create a script to load environment variables
COPY set_env.php /var/www/html/
RUN chown www-data:www-data /var/www/html/set_env.php

# Ensure the sessions directory exists and has correct permissions
RUN mkdir -p /var/lib/php/sessions \
    && chown -R www-data:www-data /var/lib/php/sessions \
    && chmod 1733 /var/lib/php/sessions

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Enable debug logging and copy files (exclude .env car déjà copié)
COPY --chown=www-data:www-data . .

# S'assurer que le .env a les bonnes permissions après la copie
RUN chmod 640 /var/www/html/.env
RUN chown www-data:www-data /var/www/html/.env

# Copy and set execute permission for entrypoint
COPY docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh

# Create necessary directories with proper permissions
RUN mkdir -p /var/log/apache2 && \
    touch /var/log/apache2/error.log && \
    touch /var/log/apache2/access.log && \
    chown -R www-data:www-data /var/log/apache2 && \
    mkdir -p /var/www/html/uploads/{events,logos,medias,players,profiles} && \
    chown -R www-data:www-data /var/www/html

# Create apache2-foreground script
RUN echo '#!/bin/bash\nset -e\n\n# Apache gets grumpy about PID files pre-existing\nrm -f /var/run/apache2/apache2*.pid\n\n# Start Apache in the background\n/usr/sbin/apache2ctl -D FOREGROUND &\n\n# Wait for Apache to be ready\nwhile ! pgrep -f "apache2 -D FOREGROUND" > /dev/null; do\n  sleep 1\ndone\n\n# Keep the container running\nwhile pgrep -f "apache2 -D FOREGROUND" > /dev/null; do\n  sleep 1\ndone' > /usr/local/bin/apache2-foreground && \
    chmod +x /usr/local/bin/apache2-foreground

# Expose Railway ports
EXPOSE 8080 8443

# Use the entrypoint script
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
