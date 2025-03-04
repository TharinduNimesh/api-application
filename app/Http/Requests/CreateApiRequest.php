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
            // Add custom validation for file parameters
            'endpoints.*.parameters.*' => [
                function ($attribute, $value, $fail) {
                    if ($value['type'] === 'file' && $value['location'] !== 'body') {
                        $fail('File parameters must be in the body location.');
                    }
                },
            ],
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
            'endpoints.*.parameters.*.type' => 'required|string|in:string,number,boolean,object,array,file',
            'endpoints.*.parameters.*.location' => 'required|string|in:query,path,body,header',
            'endpoints.*.parameters.*.fileConfig' => 'required_if:endpoints.*.parameters.*.type,file',
            'endpoints.*.parameters.*.fileConfig.mimeTypes' => 'required_if:endpoints.*.parameters.*.type,file|array',
            'endpoints.*.parameters.*.fileConfig.maxSize' => 'required_if:endpoints.*.parameters.*.type,file|integer|min:1',
            'endpoints.*.parameters.*.fileConfig.multiple' => 'boolean',
            'endpoints.*.parameters.*.required' => 'required|boolean',
            'endpoints.*.parameters.*.description' => 'required|string',
            'endpoints.*.parameters.*.defaultValue' => 'string|nullable',
        ];
    }
}
