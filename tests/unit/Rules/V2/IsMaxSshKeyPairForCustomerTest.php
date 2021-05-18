<?php

namespace Tests\unit\Rules\V2;

use App\Http\Middleware\IsMaxSshKeyPairForCustomer;
use App\Models\V2\SshKeyPair;
use App\Models\V2\Vpc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMaxSshKeyPairForCustomerTest extends TestCase
{
    public function testMaxLimitReachedReturnsFails()
    {
        $keypair = new SshKeyPair([
            'reseller_id' => 1,
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ=='
        ]);
        $keypair->save();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.ssh_key_pair.max_count', 1);
        $rule = new IsMaxSshKeyPairForCustomer();

        // Now assert that we're at the limit
        $this->assertFalse($rule->isWithinLimit());
    }

    public function testMaxLimitNotReachedPasses()
    {
        $keypair = new SshKeyPair([
            'reseller_id' => 1,
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ=='
        ]);
        $keypair->save();

        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.ssh_key_pair.max_count', 5);
        $rule = new IsMaxSshKeyPairForCustomer();

        // Now assert that we're at the limit
        $this->assertTrue($rule->isWithinLimit());
    }

    public function testBypassedResellerPasses()
    {
        $keypair = new SshKeyPair([
            'reseller_id' => 1,
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAAAgQCuxFiJFGtRIxU7IZA35zya75IJokX21zVrM90rxdWykbZz9cb5obLMXGqPLiHDOKL2frUd9TtTvPI/OQzCu5Sd2x41PdYyLcjXoLAaPqlmUbi3ExzigDKWjVu7RCBYWNBIi63boq3SqUZRdf9oF/R81EGUsF8lMnEIoutDncH8jQ=='
        ]);
        $keypair->save();

        $this->be(new Consumer(7052, [config('app.name') . '.read', config('app.name') . '.write']));
        Config::set('defaults.ssh_key_pair.max_count', 1);
        $rule = new IsMaxSshKeyPairForCustomer();

        // Now assert that we're at the limit
        $this->assertTrue($rule->isWithinLimit());
    }
}