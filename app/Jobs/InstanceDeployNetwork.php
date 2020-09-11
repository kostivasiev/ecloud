<?php

namespace App\Jobs;

use Illuminate\Http\Request;

class InstanceDeployNetwork extends Job
{
    /** @var Request */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle()
    {
        dd($this->request);
    }
}
