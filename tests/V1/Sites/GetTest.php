<?php

namespace Tests\V1\Sites;

use App\Models\V1\SolutionSite;
use Tests\Traits\ResellerDatabaseMigrations;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    use ResellerDatabaseMigrations;

    public SolutionSite $solutionSite;

    public function setUp(): void
    {
        parent::setUp();
        $this->solutionSite = SolutionSite::factory()->create();
    }

    public function testGetCollectionSuccess()
    {
        $this->asAdmin()
            ->get('/v1/sites')
            ->assertStatus(200);
    }

    public function testGetResourceSuccess()
    {
        $this->asAdmin()
            ->get(
                sprintf(
                    '/v1/sites/%d',
                    $this->solutionSite->getKey()
                )
            )->assertStatus(200);
    }
}