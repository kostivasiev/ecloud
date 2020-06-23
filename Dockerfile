FROM php:7.1-apache AS apio
RUN apt update && \
    apt install -y \
    libmcrypt-dev
RUN docker-php-ext-install pdo_mysql \
                           mcrypt \
                           opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

FROM apio AS builder
RUN apt update && \
    apt install -y \
    ssh \
    git \
    zip

# Use prestissimo until composer v2
RUN composer global require hirak/prestissimo

ARG SSH_PRIVATE_KEY

RUN mkdir -p /root/.ssh && \
    chmod 0700 /root/.ssh && \
    echo "${SSH_PRIVATE_KEY}" > /root/.ssh/id_rsa && \
    chmod 600 /root/.ssh/id_rsa && \
    ssh-keyscan gitlab.devops.ukfast.co.uk > /root/.ssh/known_hosts

WORKDIR /build
COPY . /build

ARG APP_ENV=dev
RUN if [ ${APP_ENV} = "dev" ]; then \
composer install; \
else \
composer install --no-dev; \
fi

FROM apio
RUN a2enmod rewrite

COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/start.sh /start.sh
COPY --from=builder --chown=www-data:www-data /build /var/www/html

CMD ["app"]
ENTRYPOINT ["/bin/bash", "/start.sh"]