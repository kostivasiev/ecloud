<?php

namespace Tests\V2\HostSpec;

use Tests\TestCase;

class IsHiddenTest extends TestCase
{
    public const HOST_GROUP_ITEM = '/v2/host-groups/%s';
    public const HOST_SPECS_ITEM = '/v2/host-specs/%s';

    public function setUp(): void
    {
        parent::setUp();
        $this->hostGroup();
        $this->hostSpec()->setAttribute('is_hidden', true)->saveQuietly();
    }

    public function testHiddenSpecNotVisibleToUser()
    {
        $this->asUser()
            ->get(
                sprintf(static::HOST_SPECS_ITEM, $this->hostSpec()->id)
            )->assertStatus(404);
    }

    public function testHiddenSpecVisibleToAdmin()
    {
        $this->asAdmin()
            ->get(
                sprintf(static::HOST_SPECS_ITEM, $this->hostSpec()->id)
            )->assertJsonFragment([
                'id' => $this->hostSpec()->id,
            ])->assertStatus(200);
    }

    public function testHostSpecIdHiddenFromUser()
    {
        $this->asUser()
            ->get(
                sprintf(static::HOST_GROUP_ITEM, $this->hostGroup()->id)
            )->assertJsonFragment([
                'host_spec_id' => null,
            ])->assertStatus(200);
    }

    public function testHostSpecIdVisibleToAdmin()
    {
        $this->asAdmin()
            ->get(
                sprintf(static::HOST_GROUP_ITEM, $this->hostGroup()->id)
            )->assertJsonFragment([
                'host_spec_id' => $this->hostSpec()->id,
            ])->assertStatus(200);
    }
}