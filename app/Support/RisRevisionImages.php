<?php

namespace App\Support;

use App\Exceptions\RevisionImageUploadException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Proof images (max 3) attached to revision remarks: RIS (Administrator) and, via DocumentRevisionNotes,
 * ATP / PO / RFC / LIQ (Accounting) and RR (Receiving Officer).
 * Stored on the public disk; served through owner-checked routes because there is no public storage link.
 */
class RisRevisionImages
{
    public const MAX = 3;

    public const MAX_KB = 5120;

    public const FIELD = 'revision_images';

    public static function supported(): bool
    {
        return Schema::hasTable('ris_revision_notes_table')
            && Schema::hasColumn('ris_revision_notes_table', 'ris_revision_images');
    }

    /**
     * First validation error message, or null when the uploads are acceptable.
     */
    public static function validationError(Request $request): ?string
    {
        $validator = Validator::make($request->all(), [
            self::FIELD => ['nullable', 'array', 'max:'.self::MAX],
            self::FIELD.'.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KB],
        ], [
            self::FIELD.'.max' => 'You can attach up to '.self::MAX.' images only.',
            self::FIELD.'.*.image' => 'Revision proof must be an image (JPG, PNG or WEBP).',
            self::FIELD.'.*.mimes' => 'Revision proof must be an image (JPG, PNG or WEBP).',
            self::FIELD.'.*.max' => 'Each revision image must be 5 MB or smaller.',
            self::FIELD.'.*.uploaded' => 'An image failed to upload. Please try a smaller file.',
        ]);

        return $validator->fails() ? $validator->errors()->first() : null;
    }

    /**
     * @return array<int, UploadedFile>
     */
    public static function uploadedFiles(Request $request): array
    {
        $files = $request->file(self::FIELD, []);
        $files = is_array($files) ? $files : [$files];

        return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile && $file->isValid()));
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{path: string, name: string}>
     */
    public static function store(array $files, int $risId): array
    {
        return self::storeIn($files, 'ris-revision-images/'.$risId);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array<int, array{path: string, name: string}>
     *
     * @throws RevisionImageUploadException
     */
    public static function storeIn(array $files, string $directory): array
    {
        $stored = [];

        try {
            foreach (array_slice($files, 0, self::MAX) as $file) {
                $path = $file->storeAs($directory, $file->hashName(), 'public');
                if (!$path) {
                    throw new RevisionImageUploadException('Unable to save a revision image. Please try again.');
                }
                $stored[] = [
                    'path' => $path,
                    'name' => mb_substr((string) $file->getClientOriginalName(), 0, 150),
                ];
            }
        } catch (\Throwable $e) {
            self::delete($stored);
            throw $e instanceof RevisionImageUploadException
                ? $e
                : new RevisionImageUploadException('Unable to save a revision image. Please try again.');
        }

        return $stored;
    }

    /**
     * @param  array<int, array{path: string, name: string}>  $images
     */
    public static function delete(array $images): void
    {
        foreach ($images as $image) {
            $path = (string) ($image['path'] ?? '');
            if ($path !== '') {
                try {
                    Storage::disk('public')->delete($path);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    public static function encode(array $images): ?string
    {
        return $images === [] ? null : json_encode(array_values($images));
    }

    /**
     * @return array<int, array{path: string, name: string}>
     */
    public static function decode($value): array
    {
        if (is_object($value)) {
            $value = $value->ris_revision_images ?? $value->revision_images ?? null;
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($image) => is_array($image) && !empty($image['path'])));
    }
}
