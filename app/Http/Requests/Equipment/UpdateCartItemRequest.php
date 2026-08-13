<?php

declare(strict_types=1);

namespace App\Http\Requests\Equipment;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate equipment cart quantity / rental-day updates.
 */
class UpdateCartItemRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'rental_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }
}
