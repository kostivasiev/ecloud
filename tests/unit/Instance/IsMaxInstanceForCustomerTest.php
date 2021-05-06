<?php
namespace Tests\unit\Instance;

use App\Http\Middleware\IsMaxInstanceForCustomer;
use Illuminate\Support\Facades\Config;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxInstanceForCustomerTest extends TestCase
{
    use DatabaseMigrations;

    protected $validationRule;

    public function setUp(): void
    {
        parent::setUp();
        $this->validationRule = \Mockery::mock(IsMaxInstanceForCustomer::class)->makePartial();
        Config::set('instance.max_limit.total', 1);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testConfigValueHasBeenChanged()
    {
        $this->assertEquals(1, config('instance.max_limit.total'));
    }

    public function testValidationFails()
    {
        // Use the instance
        $this->instance();
        $this->assertFalse($this->validationRule->isWithinLimit());
    }

    public function testValidationSucceeds()
    {
        $this->assertTrue($this->validationRule->isWithinLimit());
    }

}