<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/lumen.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/lumen.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'ukfast' => [
            'driver' => 'single',
            'path' => storage_path('logs/lumen.log'),
            'formatter' => \UKFast\Logging\JsonFormatter::class,
            'ignore_exceptions' => true,
        ],

        'staging' => [
            'driver' => 'stack',
            'channels' => ['elasticsearch', 'single', 'ukfast'],
            'ignore_exceptions' => true,
        ],

        'elasticsearch' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => \Monolog\Handler\ElasticSearchHandler::class,
            'handler_with' => [
                'client' => new \Elastica\Client([
                    'url' => env('ELASTICSEARCH_URL'),
                    'username' => env('ELASTICSEARCH_USERNAME'),
                    'password' => env('ELASTICSEARCH_PASSWORD'),
                    'log' => false,
                ]),
                'options' => [
                    'index' => env('ELASTICSEARCH_INDEX_PREFIX') . Carbon\Carbon::now()->format('-Y-m-d'),
                    'type' => env('ELASTICSEARCH_TYPE'),
                ],
            ],
            'formatter' => \Monolog\Formatter\ElasticaFormatter::class,
            'formatter_with' => [
                'index' => env('ELASTICSEARCH_INDEX_PREFIX') . Carbon\Carbon::now()->format('-Y-m-d'),
                'type' => env('ELASTICSEARCH_TYPE'),
            ],
        ],
    ],
];
