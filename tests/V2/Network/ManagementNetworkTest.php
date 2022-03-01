<?php

namespace Tests\V2\Network;

use App\Models\V2\Network;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class ManagementNetworkTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->router()->setAttribute('is_management', true)->save();
    }

    public function testGetManagedNetworkNotAdminFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $this->get('/v2/networks/' . $this->network()->id)
            ->assertResponseStatus(404);
    }

    public function testGetManagedNetworkAdminPasses()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $this->get('/v2/networks/' . $this->network()->id)
            ->seeJson([
                'is_hidden' => true
            ])
            ->assertResponseStatus(200);
    }
}
