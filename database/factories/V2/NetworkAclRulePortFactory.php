<?php
namespace Database\Factories\V2;

use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkAclRulePortFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NetworkRulePort::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ];
    }
}
