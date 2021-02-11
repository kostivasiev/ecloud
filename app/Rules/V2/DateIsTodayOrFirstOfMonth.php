<?php

namespace App\Rules\V2;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;

class DateIsTodayOrFirstOfMonth implements Rule
{

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        $now = Carbon::now();
        $theDate = Carbon::createFromTimeString($value);
        return ($theDate->isSameDay($now->firstOfMonth()) || $theDate >= Carbon::now());
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute should be either the first of the current month or a date from today';
    }
}
