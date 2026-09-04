<?php

namespace App\Services;

/**
 * Shared helpers for Excel/Word form exporters.
 *
 * Document payload resolution supports the optional-blank pattern used by
 * ATP/RIS/RR and the array payload pattern used by Liquidation:
 *   - null → blank template
 *   - array → full data payload (e.g. ['liq' => ..., 'items' => ...])
 *   - object (+ optional items) → live document export
 */
trait FormExporterHelpers
{
    protected function plainName(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'data:image') || str_starts_with($value, 'data:')) {
            return '';
        }

        return $value;
    }

    protected function formatDate($value, string $format = 'd/m/Y'): string
    {
        if (blank($value)) {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function d($value, string $format = 'd/m/Y'): string
    {
        return $this->formatDate($value, $format);
    }

    protected function n($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, $decimals);
    }

    protected function companyName(): string
    {
        return 'STI COLLEGE ORMOC, INC.';
    }

    /**
     * Normalize exporter input to a data array with a document key and items.
     *
     * @param  array|object|null  $docOrData
     * @param  mixed  $items
     */
    protected function resolveExportData($docOrData, $items, string $documentKey, callable $blankFactory): array
    {
        if ($docOrData === null) {
            return $blankFactory();
        }

        if (is_array($docOrData)) {
            return $docOrData;
        }

        return [
            $documentKey => $docOrData,
            'items' => $items ?? collect(),
        ];
    }

    /**
     * Normalize exporters that work on a single document object (no line items).
     *
     * @param  array|object|null  $docOrData
     */
    protected function resolveExportDocument($docOrData, string $documentKey, callable $blankFactory): object
    {
        if ($docOrData === null) {
            $blank = $blankFactory();

            return is_array($blank) ? (object) ($blank[$documentKey] ?? $blank) : $blank;
        }

        if (is_array($docOrData)) {
            return (object) ($docOrData[$documentKey] ?? $docOrData);
        }

        return $docOrData;
    }

    protected function signatureImagePath(?string $dataUrl): ?string
    {
        $dataUrl = trim((string) $dataUrl);
        if ($dataUrl === '') {
            return null;
        }

        $binary = null;
        $ext = 'png';

        if (str_starts_with($dataUrl, 'data:image')) {
            if (!preg_match('#^data:image/(png|jpeg|jpg);base64,(.+)$#is', $dataUrl, $matches)) {
                return null;
            }

            $binary = base64_decode($matches[2], true);
            $ext = strtolower($matches[1]) === 'jpg' ? 'jpeg' : strtolower($matches[1]);
        } else {
            $path = parse_url($dataUrl, PHP_URL_PATH) ?: $dataUrl;
            $relative = ltrim((string) preg_replace('#^.*?/storage/#', '', str_replace('\\', '/', (string) $path)), '/');
            if ($relative !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($relative)) {
                $binary = \Illuminate\Support\Facades\Storage::disk('public')->get($relative);
            }
        }

        if ($binary === false || $binary === null || $binary === '') {
            return null;
        }

        $path = tempnam(sys_get_temp_dir(), 'sig') . '.' . $ext;
        file_put_contents($path, $binary);

        return $path;
    }
}
