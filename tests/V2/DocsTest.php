<?php

namespace Tests\V2;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function testDocs()
    {
        $this->get('/v2/docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(200);
    }
}
