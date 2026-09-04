<?php

namespace App\Support;

use Carbon\Carbon;

class ReplacementRequestCode
{
    /**
     * Human-friendly replacement request code for display (not a DB column).
     * Example: REQ-20260903-URG-014
     *
     * Parts: date sent + urgency (URG/STD) + request sequence (id).
     */
    public static function code(object|int|null $request, mixed $createdAt = null, mixed $urgency = null): string
    {
        if (is_object($request)) {
            $id = (int) ($request->procurement_request_id ?? 0);
            $createdAt = $request->procurement_request_created_at ?? $createdAt;
            $urgency = $request->report_urgency_level ?? $urgency;
        } else {
            $id = (int) ($request ?? 0);
        }

        $ymd = now()->format('Ymd');
        if (! empty($createdAt)) {
            try {
                $ymd = Carbon::parse($createdAt)->format('Ymd');
            } catch (\Throwable $e) {
                // keep today fallback
            }
        }

        $urgencyCode = self::urgencyCode($urgency);

        return sprintf('REQ-%s-%s-%03d', $ymd, $urgencyCode, max(0, $id));
    }

    public static function urgencyCode(mixed $urgency): string
    {
        return strcasecmp(trim((string) $urgency), 'Urgent') === 0 ? 'URG' : 'STD';
    }

    /**
     * Extract a procurement_request_id from generated code or raw id search text.
     */
    public static function parseSearch(?string $search): ?int
    {
        $search = trim((string) $search);
        if ($search === '') {
            return null;
        }

        if (preg_match('/^REQ-(\d{8})-(URG|STD)-(\d+)$/i', $search, $matches)) {
            return (int) $matches[3];
        }

        if (preg_match('/^#?(\d+)$/', $search, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
