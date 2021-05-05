<?php
namespace Tests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class GetTest extends TestCase
{
    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        Model::withoutEvents(function () {
            $this->networkRule = factory(NetworkRule::class)->make([
                'id' => 'nr-test',
                'name' => 'nr-test',
            ]);

            $this->networkPolicy()->networkRules()->save($this->networkRule);
        });
    }

    public function testGetCollection()
    {
        $this->get('/v2/network-rules')
            ->seeJson([
                'id' => 'nr-test',
                'network_policy_id' => $this->networkPolicy()->id,
                'sequence' => 1,
                'source' => '10.0.1.0/32',
                'destination' => '10.0.2.0/32',
            ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get('/v2/network-rules/nr-test')
            ->seeJson([
                'id' => 'nr-test',
                'network_policy_id' => 'np-test',
                'sequence' => 1,
                'source' => '10.0.1.0/32',
                'destination' => '10.0.2.0/32',
            ])->assertResponseStatus(200);
    }
}
