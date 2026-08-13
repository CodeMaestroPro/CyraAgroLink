<?php

declare(strict_types=1);

namespace App\Services\Farm;

use App\Contracts\Repositories\FarmRepositoryInterface;
use App\Enums\FarmStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Farm registration wizard business logic.
 */
class FarmRegistrationService
{
    public const TOTAL_STEPS = 5;

    /** @var list<string> */
    private const DOCUMENT_KEYS = ['land_title', 'farm_certificate', 'identity_document'];

    public function __construct(
        protected FarmRepositoryInterface $farmRepository
    ) {
    }

    /**
     * Start or resume a draft farm registration for the user.
     */
    public function startOrResume(User $user, ?int $farmId = null, bool $forceNew = false): Farm
    {
        if ($farmId !== null && ! $forceNew) {
            return $this->farmRepository->findOwnedOrFail($user, $farmId);
        }

        if (! $forceNew) {
            $draft = $this->farmRepository->findDraftForUser($user);

            if ($draft !== null) {
                return $draft;
            }
        }

        /** @var Farm $farm */
        $farm = $this->farmRepository->create([
            'user_id' => $user->id,
            'status' => FarmStatus::Draft,
            'registration_step' => 1,
            'latitude' => 7.3775,
            'longitude' => 3.9470,
            'state' => 'Oyo',
            'local_government' => 'Ibadan North',
            'address' => 'Akinyele, Ibadan',
            'documents' => [],
        ]);

        return $farm;
    }

    /**
     * @return Collection<int, Farm>
     */
    public function listForUser(User $user): Collection
    {
        return $this->farmRepository->getForUser($user);
    }

    /**
     * Persist farm location (wizard step 1).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveLocation(User $user, Farm $farm, array $data): Farm
    {
        $this->assertOwnedDraft($user, $farm);

        /** @var Farm $updated */
        $updated = $this->farmRepository->update($farm->id, [
            'state' => $data['state'],
            'local_government' => $data['local_government'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'registration_step' => max($farm->registration_step, 2),
        ]);

        return $updated;
    }

    /**
     * Persist farm details (wizard step 2).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveDetails(User $user, Farm $farm, array $data): Farm
    {
        $this->assertOwnedDraft($user, $farm);

        /** @var Farm $updated */
        $updated = $this->farmRepository->update($farm->id, [
            'name' => $data['name'],
            'size_hectares' => $data['size_hectares'] ?? null,
            'soil_type' => $data['soil_type'] ?? null,
            'description' => $data['description'] ?? null,
            'registration_step' => max($farm->registration_step, 3),
        ]);

        return $updated;
    }

    /**
     * Persist selected crops (wizard step 3).
     *
     * @param  array<string, mixed>  $data
     */
    public function saveCrops(User $user, Farm $farm, array $data): Farm
    {
        $this->assertOwnedDraft($user, $farm);

        /** @var Farm $updated */
        $updated = $this->farmRepository->update($farm->id, [
            'crops' => $data['crops'] ?? [],
            'registration_step' => max($farm->registration_step, 4),
        ]);

        return $updated;
    }

    /**
     * Persist optional supporting documents (wizard step 4).
     *
     * @param  array<string, UploadedFile|null>  $files
     */
    public function saveDocuments(User $user, Farm $farm, array $files = []): Farm
    {
        $this->assertOwnedDraft($user, $farm);

        $documents = is_array($farm->documents) ? $farm->documents : [];

        foreach (self::DOCUMENT_KEYS as $key) {
            $file = $files[$key] ?? null;

            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! empty($documents[$key]['path'])) {
                Storage::disk('public')->delete($documents[$key]['path']);
            }

            $stored = $file->store('farm-documents/'.$farm->id, 'public');

            $documents[$key] = [
                'path' => $stored,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        /** @var Farm $updated */
        $updated = $this->farmRepository->update($farm->id, [
            'documents' => $documents,
            'registration_step' => max($farm->registration_step, 5),
        ]);

        return $updated;
    }

    /**
     * Finalize registration (wizard step 5).
     */
    public function submit(User $user, Farm $farm): Farm
    {
        $this->assertOwnedDraft($user, $farm);
        $this->assertReadyForSubmission($farm);

        return DB::transaction(function () use ($farm): Farm {
            /** @var Farm $updated */
            $updated = $this->farmRepository->update($farm->id, [
                'status' => FarmStatus::PendingReview,
                'registration_step' => self::TOTAL_STEPS,
                'registered_at' => now(),
            ]);

            return $updated;
        });
    }

    /**
     * Ensure the farm belongs to the user and remains a draft.
     */
    protected function assertOwnedDraft(User $user, Farm $farm): void
    {
        if ($farm->user_id !== $user->id) {
            throw new BusinessLogicException('You are not authorized to update this farm.', 'FARM_FORBIDDEN', 403);
        }

        if ($farm->status !== FarmStatus::Draft) {
            throw new BusinessLogicException('This farm registration can no longer be edited.');
        }
    }

    /**
     * Validate minimum data before final submission.
     */
    protected function assertReadyForSubmission(Farm $farm): void
    {
        if (blank($farm->state) || blank($farm->local_government) || blank($farm->address)) {
            throw new BusinessLogicException('Farm location is incomplete.');
        }

        if ($farm->latitude === null || $farm->longitude === null) {
            throw new BusinessLogicException('Farm coordinates are required.');
        }

        if (blank($farm->name)) {
            throw new BusinessLogicException('Farm name is required before submission.');
        }
    }
}
