<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ModuleReminderAssignerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    protected function failedValidation(Validator $v) {
        $message = collect($v->errors()->getMessages())->flatten()->implode(' ');
        $response = response()->json(['success' => false, 'message' => $message], 422);
        throw new HttpResponseException($response, 422);
    }
}
