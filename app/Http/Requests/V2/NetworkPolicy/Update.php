<?php
namespace App\Http\Requests\V2\NetworkPolicy;

use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\NetworkHasNoPolicy;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ($this->user()->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [];
    }
}
