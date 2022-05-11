################################################################################################
## Base PHP image stage - used by composer vendor builder (composer-builder) and final stages ##
################################################################################################
FROM php:8.1-apache AS apio
RUN pecl install redis-5.3.7 \
   && docker-php-ext-enable redis
RUN docker-php-ext-install pdo_mysql \
                           opcache \
                           pcntl

ARG APP_ENV=dev
RUN if [ ${APP_ENV} = "prod" ]; then \
apt update && \
    apt install -y \
    wget \
    gnupg; \
echo 'deb http://apt.newrelic.com/debian/ newrelic non-free' | tee /etc/apt/sources.list.d/newrelic.list; \
wget -O /tmp/nr.key https://download.newrelic.com/548C16BF.gpg && apt-key add /tmp/nr.key && rm -f /tmp/nr.key; \
apt-get update; \
apt-get -y install newrelic-php5; \
NR_INSTALL_SILENT=1 newrelic-install install; \
fi

###########################################################################
## Composer builder stage - downloads and installs composer dependencies ##
###########################################################################
FROM apio AS composer-builder
RUN apt update && \
    apt install -y \
    ssh \
    git \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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

#################################################################################
## Final image stage - builds final image from project and builder stage files ##
#################################################################################
FROM apio
RUN a2enmod rewrite

ENV LOG_CHANNEL=ukfast

COPY .docker/ca-certificates/ /usr/local/share/ca-certificates/
RUN update-ca-certificates

COPY .docker/php.ini /usr/local/etc/php/php.ini
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/start.sh /start.sh
COPY .docker/newrelic.sh /newrelic.sh

COPY --chown=www-data:www-data . /var/www/html
COPY --from=composer-builder --chown=www-data:www-data /build/vendor /var/www/html/vendor/

STOPSIGNAL SIGTERM

CMD ["app"]
ENTRYPOINT ["/bin/bash", "/start.sh"]