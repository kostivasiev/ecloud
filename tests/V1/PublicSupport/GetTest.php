<?php

namespace Tests\V1\PublicSupport;

use App\Models\V1\PublicSupport;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }


    public function testAdminCanSeeCollection()
    {
        $total = 2;
        PublicSupport::factory($total)->create();

        $this->get('/v1/support', [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'total' => $total,
        ])->assertStatus(200);
    }

    public function testAdminCanSeeItem()
    {
        $item = PublicSupport::factory()->create();

        $this->get('/v1/support/' . $item->getKey(), [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }


    public function testClientCantSeeCollection()
    {
        PublicSupport::factory()->create();

        $this->get('/v1/support', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(401);
    }

    public function testClientCantSeeItem()
    {
        $item = PublicSupport::factory()->create();

        $this->get('/v1/support/' . $item->getKey(), [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(401);
    }

    public function testCanFilterCollectionByResellerId()
    {
        PublicSupport::factory()->create([
            'reseller_id' => 1,
        ]);

        PublicSupport::factory()->create([
            'reseller_id' => 2,
        ]);

        $this->get('/v1/support?reseller_id=1', [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertJsonFragment([
            'total' => 1,
        ])->assertStatus(200);
    }
}
