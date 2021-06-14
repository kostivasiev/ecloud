<?php
namespace App\Http\Requests\V2\VpnSession;

use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function rules()
    {
        return [];
    }
}
