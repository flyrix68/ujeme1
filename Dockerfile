FROM debian:bookworm
WORKDIR /var/www/html

# Add Debian PHP repository
RUN apt-get update && apt-get install -y \
    ca-certificates \
    apt-transport-https \
    lsb-release \
    gnupg \
    curl \
    procps  # For ps command used in apache2-foreground
RUN curl -sSLo /usr/share/keyrings/php.gpg https://packages.sury.org/php/apt.gpg
RUN echo "deb [signed-by=/usr/share/keyrings/php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# Install Apache and PHP
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.2 \
    libapache2-mod-php8.2 \
    php8.2-mysql \
    php8.2-zip \
    php8.2-common \
    php8.2-opcache \
    libzip-dev \
    unzip \
    netcat-openbsd \
    default-mysql-client

# Configure Apache
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "Listen 80" > /etc/apache2/ports.conf
RUN a2enmod php8.2

# PHP configuration
RUN mkdir -p /etc/php/8.2/apache2/conf.d
RUN echo "display_errors = On" >> /etc/php/8.2/apache2/conf.d/error-logging.ini
RUN echo "error_log = /var/log/php_errors.log" >> /etc/php/8.2/apache2/conf.d/error-logging.ini
RUN echo "log_errors = On" >> /etc/php/8.2/apache2/conf.d/error-logging.ini

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html


# Enable debug logging and copy files
COPY --chown=www-data:www-data . .

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

# Expose port 80
EXPOSE 80

# Use the entrypoint script
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
