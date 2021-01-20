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
        return ($this->firstOfThisMonth($theDate, $now) || $theDate >= Carbon::now());
    }

    public function firstOfThisMonth($theDate, $now): bool
    {
        return ($theDate->firstOfMonth() && $theDate->month == $now->month && $theDate->year == $now->year);
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        return 'The :attribute should be either the first of the current month or a date from today';
    }
}