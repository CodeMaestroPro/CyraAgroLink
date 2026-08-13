<?php

declare(strict_types=1);

namespace App\Http\Requests\Farm;

use App\Enums\FarmStatus;
use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate farm details wizard step.
 */
class StoreFarmDetailsRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'size_hectares' => ['nullable', 'numeric', 'min:0.01', 'max:100000'],
            'soil_type' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
