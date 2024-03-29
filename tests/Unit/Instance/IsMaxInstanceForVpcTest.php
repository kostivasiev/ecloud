<?php

namespace Tests\Unit\Instance;

use App\Rules\V2\IsMaxInstanceForVpc;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxInstanceForVpcTest extends TestCase
{
    protected IsMaxInstanceForVpc $validationRule;

    public function setUp(): void
    {
        parent::setUp();
        $this->validationRule = new IsMaxInstanceForVpc();
        Config::set('instance.max_limit.per_vpc', 1);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testConfigValueHasBeenChanged()
    {
        $this->assertEquals(1, config('instance.max_limit.per_vpc'));
    }

    public function testValidationFails()
    {
        // Use the instance
        $instance = $this->instanceModel();
        $this->assertFalse($this->validationRule->passes('vpc_id', $instance->vpc->id));
    }

    public function testValidationSucceeds()
    {
        $this->assertTrue($this->validationRule->passes('vpc_id', $this->vpc()->id));
    }

}