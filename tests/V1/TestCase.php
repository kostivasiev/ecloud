<?php

namespace Tests\V1;

use Illuminate\Support\Facades\Event;
use Tests\CreatesApplication;
use Tests\Traits\ResellerDatabaseMigrations;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use ResellerDatabaseMigrations, CreatesApplication;

    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];

    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.read, ecloud.write',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            \App\Events\V1\DatastoreCreatedEvent::class,
        ]);
    }
}
