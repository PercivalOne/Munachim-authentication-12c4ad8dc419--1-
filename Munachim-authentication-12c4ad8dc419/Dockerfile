FROM php:7.4-fpm-alpine3.13

WORKDIR /var/www

# Install system packages
RUN apk --no-cache update \
    && apk --no-cache upgrade \
    && apk --no-cache add ca-certificates

RUN docker-php-ext-install pdo pdo_mysql

COPY ./config/php/local.ini /usr/local/etc/php/conf.d/local.ini
# RUN cp /var/www/.env.example /var/www/.env

# Add Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#RUN chown -R nobody.nobody /run \
 #   && chown -R nobody.nobody /var/lib/nginx \
  #  && chown -R nobody.nobody /var/log/nginx

# Add application
COPY . /var/www/

# RUN chown -R 777 /
RUN chown -R www-data:www-data /var/www

# Switch to use a non-root user from here on
#USER nobody

# Expose the port nginx is reachable on
EXPOSE 9000
