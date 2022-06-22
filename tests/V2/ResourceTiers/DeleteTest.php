<?php

namespace Tests\V2\ResourceTiers;

use App\Events\V2\Task\Created;
use App\Models\V2\ResourceTier;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    public const RESOURCE_URI = '/v2/resource-tiers/%s';
    public ResourceTier $resourceTier;

    public function setUp(): void
    {
        parent::setUp();
        $this->resourceTier = ResourceTier::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
        ])->create();
    }

    public function testDeleteResourceAsAdmin()
    {

        $this->asAdmin()
            ->delete(sprintf(static::RESOURCE_URI, $this->resourceTier->id))
            ->assertStatus(204);
    }

    public function testDeleteResourceFailsAsUser()
    {
        $this->asUser()
            ->delete(sprintf(static::RESOURCE_URI,  $this->resourceTier->id))
            ->assertStatus(401);
    }
}
