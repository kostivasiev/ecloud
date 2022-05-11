<?php

namespace Tests\V2;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function testDocsPublic()
    {
        $this->get('/v2/docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }

    public function testDocsAdmin()
    {
        $this->get('/v2/admin-docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }
}
