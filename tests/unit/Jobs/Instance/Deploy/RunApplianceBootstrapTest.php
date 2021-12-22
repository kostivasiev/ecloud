<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Models\V2\Credential;
use App\Models\V2\ImageMetadata;
use App\Models\V2\ImageParameter;
use Database\Seeders\Images\CpanelImageSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Tests\TestCase;
use UKFast\Admin\Account\AdminContactClient;

class RunApplianceBootstrapTest extends TestCase
{
    public function testLoadsAndRendersEncryptedPasswords()
    {
        factory(ImageParameter::class)->create([
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

        $this->instance()->setAttribute('deploy_data', [
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
        $this->instance()->credentials()->save($credential);

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'plesk_admin_password',
            'username' => 'plesk_admin_password',
            'password' => 'somepassword',
            'is_hidden' => true
        ]);

        $this->instance()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
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

        dispatch(new RunApplianceBootstrap($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCreatesDefaultCpanelHostname()
    {
        (new CpanelImageSeeder())->run();

        $this->instance()
            ->setAttribute('image_id', 'img-cpanel')
            ->setAttribute('deploy_data', [
                'floating_ip_id' => $this->floatingIp()->id,
            ])
            ->save();

        $this->instance()->image->setAttribute(
            'script_template',
            'ARG_HOSTNAME="{{cpanel_hostname}}"'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
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

        dispatch(new RunApplianceBootstrap($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testCpanelHostnamePopulatesFromImageData()
    {
        (new CpanelImageSeeder())->run();

        $this->instance()
            ->setAttribute('image_id', 'img-cpanel')
            ->setAttribute('deploy_data', [
                'floating_ip_id' => $this->floatingIp()->id,
                'image_data' => [
                    'cpanel_hostname' => 'my.cpanel.hostname'
                ]
            ])
            ->save();

        $this->instance()->image->setAttribute(
            'script_template',
            'ARG_HOSTNAME="{{cpanel_hostname}}"'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
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

        dispatch(new RunApplianceBootstrap($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGetsDefaultAdminEmailFromCustomerAccount()
    {
        factory(ImageParameter::class)->create([
            'image_id' => $this->image()->id,
            'name' => 'Plesk Admin Password',
            'key' => 'plesk_admin_password',
            'type' => 'Password',
            'description' => 'Plesk Admin Password',
            'required' => true,
            'validation_rule' => '/\w+/',
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.identifier',
            'value' => 'PLESK-12-VPS-WEB-HOST-1M',
            'image_id' => $this->image()->id
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'plesk',
            'image_id' => $this->image()->id
        ]);

        $this->image()->setAttribute(
            'script_template',
            '{{{ plesk_admin_email_address }}} {{{ plesk_admin_password }}}'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'plesk_admin_password',
            'username' => 'plesk_admin_password',
            'password' => 'somepassword',
            'is_hidden' => true
        ]);
        $this->instance()->credentials()->save($credential);

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
                '/api/v2/vpc/' . $this->instance()->vpc->id .
                '/instance/' . $this->instance()->id .
                '/guest/linux/script',
                [
                    'json' => [
                        'encodedScript' => base64_encode('captain.kirk@example.com somepassword'),
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new RunApplianceBootstrap($this->instance()));

        $pleskAdminCredential = $this->instance()
            ->credentials()
            ->where('name', '=', 'Plesk Administrator')
            ->first();
        $this->assertEquals('somepassword', $pleskAdminCredential->password);
        $this->assertEquals(config('plesk.admin.port', 8880), $pleskAdminCredential->port);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testGeneratesRandomPassword()
    {
        factory(ImageParameter::class)->create([
            'image_id' => $this->image()->id,
            'name' => 'Plesk Admin Password',
            'key' => 'plesk_admin_password',
            'type' => 'Password',
            'description' => 'Plesk Admin Password',
            'required' => false,
            'validation_rule' => '/\w+/',
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.identifier',
            'value' => 'PLESK-12-VPS-WEB-HOST-1M',
            'image_id' => $this->image()->id
        ]);

        factory(ImageMetadata::class)->create([
            'key' => 'ukfast.license.type',
            'value' => 'plesk',
            'image_id' => $this->image()->id
        ]);

        $this->image()->setAttribute(
            'script_template',
            '{{{ plesk_admin_email_address }}} {{{ plesk_admin_password }}}'
        )->save();

        $credential = app()->make(Credential::class);
        $credential->fill([
            'name' => 'root',
            'username' => 'root',
            'password' => 'somepassword'
        ]);
        $this->instance()->credentials()->save($credential);

        $this->instance()->setAttribute('deploy_data', [
            'image_data' => [
                'plesk_admin_email_address' => 'elmer.fudd@example.com'
            ]
        ])->save();

        $this->kingpinServiceMock()
            ->expects('post')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $job = new RunApplianceBootstrap($this->instance());
        $job->handle();


        Event::assertNotDispatched(JobFailed::class);
    }
}
