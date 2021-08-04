<?php
namespace App\Rules\V2;

use Illuminate\Contracts\Validation\Rule;

class EitherOrNotBoth implements Rule
{
    public \Illuminate\Http\Request $request;
    public string $field;

    public function __construct($field)
    {
        $this->field = $field;
        $this->request = app('request');
    }

    public function passes($attribute, $value)
    {
        return !($this->request->has($attribute) && $this->request->has($this->field));
    }

    public function message()
    {
        return 'Either the :attribute value or the ' . $this->field . ' value can be used, but not both';
    }
}
