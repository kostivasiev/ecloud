<?php

namespace Tests\V1\VirtualMachines;

use App\Models\V1\VirtualMachine;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testValidCollection()
    {
        $total = rand(1, 2);
        factory(VirtualMachine::class, $total)->create();

        $this->get('/v1/vms', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $total,
        ]);
    }

    public function testValidItem()
    {
        factory(VirtualMachine::class, 1)->create([
            'servers_id' => 123,
        ]);

        $this->get('/v1/vms/123', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200);
    }

    /**
     * Validation failure
     */
    public function testInvalidItemValidation()
    {
        $this->get('/v1/vms/abc', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(422);
    }

    /**
     * Non-existent item
     */
    public function testInvalidItem()
    {
        $this->get('/v1/vms/999', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(404);
    }


    //TODO: tags test?
}
