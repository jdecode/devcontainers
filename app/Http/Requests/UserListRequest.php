<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sortBy' => ['sometimes', Rule::in(['id', 'first_name', 'last_name', 'email', 'email_verified_at'])],
            'orderBy' => ['sometimes', Rule::in(['asc', 'desc'])],
            'perPage' => ['sometimes', 'integer'],
            'search' => ['sometimes', 'string', 'nullable']
        ];
    }
}
