<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    /** @var Credential */
    private $credential;

    public function setUp(): void
    {
        parent::setUp();

        $this->credential = factory(Credential::class)->create();
    }

    public function testValidDataSucceeds()
    {
        $newCredential = factory(Credential::class)->make();

        $this->patch(
            '/v2/credentials/' . $this->credential->getKey(),
            $newCredential->toArray(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
        ->seeInDatabase(
            'credentials',
            array_merge(['id' => $this->credential->getKey()], $newCredential->toArray()),
            'ecloud'
        )->assertResponseStatus(200);
    }
}
