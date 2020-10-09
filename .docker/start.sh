#/bin/bash
set -e
ROLE=$1
if [ "$ROLE" = "app" ]; then
    exec apache2-foreground
elif [ "$ROLE" = "queue" ]; then
    php /var/www/html/artisan queue:work --verbose --tries 1000
elif [ "$ROLE" = "scheduler" ]; then
    while [ true ]
    do
      php /var/www/html/artisan schedule:run --verbose &
      sleep 60
    done
else
    echo "invalid role '${ROLE}'"
    exit 1
fi
