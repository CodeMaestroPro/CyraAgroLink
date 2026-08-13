<?php

declare(strict_types=1);

namespace App\Http\Requests\Farm;

use App\Enums\FarmStatus;
use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate farm location wizard step.
 */
class StoreFarmLocationRequest extends FormRequest
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
            'state' => ['required', 'string', 'max:100', Rule::in(config('cyra.nigeria_states', []))],
            'local_government' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
