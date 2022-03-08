<?php

namespace Database\Factories\V2;

use App\Models\V2\DiscountPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = DiscountPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'reseller_id' => 1,
            'name' => 'test-commitment',
            'commitment_amount' => '2000',
            'commitment_before_discount' => '1000',
            'discount_rate' => '5',
            'term_length' => '24',
            'term_start_date' => date('Y-m-d H:i:s', strtotime('now')),
            'term_end_date' => date('Y-m-d H:i:s', strtotime('2 days')),
        ];
    }
}
