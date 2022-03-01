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

        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);


        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);


        $this->json('PATCH', '/v1/solutions/123', [
            'name' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);


        $this->assertResponseStatus(204) && $this->seeInDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_solution_name' => $testString,
        ]);
    }

    public function testSetTag()
    {
        $testString = 'phpUnit test string';

        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
        ]);

        factory(Tag::class, 1)->create([
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
        ]);


        $this->missingFromDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
            'metadata_value' => $testString,
        ]);


        $this->json('PATCH', '/v1/solutions/123/tags/test', [
            'value' => $testString,
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);


        $this->assertResponseStatus(200) && $this->seeInDatabase('metadata', [
            'metadata_resource' => 'ucs_reseller',
            'metadata_resource_id' => 123,
            'metadata_key' => 'test',
            'metadata_value' => $testString,
        ]);
    }

    public function testEnableEncryption()
    {
        Event::fake();
        $solution = factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'No'
        ]);

        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes',
        ]);


        $res = $this->json('PATCH', '/v1/solutions/123', [
            'encryption_enabled' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);


        Event::assertDispatched(EncryptionEnabledOnSolutionEvent::class, function ($e) use ($solution) {
            return $e->solution->ucs_reseller_id === $solution->first()->ucs_reseller_id;
        });

        $this->assertResponseStatus(204);

        $this->seeInDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes'
        ]);
    }

    public function testEnableEncryptionUnauthorised()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123
        ]);

        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes',
        ]);


        $this->json('PATCH', '/v1/solutions/123', [
            'encryption_billing_type' => 'Contract',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(204);

        /**
         * Should not have been updated due to whitelist
         */
        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_enabled' => 'Yes',
        ]);
    }

    public function testSetEncryptionDefault()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'No'
        ]);

        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Yes',
        ]);


        $this->json('PATCH', '/v1/solutions/123', [
            'encryption_default' => true,
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(204);

        $this->seeInDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Yes'
        ]);
    }

    public function testSetEncryptionBillingType()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123
        ]);

        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Contract',
        ]);


        $this->json('PATCH', '/v1/solutions/123', [
            'encryption_billing_type' => 'Contract',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(204);

        $this->seeInDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_billing_type' => 'Contract'
        ]);
    }

    public function testSetEncryptionBillingTypeUnauthorised()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123
        ]);

        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Contract',
        ]);


        $this->json('PATCH', '/v1/solutions/123', [
            'encryption_billing_type' => 'Contract',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(204);

        /**
         * Should not have been updated due to whitelist
         */
        $this->missingFromDatabase('ucs_reseller', [
            'ucs_reseller_id' => 123,
            'ucs_reseller_encryption_default' => 'Contract',
        ]);
    }

    public function testSetEncryptionBillingTypeUnknown()
    {
        factory(Solution::class, 1)->create([
            'ucs_reseller_id' => 123
        ]);

        $this->json('PATCH', '/v1/solutions/123', [
            'encryption_billing_type' => 'RANDOM STRING',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(422);
    }
}
