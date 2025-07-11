FROM php:8.2-apache
EXPOSE 80
WORKDIR /var/www/html

# Debug Apache status
RUN echo "PS1='\[\e[31m\]\u@\h\[\e[0m\]:\[\e[34m\]\w\[\e[0m\] \$ '" >> /root/.bashrc
RUN echo "alias ll='ls -la'" >> /root/.bashrc

# Configure Apache
COPY apache-config.conf /etc/apache2/conf-available/000-default.conf
RUN a2enconf 000-default
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

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
RUN chmod -R 755 /var/www/html && \
    mkdir -p /var/log/apache2 && \
    chown www-data:www-data /var/log/apache2

# File permissions handled by Railway volumes configuration
