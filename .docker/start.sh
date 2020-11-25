#/bin/bash
set -e
ROLE=$1
if [ "$ROLE" = "app" ]; then
    exec apache2-foreground
elif [ "$ROLE" = "queue" ]; then
    php /var/www/html/artisan queue:work --verbose --tries 3 --timeout=900
elif [ "$ROLE" = "scheduler" ]; then
    function signal_exit
    {
        echo  "Caught signal, waiting for background processes to finish.."
        wait $(jobs -pr)
        exit 0
    }

    trap signal_exit SIGINT SIGTERM

    while [ true ]
    do
      php /var/www/html/artisan schedule:run --verbose &
      sleep 60
    done
else
    echo "invalid role '${ROLE}'"
    exit 1
fi
