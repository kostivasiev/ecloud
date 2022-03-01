<?php
namespace Tests\V2\Instances;

use App\Http\Requests\V2\Instance\CreateRequest;
use App\Models\V2\ImageParameter;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class ImageParameterTest extends TestCase
{
    protected CreateRequest $createRequest;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        $paramRules = [
            [
                'id' => 'imgparam-091ec92b',
                'image_id' => $this->image()->id,
                'name' => 'URL',
                'key' => 'moodle_url',
                'type' => 'String',
                'description' => 'Optional URL for moodle (including scheme)',
                'required' => false,
                'validation_rule' => '/^(http|https):\/\/\w+(\.\w+)+$/',
            ],
            [
                'id' => 'imgparam-1adc3885',
                'image_id' => $this->image()->id,
                'name' => 'Admin password',
                'key' => 'moodle_admin_password',
                'type' => 'Password',
                'description' => 'Admin password for accessing Moodle',
                'required' => true,
                'validation_rule' => '/^.{8,}$/',
            ]
        ];
        foreach ($paramRules as $rule) {
            Model::withoutEvents(function () use ($rule) {
                factory(ImageParameter::class)->create($rule);
            });
        }

        $data = [
            'vpc_id' => 'vpc-d14dd497',
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'volume_capacity' => 20,
            'image_id' => $this->image()->id,
            'network_id' => $this->network()->id
        ];
        $this->createRequest = new CreateRequest([], $data);
        $this->setProtectedProperty($this->createRequest, 'image', $this->image());
    }

    private function getReflectionProperty($object, $property)
    {
        $reflection         = new \ReflectionClass(get_class($object));
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty;
    }

    private function setProtectedProperty(&$object, $property, $value)
    {
        $reflectionProperty = $this->getReflectionProperty($object, $property);
        $reflectionProperty->setValue($object, $value);
    }

    public function testRules()
    {
        $rules = $this->createRequest->rules();
        $this->assertTrue(array_key_exists('image_data.moodle_admin_password', $rules));
        $this->assertEquals('required', $rules['image_data.moodle_admin_password'][0]);
        $this->assertEquals('regex:/^.{8,}$/', $rules['image_data.moodle_admin_password'][1]);
        $this->assertEquals('string', $rules['image_data.moodle_admin_password'][2]);
    }
}