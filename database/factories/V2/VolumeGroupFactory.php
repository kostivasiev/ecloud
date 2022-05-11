<?php
namespace Database\Factories\V2;

use App\Models\V2\VolumeGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class VolumeGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VolumeGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Primary Volume Group',
        ];
    }
}
