<?php
namespace Database\Factories\V1;

use App\Models\V1\ActiveDirectoryDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiveDirectoryDomainFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ActiveDirectoryDomain::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ad_domain_reseller_id' => 1,
            'ad_domain_name' => $this->faker->domainName,
        ];
    }
}
