<?php

namespace Tests\V2\Nic;

use App\Models\V2\Nic;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeletionRulesTest extends TestCase
{
    public function testFailedDeletion()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->floatingIp()->resource()->associate($this->nic());
        $this->floatingIp()->save();

        $this->delete('/v2/nics/' . $this->nic()->id)
            ->assertJsonFragment([
                'detail' => 'The specified resource has dependant relationships and cannot be deleted',
            ])->assertStatus(412);
        $nic = Nic::withTrashed()->findOrFail($this->nic()->id);
        $this->assertNull($nic->deleted_at);
    }
}
