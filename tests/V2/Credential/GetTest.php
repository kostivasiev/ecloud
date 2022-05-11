<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use Tests\TestCase;

class GetTest extends TestCase
{
    /**
     * @var Credential
     */
    protected $credential;

    public function setUp(): void
    {
        parent::setUp();

        $this->credential = Credential::factory()->create();
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/credentials',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->assertJsonFragment([
                'resource_id' => 'abc-abc132',
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'password' => 'somepassword',
                'port' => 8080
        ])->assertStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/credentials/' . $this->credential->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->assertJsonFragment([
                'resource_id' => 'abc-abc132',
                'host' => 'https://127.0.0.1',
                'username' => 'someuser',
                'password' => 'somepassword',
                'port' => 8080
        ])->assertStatus(200);
    }
}
