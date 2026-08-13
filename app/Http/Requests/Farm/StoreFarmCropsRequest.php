<?php

declare(strict_types=1);

namespace App\Http\Requests\Farm;

use App\Enums\FarmStatus;
use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate farm crops wizard step.
 */
class StoreFarmCropsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Farm|null $farm */
        $farm = $this->route('farm');

        return $user !== null
            && $farm instanceof Farm
            && $farm->user_id === $user->id
            && $farm->status === FarmStatus::Draft;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'crops' => ['nullable', 'array'],
            'crops.*' => ['string', 'max:80'],
        ];
    }
}
