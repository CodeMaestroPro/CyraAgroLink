<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate warehouse stock receive/release.
 */
class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'quantity_tons' => ['required', 'integer', 'min:1', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ];

        // Receive stock needs commodity name; release uses the stock model.
        if ($this->routeIs('warehouse.stock.receive')) {
            $rules['commodity_name'] = ['required', 'string', 'max:120'];
            $rules['icon'] = ['nullable', 'string', Rule::in(['maize', 'rice', 'cassava', 'others'])];
        }

        return $rules;
    }
}
