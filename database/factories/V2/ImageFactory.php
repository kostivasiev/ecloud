<?php

namespace Database\Factories\V2;

use App\Models\V2\Image;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Image::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => 'Test Image',
            'vpc_id' => null,
            'logo_uri' => 'https://images.ukfast.co.uk/logos/centos/300x300_white.png',
            'documentation_uri' => 'https://docs.centos.org/en-US/docs/',
            'description' => 'CentOS (Community enterprise Operating System)',
            'script_template' => '',
            'readiness_script' => '',
            'vm_template' => 'CentOS7 x86_64',
            'platform' => 'Linux',
            'active' => true,
            'public' => true,
            'visibility' => Image::VISIBILITY_PUBLIC,
        ];
    }
}
