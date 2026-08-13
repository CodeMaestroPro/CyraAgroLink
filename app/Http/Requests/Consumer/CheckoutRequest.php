<?php

declare(strict_types=1);

namespace App\Http\Requests\Consumer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate consumer checkout.
 */
class CheckoutRequest extends FormRequest
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
            'delivery_note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
