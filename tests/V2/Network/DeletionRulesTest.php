<?php

namespace Tests\V2\Network;

use App\Models\V2\Network;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    public function testFailedDeletion()
    {
        $this->nic();

        $this->delete(
            '/v2/networks/' . $this->network()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertJsonFragment([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->nic()->id,
        ])->assertStatus(412);
        $network = Network::withTrashed()->findOrFail($this->network()->id);
        $this->assertNull($network->deleted_at);
    }
}
