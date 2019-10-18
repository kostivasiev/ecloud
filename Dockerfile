FROM gliderlabs/herokuish

ARG SSH_PRIVATE_KEY
ARG BUILDPACK_URL

ENV BUILDPACK_URL ${BUILDPACK_URL}
ENV PORT 8080

ADD . /app

# Create ".env" file if it doesn't exist
RUN cp -n /app/.env.example /app/.env

# Authorize SSH Host
RUN mkdir -p /app/.ssh && \
    chmod 0700 /app/.ssh && \
    ssh-keyscan gitlab.devops.ukfast.co.uk > /app/.ssh/known_hosts

# Add the keys and set permissions
RUN echo "${SSH_PRIVATE_KEY}" > /app/.ssh/id_rsa && \
    chmod 600 /app/.ssh/id_rsa

EXPOSE $PORT

RUN apt update && apt install cron && apt clean
RUN echo "SHELL=/bin/sh" > /etc/crontab
RUN echo "PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin" >> /etc/crontab
RUN echo "* * * * * root herokuish procfile exec php artisan schedule:run > /proc/1/fd/1 2>/proc/1/fd/2" >> /etc/crontab

ENTRYPOINT ["sh", "-c", "/build && /start web"]
