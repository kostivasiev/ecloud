<?php

namespace Tests\V2\ResourceTiers;

use App\Events\V2\Task\Created;
use App\Models\V2\ResourceTier;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public const RESOURCE_URI = '/v2/resource-tiers';
    public ResourceTier $resourceTier;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testCreateResourceTierAsAdmin()
    {
        $data = [
            'availability_zone_id' => $this->availabilityZone()->id,
        ];


        $this->asAdmin()
            ->post(static::RESOURCE_URI, $data)
            ->assertStatus(201);

        $this->assertDatabaseHas(
            'resource_tiers',
            $data,
            'ecloud'
        );
    }

    public function testCreateResourceTierAsUser()
    {
        $data = [
            'availability_zone_id' => $this->availabilityZone()->id,
        ];


        $this->asUser()
            ->post(static::RESOURCE_URI, $data)
            ->assertStatus(401);

        $this->assertDatabaseMissing(
            'resource_tiers',
            $data,
            'ecloud'
        );
    }
}
