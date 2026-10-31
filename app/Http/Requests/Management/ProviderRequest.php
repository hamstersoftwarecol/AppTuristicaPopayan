<?php

namespace App\Http\Requests\Management;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('providers.manage') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url:https', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id'), function ($attribute, $value, $fail): void {
                if (! User::whereKey($value)->first()?->hasRole('proveedor')) {
                    $fail('La cuenta seleccionada debe tener el rol de proveedor.');
                }
            }],
        ];
    }
}
