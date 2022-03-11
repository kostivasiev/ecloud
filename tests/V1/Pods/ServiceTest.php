<?php

namespace Tests\V1\Pods;

use App\Models\V1\Pod;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\V1\TestCase;

class ServiceTest extends TestCase
{
    /**
     * Test service enabled
     * @return void
     */
    public function testValidServiceEnabled()
    {
        $pod = Pod::factory()->create([
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
        $pod = Pod::factory()->create([
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
        $pod = Pod::factory()->create();

        $this->assertFalse($pod->hasEnabledService('unknown'));
    }
}
