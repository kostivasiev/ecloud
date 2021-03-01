<?php

namespace Tests\V1;

use Illuminate\Support\Facades\Event;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];

    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.read, ecloud.write',
    ];

    public function createApplication()
    {
        return require __DIR__ . '/../../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            \App\Events\V1\DatastoreCreatedEvent::class,
        ]);
    }
}
