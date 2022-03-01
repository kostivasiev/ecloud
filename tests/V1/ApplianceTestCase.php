<?php

namespace Tests\V1;

use App\Models\V1\Appliance;
use App\Models\V1\ApplianceParameter;
use App\Models\V1\AppliancePodAvailability;
use App\Models\V1\ApplianceVersion;
use App\Models\V1\Pod;

class ApplianceTestCase extends TestCase
{
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
                    'appliance_version_version' => ($i + 1),
                ];

                $applianceVersion = factory(ApplianceVersion::class)->make($applianceFactoryConfig);

                if ($this->verbose) {
                    echo 'Creating appliance version \'' . ($i + 1) . '\' for appliance \'' . $appliance->getKey() . '\'';
                }
                $applianceVersion->save();

                $applianceVersion->refresh();

                if ($this->verbose) {
                    echo 'with id \'' . $applianceVersion->uuid . '\'' . PHP_EOL;
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
                        echo 'Creating Parameter \'' . $parameter . '\'' . PHP_EOL;
                    }

                    $applianceParameterConfig = [
                        'appliance_script_parameters_name' => ucwords(str_replace('_', ' ', $parameter)),
                        'appliance_script_parameters_key' => $parameter
                    ];

                    if ($parameter == 'mysql_root_password') {
                        $applianceParameterConfig['appliance_script_parameters_validation_rule'] = '/w+/';
                    }

                    $applianceVersion->parameters()->save(
                        factory(ApplianceParameter::class)->make($applianceParameterConfig)
                    );
                }
            }

            if ($this->verbose) {
                echo PHP_EOL;
            }

        });
    }

    /**
     * Create some Pods and add some appliances to them
     */
    public function setUpAppliancePodTestData()
    {
        // Create a pod with one-click enabled
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 1,
            'ucs_datacentre_oneclick_enabled' => 'Yes'
        ]);
        // Add an appliance to the pod
        $availability = new AppliancePodAvailability();
        $availability->appliance_id = $this->appliances[0]->id;
        $availability->ucs_datacentre_id = 1;
        $availability->save();

        // Create a Pod with one-click disabled
        factory(Pod::class, 1)->create([
            'ucs_datacentre_id' => 2,
            'ucs_datacentre_oneclick_enabled' => 'No'
        ]);
        // Add an appliance to the pod
        $availability = new AppliancePodAvailability();
        $availability->appliance_id = $this->appliances[0]->id;
        $availability->ucs_datacentre_id = 2;
        $availability->save();
    }
}
