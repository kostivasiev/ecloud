<?php

namespace App\Http\Controllers\V1;

// Models
use App\Models\V1\VMModel;
use Illuminate\Http\Request;

class VMController extends BaseController
{

    /**
     * List all solutions
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }


    /**
     * Show a specific solution
     * @param Request $request
     * @param $solutionId
     * @return \Illuminate\Http\Response
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ForbiddenException
     */
    public function show(Request $request, $vmId)
    {

    }
}
