<?php

namespace Tests\V1\Appliance\Version;

use App\Models\V1\Appliance;
use App\Models\V1\ApplianceVersion;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Http\Response;

class DataTest extends TestCase
{
    use DatabaseTransactions, DatabaseMigrations;

    /**
     * @var ApplianceVersion
     */
    protected $applianceVersion;

    protected function setUp() : void
    {
        parent::setUp();

        $this->applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_uuid' => function () {
                return factory(Appliance::class)->create()->appliance_uuid;
            },
            'appliance_version_version' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        $this->applianceVersion = null;

        parent::tearDown();
    }

    public function valueDataProvider()
    {
        $faker = \Faker\Factory::create();
        return [
            'valid_value_returns_OK' => [
                'data' => [
                    'key' => $faker->word(),
                    'value' => $faker->sentence(),
                ],
                'responseCode' => Response::HTTP_OK,
                'databaseCheckMethod' => 'seeInDatabase',
            ],
            'invalid_value_returns_BAD_REQUEST' => [
                'data' => [
                    'key' => $faker->word(),
                    'value' => '',
                ],
                'responseCode' => Response::HTTP_BAD_REQUEST,
                'databaseCheckMethod' => 'notSeeInDatabase',
            ],
        ];
    }

    /**
     * @dataProvider valueDataProvider
     * @param array $data
     * @param int $responseCode
     * @param string $databaseCheckMethod
     */
    public function testValue(array $data, int $responseCode, string $databaseCheckMethod)
    {
        $response = $this->json(
            'POST',
            '/v1/appliance-versions/' . $this->applianceVersion->appliance_version_uuid . '/data',
            $data,
            $this->validWriteHeaders
        );
        $response->seeStatusCode($responseCode);

        $this->$databaseCheckMethod(
            'appliance_version_data',
            $data + [
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
            ],
            'ecloud'
        );
    }

    public function applianceVersionUuidDataProvider()
    {
        return [
            'valid_appliance_version_uuid_returns_OK' => [
                'responseCode' => Response::HTTP_OK,
                'useValidUuid' => true,
            ],
            'invalid_appliance_version_uuid_returns_NOT_FOUND' => [
                'responseCode' => Response::HTTP_NOT_FOUND,
                'useValidUuid' => false,
            ],
        ];
    }

    /**
     * Return the Appliance Version UUID or and invalid value
     * @param bool $valid
     * @return string
     */
    protected function getApplianceVersionUuid(bool $valid = true)
    {
        return $valid ? $this->applianceVersion->appliance_version_uuid : 'x';
    }

    /**
     * @dataProvider applianceVersionUuidDataProvider
     * @param int $responseCode
     * @param bool $useValidUuid
     */
    public function testApplianceVersionUuid(int $responseCode, bool $useValidUuid)
    {
        $faker = \Faker\Factory::create();
        $data = [
            'key' => $faker->word(),
            'value' => $faker->sentence(),
        ];

        $response = $this->json(
            'POST',
            '/v1/appliance-versions/' . $this->getApplianceVersionUuid($useValidUuid) . '/data',
            $data,
            $this->validWriteHeaders
        );
        $response->seeStatusCode($responseCode);
    }

    public function applianceStateDataProvider()
    {
        return [
            'appliance_active_and_public_returns_OK' => [
                'active' => 'Yes',
                'is_public' => 'Yes',
                'responseCode' => Response::HTTP_OK,
            ],
            'appliance_not_active_returns_NOT_FOUND' => [
                'active' => 'No',
                'is_public' => 'Yes',
                'responseCode' => Response::HTTP_NOT_FOUND,
            ],
            'appliance_not_public_returns_NOT_FOUND' => [
                'active' => 'Yes',
                'is_public' => 'No',
                'responseCode' => Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /**
     * @dataProvider applianceStateDataProvider
     * @param $active
     * @param $isPublic
     * @param $responseCode
     */
    public function testApplianceState($active, $isPublic, $responseCode)
    {
        $appliance = $this->applianceVersion->appliance;
        $appliance->active = $active;
        $appliance->is_public = $isPublic;
        $appliance->save();

        $faker = \Faker\Factory::create();
        $data = [
            'key' => $faker->word(),
            'value' => $faker->sentence(),
        ];

        $response = $this->json(
            'POST',
            '/v1/appliance-versions/' . $this->applianceVersion->appliance_version_uuid . '/data',
            $data,
            $this->validWriteHeaders
        );
        $response->seeStatusCode($responseCode);
    }

    public function applianceVersionStateDataProvider()
    {
        return [
            'appliance_version_active_returns_OK' => [
                'active' => 'Yes',
                'responseCode' => Response::HTTP_OK,
            ],
            'appliance_version_not_active_returns_NOT_FOUND' => [
                'active' => 'No',
                'responseCode' => Response::HTTP_NOT_FOUND,
            ],
        ];
    }

    /**
     * @dataProvider applianceVersionStateDataProvider
     * @param $active
     * @param $responseCode
     */
    public function testApplianceVersionState($active, $responseCode)
    {
        $this->applianceVersion->active = $active;
        $this->applianceVersion->save();

        $faker = \Faker\Factory::create();
        $data = [
            'key' => $faker->word(),
            'value' => $faker->sentence(),
        ];

        $response = $this->json(
            'POST',
            '/v1/appliance-versions/' . $this->applianceVersion->appliance_version_uuid . '/data',
            $data,
            $this->validWriteHeaders
        );
        $response->seeStatusCode($responseCode);
    }
}
