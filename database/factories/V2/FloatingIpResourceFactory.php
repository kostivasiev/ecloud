<?php

namespace Database\Factories\V2;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\V2\FloatingIpResource>
 */
class FloatingIpResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            //
        ];
    }

    public function assignedTo($floatingIp, $resource)
    {
        return $this->state(function (array $attributes) use ($floatingIp, $resource) {
            return [
                'floating_ip_id' => $floatingIp->id,
                'resource_id' => $resource->id,
                'resource_type' => $resource->getMorphClass(),
            ];
        });
    }
}
