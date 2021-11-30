<?php
namespace Database\Factories\V2;

use App\Models\V2\Script;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScriptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Script::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'script' => 'exit 0'
        ];
    }
}
