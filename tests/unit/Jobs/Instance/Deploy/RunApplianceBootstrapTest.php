<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Models\V2\Credential;
use App\Models\V2\ImageMetadata;
use App\Models\V2\ImageParameter;
use App\Services\V2\PasswordService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Mustache_Engine;
use Tests\TestCase;

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

    public function testGetsAdminEmailAndPassword()
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

        $this->accountsServiceMock()
            ->allows('getPrimaryContactId')
            ->andReturns('111');
        $this->accountsServiceMock()
            ->allows('getPrimaryContactEmail')
            ->andReturns('captain.kirk@example.com');

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

        $pleskAdminPassword = ($this->instance()
            ->credentials()
            ->where('name', '=', 'plesk_admin_password')
            ->first())
            ->password;
        $this->assertEquals('somepassword', $pleskAdminPassword);

        Event::assertNotDispatched(JobFailed::class);
    }
}
