<?php

namespace Tests\V1;

use App\Models\V1\VirtualMachine;
use App\VM\Exceptions\InvalidVmStateException;
use App\VM\ResizeCheck;
use App\VM\Status;

class ResizeCheckTest extends TestCase
{
    public function invalidStatuses()
    {
        return [
            [Status::INCOMPLETE],
            [Status::UNKNOWN],
            [Status::SETUP_STARTED],
            [Status::BEING_BUILT],
        ];
    }

    public function validStatuses()
    {
        return [
            [ResizeCheck::ALLOWED_STATUSES],
        ];
    }

    /**
     * @test
     * @dataProvider validStatuses
     */
    public function allows_resize_when_vm_complete($status)
    {
        $vm = new VirtualMachine;
        $vm->servers_status = Status::COMPLETE;

        $check = new ResizeCheck($vm);

        $this->assertTrue($check->validate());
    }

    /**
     * @test
     * @dataProvider invalidStatuses
     */
    public function throws_exception_when_in_invalid_state($status)
    {
        $vm = new VirtualMachine;
        $vm->servers_status = $status;

        $check = new ResizeCheck($vm);

        try {
            $check->validate();
        } catch (InvalidVmStateException $e) {
            $this->assertEquals($status, $e->getState());
            $this->assertEquals(400, $e->getStatusCode());
            $this->assertEquals('Cannot resize VM whilst in this status', $e->detail);
            return;
        }

        // If it reaches this point, then the exception wasn't thrown
        $this->expectException(InvalidVmStateException::class);
    }
}