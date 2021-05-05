<?php

namespace Tests\V2\Credential;

use Tests\TestCase;

class CreateTest extends TestCase
{
    public function testValidDataSucceeds()
    {
        $this->post(
            '/v2/credentials',
            [
                'resource_id' => 'abc-abc132',
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'password' => 'somepassword',
                'port' => 8080
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'credentials',
                [
                    'resource_id' => 'abc-abc132',
                    'host' => 'https://127.0.0.1',
                    'username' => 'someuser',
                    'port' => 8080
                ],
                'ecloud'
            )
            // Assert that we're not storing the plain text password in the db
            ->missingFromDatabase(
                'credentials',
                ['password' => 'somepassword'],
                'ecloud'
            )
            ->assertResponseStatus(201);
    }
}
