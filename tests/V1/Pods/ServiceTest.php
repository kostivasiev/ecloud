<?php

namespace Tests\V1\Pods;

use App\Models\V1\Pod;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Test service enabled
     * @return void
     */
    public function testValidServiceEnabled()
    {
        $pod = factory(Pod::class)->create([
            'ucs_datacentre_public_enabled' => 'Yes',
        ]);

        $this->assertTrue($pod->hasEnabledService('Public'));
    }

    /**
     * Test service disabled
     * @return void
     */
    public function testValidServiceDisabled()
    {
        $pod = factory(Pod::class)->create([
            'ucs_datacentre_public_enabled' => 'No',
        ]);

        $this->assertFalse($pod->hasEnabledService('Public'));
    }

    /**
     * Test unknown service returns false
     * @return void
     */
    public function testUnknownServiceFails()
    {
        $pod = factory(Pod::class)->create();

        $this->assertFalse($pod->hasEnabledService('unknown'));
    }
}
