<?php

namespace App\Rules\V2\Instance;

use App\Models\V2\Instance;
use Illuminate\Contracts\Validation\Rule;

class IsNotAssignedToHostGroup implements Rule
{
    public function __construct(
        public Instance $instance
    ) {}

    public function passes($attribute, $value)
    {
        return $this->instance->host_group_id != $value;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The instance is already assigned to the specified host group';
    }
}
