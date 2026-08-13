<?php

declare(strict_types=1);

namespace App\Http\Requests\Farm;

use App\Enums\FarmStatus;
use App\Models\Farm;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate optional document uploads for farm registration.
 */
class StoreFarmDocumentsRequest extends FormRequest
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
            'land_title' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'farm_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'identity_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'skip_documents' => ['sometimes', 'boolean'],
        ];
    }
}
