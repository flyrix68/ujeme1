FROM php:8.2-apache
WORKDIR /var/www/html

# Debug Apache status
RUN echo "PS1='\[\e[31m\]\u@\h\[\e[0m\]:\[\e[34m\]\w\[\e[0m\] \$ '" >> /root/.bashrc
RUN echo "alias ll='ls -la'" >> /root/.bashrc

# Configure Apache for Railway
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "Listen \${PORT}" > /etc/apache2/ports.conf

# Debug startup
RUN echo "#!/bin/sh" > /start-debug.sh
RUN echo "apache2ctl start" >> /start-debug.sh 
RUN echo "tail -f /var/log/apache2/error.log" >> /start-debug.sh
RUN chmod +x /start-debug.sh

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable debug logging and copy files
RUN echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/error-logging.ini
COPY --chown=www-data:www-data . .

# Copy and set execute permission for entrypoint
COPY docker-entrypoint.sh /
RUN chmod +x /docker-entrypoint.sh

# Create necessary directories
RUN mkdir -p /var/log/apache2 /var/www/html/uploads/{events,logos,medias,players,profiles}

# Use the entrypoint script
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
