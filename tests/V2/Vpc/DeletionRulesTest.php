<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    use DatabaseMigrations;

    public function testFailedDeletion()
    {
        $this->instance();
        $this->delete(
            '/v2/vpcs/' . $this->vpc()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
        ])->assertResponseStatus(412);
        $vpc = Vpc::withTrashed()->findOrFail($this->vpc()->id);
        $this->assertNull($vpc->deleted_at);
    }
}
