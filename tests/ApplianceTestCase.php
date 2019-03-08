<?php

namespace Tests;

use App\Models\V1\Appliance;
use App\Models\V1\ApplianceVersion;
use App\Models\V1\ApplianceParameters;

use Laravel\Lumen\Testing\DatabaseMigrations;

class ApplianceTestCase extends TestCase
{
    use DatabaseMigrations;

    public $appliances;

    /**
     * Output verbose data when running tests
     * @var bool
     */
    public $verbose = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpApplianceData();
    }

    /**
     * Create our test data at the start of each test (Appliances, ApplianceVersions & ApplianceParameters)
     *
     * Creates 2 appliances, each with 3 versions, using the wordpress script and parameters
     */
    protected function setUpApplianceData()
    {
        $total = 2;

        // Create some appliances
        $this->appliances = factory(Appliance::class, $total)->create()->each(function ($appliance) {
            // Save the $appliance
            $appliance->save();
            $appliance->refresh();

            // Create the appliance versions
            $version = 3;

            if ($this->verbose) {
                echo 'Creating appliance \'' . $appliance->getKey() . PHP_EOL;
            }

            for ($i = 0; $i < $version; $i++) {
                $applianceFactoryConfig = [
                    'appliance_version_appliance_id' => $appliance->id,
                    'appliance_version_version' => ($i+1),
                ];

                $applianceVersion = factory(ApplianceVersion::class)->make($applianceFactoryConfig);

                if ($this->verbose) {
                    echo 'Creating appliance version \'' . ($i + 1) . '\' for appliance \'' . $appliance->getKey() . '\'';
                }
                $applianceVersion->save();

                $applianceVersion->refresh();

                if ($this->verbose) {
                    echo  'with id \'' . $applianceVersion->uuid .'\'' . PHP_EOL;
                }

                // Create appliance version parameters
                $parameters = [
                    'mysql_root_password',
                    'mysql_wordpress_user_password',
                    'wordpress_url'
                ];

                // For each version add the three parameters
                foreach ($parameters as $parameter) {
                    if ($this->verbose) {
                        echo  'Creating Parameter \'' . $parameter .'\'' . PHP_EOL;
                    }

                    $applianceParameterConfig = [
                        'appliance_script_parameters_name' => ucwords(str_replace('_', ' ', $parameter)),
                        'appliance_script_parameters_key' => $parameter
                    ];

                    if ($parameter == 'mysql_root_password') {
                        $applianceParameterConfig['appliance_script_parameters_validation_rule'] = '/w+/';
                    }

                    $applianceVersion->parameters()->save(
                        factory(ApplianceParameters::class)->make($applianceParameterConfig)
                    );
                }
            }

            if ($this->verbose) {
                echo  PHP_EOL;
            }

        });
    }
}
