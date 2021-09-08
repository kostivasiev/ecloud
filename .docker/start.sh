#/bin/bash
set -e

tail -F /var/www/html/storage/logs/lumen.log &

ROLE=$1
if [ "$ROLE" = "app" ]; then
    exec apache2-foreground
elif [ "$ROLE" = "queue" ]; then
    exec php /var/www/html/artisan queue:work --verbose --tries 3 --timeout=900
elif [ "$ROLE" = "scheduler" ]; then
    function signal_exit
    {
        echo "Caught signal, waiting for background processes to finish.."
        wait $(jobs -pr)
        exit 0
    }

    trap signal_exit SIGINT SIGTERM

    while [ true ]
    do
      php /var/www/html/artisan schedule:run --verbose &
      sleep 60
    done
elif [ "$ROLE" = "migrations" ]; then
    php artisan migrate --database=ecloud --force
else
    echo "invalid role '${ROLE}'"
    exit 1
fi

echo "debug 1"