<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:FREE,PAID',
            'baseUrl' => 'required|url',
            'rateLimit' => 'required|integer|min:1|max:1000',
            'endpoints' => 'required|array|min:1',
            'endpoints.*.id' => 'required|string',
            'endpoints.*.name' => 'required|string',
            'endpoints.*.method' => 'required|string|in:GET,POST,PUT,DELETE,PATCH',
            'endpoints.*.path' => 'required|string',
            'endpoints.*.description' => 'required|string',
            'endpoints.*.parameters' => 'array',
            'endpoints.*.parameters.*.id' => 'required|string',
            'endpoints.*.parameters.*.name' => 'required|string',
            'endpoints.*.parameters.*.type' => 'required|string',
            'endpoints.*.parameters.*.location' => 'required|string',
            'endpoints.*.parameters.*.required' => 'required|boolean',
            'endpoints.*.parameters.*.description' => 'required|string',
            'endpoints.*.parameters.*.defaultValue' => 'string|nullable',
        ];
    }
}
