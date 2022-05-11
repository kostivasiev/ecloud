<?php

namespace Tests\V1;

class DocsTest extends TestCase
{
    public function testDocsPublic()
    {
        $this->get('/v1/docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }

    public function testDocsAdmin()
    {
        $this->get('/v1/admin-docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertStatus(200);
    }
}
