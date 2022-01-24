<?php

namespace Tests\unit\Rules\V2;

use App\Rules\V2\IsScoped;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsScopedTest extends TestCase
{
    public function testIsScoped()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))
            ->setIsAdmin(true));

        $rule = new IsScoped();

        $this->assertFalse(
            $rule->passes('', '')
        );
    }

    public function testIsntScoped()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))
            ->setIsAdmin(false));

        $rule = new IsScoped();

        $this->assertTrue(
            $rule->passes('', '')
        );
    }
}
