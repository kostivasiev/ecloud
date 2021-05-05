<?php

namespace Tests\V2\RouterThroughput;

use App\Models\V2\RouterThroughput;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ScopingTest extends TestCase
{
    public RouterThroughput $routerThroughput;

    public function setUp(): void
    {
        parent::setUp();
        $this->routerThroughput = factory(RouterThroughput::class)->create([
            'availability_zone_id' => $this->availabilityZone()->getKey()
        ]);
    }

    public function testGetPublicResource()
    {
        $this->get(
            '/v2/router-throughputs/'.$this->routerThroughput->id,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeJson(
            [
                'availability_zone_id' => 'az-test'
            ]
        )->assertResponseStatus(200);
    }

    public function testGetNonPublicResourceAsAdmin()
    {
        // Make Availability Zone non-public
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->save();

        $this->get(
            '/v2/router-throughputs/'.$this->routerThroughput->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->seeJson(
            [
                'availability_zone_id' => 'az-test'
            ]
        )->assertResponseStatus(200);
    }

    public function testGetNonPublicResourceAsUser()
    {
        // Make Availability Zone non-public
        $this->availabilityZone()->is_public = false;
        $this->availabilityZone()->save();

        $this->get(
            '/v2/router-throughputs/'.$this->routerThroughput->id,
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
            ]
        )->assertResponseStatus(404);
    }
}