<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate receiving stock into a warehouse.
 */
class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->route('warehouse');

        if ($user === null || ! $warehouse instanceof Warehouse) {
            return false;
        }

        return $user->isAdmin() || (int) $warehouse->user_id === (int) $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'commodity_name' => ['required', 'string', 'max:120'],
            'quantity_tons' => ['required', 'integer', 'min:1', 'max:100000'],
            'icon' => ['nullable', 'string', Rule::in(['maize', 'rice', 'cassava', 'others'])],
            'source' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'commodity_name.required' => 'Select or enter a commodity to stock in.',
            'quantity_tons.required' => 'Enter how many tons you are receiving.',
            'quantity_tons.min' => 'Stock in quantity must be at least 1 ton.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $commodity = trim((string) $this->input('commodity_name', ''));

        if ($commodity === '__custom__') {
            $commodity = trim((string) $this->input('custom_commodity_name', ''));
        }

        $this->merge([
            'commodity_name' => $commodity,
            'source' => filled($this->input('source')) ? trim((string) $this->input('source')) : null,
            'note' => filled($this->input('note')) ? trim((string) $this->input('note')) : null,
        ]);
    }
}
