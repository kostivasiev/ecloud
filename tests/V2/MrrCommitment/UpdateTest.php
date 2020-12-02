<?php

namespace Tests\V2\MrrCommitment;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Models\V2\Vpn;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{

    use DatabaseMigrations;

    protected \Faker\Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }
}