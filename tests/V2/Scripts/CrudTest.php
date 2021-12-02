<?php

namespace Tests\V2\Scripts;

use App\Models\V2\Software;
use Database\Seeders\SoftwareSeeder;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class CrudTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        (new SoftwareSeeder())->run();
    }

    public function testIndex()
    {
        // 'public' visibility of related software displays to non admin
        $this->get('/v2/scripts')
            ->seeJson([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertResponseStatus(200);

        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        // 'private' visibility of related software does not display to non admin
        $this->get('/v2/scripts')
            ->dontSeeJson([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertResponseStatus(200);

        // 'private' visibility of related software displays to admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/scripts')
            ->seeJson([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertResponseStatus(200);
    }

    public function testShow()
    {
        // 'public' visibility of related software displays to non admin
        $this->get('/v2/scripts/scr-test-1')
            ->seeJson([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertResponseStatus(200);

        $software = Software::first();
        $software->setAttribute('visibility', Software::VISIBILITY_PRIVATE)->save();

        // 'private' visibility of related software does not display to non admin
        $this->get('/v2/scripts/scr-test-1')->assertResponseStatus(404);

        // 'private' visibility of related software displays to admin
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->get('/v2/scripts/scr-test-1')
            ->seeJson([
                'id' => 'scr-test-1',
                'name' => 'Script 1',
                'software_id' => 'soft-aaaaaaaa',
                'sequence' => 1,
                'script' => 'exit 0',
            ])
            ->assertResponseStatus(200);
    }

    public function testStore()
    {
        $data = [
            'name' => 'Script Test',
            'software_id' => 'soft-aaaaaaaa',
            'script' => 'exit 0'
        ];

        // Not admin fails
        $this->post('/v2/scripts', $data)->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        // Sequence number already used fails
        $this->post('/v2/scripts', array_merge($data, ['sequence' => 1]))->assertResponseStatus(422);

        $data = array_merge($data, ['sequence' => 4]);
        $this->post('/v2/scripts', $data)
            ->seeInDatabase('scripts', $data, 'ecloud')
            ->assertResponseStatus(201);
    }

    public function testUpdate()
    {
        $data = [
            'name' => 'Test - UPDATED',
            'sequence' => 99,
            'script' => 'exit 1',
        ];

        // Not admin fails
        $this->patch('/v2/scripts/scr-test-1', $data)->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->patch('/v2/scripts/scr-test-1', $data)
            ->seeInDatabase('scripts', $data, 'ecloud')
            ->assertResponseStatus(200);
    }

    public function testDestroy()
    {
        // Not admin fails
        $this->delete('/v2/scripts/scr-test-1')->assertResponseStatus(401);

        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/scripts/scr-test-1')
            ->notSeeInDatabase(
                'scripts',
                [
                    'id' => 'scr-test-1',
                    'deleted_at' => null,
                ],
                'ecloud'
            )->assertResponseStatus(204);
    }
}
