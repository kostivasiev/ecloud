<?php
namespace Database\Factories\V2;

use App\Models\V2\Volume;
use Illuminate\Database\Eloquent\Factories\Factory;

class VolumeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Volume::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'capacity' => 100,
        ];
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function sharedVolume($volumeGroupId = null)
    {
        return $this->state(function (array $attributes) use ($volumeGroupId) {
            return [
                'os_volume' => false,
                'is_shared' => true,
                'port' => 1,
                'volume_group_id' => $volumeGroupId
            ];
        });
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function osVolume()
    {
        return $this->state(function (array $attributes) {
            return [
                'os_volume' => true,
                'vmware_uuid' => 'uuid-test-uuid-test-uuid-test',
            ];
        });
    }
}
