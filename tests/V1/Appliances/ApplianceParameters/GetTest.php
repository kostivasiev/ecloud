<?php

namespace Tests\V1\Appliances\ApplianceParameters;

use App\Models\V1\ApplianceParameter;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\V1\ApplianceTestCase;

class GetTest extends ApplianceTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test GET Appliance parameters collection
     */
    public function testValidCollection()
    {
        $this->get('/v1/appliance-parameters', $this->validReadHeaders)
            ->assertStatus(200)
            ->assertJsonFragment([
                'total' => ApplianceParameter::query()->count()
            ]);
    }

    /**
     * Test GET Appliance parameter Item
     */
    public function testValidItem()
    {
        $parameter = $this->appliances[0]->getLatestVersion()->parameters[0];

        $this->get('/v1/appliance-parameters/' . $parameter->uuid, $this->validReadHeaders)
            ->assertStatus(200);
    }
}
