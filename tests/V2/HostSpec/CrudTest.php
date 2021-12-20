<?php

namespace Tests\V2\HostSpec;

use Laravel\Lumen\Testing\DatabaseMigrations;
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

    public function testIndex()
    {
        $this->get('/v2/host-specs')
            ->seeJson([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
                'ucs_specification_name' => 'test-host-spec',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2643 v3',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 4000,
                'ram_capacity' => 64,
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        $this->get('/v2/host-specs/' . $this->hostSpec()->id)
            ->seeJson([
                'id' => 'hs-test',
                'name' => 'test-host-spec',
                'ucs_specification_name' => 'test-host-spec',
                'cpu_sockets' => 2,
                'cpu_type' => 'E5-2643 v3',
                'cpu_cores' => 6,
                'cpu_clock_speed' => 4000,
                'ram_capacity' => 64,
            ])
            ->assertResponseStatus(200);
    }

    public function testStoreNotAdmin()
    {
        $this->post('/v2/host-specs', ['name' => 'test-host-spec'])->assertResponseStatus(401);
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
            ->seeInDatabase('host_specs', $data, 'ecloud')
            ->assertResponseStatus(201);
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
        $this->post('/v2/host-specs', $data)
            ->assertResponseStatus(201);

        $hostSpecId = json_decode($this->response->getContent())->data->id;

        $this->seeInDatabase('availability_zone_host_spec', [
            'host_spec_id' => $hostSpecId,
            'availability_zone_id' => $this->availabilityZone()->id
        ], 'ecloud');
    }

    public function testUpdateNotAdmin()
    {
        $this->patch('/v2/host-specs/' . $this->hostSpec()->id, ['name' => 'test-host-spec'])->assertResponseStatus(401);
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
            ->seeInDatabase('host_specs', $data, 'ecloud')
            ->assertResponseStatus(200);
    }

    public function testDestroy()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->delete('/v2/host-specs/hs-test')
            ->seeInDatabase(
                'host_specs',
                [
                    'id' => 'hs-test',
                ],
                'ecloud'
            )->notSeeInDatabase(
                'host_specs',
                [
                    'id' => 'hs-test',
                    'deleted_at' => null,
                ],
                'ecloud'
            )
            ->assertResponseStatus(204);
    }

}
