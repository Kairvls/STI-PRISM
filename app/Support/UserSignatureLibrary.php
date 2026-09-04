<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserSignatureLibrary
{
    public const MAX_PER_USER = 4;

    public static function tableReady(): bool
    {
        return Schema::hasTable('user_signatures_table');
    }

    public static function forUser(int $userId): Collection
    {
        if (!self::tableReady() || $userId <= 0) {
            return collect();
        }

        self::trimToMax($userId);

        return DB::table('user_signatures_table')
            ->where('user_id', $userId)
            ->orderByDesc('user_signature_is_default')
            ->orderByDesc('user_signature_created_at')
            ->orderByDesc('user_signature_id')
            ->get()
            ->map(function ($row) {
                $row->preview_url = self::dataUrlForPath((string) $row->user_signature_path);

                return $row;
            })
            ->filter(fn ($row) => is_string($row->preview_url) && $row->preview_url !== '');
    }

    public static function trimToMax(int $userId): void
    {
        if (!self::tableReady() || $userId <= 0) {
            return;
        }

        $ids = DB::table('user_signatures_table')
            ->where('user_id', $userId)
            ->orderByDesc('user_signature_is_default')
            ->orderByDesc('user_signature_created_at')
            ->orderByDesc('user_signature_id')
            ->pluck('user_signature_id');

        if ($ids->count() <= self::MAX_PER_USER) {
            return;
        }

        foreach ($ids->slice(self::MAX_PER_USER) as $signatureId) {
            self::deleteForUser($userId, (int) $signatureId);
        }
    }

    public static function dataUrlForPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || !Storage::disk('public')->exists($path)) {
            return '';
        }

        $binary = Storage::disk('public')->get($path);
        if (!is_string($binary) || $binary === '') {
            return '';
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        if (!str_starts_with($mime, 'image/')) {
            $mime = 'image/png';
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    public static function countForUser(int $userId): int
    {
        if (!self::tableReady() || $userId <= 0) {
            return 0;
        }

        return (int) DB::table('user_signatures_table')
            ->where('user_id', $userId)
            ->count();
    }

    public static function canSaveMore(int $userId): bool
    {
        return self::countForUser($userId) < self::MAX_PER_USER;
    }

    public static function storeFromDataUrl(int $userId, string $dataUrl, ?string $label = null): ?object
    {
        if (!self::tableReady() || $userId <= 0) {
            return null;
        }

        if (!self::canSaveMore($userId)) {
            return null;
        }

        $normalized = RisWorkflow::normalizeDrawnSignature($dataUrl);
        if (!$normalized || !preg_match('#^data:image/(png|jpeg|jpg|webp|gif);base64,(.+)$#is', $normalized, $matches)) {
            return null;
        }

        $binary = base64_decode($matches[2], true);
        if ($binary === false || $binary === '' || strlen($binary) > 1500000) {
            return null;
        }

        $ext = strtolower($matches[1]) === 'jpg' ? 'jpeg' : strtolower($matches[1]);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        return self::persistBinary($userId, $binary, $ext, $label);
    }

    public static function storeFromUpload(int $userId, UploadedFile $file, ?string $label = null): ?object
    {
        if (!self::tableReady() || $userId <= 0 || !$file->isValid()) {
            return null;
        }

        if (!self::canSaveMore($userId)) {
            return null;
        }

        $mime = (string) $file->getMimeType();
        if (!str_starts_with($mime, 'image/')) {
            return null;
        }

        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '' || strlen($binary) > 1500000) {
            return null;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
            $ext = str_contains($mime, 'png') ? 'png' : 'jpg';
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $label = $label !== null && trim($label) !== ''
            ? $label
            : pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);

        return self::persistBinary($userId, $binary, $ext, $label);
    }

    public static function deleteForUser(int $userId, int $signatureId): bool
    {
        if (!self::tableReady() || $userId <= 0 || $signatureId <= 0) {
            return false;
        }

        $row = DB::table('user_signatures_table')
            ->where('user_signature_id', $signatureId)
            ->where('user_id', $userId)
            ->first();

        if (!$row) {
            return false;
        }

        $path = trim((string) ($row->user_signature_path ?? ''));
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        DB::table('user_signatures_table')
            ->where('user_signature_id', $signatureId)
            ->where('user_id', $userId)
            ->delete();

        return true;
    }

    private static function persistBinary(int $userId, string $binary, string $ext, ?string $label): ?object
    {
        if (!self::canSaveMore($userId)) {
            return null;
        }

        $path = 'user-signatures/'.$userId.'/'.Str::uuid()->toString().'.'.$ext;
        Storage::disk('public')->put($path, $binary);

        $cleanLabel = trim((string) $label);
        if ($cleanLabel === '') {
            $cleanLabel = 'Signature '.now()->format('d/m/Y H:i');
        }
        $cleanLabel = Str::limit($cleanLabel, 120, '');

        $id = DB::table('user_signatures_table')->insertGetId([
            'user_id' => $userId,
            'user_signature_label' => $cleanLabel,
            'user_signature_path' => $path,
            'user_signature_is_default' => false,
            'user_signature_created_at' => now(),
        ]);

        $row = DB::table('user_signatures_table')->where('user_signature_id', $id)->first();
        if ($row) {
            $row->preview_url = self::dataUrlForPath($path);
        }

        return $row;
    }
}
