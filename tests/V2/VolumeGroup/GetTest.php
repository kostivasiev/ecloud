<?php
namespace Tests\V2\VolumeGroup;

use App\Models\V2\Volume;
use App\Models\V2\VolumeGroup;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    use VolumeGroupMock;

    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->volume = Volume::withoutEvents(function () {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'name' => 'Volume',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
                'volume_group_id' => $this->volumeGroup()->id,
                'port' => 0,
            ]);
        });
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testGetCollection()
    {
        $this->get('/v2/volume-groups')
            ->seeJson(
                [
                    'id' => $this->volumeGroup()->id,
                ]
            )->seeJsonDoesntContains(
                [
                    'reseller_id' => $this->volumeGroup()->getResellerId(),
                ]
            )->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/volume-groups/'.$this->volumeGroup()->id)
            ->seeJson(
                [
                    'id' => $this->volumeGroup()->id,
                ]
            )->seeJsonDoesntContains(
                [
                    'reseller_id' => $this->volumeGroup()->getResellerId(),
                ]
            )->assertResponseStatus(200);
    }

    public function testGetVolumeCollection()
    {
        $this->get('/v2/volume-groups/'.$this->volumeGroup()->id.'/volumes')
            ->seeJson(
                [
                    'id' => $this->volume->id,
                ]
            )->assertResponseStatus(200);
    }
}