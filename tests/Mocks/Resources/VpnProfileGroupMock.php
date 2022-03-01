<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\VpnProfileGroup;
use Illuminate\Database\Eloquent\Model;

trait VpnProfileGroupMock
{
    protected $vpnProfileGroup;

    public function vpnProfileGroup($id = 'vpnpg-test'): VpnProfileGroup
    {
        if (!$this->vpnProfileGroup) {
            Model::withoutEvents(function () use ($id) {
                $this->vpnProfileGroup = factory(VpnProfileGroup::class)->create([
                    'id' => $id,
                    'availability_zone_id' => $this->availabilityZone()->id
                ]);
            });
        }
        return $this->vpnProfileGroup;
    }
}