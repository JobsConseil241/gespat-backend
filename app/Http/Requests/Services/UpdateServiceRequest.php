<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('services.update') ?? false;
    }

    public function rules(): array
    {
        $serviceId = $this->route('service')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:30', Rule::unique('services', 'code')->ignore($serviceId)],
            'libelle' => ['sometimes', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', 'exists:services,id', "different:{$serviceId}"],
            'site_principal_id' => ['nullable', 'integer', 'exists:sites,id'],
            'is_active' => ['boolean'],
        ];
    }
}
