<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    /** @var Credential */
    private $credential;

    public function setUp(): void
    {
        parent::setUp();

        $this->credential = factory(Credential::class)->create();
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/credentials/' . $this->credential->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $resource = Credential::withTrashed()->findOrFail($this->credential->id);
        $this->assertNotNull($resource->deleted_at);
    }
}
