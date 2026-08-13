<?php

declare(strict_types=1);

namespace App\Http\Requests\Exchange;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate commodity exchange order placement.
 */
class PlaceExchangeOrderRequest extends FormRequest
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
        return [
            'side' => ['required', 'string', Rule::in(['buy', 'sell'])],
            'quantity_tons' => ['required', 'integer', 'min:1', 'max:100000'],
            'price_per_ton' => ['required', 'integer', 'min:1'],
        ];
    }
}
