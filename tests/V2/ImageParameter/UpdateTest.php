<?php
namespace Tests\V2\ImageParameter;

use App\Models\V2\Image;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testUpdateAdminSucceeds()
    {
        $this->be((new Consumer(0, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        factory(Image::class)->create([
            'id' => 'img-test-2',
        ]);

        $this->patch(
            '/v2/image-parameters/' . $this->imageParameter()->id,
            [
                'name' => 'UPDATED NAME',
                'key' => 'UPDATEDKEY',
                'type' => 'Numeric',
                'description' => 'UPDATED DESCRIPTION',
                'required' => false,
                'validation_rule' => 'UPDATED VALIDATION RULE',
                'image_id' => 'img-test-2'
            ])->seeInDatabase(
            'image_parameters',
            [
                'name' => 'UPDATED NAME',
                'key' => 'UPDATEDKEY',
                'type' => 'Numeric',
                'description' => 'UPDATED DESCRIPTION',
                'required' => false,
                'validation_rule' => 'UPDATED VALIDATION RULE',
                'image_id' => 'img-test-2'
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testUpdateNotAdminFails()
    {
        $this->patch('/v2/image-parameters/' . $this->imageParameter()->id, [])->assertResponseStatus(401);
    }
}