<?php

namespace Tests\V1\Credits;

use App\Services\AccountsService;
use Tests\V1\TestCase;

class GetTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test GET credits
     */
    public function testValidCollection()
    {
        app()->bind(AccountsService::class, function () {
            $mock = \Mockery::mock(AccountsService::class)->makePartial();
            $mock->allows('getVmEncryptionCredits')
                ->andReturnUsing(function () {
                    return [
                        'type' => 'ecloud_vm_encryption',
                    ];
                });
            return $mock;
        });
        $this->get('/v1/credits', $this->validReadHeaders)
            ->assertJsonFragment([
                'type' => 'ecloud_vm_encryption'
            ])
            ->assertStatus(200);
    }
}
