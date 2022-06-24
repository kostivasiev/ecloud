<?php

namespace Database\Factories\V2;

use App\Models\V2\ResourceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceTierFactory extends Factory
{
    protected $model = ResourceTier::class;

    public function definition()
    {
        return [
            'active' => true
        ];
    }
}
