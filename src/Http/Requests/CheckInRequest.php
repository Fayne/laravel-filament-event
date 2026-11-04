<?php

namespace Fayne\Event\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'check_in_code' => ['required', 'string', 'max:255'],
        ];
    }
}
