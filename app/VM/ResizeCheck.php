<?php

namespace App\VM;

use App\Models\V1\VirtualMachine;
use App\VM\Exceptions\InvalidVmStateException;

class ResizeCheck
{
    /**
     * List of allowed statuses for a vm to be in
     * when being resized
     *
     * @var array
     */
    const ALLOWED_STATUSES = [
        Status::COMPLETE
    ];

    /**
     * @param App\Models\V1\VirtualMachine $vm
     */
    public function __construct(VirtualMachine $vm)
    {
        $this->vm = $vm;
    }

    public function validate()
    {
        if (collect(static::ALLOWED_STATUSES)->contains($this->vm->servers_status)) {
            return true;
        }

        $e = new InvalidVmStateException($this->vm->servers_status);
        $e->detail = 'Cannot resize VM whilst in this status';

        throw $e;
    }
}
