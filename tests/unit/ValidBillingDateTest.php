<?php

namespace Tests\unit;

use App\Rules\V2\DateIsTodayOrFirstOfMonth;
use Carbon\Carbon;
use Tests\TestCase;

class ValidBillingDateTest extends TestCase
{

    protected DateIsTodayOrFirstOfMonth $validationRule;

    public function setUp(): void
    {
        parent::setUp();
        $this->validationRule = new DateIsTodayOrFirstOfMonth();
    }

    public function testDateIsFirstOfThisMonth()
    {
        $this->assertTrue($this->validationRule->passes('', date('Y-m-01 H:i:s')));
    }

    public function testDateIsFirstOfLastMonth()
    {
        $lastMonth = new Carbon('first day of last month');
        $this->assertFalse($this->validationRule->passes('', $lastMonth->format('Y-m-d H:i:s')));
    }

    public function testAnyDayLastMonth()
    {
        $sometimeLastMonth = Carbon::now()->subDays(35);
        $this->assertFalse($this->validationRule->passes('', $sometimeLastMonth->format('Y-m-d H:i:s')));
    }

    public function testDateIsTomorrow()
    {
        $tomorrow = new Carbon('tomorrow');
        $this->assertTrue($this->validationRule->passes('', $tomorrow->format('Y-m-d H:i:s')));
    }
}