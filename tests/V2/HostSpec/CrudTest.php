<?php

namespace Tests\V2\HostSpec;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->hostSpec();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndexAsAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/host-specs')
            ->assertJsonFragment([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
                'ucs_specification_name' => 'test-host-spec',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2643 v3',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 4000,
                'ram_capacity' => 64,
            ])
            ->assertStatus(200);
    }

    public function testShowAsAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/host-specs/' . $this->hostSpec()->id)
            ->assertJsonFragment([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
                'ucs_specification_name' => 'test-host-spec',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2643 v3',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 4000,
                'ram_capacity' => 64,
            ])
            ->assertStatus(200);
    }

    public function testIndex()
    {
        $this->get('/v2/host-specs')
            ->assertJsonFragment([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2643 v3',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 4000,
                'ram_capacity' => 64,
            ])
            ->assertStatus(200);
    }

    public function testShow()
    {
        $this->get('/v2/host-specs/' . $this->hostSpec()->id)
            ->assertJsonFragment([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2643 v3',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 4000,
                'ram_capacity' => 64,
            ])
            ->assertStatus(200);
    }

    public function testStoreNotAdmin()
    {
        $this->post('/v2/host-specs', ['name' => 'test-host-spec'])->assertStatus(401);
    }

    public function testStoreAsAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'name' =>  'test-host-spec',
            'ucs_specification_name' => 'test-host-spec',
            'cpu_sockets' => 2,
            'cpu_type' => 'E5-2643 v3',
            'cpu_cores' => 6,
            'cpu_clock_speed' => 4000,
            'ram_capacity' => 64
        ];
        $this->post('/v2/host-specs', $data)
            ->assertStatus(201);

        $this->assertDatabaseHas('host_specs', $data, 'ecloud');
    }

    public function testAssignToAvailabilityZone()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'name' => 'test-host-spec',
            'ucs_specification_name' => 'test-host-spec',
            'cpu_sockets' => 2,
            'cpu_type' => 'E5-2643 v3',
            'cpu_cores' => 6,
            'cpu_clock_speed' => 4000,
            'ram_capacity' => 64,
            'availability_zones' => [
                [
                    'id' => $this->availabilityZone()->id
                ]
            ]
        ];
        $response = $this->post('/v2/host-specs', $data)
            ->assertStatus(201);

        $hostSpecId = json_decode($response->getContent())->data->id;

        $this->assertDatabaseHas('availability_zone_host_spec', [
            'host_spec_id' => $hostSpecId,
            'availability_zone_id' => $this->availabilityZone()->id
        ], 'ecloud');
    }

    public function testUpdateNotAdmin()
    {
        $this->patch('/v2/host-specs/' . $this->hostSpec()->id, ['name' => 'test-host-spec'])->assertStatus(401);
    }

    public function testUpdateAsAdmin()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $data = [
            'name' =>  'test-host-spec - RENAMED',
            'ucs_specification_name' => 'test-host-spec',
            'cpu_sockets' => 1,
            'cpu_type' => "E5-2643 v3 - RENAMED",
            'cpu_cores' => 1,
            'cpu_clock_speed' => 1,
            'ram_capacity' => 1
        ];
        $this->patch('/v2/host-specs/' . $this->hostSpec()->id, $data)
            ->assertStatus(200);
        $this->assertDatabaseHas('host_specs', $data, 'ecloud');
    }

    public function testDestroy()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->delete('/v2/host-specs/hs-test')
            ->assertStatus(204);

        $this->assertDatabaseHas(
            'host_specs',
            [
                'id' => 'hs-test',
            ],
            'ecloud'
        )->assertDatabaseMissing(
            'host_specs',
            [
                'id' => 'hs-test',
                'deleted_at' => null,
            ],
            'ecloud'
        );
    }
}
