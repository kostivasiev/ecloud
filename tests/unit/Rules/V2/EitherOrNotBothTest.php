<?php
namespace Tests\unit\Rules\V2;

use App\Rules\V2\EitherOrNotBoth;
use Illuminate\Http\Request;
use Tests\TestCase;

class EitherOrNotBothTest extends TestCase
{
    protected $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = \Mockery::mock(EitherOrNotBoth::class)->makePartial();
    }

    public function testOneValueShouldPass()
    {
        $data = [
            'local_networks' => '192.168.0.0/16',
        ];
        $this->rule->field = 'remote_networks';
        $this->rule->request = new Request($data);
        $this->assertTrue($this->rule->passes('local_networks', $data['local_networks']));
    }

    public function testBothValuesShouldFail()
    {
        $data = [
            'local_networks' => '192.168.0.0/16',
            'remote_networks' => '192.168.0.0/16',
        ];
        $this->rule->field = 'remote_networks';
        $this->rule->request = new Request($data);
        $this->assertFalse($this->rule->passes('local_networks', $data['local_networks']));
        $this->assertFalse($this->rule->passes('remote_networks', $data['remote_networks']));
    }
}