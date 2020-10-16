<?php

namespace Tests\unit;

use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class NatTest extends TestCase
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

    public function testDestinationResourceReturnsFloatingIp()
    {
        $this->assertTrue($this->nat->destination_resource instanceof FloatingIp);
    }

    public function testTranslatedResourceReturnsNic()
    {
        $this->assertTrue($this->nat->translated_resource instanceof Nic);
    }

    public function testRuleIdReturnsCorrectValue()
    {
        $this->assertTrue($this->nat->rule_id == $this->floatingIp->id . '-to-' . $this->nic->id);
    }
}
