<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
use Illuminate\Foundation\Testing\DatabaseMigrations;;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    public function testFailedDeletion()
    {
        $this->instanceModel();
        $this->delete(
            '/v2/vpcs/' . $this->vpc()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
        ])->assertStatus(412);
        $vpc = Vpc::withTrashed()->findOrFail($this->vpc()->id);
        $this->assertNull($vpc->deleted_at);
    }
}
