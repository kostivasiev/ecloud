<?php
namespace Database\Factories\V1;

use App\Models\V1\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Storage::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ucs_datacentre_id' => 1,
            'server_id' => 1,
            'qos_enabled' => 'No',
        ];
    }
}
