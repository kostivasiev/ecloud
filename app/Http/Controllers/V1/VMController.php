<?php

namespace App\Http\Controllers\V1;

// Models
use App\Models\V1\VMModel;
use Illuminate\Http\Request;

class VMController extends BaseController
{

    /**
     * List all VM's
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $vms = $this->getVMs();
        $this->transformQuery($vms, VMModel::class);
        return $this->respondCollection(
            $request,
            $vms->paginate($this->count)
        );
    }

    /**
     * List VM's
     * For admin list all except when $resellerId is passed in
     * @param null $resellerId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getVMs($resellerId = null)
    {
        $VMQuery = VMModel::query();

        if ($this->is_admin) {
            if (!is_null($resellerId)) {
                $VMQuery->withResellerId($resellerId);
            }
            // Return ALL VM's
            return $VMQuery;
        }

        //For non-admin filter on reseller ID
        return $VMQuery->withResellerId($this->resellerId);
    }
}
