<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__ . '/../')
);

$app->configure('app');
$app->configure('database');
$app->configure('defaults');
$app->configure('logging');
$app->configure('mail');
$app->configure('gpu');
$app->configure('encryption');
$app->configure('queue');
$app->configure('erd-generator');
$app->configure('instance');
$app->configure('volume');
$app->configure('kingpin');
$app->configure('conjurer');
$app->configure('artisan');
$app->configure('job-status');
$app->configure('firewall');
$app->configure('alerts');
$app->configure('router');
$app->configure('network');
$app->configure('auth');
$app->configure('host');
$app->configure('billing');

$app->alias('mailer', Illuminate\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\Mailer::class);
$app->alias('mailer', Illuminate\Contracts\Mail\MailQueue::class);

$app->withFacades();
$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->routeMiddleware([
    'auth' => UKFast\Api\Auth\Authenticate::class,
    'is-admin' => \UKFast\Api\Auth\Middleware\IsAdmin::class,
    'paginator-limit' => UKFast\Api\Paginator\Middleware\PaginatorLimit::class,
    'has-reseller-id' => \App\Http\Middleware\HasResellerId::class,
    'is-locked' => \App\Http\Middleware\IsLocked::class,
    'can-enable-support' => \App\Http\Middleware\CanEnableSupport::class,
    'is-pending' => \App\Http\Middleware\DiscountPlan\IsPending::class
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(Illuminate\Database\Eloquent\LegacyFactoryServiceProvider::class);

// ukfast
$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\IntapiServiceProvider::class);
$app->register(UKFast\Responses\ResponseServiceProvider::class);
$app->register(UKFast\Api\Paginator\PaginationServiceProvider::class);
$app->register(UKFast\HealthCheck\HealthCheckServiceProvider::class);
$app->register(\UKFast\Api\Auth\AuthServiceProvider::class);
$app->register(UKFast\Api\Exceptions\Providers\UKFastExceptionServiceProvider::class);
$app->register(UKFast\Api\Resource\ResourceServiceProvider::class);
$app->register(UKFast\ApiInternalCommunication\AccountAdminClientServiceProvider::class);
$app->register(UKFast\ApiInternalCommunication\DevicesAdminClientServiceProvider::class);
$app->register(UKFast\ApiInternalCommunication\eCloudAdminClientServiceProvider::class);
$app->register(UKFast\ApiInternalCommunication\NetworkingAdminClientServiceProvider::class);
$app->register(UKFast\ApiInternalCommunication\BillingAdminClientServiceProvider::class);

$app->register(UKFast\FormRequests\FormRequestServiceProvider::class);


// ecloud service providers
$app->register(App\Providers\KingpinServiceProvider::class);
$app->register(App\Providers\ArtisanServiceProvider::class);
$app->register(\App\Providers\EncryptionServiceProvider::class);
$app->register(App\Providers\V2\KingpinServiceProvider::class);
$app->register(App\Providers\V2\ConjurerServiceProvider::class);
$app->register(App\Providers\V2\ArtisanServiceProvider::class);


// apio service providers
$app->register(App\Providers\NetworkingServiceProvider::class);
$app->register(App\Providers\AccountsServiceProvider::class);
$app->register(App\Providers\BillingServiceProvider::class);

// NSX service provider
$app->register(App\Providers\V2\NsxServiceProvider::class);

// Job status
$app->bind(\Illuminate\Queue\QueueManager::class, function ($app) {
    return new \Illuminate\Queue\QueueManager($app);
});

// ErdGenerator - Only enable on dev
if (is_dir($app->basePath('vendor/beyondcode/laravel-er-diagram-generator'))) {
    $app->register(BeyondCode\ErdGenerator\ErdGeneratorServiceProvider::class);
}

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
