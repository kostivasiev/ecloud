<?php

namespace Tests\unit\Rules\V2;

use App\Rules\V2\IsNotMaxCommaSeperatedItems;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsNotMaxCommaSeperatedItemsTest extends TestCase
{
    public function testMaxNotReachedPasses()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $rule = new IsNotMaxCommaSeperatedItems(2);

        $this->assertTrue($rule->passes('test', '10.0.0.1, 10.0.0.2'));
    }

    public function testMaxReachedFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $rule = new IsNotMaxCommaSeperatedItems(2);

        $this->assertFalse($rule->passes('test', '10.0.0.1, 10.0.0.2, 10.0.0.3'));
    }
}