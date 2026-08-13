<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use App\Enums\UserRole;
use App\Models\MarketplaceCommodity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate marketplace listing updates by the owner.
 */
class UpdateCommodityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var MarketplaceCommodity|null $commodity */
        $commodity = $this->route('commodity');

        if ($user === null || ! $commodity instanceof MarketplaceCommodity) {
            return false;
        }

        return $user->isAdmin()
            || ((int) $commodity->user_id === (int) $user->id
                && ($user->hasRole(UserRole::Farmer) || $user->hasRole(UserRole::Supplier)));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price_per_ton' => ['required', 'integer', 'min:1'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80', Rule::in(config('cyra.nigeria_states', []))],
            'category_id' => ['nullable', 'integer', Rule::exists('marketplace_categories', 'id')],
        ];
    }
}
