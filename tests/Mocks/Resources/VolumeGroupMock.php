<?php

namespace Tests\Mocks\Resources;

use App\Models\V2\VolumeGroup;
use Illuminate\Database\Eloquent\Model;

trait VolumeGroupMock
{
    protected $volumeGroup;

    public function volumeGroup($id = 'volgroup-test'): VolumeGroup
    {
        if (!$this->volumeGroup) {
            Model::withoutEvents(function () use ($id) {
                $this->volumeGroup = volumeGroup::factory()->create([
                    'id' => $id,
                    'name' => $id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'vpc_id' => $this->vpc()->id,
                ]);
            });
        }
        return $this->volumeGroup;
    }
}