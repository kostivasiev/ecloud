<?php

namespace Tests\V2\Instances;

use App\Models\V2\HostGroup;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class ChangeHostgroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->instance()->host_group_id = $this->hostGroup()->id;
        $this->instance()->deployed = true;
        $this->instance()->saveQuietly();

        $this->kingpinServiceMock()
            ->shouldReceive('get')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test')
            ->andThrow(
                new RequestException(
                    'Not Found',
                    new Request('delete', '', []),
                    new Response(404)
                )
            );
    }

    public function testEvent()
    {
        $hostGroup = HostGroup::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-newitem',
                'name' => 'hg-newitem',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });

        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/instance/i-test/reschedule')
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->patch(
            '/v2/instances/' . $this->instance()->id,
            [
                'host_group_id' => $hostGroup->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);
    }
}