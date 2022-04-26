<?php

namespace Tests\Unit\Console\Commands\Nic;

use App\Console\Commands\Nic\MigrateIpAddressToIpAddressModel;
use App\Models\V2\IpAddress;
use Tests\TestCase;

class MigrateIpAddressToIpAddressModelTest extends TestCase
{
    public function testSuccess()
    {
        $this->assertEquals(0, IpAddress::all()->count());

        $command = \Mockery::mock(MigrateIpAddressToIpAddressModel::class)->makePartial();
        $command->updated = 0;
        $command->errors = 0;

        $command->allows('option')->andReturnFalse();
        $command->allows('info')->with(\Mockery::capture($message));

        // Create a NIC with an IP address on the model
        $this->nic()->setAttribute('ip_address', '10.0.0.5')->save();

        // Run the script
        $returnCode = $command->handle();

        // Assert return status code is 0 (Success)
        $this->assertEquals(0, $returnCode);
        // Assert 1 record created
        $this->assertEquals('Total Updated: 1, Total Errors: 0', $message);
        // Assert record added to database
        $this->assertEquals(1, IpAddress::all()->count());

        // Assert IpAddress record is associated with the NIC
        $ipAddress = $this->nic()->ipAddresses()->first();

        // Assert IP address matches IP from NIC
        $this->assertEquals('10.0.0.5', $ipAddress->getIpAddress());
        $this->assertEquals(IpAddress::TYPE_DHCP, $ipAddress->type);

        // Assert IP address is deleted from NIC record
        $this->nic()->refresh();
        $this->assertNull($this->nic()->ip_adddress);
    }

    public function testNoIpOnNicModelSkips()
    {
        $this->nic();

        $pendingCommand = $this->artisan('nic:migrate-ip-address')
            ->expectsOutput('Total Updated: 0, Total Errors: 0');
        $pendingCommand->assertSuccessful();
    }

    public function testIpRecordAlreadyExistsDhcpSuccess()
    {
        $this->nic()->setAttribute('ip_address', '10.0.0.5')->save();

        IpAddress::factory()->for($this->network())->create([
            'ip_address' => '10.0.0.5',
            'type' => IpAddress::TYPE_DHCP
        ]);

        $this->assertEquals(1, IpAddress::all()->count());

        $pendingCommand = $this->artisan('nic:migrate-ip-address')
            ->expectsOutput('Total Updated: 0, Total Errors: 0');

        $pendingCommand->assertSuccessful();

        $this->assertEquals(1, IpAddress::all()->count());
    }

    public function testIpRecordAlreadyExistsTypeClusterOutputsError()
    {
        $this->nic()->setAttribute('ip_address', '10.0.0.5')->save();

        IpAddress::factory()->for($this->network())->create([
            'ip_address' => '10.0.0.5',
            'type' => IpAddress::TYPE_CLUSTER
        ]);

        $pendingCommand = $this->artisan('nic:migrate-ip-address')
            ->expectsOutputToContain('The IP address is already in use')
            ->expectsOutput('Total Updated: 0, Total Errors: 1');

        $pendingCommand->assertSuccessful();
    }
}
