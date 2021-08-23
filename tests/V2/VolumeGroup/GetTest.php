<?php
namespace Tests\V2\VolumeGroup;

use App\Models\V2\VolumeGroup;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
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

    public function testGetCollection()
    {
        $this->get('/v2/volume-groups')
            ->seeJson(
                [
                    'id' => $this->volumeGroup->id,
                ]
            )->seeJsonDoesntContains(
                [
                    'reseller_id' => $this->volumeGroup->getResellerId(),
                ]
            )->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/volume-groups/'.$this->volumeGroup->id)
            ->seeJson(
                [
                    'id' => $this->volumeGroup->id,
                ]
            )->seeJsonDoesntContains(
                [
                    'reseller_id' => $this->volumeGroup->getResellerId(),
                ]
            )->assertResponseStatus(200);
    }

    public function testGetCollectionAsAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/volume-groups')
            ->seeJson(
                [
                    'id' => $this->volumeGroup->id,
                    'reseller_id' => $this->volumeGroup->getResellerId(),
                ]
            )->assertResponseStatus(200);
    }

    public function testGetResourceAsAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/volume-groups/'.$this->volumeGroup->id)
            ->seeJson(
                [
                    'id' => $this->volumeGroup->id,
                    'reseller_id' => $this->volumeGroup->getResellerId(),
                ]
            )->assertResponseStatus(200);
    }
}