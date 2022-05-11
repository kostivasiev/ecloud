<?php
namespace Tests\Mocks\Resources;

use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Model;

trait VolumeMock
{
    protected $volume;

    public function volume($id = 'vol-test'): Volume
    {
        if (!$this->volume) {
            $this->volume = Volume::withoutEvents(function () use ($id) {
                return Volume::factory()->create([
                    'id' => $id,
                    'vpc_id' => $this->vpc()->id,
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
                ]);
            });
        }
        return $this->volume;
    }
}