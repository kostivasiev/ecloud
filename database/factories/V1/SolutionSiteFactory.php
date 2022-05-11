<?php

namespace Database\Factories\V1;

use App\Models\V1\Pod;
use App\Models\V1\Solution;
use App\Models\V1\Storage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\V1\SolutionSite>
 */
class SolutionSiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $storage = Storage::factory()->create();
        return [
            'ucs_site_state' => 'Active',
            'ucs_site_order' => 1,
            'ucs_site_ucs_reseller_id' => Solution::factory()->create(),
            'ucs_site_ucs_datacentre_id' => Pod::factory()->create(),
        ];
    }
}
