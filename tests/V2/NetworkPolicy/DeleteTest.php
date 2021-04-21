<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;

    public function setUp(): void
    {
        parent::setUp();
        $this->network();

        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('policy/api/v1/infra/domains/default/security-policies/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                    ],
                    'result_count' => 0,
                    'sort_by' => 'precedence',
                    'sort_ascending' => true
                ]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->expects('get')
            ->with('policy/api/v1/infra/domains/default/groups/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                    ],
                    'result_count' => 0,
                    'sort_by' => 'precedence',
                    'sort_ascending' => true
                ]));
            });

        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(
                    [
                        'publish_status' => 'REALIZED'
                    ]
                ));
            });

        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);
    }

    public function testDeleteResource()
    {
        $this->delete(
            '/v2/network-policies/' . $this->networkPolicy->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(202);
        $networkPolicy = NetworkPolicy::withTrashed()->findOrFail($this->networkPolicy->id);
        $this->assertNotNull($networkPolicy->deleted_at);
    }
}