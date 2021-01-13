<?php
namespace Tests\V2\Instances;

use App\Http\Controllers\V2\InstanceController;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceScriptParameters;
use App\Models\V2\ApplianceVersion;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class ApplianceDataValidationTest extends TestCase
{

    use DatabaseMigrations;

    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected Request $request;
    protected $instanceController;
    protected array $applianceData;

    public function setUp(): void
    {
        parent::setUp();
        $this->applianceData = [
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
        foreach ($this->applianceData as $key => $value) {
            $type = ($key == 'mysql_root_password' || $key == 'mysql_gogs_user_password') ? 'Password' : 'String';
            factory(ApplianceScriptParameters::class)->create([
                'appliance_script_parameters_appliance_version_id' => $this->appliance_version->appliance_version_id,
                'appliance_script_parameters_name' => 'Random Parameter Name',
                'appliance_script_parameters_key' => $key,
                'appliance_script_parameters_type' => $type,
                'appliance_script_parameters_validation_rule' => '/.*/'
            ]);
        }
        $this->request = Request::create('', 'POST', [
            'appliance_id' => $this->appliance->getKey(),
            'appliance_data' => json_encode($this->applianceData)
        ]);
        $this->instanceController = \Mockery::mock(InstanceController::class)->makePartial();
    }

    public function testSuccessfulValidation()
    {
        $this->instanceController
            ->shouldReceive('validate')
            ->with($this->request, \Mockery::capture($rules));
        $this->instanceController->validateApplianceData($this->request);
        foreach ($rules as $ruleName => $rule) {
            $key = str_replace('appliance_data.', '', $ruleName);
            $this->assertArrayHasKey($key, $this->applianceData);
        }
    }

    public function testFailedValidation()
    {
        $this->expectException(ValidationException::class);
        $request = Request::create('', 'POST', [
            'appliance_id' => $this->appliance->getKey(),
            'appliance_data' => json_encode([
                'mysql_root_password' => null,
                'mysql_gogs_user_password' => null,
                'gogs_url' => null,
                'gogs_secret_key' => null
            ])
        ]);
        $this->instanceController->validateApplianceData($request);
    }
}