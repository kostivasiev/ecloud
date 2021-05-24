<?php

namespace Tests\V2\Instances;

use App\Models\V2\Task;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class CreateImageTest extends TestCase
{
    public function testCreateImageTest()
    {
        $this->image()->name = 'createImageTest';
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/template')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->post(
            '/v2/instances/' . $this->instance()->id . '/create-image',
            [
                'name' => $this->image()->name,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);
    }
}