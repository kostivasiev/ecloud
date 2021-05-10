<?php

namespace Tests\V1\PublicSupport;

use App\Models\V1\PublicSupport;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
        factory(PublicSupport::class, $total)->create();

        $this->get('/v1/support', [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => $total,
        ]);
    }

    public function testAdminCanSeeItem()
    {
        $item = factory(PublicSupport::class)->create();

        $this->get('/v1/support/' . $item->getKey(), [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200);
    }


    public function testClientCantSeeCollection()
    {
        $total = 1;
        factory(PublicSupport::class, $total)->create();

        $this->get('/v1/support', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(401);
    }

    public function testClientCantSeeItem()
    {
        $item = factory(PublicSupport::class)->create();

        $this->get('/v1/support/' . $item->getKey(), [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(401);
    }

    public function testCanFilterCollectionByResellerId()
    {
        factory(PublicSupport::class)->create([
            'reseller_id' => 1,
        ]);

        factory(PublicSupport::class)->create([
            'reseller_id' => 2,
        ]);

        $this->get('/v1/support?reseller_id=1', [
            'X-consumer-custom-id' => '0-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->assertResponseStatus(200) && $this->seeJson([
            'total' => 1,
        ]);
    }
}
