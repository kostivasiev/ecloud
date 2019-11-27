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

    const TEST_DATA = [
        'key' => 'test-key',
        'value' => 'test-value',
    ];

    const HEADERS_PUBLIC = [
        'X-consumer-custom-id' => '1-0',
        'X-consumer-groups' => 'ecloud.write',
    ];

    const HEADERS_ADMIN = [
        'X-consumer-custom-id' => '0-0',
        'X-consumer-groups' => 'ecloud.write',
    ];

    /**
     * @var ApplianceVersion
     */
    protected $applianceVersion;

    /**
     * Return the URI for the appliance version data endpoint or an invalid one
     * @param bool $valid
     * @param string $invalidValue
     * @return string
     */
    protected function getApplianceVersionDataUri(bool $valid = true, string $invalidValue = 'x')
    {
        $uuid = $valid ? $this->applianceVersion->appliance_version_uuid : $invalidValue;
        return '/v1/appliance-versions/' . $uuid . '/data';
    }

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

    public function testCreateIsAdminOnly()
    {
        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            self::TEST_DATA,
            self::HEADERS_PUBLIC
        )->seeStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteIsAdminOnly()
    {
        $this->json(
            'DELETE',
            $this->getApplianceVersionDataUri() . '/test-key',
            [],
            self::HEADERS_PUBLIC
        )->seeStatusCode(Response::HTTP_UNAUTHORIZED);
    }

    public function valueDataProvider()
    {
        return [
            'valid_value_returns_OK' => [
                'data' => self::TEST_DATA,
                'responseCode' => Response::HTTP_OK,
                'databaseCheckMethod' => 'seeInDatabase',
            ],
            'invalid_value_returns_BAD_REQUEST' => [
                'data' => [
                    'key' => 'test-key',
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
        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            $data,
            self::HEADERS_ADMIN
        )->seeStatusCode($responseCode);

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
     * @dataProvider applianceVersionUuidDataProvider
     * @param int $responseCode
     * @param bool $useValidUuid
     */
    public function testApplianceVersionMiddleware(int $responseCode, bool $useValidUuid)
    {
        $this->json(
            'POST',
            $this->getApplianceVersionDataUri($useValidUuid),
            self::TEST_DATA,
            self::HEADERS_ADMIN
        )->seeStatusCode($responseCode);
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
                'responseCode' => Response::HTTP_OK,
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

        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            self::TEST_DATA,
            self::HEADERS_ADMIN
        )->seeStatusCode($responseCode);
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

        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            self::TEST_DATA,
            self::HEADERS_ADMIN
        )->seeStatusCode($responseCode);
    }

    public function testDuplicateKey()
    {
        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            self::TEST_DATA,
            self::HEADERS_ADMIN
        )->seeStatusCode(Response::HTTP_OK);

        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            self::TEST_DATA,
            self::HEADERS_ADMIN
        )->seeStatusCode(Response::HTTP_CONFLICT);
    }

    public function testDeleteExistingKey()
    {
        $this->json(
            'POST',
            $this->getApplianceVersionDataUri(),
            self::TEST_DATA,
            self::HEADERS_ADMIN
        );

        $this->json(
            'DELETE',
            $this->getApplianceVersionDataUri() . '/test-key',
            [],
            self::HEADERS_ADMIN
        )->seeStatusCode(Response::HTTP_OK);

        $this->notSeeInDatabase(
            'appliance_version_data',
            self::TEST_DATA + [
                'appliance_version_uuid' => $this->applianceVersion->appliance_version_uuid,
                'deleted_at' => null,
            ],
            'ecloud'
        );
    }

    public function testDeleteNonExistentKey()
    {
        $this->json(
            'DELETE',
            $this->getApplianceVersionDataUri() . '/test-key',
            [],
            self::HEADERS_ADMIN
        )->seeStatusCode(Response::HTTP_NOT_FOUND);
    }
}
