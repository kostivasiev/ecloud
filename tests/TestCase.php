<?php

namespace Tests;

use App\Models\V1\Datastore;
use App\Models\V2\Router;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{

    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];

    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.write',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Do not dispatch default ORM events on the following models, otherwise deployments will happen
        Datastore::flushEventListeners();
        Router::flushEventListeners();
    }

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
