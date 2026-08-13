<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a quick buy order from the marketplace catalog.
 */
class QuickBuyRequest extends FormRequest
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
            'quantity_tons' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }
}
