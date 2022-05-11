<?php

namespace Tests\V2\Instances;

use App\Events\V2\Task\Created;
use App\Models\V2\ImageParameter;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class PasswordImageParameterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testPasswordImageParamsAreStoredToCredentials()
    {
        Event::fake(Created::class);

        $this->imageParameter()
            ->setAttribute('type', ImageParameter::TYPE_PASSWORD)
            ->setAttribute('key', 'plesk_admin_password')
            ->save();

        $this->image()->imageParameters()->save($this->imageParameter());

        $data = [
            'vpc_id' => $this->vpc()->id,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id,
            'vcpu_cores' => 1,
            'ram_capacity' => 2048,
            'volume_capacity' => 30,
            'volume_iops' => 600,
            'image_data' => [
                'plesk_admin_email_address' => 'elmer.fudd@example.com',
                'plesk_admin_password' => 'plesky_wabbit'
            ]
        ];

        $post = $this->post('/v2/instances', $data)->assertStatus(202);

        $instance = Instance::find((json_decode($post->getContent()))->data->id);

        // Check removed from plain text deploy data
        $this->assertFalse(in_array('plesk_admin_password', array_keys($instance->deploy_data['image_data'])));

        // Check password encrypted in credentials resource
        $this->assertEquals(1, $instance->credentials->where('username', 'plesk_admin_password')->count());
    }
}
