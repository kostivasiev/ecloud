<?php

namespace Tests\V2\ResourceTierHostGroup;

use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\AvailabilityZone;
use Database\Seeders\ResourceTierSeeder;
use Tests\TestCase;

class GetTest extends TestCase
{
    public const RULE_RESOURCE_URI = '/v2/affinity-rules/%s/members';
    public const MEMBER_RESOURCE_URI = '/v2/affinity-rule-members/%s';



    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();
    }

    public function testGetCollectionAdminPasses()
    {

    }

    public function testGetCollectionUserFails()
    {

    }

    public function testGetitemAdminPasses()
    {

    }

    public function testGetItemUserFails()
    {

    }
}
