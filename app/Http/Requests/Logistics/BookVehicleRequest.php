<?php

declare(strict_types=1);

namespace App\Http\Requests\Logistics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate logistics vehicle booking.
 */
class BookVehicleRequest extends FormRequest
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
            'cargo_name' => ['required', 'string', 'max:120'],
            'cargo_tons' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
