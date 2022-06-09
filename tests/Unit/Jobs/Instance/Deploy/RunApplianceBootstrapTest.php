<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\ImageParameter;
use Database\Seeders\Images\CpanelImageSeeder;
use Database\Seeders\Images\PleskImageSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use UKFast\Admin\Account\AdminContactClient;

class RunApplianceBootstrapTest extends TestCase
{
    public function testLoadsAndRendersEncryptedPasswords()
    {
        ImageParameter::factory()->create([
            'image_id' => $this->image()->id,
            'name' => 'Plesk Admin Password',
            'key' => 'plesk_admin_password',
            'type' => 'Password',
            'description' => 'Plesk Admin Password',
            'required' => true,
            'validation_rule' => '/\w+/',
        ]);

        $this->image()->setAttribute(
            'script_template',
            '{{{ plesk_admin_email_address }}} {{{ plesk_admin_password }}}'
        )->save();

        $this->instanceModel()->setAttribute('deploy_data', [
            'image_data' => [
                'plesk_admin_email_address' => 'elmer.fudd@example.com'
            ]
        ])->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instanceModel()->credentials()->save($credential);

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'plesk_admin_password',
            'username' => 'plesk_admin_password',
            'password' => 'somepassword',
            'is_hidden' => true
        ]);

        $this->instanceModel()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('elmer.fudd@example.com somepassword'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new RunApplianceBootstrap($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCreatesDefaultCpanelHostname()
    {
        (new CpanelImageSeeder())->run();

        $this->instanceModel()
            ->setAttribute('image_id', 'img-cpanel')
            ->setAttribute('deploy_data', [
                'floating_ip_id' => $this->floatingIp()->id,
            ])
            ->save();

        $this->instanceModel()->image->setAttribute(
            'script_template',
            'ARG_HOSTNAME="{{cpanel_hostname}}"'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instanceModel()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('ARG_HOSTNAME="' . $this->floatingIp()->ip_address . '.srvlist.ukfast.net"'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new RunApplianceBootstrap($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCpanelHostnamePopulatesFromImageData()
    {
        (new CpanelImageSeeder())->run();

        $this->instanceModel()
            ->setAttribute('image_id', 'img-cpanel')
            ->setAttribute('deploy_data', [
                'floating_ip_id' => $this->floatingIp()->id,
                'image_data' => [
                    'cpanel_hostname' => 'my.cpanel.hostname'
                ]
            ])
            ->save();

        $this->instanceModel()->image->setAttribute(
            'script_template',
            'ARG_HOSTNAME="{{cpanel_hostname}}"'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instanceModel()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('ARG_HOSTNAME="my.cpanel.hostname"'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new RunApplianceBootstrap($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGetsDefaultAdminEmailFromCustomerAccount()
    {
        (new PleskImageSeeder())->run();

        Image::find('img-plesk')
            ->setAttribute('script_template', '{{{ plesk_admin_email_address }}}')
            ->save();

        $this->instanceModel()->setAttribute('image_id', 'img-plesk')->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instanceModel()->credentials()->save($credential);

        $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
        $mockAccountAdminClient->allows('setResellerId')
            ->withAnyArgs()
            ->andReturnUsing(function () {
                $mockAdminContactClient = \Mockery::mock(AdminContactClient::class)->makePartial();
                $mockAdminContactClient->allows('customers')->andReturnSelf();
                $mockAdminContactClient->allows('contacts')->andReturnSelf();
                $mockAdminContactClient->allows('getById')
                    ->with(1)
                    ->andReturnUsing(function () {
                        return new \UKFast\Admin\Account\Entities\Customer([
                            'primaryContactId' => 111,
                        ]);
                    });
                $mockAdminContactClient->allows('getById')
                    ->with(111)
                    ->andReturnUsing(function () {
                        return new \UKFast\Admin\Account\Entities\Contact([
                            'emailAddress' => 'captain.kirk@example.com',
                        ]);
                    });
                return $mockAdminContactClient;
            });

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () use ($mockAccountAdminClient) {
            return $mockAccountAdminClient;
        });

        $this->kingpinServiceMock()
            ->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id .
                '/instance/' . $this->instanceModel()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('captain.kirk@example.com'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new RunApplianceBootstrap($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGeneratesRandomPassword()
    {
        (new PleskImageSeeder())->run();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instanceModel()->credentials()->save($credential);

        $this->instanceModel()
            ->setAttribute('deploy_data', [
                'image_data' => [
                    'plesk_admin_email_address' => 'elmer.fudd@example.com'
                ]
            ])
            ->setAttribute('image_id', 'img-plesk')
            ->save();

        $this->kingpinServiceMock()
            ->expects('post')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $job = new RunApplianceBootstrap($this->instanceModel());
        $job->handle();

        $this->assertTrue(isset($job->imageData['plesk_admin_password']));
        $this->assertNotEmpty($job->imageData['plesk_admin_password']);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGeneratesPleskCredentials()
    {
        (new PleskImageSeeder())->run();

        $this->instanceModel()->credentials()->save(
            app()->make(Credential::class)
            ->fill([
                'name' => 'root',
                'username' => 'root',
                'password' => 'root'
            ])
        );

        $this->instanceModel()
            ->setAttribute('deploy_data', [
                'image_data' => [
                    'plesk_admin_email_address' => 'elmer.fudd@example.com'
                ]
            ])
            ->setAttribute('image_id', 'img-plesk')
            ->save();

        $this->kingpinServiceMock()
            ->expects('post')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $job = new RunApplianceBootstrap($this->instanceModel());
        $job->handle();

        $this->assertTrue(isset($job->imageData['plesk_admin_password']));
        $this->assertNotEmpty($job->imageData['plesk_admin_password']);

        $credential = Credential::where('name', 'Plesk Administrator')->first();

        $this->assertNotNull($credential);
        $this->assertEquals('Plesk Administrator', $credential->name);
        $this->assertEquals('admin', $credential->username);
        $this->assertEquals('somepassword', $credential->password);
        $this->assertEquals(8880, $credential->port);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNonZeroExitCode()
    {
        (new PleskImageSeeder())->run();

        $this->instanceModel()->credentials()->save(
            app()->make(Credential::class)
                ->fill([
                    'name' => 'root',
                    'username' => 'root',
                    'password' => 'root'
                ])
        );

        $this->instanceModel()
            ->setAttribute('deploy_data', [
                'image_data' => [
                    'plesk_admin_email_address' => 'elmer.fudd@example.com'
                ]
            ])
            ->setAttribute('image_id', 'img-plesk')
            ->save();

        $this->kingpinServiceMock()
            ->expects('post')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'exitCode' => 1,
                    'output' => 'General order 66 received',
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new RunApplianceBootstrap($this->instanceModel()));

        Event::assertDispatched(JobFailed::class);
    }
}
