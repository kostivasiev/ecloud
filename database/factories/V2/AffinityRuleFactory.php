<?php

namespace Database\Factories\V2;

use App\Models\V2\AffinityRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffinityRuleFactory extends Factory
{
    protected $model = AffinityRule::class;

    public function definition()
    {
        return [
            'type' => 'anti-affinity',
        ];
    }
}
