<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RunApplianceBootstrap;
use App\Models\V2\Credential;
use App\Models\V2\ImageParameter;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
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
}
