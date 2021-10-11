<?php

namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

/**
 * Class IsNotMaxCommaSeperatedItems
 * @package App\Rules\V2
 */
class IsNotMaxCommaSeperatedItems implements Rule
{
    protected int $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    public function passes($attribute, $value)
    {
        if (count(explode(',', $value)) <= $this->count) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return ":attribute must contain less than {$this->count} comma-seperated items";
    }
}
