<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    public function testValidDataSucceeds()
    {
        $credential = factory(Credential::class)->make();

        $this->post(
            '/v2/credentials',
            $credential->toArray(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            //TODO: This will need updating when we're encrypting the password in the database.
            ->seeInDatabase(
                'credentials',
                $credential->toArray(),
                'ecloud'
            )
            ->assertResponseStatus(201);
    }
}
