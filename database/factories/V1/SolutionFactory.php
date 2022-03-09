<?php
namespace Database\Factories\V1;

use App\Models\V1\Solution;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolutionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Solution::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'ucs_reseller_reseller_id' => 1,
            'ucs_reseller_datacentre_id' => 1,
            'ucs_reseller_solution_name' => $this->faker->sentence(),
            'ucs_reseller_status' => 'Completed',
            'ucs_reseller_active' => 'Yes',
            'ucs_reseller_encryption_enabled' => 'No',
            'ucs_reseller_encryption_default' => 'Yes',
            'ucs_reseller_encryption_billing_type' => 'PAYG',
            'ucs_reseller_nplusone_active' => 'Yes',
            'ucs_reseller_nplus_redundancy' => 'None',
            'ucs_reseller_nplus_overprovision' => 'No',
        ];
    }
}
