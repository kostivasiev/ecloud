<?php
namespace Tests\V2\Instances;

use App\Http\Controllers\V2\InstanceController;
use Illuminate\Http\Request;
use Tests\TestCase;

class ApplianceDataValidationTest extends TestCase
{

    protected Request $request;
    protected $instanceController;
    protected $generatedRules;
    protected array $applianceData;

    public function setUp(): void
    {
        parent::setUp();
        $this->applianceData = [
            [
                'id' => 'e79f5dbe-9e9b-42cc-8dff-2a4dc555f476',
                'version_id' => '2c396d59-5446-4e98-9523-b4cef692ccd0',
                'name' => 'MySQL root password',
                'key' => 'mysql_root_password',
                'type' => 'Password',
                'description' => 'The root password for the MySQL database',
                'required' => true,
                'validation_rule' => '\/\\w+\/'
            ],
            [
                'id' => 'a996f6be-930f-452e-822f-12bb4c9583bd',
                'version_id' => '2c396d59-5446-4e98-9523-b4cef692ccd0',
                'name' => 'MySQL Gogs user password',
                'key' => 'mysql_gogs_user_password',
                'type' => 'Password',
                'description' => 'The Gogs mysql user password',
                'required' => true,
                'validation_rule' => '\/\\w+\/'
            ],
            [
                'id' => '70edd019-3811-4383-89dc-97e056365f9e',
                'version_id' => '2c396d59-5446-4e98-9523-b4cef692ccd0',
                'name' => 'Gogs URL',
                'key' => 'gogs_url',
                'type' => 'String',
                'description' => 'Domain name to access your Gogs installation',
                'required' => true,
                'validation_rule' => '\/\\w+\/'
            ],
            [
                'id' => 'bce66294-e0c8-4b9a-90f9-ad4feb01997f',
                'version_id' => '2c396d59-5446-4e98-9523-b4cef692ccd0',
                'name' => 'Gogs Secret Key',
                'key' => 'gogs_secret_key',
                'type' => 'String',
                'description' => 'Global secret key for your server security',
                'required' => true,
                'validation_rule' => null
            ]
        ];
        $this->request = Request::create('', 'POST', [
           'appliance_data' => json_encode($this->applianceData)
        ]);
    }

    public function testGeneratedValidation()
    {
        $this->instanceController = \Mockery::mock(InstanceController::class)->makePartial();
        $this->instanceController
            ->shouldReceive('validate')
            ->with($this->request, \Mockery::capture($rules));
        $this->instanceController->validateApplianceData($this->request);
        $counter = 0;
        foreach ($rules as $rule) {
            if ($this->applianceData[$counter]['required'] === true) {
                $this->assertEquals('required', $rule[0]);
            }
            if (!empty($this->applianceData[$counter]['validation_rule'])) {
                $this->assertEquals('regex:', substr($rule[1], 0, 6));
            }
            if (strtolower($this->applianceData[$counter]['type']) == 'password') {
                $this->applianceData[$counter]['type'] = 'string';
            }
            if (empty($this->applianceData[$counter]['validation_rule'])) {
                $this->assertEquals($rule[1], strtolower($this->applianceData[$counter]['type']));
            } else {
                $this->assertEquals($rule[2], strtolower($this->applianceData[$counter]['type']));
            }
            $counter++;
        }
    }
}