FROM gliderlabs/herokuish

ARG SSH_PRIVATE_KEY

ENV PORT 8080

ADD . /app

# Authorize SSH Host
RUN mkdir -p /app/.ssh && \
    chmod 0700 /app/.ssh && \
    ssh-keyscan gitlab.devops.ukfast.co.uk > /app/.ssh/known_hosts

# Add the keys and set permissions
RUN echo "${SSH_PRIVATE_KEY}" > /app/.ssh/id_rsa && \
    chmod 600 /app/.ssh/id_rsa

EXPOSE $PORT

# Crontab
RUN apt update && apt install cron && apt clean
RUN echo "SHELL=/bin/sh" > /etc/crontab
RUN echo "PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin" >> /etc/crontab
RUN echo "* * * * * root herokuish procfile exec php artisan schedule:run > /proc/1/fd/1 2>/proc/1/fd/2" >> /etc/crontab

# Opcache clearing tool
RUN curl -o /app/cachetool.phar -s http://gordalina.github.io/cachetool/downloads/cachetool.phar
RUN chmod +x /app/cachetool.phar
RUN echo "alias opcache-status=\"herokuish procfile exec php cachetool.phar opcache:status --fcgi=/tmp/heroku.fcgi.$PORT.sock\"" >> ~/.bash_aliases
RUN echo "alias opcache-clear=\"herokuish procfile exec php cachetool.phar opcache:reset --fcgi=/tmp/heroku.fcgi.$PORT.sock\"" >> ~/.bash_aliases

ENTRYPOINT ["sh", "-c", "/build && /start web"]
