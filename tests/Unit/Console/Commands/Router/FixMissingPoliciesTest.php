<?php

namespace Tests\Unit\Console\Commands\Router;

use App\Console\Commands\Router\FixMissingPolicies;
use App\Events\V2\Task\Created;
use App\Tasks\Vpc\CreateManagementInfrastructure;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FixMissingPoliciesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->router()->setAttribute('is_management', true)->saveQuietly();
    }

    public function testMissingFirewallPolicyDispatches()
    {
        Event::fake([Created::class]);
        $this->network();

        $this->artisan(FixMissingPolicies::class)
            ->expectsOutput('Management router ' . $this->router()->id . ' has no firewall policy. Re-deploying management infrastructure...')
            ->assertExitCode(0);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::$name;
        });
    }

    public function testMissingNetworkDispatches()
    {
        Event::fake([Created::class]);
        $this->router();

        $this->artisan(FixMissingPolicies::class)
            ->expectsOutput('Management router ' . $this->router()->id . ' has no management network. Re-deploying management infrastructure...')
            ->assertExitCode(0);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::$name;
        });
    }

    public function testMissingNetworkPolicyDispatches()
    {
        Event::fake([Created::class]);
        $this->network();

        $this->artisan(FixMissingPolicies::class)
            ->expectsOutput('Management router ' . $this->router()->id . ' has no firewall policy. Re-deploying management infrastructure...')
            ->assertExitCode(0);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::$name;
        });
    }

    public function testTestRunDoesNotDispatchTask()
    {
        Event::fake([Created::class]);
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $this->network();

        $this->artisan(FixMissingPolicies::class, ['--test-run' => true])
            ->expectsOutput('Management router ' . $this->router()->id . ' has no network policy. Re-deploying management infrastructure...')
            ->assertExitCode(0);

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::$name;
        });
    }

    public function testDoesNothingWithNonManagement()
    {
        $this->router()->setAttribute('is_management', false)->saveQuietly();

        Event::assertNotDispatched(Created::class, function ($event) {
            return $event->model->name == CreateManagementInfrastructure::$name;
        });
    }
}
