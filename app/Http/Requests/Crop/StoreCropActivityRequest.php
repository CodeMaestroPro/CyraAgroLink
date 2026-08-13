<?php

declare(strict_types=1);

namespace App\Http\Requests\Crop;

use App\Enums\CropActivityType;
use App\Enums\CropHealthStatus;
use App\Models\Crop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate crop care / health / harvest form submissions.
 */
class StoreCropActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        /** @var Crop|null $crop */
        $crop = $this->route('crop');

        return $user !== null
            && $crop instanceof Crop
            && $crop->user_id === $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = (string) $this->input('type');

        return match ($type) {
            CropActivityType::Health->value => [
                'type' => ['required', Rule::in(CropActivityType::values())],
                'health_status' => ['required', Rule::in(CropHealthStatus::values())],
                'health_notes' => ['nullable', 'string', 'max:500'],
            ],
            CropActivityType::Harvest->value => [
                'type' => ['required', Rule::in(CropActivityType::values())],
                'quantity' => ['nullable', 'string', 'max:80'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ],
            default => [
                'type' => ['required', Rule::in([
                    CropActivityType::Activity->value,
                    CropActivityType::Irrigation->value,
                    CropActivityType::Fertilizer->value,
                ])],
                'title' => ['required', 'string', 'max:150'],
                'quantity' => ['nullable', 'string', 'max:80'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'next_activity' => ['nullable', 'string', 'max:150'],
                'next_activity_at' => ['nullable', 'date'],
            ],
        };
    }
}
