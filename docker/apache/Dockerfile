
FROM php:8.1-apache
ARG TIMEZONE=Europe/Paris

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    openssl \
    git \
    unzip \
    libpq-dev \
    zlib1g-dev \
    libzip-dev \
    python3-pip \
    python3-bs4 \
    wget

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer --version

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

# Install PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql pdo_mysql exif zip

# Standard aliases
RUN echo 'alias sf="php app/console"' >> ~/.bashrc \
    && echo 'alias sf3="php bin/console"' >> ~/.bashrc

COPY ./docker/apache/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default.conf

WORKDIR /var/www/wpe

CMD ["apache2-foreground"]
