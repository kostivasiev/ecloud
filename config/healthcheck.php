<?php

return [
    /**
     * Base path for the health check endpoints, by default will use /
     */
    'base-path' => '',

    /**
     * List of health checks to run when determining the health
     * of the service
     */
    'checks' => [
        //UKFast\HealthCheck\Checks\LogHealthCheck::class,
        UKFast\HealthCheck\Checks\DatabaseHealthCheck::class,
        UKFast\HealthCheck\Checks\EnvHealthCheck::class,
        UKFast\HealthCheck\Checks\RedisHealthCheck::class,
    ],

    /**
     * A list of middlewares to run on the health-check route
     * It's recommended that you have a middelware that only
     * allows admin consumers to see the endpoint. There is
     * a default one that goes through common ukfast admin
     * checks, but if you need a more custom setup, you can
     * override it here
     */
    'middleware' => [
        UKFast\HealthCheck\Middleware\RequiresAdmin::class,
    ],

    /**
     * Can define a list of connection names to test. Nameas can be
     * found in your config/database.php file. By default, we just
     * check the 'default' connection
     */
    'database' => [
        'connections' => ['reseller', 'ecloud'],
    ],

    /**
     * Can give an array of required .env file parameters, for example
     * 'REDIS_HOST'. If any don't exist, then it'll be surfaced in the
     * context of the healthcheck
     */
    'required-env' => [],

    /**
     * Additional config can be put here. For example, a health check
     * for your .env file needs to know which keys need to be present.
     * You can pass this information by specifiying a new key here then
     * accessing it via config('healthcheck.env') in your healthcheck class
     */
];
