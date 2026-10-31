<?php

namespace App\Http\Requests\Management;

use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('services.manage') || $this->user()?->can('inventory.manage-own');
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        $admin = $this->user()?->can('services.manage');
        $categories = array_diff(array_keys(config('tourism.modules')), ['blog']);

        return [
            'provider_id' => ['required', 'integer', Rule::exists('providers', 'id'), function ($attribute, $value, $fail) use ($admin): void {
                $provider = Provider::whereKey($value)->first();
                if (! $admin && $provider?->owner_id !== $this->user()?->id) {
                    $fail('Solo puedes gestionar servicios de tus proveedores.');
                }
                if ($this->input('status') === 'published' && $provider?->status !== 'active') {
                    $fail('Activa el proveedor antes de publicar sus servicios.');
                }
            }],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in($categories)],
            'destination' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:20000'],
            'conditions' => ['nullable', 'string', 'max:10000'],
            'reference_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'currency' => ['required', Rule::in(['COP'])],
            'status' => ['required', Rule::in($admin ? ['draft', 'review', 'published', 'archived'] : ['draft', 'review'])],
        ];
    }
}
