<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserActiveApiRequest extends BaseRequestApi
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'otp' => 'required|numeric',
            'token' => 'required',
        ];
    }
}
