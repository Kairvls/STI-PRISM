<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SupplierLogo
{
    public const DISK = 'public';

    public const DIRECTORY = 'suppliers/logos';

    public const MAX_KB = 2048;

    public const MIMES = 'jpg,jpeg,png,webp,gif';

    public static function supported(): bool
    {
        return Schema::hasTable('suppliers_table')
            && Schema::hasColumn('suppliers_table', 'supplier_logo_path');
    }

    public static function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || ! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    public static function store(UploadedFile $file, int $supplierId, ?string $previousPath = null): string
    {
        self::delete($previousPath);

        $directory = self::DIRECTORY.'/'.$supplierId;

        return $file->store($directory, self::DISK);
    }

    public static function delete(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
