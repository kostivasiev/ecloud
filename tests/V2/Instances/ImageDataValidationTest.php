<?php
namespace Tests\V2\Instances;

use App\Http\Requests\V2\Instance\CreateRequest;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceScriptParameters;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\Image;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class ImageDataValidationTest extends TestCase
{

    use DatabaseMigrations;

    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected Image $image;
    protected Request $request;
    protected array $imageData;

    public function setUp(): void
    {
        parent::setUp();
        $this->imageData = [
            'mysql_root_password' => 'EnCrYpTeD-PaSsWoRd',
            'mysql_gogs_user_password' => 'EnCrYpTeD-PaSsWoRd',
            'gogs_url' => 'mydomain.com',
            'gogs_secret_key' => 'EnCrYpTeD-sEcReT-kEy'
        ];
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->appliance_id,
        ])->refresh();  // Hack needed since this is a V1 resource
        $this->image = factory(Image::class)->create([
            'name' => 'test image',
            'appliance_version_id' => $this->appliance_version->appliance_version_uuid
        ]);
        foreach ($this->imageData as $key => $value) {
            $type = ($key == 'mysql_root_password' || $key == 'mysql_gogs_user_password') ? 'Password' : 'String';
            factory(ApplianceScriptParameters::class)->create([
                'appliance_script_parameters_appliance_version_id' => $this->appliance_version->appliance_version_id,
                'appliance_script_parameters_name' => $key,
                'appliance_script_parameters_key' => $key,
                'appliance_script_parameters_type' => $type,
                'appliance_script_parameters_validation_rule' => '/.*/'
            ]);
        }
        $this->request = CreateRequest::create('', 'POST', [
            'image_id' => $this->appliance->getKey(),
            'image_data' => $this->imageData
        ]);

        // Admin Client Mock
        app()->bind(AdminClient::class, function () {
            $adminClientMock = \Mockery::mock(AdminClient::class)
                ->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $adminClientMock->shouldReceive('licenses')->andReturnSelf();
            $adminClientMock->shouldReceive('getById')->andReturnUsing(function () {
                $obj = new \StdClass;
                $obj->category = 'Linux';
                return $obj;
            });
            return $adminClientMock;
        });
    }

    public function testSuccessfulValidation()
    {
        $this->request->rules();
        $validator = app('validator')
            ->make(
                $this->request->all(),
                $this->request->generateApplianceRules(),
                $this->request->messages()
            );
        $validator->validate();
        $this->assertTrue($validator->passes());
    }

    public function testFailedValidation()
    {
        $this->expectException(ValidationException::class);
        $request = CreateRequest::create('', 'POST', [
            'image_id' => $this->image->id,
            'image_data' => json_encode([
                'mysql_root_password' => null,
                'mysql_gogs_user_password' => null,
                'gogs_url' => null,
                'gogs_secret_key' => null
            ])
        ]);
        $request->rules();
        $validator = app('validator')
            ->make(
                $request->all(),
                $request->generateApplianceRules(),
                $request->messages()
            );
        $validator->validate();
        $this->assertFalse($validator->passes());
    }
}