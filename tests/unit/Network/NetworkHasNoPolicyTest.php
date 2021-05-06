<?php
namespace Tests\unit\Network;

use App\Models\V2\NetworkPolicy;
use App\Rules\V2\NetworkHasNoPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class NetworkHasNoPolicyTest extends TestCase
{
    protected NetworkHasNoPolicy $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new NetworkHasNoPolicy();
        $this->network();

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->shouldReceive('get')
            ->withSomeOfArgs('policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(
                    [
                        'publish_status' => 'REALIZED'
                    ]
                ));
            });
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->shouldReceive('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });
    }

    public function testRulePasses()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->assertTrue($this->rule->passes('', $this->network()->id));
    }

    public function testRuleFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Model::withoutEvents(function () {
            factory(NetworkPolicy::class)->create([
                'id' => 'np-test',
                'network_id' => $this->network()->id,
            ]);
        });
        $this->assertFalse($this->rule->passes('', $this->network()->id));
    }
}