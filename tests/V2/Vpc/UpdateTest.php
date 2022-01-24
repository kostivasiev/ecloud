<?php

namespace Tests\V2\Vpc;

use App\Events\V2\Task\Created;
use App\Events\V2\Vpc\Saved;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function testNoPermsIsDenied()
    {
        $this->patch('/v2/vpcs/' . $this->vpc()->id, [
            'name' => 'Manchester DC',
        ])->seeJson([
            'title' => 'Unauthorized',
            'detail' => 'Unauthorized',
            'status' => 401,
        ])->assertResponseStatus(401);
    }

    public function testNullNameIsDenied()
    {
        $this->patch('/v2/vpcs/' . $this->vpc()->id, [
            'name' => '',
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Validation Error',
            'detail' => 'The name field is required',
            'status' => 422,
            'source' => 'name'
        ])->assertResponseStatus(422);
    }

    public function testNonMatchingResellerIdFails()
    {
        Event::fake();
        $this->vpc()->reseller_id = 3;
        $this->vpc()->save();
        $this->patch('/v2/vpcs/' . $this->vpc()->id, [
            'name' => 'Manchester DC',
        ], [
            'X-consumer-custom-id' => '1-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson([
            'title' => 'Not found',
            'detail' => 'No Vpc with that ID was found',
            'status' => 404,
        ])->assertResponseStatus(404);
    }

    public function testNoAdminFailsWhenConsoleIsSet()
    {
        $data = [
            'name' => 'name',
            'reseller_id' => 2,
            'console_enabled' => true,
        ];
        $this->patch('/v2/vpcs/' . $this->vpc()->id, $data, [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeJson(
            [
                'title' => 'Forbidden',
                'details' => 'Console access cannot be modified',
                'status' => 403
            ]
        )->assertResponseStatus(403);
    }

    public function testValidDataIsSuccessful()
    {
        Event::fake();
        $data = [
            'name' => 'name',
            'reseller_id' => 2,
            'console_enabled' => true,
        ];
        $this->patch('/v2/vpcs/' . $this->vpc()->id, $data, [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        Event::assertDispatched(Saved::class);
    }

    public function testSupportToggledOn()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
        Event::fake(Created::class);

        $this->vpc()->disableSupport();

        $this->assertFalse($this->vpc()->refresh()->support_enabled);

        $this->patch(
            '/v2/vpcs/' . $this->vpc()->id,
            [
                'support_enabled' => true,
            ]
        );//->assertResponseStatus(202);

        dd($this->response->getContent(), $this->response->headers);

        $this->vpc()->refresh();

        $this->assertTrue($this->vpc()->support_enabled);
    }

    public function testSupportToggledOff()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(false));
        Event::fake(Created::class);

        $this->vpc()->enableSupport();

        $this->assertTrue($this->vpc()->refresh()->support_enabled);

        $this->patch(
            '/v2/vpcs/' . $this->vpc()->id,
            [
                'support_enabled' => false,
            ]
        )->assertResponseStatus(202);

        $this->vpc()->refresh();

        $this->assertFalse($this->vpc()->support_enabled);
    }
}
