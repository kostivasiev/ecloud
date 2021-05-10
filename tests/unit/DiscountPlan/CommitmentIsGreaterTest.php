<?php

namespace Tests\unit\DiscountPlan;

use App\Models\V2\DiscountPlan;
use App\Rules\V2\CommitmentIsGreater;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CommitmentIsGreaterTest extends TestCase
{
    public function testCommitmentIsGreaterRule()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $discountPlan = factory(DiscountPlan::class)->create([
            'contact_id' => 1,
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d 00:00:00', strtotime('now')),
            'term_end_date' => date('Y-m-d 00:00:00', strtotime('2 days')),
        ]);

        $validationRule = new CommitmentIsGreater($discountPlan->id);

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
}
