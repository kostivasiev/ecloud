<?php

namespace Tests\unit\Listeners\Nat;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use DatabaseMigrations;

    protected $floatingIp;
    protected $nic;
    protected $nat;

    public function setUp(): void
    {
        parent::setUp();

        $this->floatingIp = factory(FloatingIp::class)->create();
        $this->nic = factory(Nic::class)->create();
        $this->nat = factory(Nat::class)->create([
            'destination' => $this->floatingIp->id,
            'translated' => $this->nic->id,
        ]);
    }

    public function testEvent()
    {
        $newFloatingIp = factory(FloatingIp::class)->create();

//        $listener = \Mockery::mock()
//            ->shouldReceive('handle')
//            ->withArgs(['model' => $this->nat])
//            ->andReturn(false);
//        app()->bind(Deploy::class, function () use ($listener) {
//            return $listener;
//        });

        $this->nat->destination = $newFloatingIp->id;
        $this->nat->save();

        $this->assertTrue($this->nat->rule_id == $this->floatingIp->id . '-to-' . $this->nic->id);
    }
}
