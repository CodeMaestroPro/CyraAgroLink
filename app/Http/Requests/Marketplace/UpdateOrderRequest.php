<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Models\ExchangeOrder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate quantity updates for marketplace orders.
 */
class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var ExchangeOrder|null $order */
        $order = $this->route('order');

        return $user !== null
            && $order instanceof ExchangeOrder
            && ((int) $order->user_id === (int) $user->id || $user->isAdmin());
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
