<?php

namespace Tests\V1\Pods\Resources;

use App\Models\V1\Appliance;
use App\Models\V1\ApplianceVersion;
use App\Models\V1\Pod;
use Tests\Traits\ResellerDatabaseMigrations;
use Tests\V1\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use ResellerDatabaseMigrations;

    private string $id;
    /**
     * @var Pod $pod
     */
    private mixed $pod;
    /**
     * @var ApplianceVersion $applianceVersion
     */
    private mixed $applianceVersion;
    /**
     * @var Appliance $appliance
     */
    private mixed $appliance;

    public function setUp(): void
    {
        parent::setUp();
        $this->pod = Pod::factory()->create([
            'ucs_datacentre_id' => 123,
        ]);

        $this->appliance = Appliance::factory()->create();

        $this->applianceVersion = ApplianceVersion::factory()->create([
            'appliance_uuid' => function () {
                return $this->appliance->appliance_uuid;
            },
            'appliance_version_version' => 1,
        ]);
    }

    /**
     * @return void
     */
    public function testDeleteResource()
    {
        $this->be((new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']))->setIsAdmin(true));

        $resource = Pod\Resource\Console::Create([
            'token' => 123,
            'url' => 123,
            'console_url' => 123,
        ]);
        $this->pod->addResource($resource);
        $resourceId = $resource->id;

        $podId = $this->pod->ucs_datacentre_id;

        $this->delete(
            "v1/pods/$podId/resources/$resourceId"
        )->assertStatus(204);
    }
}
