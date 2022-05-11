<?php
namespace Database\Factories\V2;

use App\Models\V2\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoftwareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Software::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'platform' => 'Linux',
            'visibility' => Software::VISIBILITY_PUBLIC,
        ];
    }
}
