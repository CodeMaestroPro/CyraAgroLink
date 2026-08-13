<?php

declare(strict_types=1);

namespace App\Http\Requests\Distribution;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate smart city delivery creation.
 */
class StoreCityDeliveryRequest extends FormRequest
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
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'origin_hub_id' => ['required', 'integer', Rule::exists('smart_city_hubs', 'id')],
            'destination_hub_id' => ['required', 'integer', 'different:origin_hub_id', Rule::exists('smart_city_hubs', 'id')],
        ];
    }
}
