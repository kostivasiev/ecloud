<?php

namespace Tests\V1;

use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Event;
use Tests\CreatesApplication;
use Tests\Traits\ResellerDatabaseMigrations;
use UKFast\Api\Auth\Consumer;

abstract class TestCase extends BaseTestCase
{
    use ResellerDatabaseMigrations, CreatesApplication, InteractsWithDatabase;

    public $validReadHeaders = [
        'X-consumer-custom-id' => '1-1',
        'X-consumer-groups' => 'ecloud.read',
    ];

    public $validWriteHeaders = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.read, ecloud.write',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $mockEncrypter = \Mockery::mock(\Illuminate\Encryption\Encrypter::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        app()->bind('encrypter', function () use ($mockEncrypter) {
            $mockEncrypter->allows('encrypt')->andReturns('EnCrYpTeD-pAsSwOrD');
            $mockEncrypter->allows('decrypt')->andReturns('somepassword');
            return $mockEncrypter;
        });

        Event::fake([
            \App\Events\V1\DatastoreCreatedEvent::class,
        ]);
    }

    public function addSolution()
    {
        \DB::table('ucs_reseller')
            ->insert([
                'ucs_reseller_id' => '1',
                'ucs_reseller_reseller_id' => '1',
                'ucs_reseller_active' => 'Yes',
                'ucs_reseller_solution_name' => 'Single Site Solution',
                'ucs_reseller_status' => 'Completed',
                'ucs_reseller_start_date' => '0000-00-00 00:00:00',
                'ucs_reseller_datacentre_id' => '5',
                'ucs_reseller_encryption_enabled' => 'No',
                'ucs_reseller_encryption_default' => 'Yes',
                'ucs_reseller_encryption_billing_type' => 'PAYG'
            ]);
        return $this;
    }

    public function asAdmin()
    {
        $consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $consumer->setIsAdmin(true);
        $this->be($consumer);
        return $this;
    }

    public function asUser()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        return $this;
    }
}
