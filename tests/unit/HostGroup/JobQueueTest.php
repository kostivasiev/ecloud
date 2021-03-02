<?php

namespace Tests\unit\FloatingIps;

use Tests\TestCase;

class JobQueueTest extends TestCase
{
    public function testCreateFiresJobs()
    {
        $this->hostGroup();
    }
}
