<?php
namespace Tests\V2\ImageParameter;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    public function testAdminDeleteSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));
        $this->delete('/v2/image-parameters/' . $this->imageParameter()->id)->assertStatus(204);
    }

    public function testNotAdminDeleteFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/image-parameters/' . $this->imageParameter()->id)->assertStatus(401);
    }
}