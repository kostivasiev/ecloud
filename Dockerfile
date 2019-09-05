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

ENTRYPOINT ["sh", "-c", "/build && /start web"]
