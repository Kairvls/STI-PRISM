<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RisForwardAttachments
{
    public const TABLE = 'ris_forward_attachments_table';

    public const MAX_FILES = 10;

    public static function ready(): bool
    {
        return Schema::hasTable(self::TABLE);
    }

    /**
     * Saved supporting files for one RIS.
     *
     * @return list<array{key: string, name: string, path: string}>
     */
    public static function listed(object $ris): array
    {
        $risId = (int) ($ris->ris_id ?? 0);
        $items = [];

        if ($risId > 0 && self::ready()) {
            $rows = DB::table(self::TABLE)
                ->where('ris_id', $risId)
                ->orderBy('ris_forward_attachment_id')
                ->get();
            foreach ($rows as $row) {
                $path = trim((string) ($row->ris_forward_attachment_path ?? ''));
                if ($path === '') {
                    continue;
                }
                $items[] = [
                    'key' => (string) $row->ris_forward_attachment_id,
                    'name' => trim((string) ($row->ris_forward_attachment_name ?? '')) ?: basename($path),
                    'path' => $path,
                ];
            }
        }

        if (! Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path')) {
            return $items;
        }

        $legacyPath = trim((string) ($ris->ris_forward_attachment_path ?? ''));
        $known = array_column($items, 'path');
        if ($legacyPath !== '' && ! in_array($legacyPath, $known, true)) {
            array_unshift($items, [
                'key' => 'legacy',
                'name' => trim((string) ($ris->ris_forward_attachment_name ?? '')) ?: basename($legacyPath),
                'path' => $legacyPath,
            ]);
        }

        return $items;
    }

    /**
     * Keep the chosen saved files, drop the rest, then store newly uploaded files.
     *
     * @param  list<string>  $keepKeys
     * @param  list<UploadedFile>  $files
     * @return list<string> names of the files that remain
     */
    public static function sync(int $risId, array $keepKeys, array $files): array
    {
        $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
        $existing = $ris ? self::listed($ris) : [];
        $keep = array_fill_keys(array_map('strval', $keepKeys), true);
        $remaining = [];

        foreach ($existing as $item) {
            if (isset($keep[$item['key']])) {
                $remaining[] = $item;
                continue;
            }

            if ($item['key'] !== 'legacy' && self::ready()) {
                DB::table(self::TABLE)
                    ->where('ris_id', $risId)
                    ->where('ris_forward_attachment_id', (int) $item['key'])
                    ->delete();
            }

            $stillUsed = false;
            foreach ($existing as $other) {
                if ($other['path'] === $item['path'] && isset($keep[$other['key']])) {
                    $stillUsed = true;
                    break;
                }
            }
            if (! $stillUsed && $item['path'] !== '' && Storage::disk('public')->exists($item['path'])) {
                Storage::disk('public')->delete($item['path']);
            }
        }

        if (self::ready()) {
            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $dir = 'ris-forward-attachments/'.$risId;
                $hash = $file->hashName();
                if (! $file->storeAs($dir, $hash, 'public')) {
                    throw new \RuntimeException('Could not save a supporting attachment. Please try again.');
                }
                $path = $dir.'/'.$hash;
                $name = $file->getClientOriginalName();
                DB::table(self::TABLE)->insert([
                    'ris_id' => $risId,
                    'ris_forward_attachment_name' => $name,
                    'ris_forward_attachment_path' => $path,
                    'ris_forward_attachment_size' => $file->getSize(),
                    'ris_forward_attachment_created_at' => now(),
                ]);
                $remaining[] = [
                    'key' => 'new',
                    'name' => $name,
                    'path' => $path,
                ];
            }
        }

        $first = $remaining[0] ?? null;
        $payload = [];
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path')) {
            $payload['ris_forward_attachment_path'] = $first['path'] ?? null;
        }
        if (Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_name')) {
            $payload['ris_forward_attachment_name'] = $first['name'] ?? null;
        }
        if ($payload !== []) {
            DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->update($payload);
        }

        return array_values(array_map(fn ($item) => $item['name'], $remaining));
    }

    /**
     * @return array{name: string, path: string}|null
     */
    public static function find(int $risId, string $key): ?array
    {
        if ($key === 'legacy') {
            $ris = DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->first();
            if (! $ris) {
                return null;
            }
            foreach (self::listed($ris) as $item) {
                if ($item['key'] === 'legacy') {
                    return ['name' => $item['name'], 'path' => $item['path']];
                }
            }

            return null;
        }

        if (! self::ready() || ! ctype_digit($key)) {
            return null;
        }

        $row = DB::table(self::TABLE)
            ->where('ris_id', $risId)
            ->where('ris_forward_attachment_id', (int) $key)
            ->first();
        if (! $row) {
            return null;
        }

        $path = trim((string) ($row->ris_forward_attachment_path ?? ''));
        if ($path === '') {
            return null;
        }

        return [
            'name' => trim((string) ($row->ris_forward_attachment_name ?? '')) ?: basename($path),
            'path' => $path,
        ];
    }
}
