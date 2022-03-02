<?php
namespace Database\Factories\V2;

use App\Models\V2\VpnSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class VpnSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = VpnSession::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'remote_ip' => '218.16.12.11',
        ];
    }
}
