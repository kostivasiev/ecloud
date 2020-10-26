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

    protected $floating_ip;
    protected $nic;
    protected $nat;

    public function setUp(): void
    {
        parent::setUp();

        $this->floating_ip = factory(FloatingIp::class)->create();
        $this->nic = factory(Nic::class)->create();
        $this->nat = factory(Nat::class)->create([
            'destination_id' => $this->floating_ip->id,
            'destinationable_type' => 'fip',
            'translated_id' => $this->nic->id,
            'translatedable_type' => 'nic',
        ]);
    }

    public function testDestinationResourceReturnsFloatingIp()
    {
        $this->assertTrue($this->nat->destination instanceof FloatingIp);
    }

    public function testTranslatedResourceReturnsNic()
    {
        $this->assertTrue($this->nat->translated instanceof Nic);
    }

    public function testRuleIdReturnsCorrectValue()
    {
        $this->assertTrue($this->nat->rule_id == $this->floating_ip->id . '-to-' . $this->nic->id);
    }
}
