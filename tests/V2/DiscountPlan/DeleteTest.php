<?php

namespace Tests\V2\DiscountPlan;

use App\Models\V2\DiscountPlan;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{

    protected DiscountPlan $discountPlan;

    public function setUp(): void
    {
        parent::setUp();
        $this->discountPlan = factory(DiscountPlan::class)->create([
            'contact_id' => 1,
        ]);
    }

    public function testDeleteRecord()
    {
        $this->delete(
            '/v2/discount-plans/'.$this->discountPlan->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->assertResponseStatus(204);

        $discountPlan = DiscountPlan::withTrashed()->findOrFail($this->discountPlan->id);
        $this->assertNotNull($discountPlan->deleted_at);
    }
}