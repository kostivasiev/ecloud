<?php

namespace Tests\V2;

use Tests\TestCase;

class DocsTest extends TestCase
{
    public function testDocsPublicV1()
    {
        $this->get('/v1/docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(200);
    }

    public function testDocsPublicV2()
    {
        $this->get('/v2/docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(200);
    }

    public function testDocsAdminV1()
    {
        $this->get('/v1/admin_docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(200);
    }

    public function testDocsAdminV2()
    {
        $this->get('/v2/admin_docs.yaml', [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.read',
        ])->assertResponseStatus(200);
    }
}
