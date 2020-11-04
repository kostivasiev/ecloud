################################################################################################
## Base PHP image stage - used by composer vendor builder (composer-builder) and final stages ##
################################################################################################
FROM php:7.4-apache AS apio
RUN pecl install redis-5.1.1 \
    && docker-php-ext-enable redis
RUN docker-php-ext-install pdo_mysql \
                           opcache

FROM apio AS composer-builder
RUN apt update && \
    apt install -y \
    ssh \
    git \
    zip

COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

# Use prestissimo until composer v2
RUN composer global require hirak/prestissimo

ARG SSH_PRIVATE_KEY

RUN mkdir -p /root/.ssh && \
    chmod 0700 /root/.ssh && \
    echo "${SSH_PRIVATE_KEY}" > /root/.ssh/id_rsa && \
    chmod 600 /root/.ssh/id_rsa && \
    ssh-keyscan gitlab.devops.ukfast.co.uk > /root/.ssh/known_hosts

WORKDIR /build
COPY composer.json composer.lock /build/
COPY database /build/database/

ARG APP_ENV=dev
RUN if [ ${APP_ENV} = "dev" ]; then \
composer install; \
else \
composer install --no-dev; \
fi

#################################################################################
## Final image stage - builds final image from project and builder stage files ##
#################################################################################
FROM apio
RUN a2enmod rewrite

COPY .docker/ca-certificates/ /usr/local/share/ca-certificates/
RUN update-ca-certificates

COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/start.sh /start.sh

COPY --chown=www-data:www-data . /var/www/html
COPY --from=composer-builder --chown=www-data:www-data /build/vendor /var/www/html/vendor/

CMD ["app"]
ENTRYPOINT ["/bin/bash", "/start.sh"]