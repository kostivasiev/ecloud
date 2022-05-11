<?php

namespace Tests\V1\Solutions;

use App\Events\V1\EncryptionEnabledOnSolutionEvent;
use App\Models\V1\Solution;
use App\Models\V1\Tag;
use Illuminate\Support\Facades\Event;
use Tests\V1\TestCase;

class PatchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test for valid collection
     * @return void
     */
    public function testSetName()
    {
        $testString = 'phpUnit test string';

        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);


        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);


        $this->patchJson('/v1/solutions/123', [
            'name' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(204);


        $this->assertDatabaseHas('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);
    }

    public function testSetTag()
    {
        $testString = 'phpUnit test string';

        Solution::factory()->create([
            'ucs_reseller_id' => 123,
        ]);

        Tag::factory()->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);


        $this->assertDatabaseMissing('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
            'metadata_value' => $testString,
        ]);


        $this->patchJson('/v1/solutions/123/tags/test', [
            'value' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(200);


        $this->assertDatabaseHas('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
            'metadata_value' => $testString,
        ]);
    }

    public function testEnableEncryption()
    {
        Event::fake();
        $solution = Solution::factory()->create([
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'No'
        ]);

        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes',
        ]);


        $res = $this->patchJson('/v1/solutions/123', [
            'encryption_enabled' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(204);


        Event::assertDispatched(EncryptionEnabledOnSolutionEvent::class, function ($e) use ($solution) {
            return $e->solution->ucs_reseller_id === $solution->first()->ucs_reseller_id;
        });

        $this->assertDatabaseHas('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes'
        ]);
    }

    public function testEnableEncryptionUnauthorised()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123
        ]);

        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes',
        ]);


        $this->patchJson('/v1/solutions/123', [
            'encryption_billing_type' => 'Contract',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(204);

        /**
         * Should not have been updated due to whitelist
         */
        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes',
        ]);
    }

    public function testSetEncryptionDefault()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'No'
        ]);

        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Yes',
        ]);


        $this->patchJson('/v1/solutions/123', [
            'encryption_default' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(204);

        $this->assertDatabaseHas('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Yes'
        ]);
    }

    public function testSetEncryptionBillingType()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123
        ]);

        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Contract',
        ]);


        $this->patchJson('/v1/solutions/123', [
            'encryption_billing_type' => 'Contract',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(204);

        $this->assertDatabaseHas('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_billing_type' => 'Contract'
        ]);
    }

    public function testSetEncryptionBillingTypeUnauthorised()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123
        ]);

        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Contract',
        ]);


        $this->patchJson('/v1/solutions/123', [
            'encryption_billing_type' => 'Contract',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(204);

        /**
         * Should not have been updated due to whitelist
         */
        $this->assertDatabaseMissing('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Contract',
        ]);
    }

    public function testSetEncryptionBillingTypeUnknown()
    {
        Solution::factory()->create([
            'ucs_reseller_id' => 123
        ]);

        $this->patchJson('/v1/solutions/123', [
            'encryption_billing_type' => 'RANDOM STRING',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(422);
    }
}
