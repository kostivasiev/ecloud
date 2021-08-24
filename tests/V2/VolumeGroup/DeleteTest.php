<?php
namespace Tests\V2\VolumeGroup;

use App\Events\V2\Task\Created;
use App\Models\V2\Volume;
use App\Models\V2\VolumeGroup;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected VolumeGroup $volumeGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->volumeGroup = factory(VolumeGroup::class)->create(
            [
                'name' => 'Unit Test Volume Group',
                'availability_zone_id' => $this->availabilityZone()->id,
                'vpc_id' => $this->vpc()->id,
            ]
        );
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testSuccessfulDelete()
    {
        Event::fake(Created::class);
        $this->delete('/v2/volume-groups/' . $this->volumeGroup->id)
            ->assertResponseStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testDeleteWithAttachedVolumeFails()
    {
        $volume = Volume::withoutEvents(function () {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'name' => 'Volume',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test'
            ]);
        });
        $this->volumeGroup->volumes()->save($volume);
        $this->delete('/v2/volume-groups/' . $this->volumeGroup->id)
            ->seeJson(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted'
                ]
            )->assertResponseStatus(412);
    }
}