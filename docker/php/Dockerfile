# Use the official PHP 7.4 FPM Alpine image as the base image
FROM php:8.1-fpm-alpine
# Install required dependencies for PHP extensions
RUN apk add --update libzip-dev curl-dev git && \
    docker-php-ext-install curl && \
    apk del gcc g++ && \
    rm -rf /var/cache/apk/*
# Install Bash
RUN apk update && apk add bash
# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql sockets curl
# Install ZIP extension
RUN apk add --no-cache zip libzip-dev && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip
RUN apk --no-cache add poppler-utils
# Install dependencies for GD extension
RUN apk --no-cache add freetype-dev libpng-dev libjpeg-turbo-dev
# Configure GD extension
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
# Install GD extension
RUN docker-php-ext-install -j$(nproc) gd
# Set the Alpine Linux version
ARG ALPINE_VERSION=3.14.4
# Install MySQL, MariaDB, and other required dependencies
RUN apk add --no-cache mysql-client mariadb-dev gcc musl-dev python3 python3-dev py3-pip
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Install dependencies for wkhtmltopdf
RUN apk --no-cache add libtool libxslt-dev libxml2-dev libjpeg-turbo-dev icu-dev ttf-freefont ttf-opensans
# Download and install wkhtmltopdf binary
RUN wget -O /usr/local/bin/wkhtmltopdf https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.jammy_arm64.deb && \
    chmod +x /usr/local/bin/wkhtmltopdf
# Set the working directory to /var/www/rent
WORKDIR /var/www/wpe
# Copy the setup.sh script to /usr/local/bin/setup
#COPY setup.sh /usr/local/bin/setup
#RUN chmod +x /usr/local/bin/setup
# Download and install Symfony CLI
RUN wget https://get.symfony.com/cli/installer -O - | bash
RUN export PATH="$HOME/.symfony5/bin:$PATH"
# Move the php.ini configuration file
RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
RUN sed -ri -e 's!;date.timezone =!date.timezone = "America\/Los_Angeles"!g' /usr/local/etc/php/php.ini
# Expose port 9000
EXPOSE 9000
# Set the entry point to the setup script
ENTRYPOINT ["php-fpm"]