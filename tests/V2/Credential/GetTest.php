<?php

namespace Tests\V2\Credential;

use App\Models\V2\Credential;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var Credential
     */
    protected $credential;

    public function setUp(): void
    {
        parent::setUp();
        $this->credential = factory(Credential::class)->create();
    }
    
    public function testGetCollection()
    {
        $this->get(
            '/v2/credentials',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson($this->formatDates($this->credential->toArray()))
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/credentials/' . $this->credential->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson($this->formatDates($this->credential->toArray()))
            ->assertResponseStatus(200);
    }

    protected function formatDates(array $resource)
    {
        $resource['created_at'] = Carbon::parse($resource['created_at'])
            ->toIso8601String();
        $resource['updated_at'] = Carbon::parse($resource['updated_at'])
            ->toIso8601String();

        return $resource;
    }
}
