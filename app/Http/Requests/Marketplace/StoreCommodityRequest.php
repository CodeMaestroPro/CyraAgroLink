<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate a new marketplace commodity listing from the dashboard.
 */
class StoreCommodityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->isAdmin()
            || $user->hasRole(UserRole::Farmer)
            || $user->hasRole(UserRole::Supplier);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', Rule::exists('marketplace_categories', 'id')],
            'price_per_ton' => ['required', 'integer', 'min:1'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80', Rule::in(config('cyra.nigeria_states', []))],
            'scientific_name' => ['nullable', 'string', 'max:150'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * Strip client-controlled privilege fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->request->remove('is_featured');
        $this->request->remove('user_id');
        $this->request->remove('status');
    }
}
