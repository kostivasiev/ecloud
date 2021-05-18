<?php
namespace Tests\V2\Image;

use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->image();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testUpdatePublicResourceAdmin()
    {
        Event::fake(\App\Events\V2\Task\Created::class);

        $this->patch(
            '/v2/images/' . $this->image()->id,
            [
                'name' => '',
                'logo_uri' => '',
                'documentation_uri' => '',
                'description' => '',
                'script_template' => '',
                'vm_template' => '',
                'platform' => '',
                'active' => '',
                'public' => '',
            ]
        )->seeInDatabase(
            'network_policies',
            [
                'id' => 'np-test',
                'name' => 'New Policy Name',
            ],
            'ecloud'
        )->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testUpdatePublicResourceNotAdmin()
    {

    }

    public function testUpdatePrivateResourceAdmin()
    {

    }

    public function testUpdatePrivateResourceNotAdminIsOwner()
    {

    }

    public function testUpdatePrivateResourceNotAdminNotOwner()
    {

    }
}
