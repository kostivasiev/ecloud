<?php

namespace Database\Factories\V2;

use App\Models\V2\Credential;
use Illuminate\Database\Eloquent\Factories\Factory;

class CredentialFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Credential::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'resource_id' => 'abc-abc132',
            'host' => 'https://127.0.0.1',
            'username' => 'someuser',
            'password' => 'somepassword',
            'port' => 8080
        ];
    }
}
