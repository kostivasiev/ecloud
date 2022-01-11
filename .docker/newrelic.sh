#/bin/bash
set -e

if [ ! -z $NEW_RELIC_LICENSE_KEY ]; then
    sed -i -e "s/REPLACE_WITH_REAL_KEY/$NEW_RELIC_LICENSE_KEY/" \
    -e "s/newrelic.appname[[:space:]]=[[:space:]].*/newrelic.appname=\"$NEW_RELIC_APP_NAME\"/" \
    -e '$anewrelic.distributed_tracing_enabled=true' \
    $(php -r "echo(PHP_CONFIG_FILE_SCAN_DIR);")/newrelic.ini
fi