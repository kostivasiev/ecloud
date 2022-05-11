<?php

namespace Tests\V1\VirtualMachines;

use App\Models\V1\VirtualMachine;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidCollection()
    {
        $total = rand(1, 2);
        VirtualMachine::factory($total)->create();

        $this->get('/v1/vms', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200)
            ->assertJsonFragment([
            'total' => $total,
        ]);
    }

    public function testValidItem()
    {
        VirtualMachine::factory(1)->create([
            'servers_id' => 123,
        ]);

        $this->get('/v1/vms/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }

    /**
     * Validation failure
     */
    public function testInvalidItemValidation()
    {
        $this->get('/v1/vms/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(422);
    }

    /**
     * Non-existent item
     */
    public function testInvalidItem()
    {
        $this->get('/v1/vms/999', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(404);
    }


    //TODO: tags test?
}
