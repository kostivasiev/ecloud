<?php

namespace Tests\V2\MrrCommitment;

use App\Models\V2\MrrCommitment;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{

    use DatabaseMigrations;

    protected MrrCommitment $commitment;

    public function setUp(): void
    {
        parent::setUp();
        $this->commitment = factory(MrrCommitment::class)->create([
            'contact_id' => 1,
        ]);
    }

    public function testDeleteRecord()
    {
        $this->delete(
            '/v2/mrr-commitments/'.$this->commitment->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertResponseStatus(204);

        $commitment = MrrCommitment::withTrashed()->findOrFail($this->commitment->getKey());
        $this->assertNotNull($commitment->deleted_at);
    }
}