<?php
namespace Database\Factories\V1;

use App\Models\V1\PublicSupport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PublicSupportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PublicSupport::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'id' => Str::uuid(),
            'reseller_id' => 1,
        ];
    }
}
