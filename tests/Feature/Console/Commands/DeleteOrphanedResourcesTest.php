<?php

namespace Tests\Feature\Console\Commands;

use App\Console\Commands\Command;
use App\Console\Commands\VPC\DeleteOrphanedResources;
use Tests\TestCase;

class DeleteOrphanedResourcesTest extends TestCase
{
    public Command $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->command = new DeleteOrphanedResources::class;
    }

    public function test()
    {

        $this->router();






    }
}
