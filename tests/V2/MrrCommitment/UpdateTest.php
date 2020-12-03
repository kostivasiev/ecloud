<?php

namespace Tests\V2\MrrCommitment;

use App\Models\V2\MrrCommitment;
use App\Rules\V2\CommitmentIsGreater;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{

    use DatabaseMigrations;

    protected MrrCommitment $commitment;

    public function setUp(): void
    {
        parent::setUp();
        $this->commitment = factory(MrrCommitment::class)->create([
            'contact_id' => 1,
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d H:i:s', strtotime('now')),
            'term_end_date' => date('Y-m-d H:i:s', strtotime('2 days')),
        ]);
    }

    public function testCommitmentIsGreaterRule()
    {
        $validationRule = new CommitmentIsGreater($this->commitment->getKey());

        // commitment_amount
        $this->assertFalse($validationRule->passes('commitment_amount', 1000));
        $this->assertTrue($validationRule->passes('commitment_amount', 2500));

        // commitment_before_discount
        $this->assertFalse($validationRule->passes('commitment_before_discount', 500));
        $this->assertTrue($validationRule->passes('commitment_before_discount', 1500));

        // term_length
        $this->assertFalse($validationRule->passes('term_length', 12));
        $this->assertTrue($validationRule->passes('term_length', 36));

        // term_start_date
        $this->assertFalse($validationRule->passes('term_start_date', date('Y-m-d H:i:s', strtotime('yesterday'))));
        $this->assertTrue($validationRule->passes('term_start_date', date('Y-m-d H:i:s', strtotime('tomorrow'))));

        // term_end_date
        $this->assertFalse($validationRule->passes('term_end_date', date('Y-m-d H:i:s', strtotime('yesterday'))));
        $this->assertTrue($validationRule->passes('term_end_date', date('Y-m-d H:i:s', strtotime('3 days'))));
    }

    public function testUpdateItem()
    {
        $data = [
            'name' => 'updated-test-commitment',
            'commitment_amount' => '3000',
            'commitment_before_discount' => '2000',
            'discount_rate' => '10',
            'term_length' => '36',
            'term_start_date' => date('Y-m-d H:i:s', strtotime('tomorrow')),
            'term_end_date' => date('Y-m-d H:i:s', strtotime('4 days')),
        ];
        $this->patch(
            '/v2/mrr-commitments/'.$this->commitment->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read, ecloud.write',
                'X-Reseller-Id' => 1,
            ]
        )->seeInDatabase(
            'mrr_commitments',
            $data,
            'ecloud'
        )->assertResponseStatus(200);
    }
}
