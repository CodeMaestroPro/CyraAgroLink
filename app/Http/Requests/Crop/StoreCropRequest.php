<?php

declare(strict_types=1);

namespace App\Http\Requests\Crop;

use App\Enums\CropGrowthStage;
use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate creating a new crop cycle.
 */
class StoreCropRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $farmId = (int) $this->input('farm_id');

        return Farm::query()
            ->where('id', $farmId)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'name' => ['required', 'string', 'max:120', Rule::in(config('cyra.crop_options', []))],
            'variety' => ['nullable', 'string', 'max:120'],
            'growth_stage' => ['nullable', 'string', Rule::in(CropGrowthStage::values())],
            'planted_at' => ['nullable', 'date'],
            'expected_harvest_at' => ['nullable', 'date', 'after_or_equal:planted_at'],
        ];
    }
}
