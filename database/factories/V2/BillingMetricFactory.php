<?php

namespace Database\Factories\V2;

use App\Models\V2\BillingMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillingMetricFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BillingMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'resource_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'vpc_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'reseller_id' => 1,
            'name' => 'RAM (per Megabyte)',
            'key' => 'ram.capacity',
            'value' => '16GB',
            'start' => '2020-07-07T10:30:00+01:00',
        ];
    }
}
