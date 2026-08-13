<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates profile picture uploads.
 */
class ProfileAvatarUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.required' => 'Choose a profile picture to upload.',
            'avatar.image' => 'The profile picture must be an image file.',
            'avatar.mimes' => 'Use a JPG, PNG, or WEBP image.',
            'avatar.max' => 'Profile pictures must be 2 MB or smaller.',
        ];
    }
}
