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
            ->seeInDatabase(
                'credentials',
                collect($credential)->except('password')->toArray(),
                'ecloud'
            )
            // Assert that we're not storing the plain text password in the db
            ->missingFromDatabase(
                'credentials',
                ['password' => $credential->password],
                'ecloud'
            )
            ->assertResponseStatus(201);
    }
}
