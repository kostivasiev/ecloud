<?php

namespace Tests\V2\Region;

use App\Models\V2\Region;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    public function testGetCollectionAsAdmin()
    {
        // Region only visible to admins
        factory(Region::class)->create();

        $this->region()->is_public = true;
        $this->region()->save();

        $this->get('/v2/regions', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->region()->id,
            'name' => $this->region()->name,
        ])->assertResponseStatus(200);

        $this->assertCount(2, $this->response->original);
    }

    public function testGetCollectionAsNonAdmin()
    {
        // Region only visible to admins
        factory(Region::class)->create();

        $this->region()->is_public = true;
        $this->region()->save();

        $this->get('/v2/regions', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->region()->id,
            'name' => $this->region()->name,
        ])->assertResponseStatus(200);

        $this->assertCount(1, $this->response->original);
    }

    public function testGetPublicRegionAsAdmin()
    {
        $this->region()->is_public = true;
        $this->region()->save();

        $this->get('/v2/regions/' . $this->region()->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->region()->id,
            'name' => $this->region()->name,
        ])->assertResponseStatus(200);
    }

    public function testGetPublicRegionAsNonAdmin()
    {
        $this->region()->is_public = true;
        $this->region()->save();

        $this->get('/v2/regions/' . $this->region()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->region()->id,
            'name' => $this->region()->name,
        ])->assertResponseStatus(200);
    }

    public function testGetPrivateRegionAsAdmin()
    {
        $this->region()->is_public = false;
        $this->region()->save();

        $this->get('/v2/regions/' . $this->region()->id, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $this->region()->id,
            'name' => $this->region()->name,
        ])->assertResponseStatus(200);
    }

    public function testGetPrivateRegionAsNonAdmin()
    {
        $this->region()->is_public = false;
        $this->region()->save();

        $this->get('/v2/regions/' . $this->region()->id, [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(404);
    }

    public function testGetPublicRegionAvailabilityZonesAsNonAdmin()
    {
        $this->region()->is_public = true;
        $this->region()->save();

        $availabilityZones = $this->region()->availabilityZones()->get();

        $this->get('/v2/regions/' . $this->region()->id . '/availability-zones', [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $availabilityZones->first()->id,
            'name' => $availabilityZones->first()->name,
        ])->assertResponseStatus(200);
    }

    public function testGetPublicRegionAvailabilityZonesAsAdmin()
    {
        $this->region()->is_public = true;
        $this->region()->save();

        $availabilityZones = $this->region()->availabilityZones()->get();

        $this->get('/v2/regions/' . $this->region()->id . '/availability-zones', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->seeJson([
            'id' => $availabilityZones->first()->id,
            'name' => $availabilityZones->first()->name,
        ])->assertResponseStatus(200);
    }
}
