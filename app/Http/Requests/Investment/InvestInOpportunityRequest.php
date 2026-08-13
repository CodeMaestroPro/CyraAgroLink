<?php

declare(strict_types=1);

namespace App\Http\Requests\Investment;

use App\Models\InvestmentOpportunity;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate investing wallet funds into a farm opportunity.
 */
class InvestInOpportunityRequest extends FormRequest
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
            'amount' => ['required', 'integer', 'min:'.$this->minimumTicket(), 'max:100000000'],
            'detail' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $min = $this->minimumTicket();

        return [
            'amount.required' => 'Enter how much you want to invest.',
            'amount.min' => $min < 10000
                ? 'Invest the remaining ₦'.number_format($min).' to close this raise.'
                : 'Minimum investment is ₦10,000.',
        ];
    }

    protected function minimumTicket(): int
    {
        $opportunity = $this->route('opportunity');

        if (! $opportunity instanceof InvestmentOpportunity) {
            return 10000;
        }

        $remaining = $opportunity->remainingCapacity();

        if ($remaining > 0 && $remaining < 10000) {
            return $remaining;
        }

        return 10000;
    }
}
