<?php

namespace Tests\V2\Host;

use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testIndex()
    {
        $this->host();
        $this->get('/v2/hosts')
            ->seeJson([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => 'hg-test',
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        $this->host();
        $this->get('/v2/hosts/h-test')
            ->seeJson([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => 'hg-test',
            ])
            ->assertResponseStatus(200);
    }

    public function testStore()
    {
        $this->conjurerServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v1/vpc/vpc-test/volume')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['uuid' => 'uuid-test-uuid-test-uuid-test']));
            });











        $data = [
            'name' => 'h-test',
            'host_group_id' => $this->hostGroup()->id,
        ];
        $this->post('/v2/hosts', $data)
            ->seeInDatabase('hosts', $data, 'ecloud')
            ->assertResponseStatus(201);
    }

    public function testUpdate()
    {
        $this->host();
        $this->patch('/v2/hosts/h-test', [
            'name' => 'new name',
        ])->seeInDatabase(
            'hosts',
            [
                'id' => 'h-test',
                'name' => 'new name',
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testDestroy()
    {
        /**
         * Switch out the seeInDatabase/notSeeInDatabase with assertSoftDeleted(...) when we switch to Laravel
         * @see https://laravel.com/docs/5.8/database-testing#available-assertions
         */
        $this->host();
        $this->delete('/v2/hosts/h-test')
            ->seeInDatabase(
                'hosts',
                [
                    'id' => 'h-test',
                ],
                'ecloud'
            )->notSeeInDatabase(
                'hosts',
                [
                    'id' => 'h-test',
                    'deleted_at' => null,
                ],
                'ecloud'
            )->assertResponseStatus(204);
    }
}
