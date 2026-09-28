<?php

namespace App\Support;

use App\Exceptions\RevisionImageUploadException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Revision remarks (with up to 3 proof images) sent back to the Purchaser on
 * ATP / PO / RFC / LIQ (Accounting) and RR (Receiving Officer).
 * The document row keeps its own remarks column; this table adds the images and a per-return history.
 */
class DocumentRevisionNotes
{
    public const KIND_REVISION = 'revision';

    public const KIND_RETURN = 'return';

    private const TABLE = 'document_revision_notes_table';

    public const DOCUMENTS = [
        'ATP' => ['table' => 'authority_to_purchase_table', 'key' => 'authority_purchase_id', 'owner' => 'atp'],
        'PO' => ['table' => 'purchase_orders_table', 'key' => 'purchase_order_id', 'owner' => 'po'],
        'RFC' => ['table' => 'request_check_table', 'key' => 'request_check_id', 'owner' => 'rfc'],
        'LIQ' => ['table' => 'liquidation_reports_table', 'key' => 'liquidation_report_id', 'owner' => 'liq'],
        'RR' => ['table' => 'receiving_reports_table', 'key' => 'receiving_report_id', 'owner' => 'rr'],
    ];

    private static ?bool $supported = null;

    /** @var array<string, object|null> */
    private static array $latest = [];

    public static function supported(): bool
    {
        if (self::$supported === null) {
            try {
                self::$supported = Schema::hasTable(self::TABLE);
            } catch (\Throwable $e) {
                self::$supported = false;
            }
        }

        return self::$supported;
    }

    /**
     * Stores the uploaded proof images, runs $apply(array $images), then records the revision note.
     * The images are deleted again if $apply or the insert fails.
     *
     * @throws RevisionImageUploadException
     */
    public static function record(Request $request, string $type, int $documentId, string $remarks, string $kind, callable $apply)
    {
        $images = self::supported()
            ? RisRevisionImages::storeIn(
                RisRevisionImages::uploadedFiles($request),
                'document-revision-images/'.strtolower($type).'/'.$documentId
            )
            : [];

        try {
            $result = $apply($images);

            if (self::supported()) {
                DB::table(self::TABLE)->insert([
                    'document_type' => $type,
                    'document_id' => $documentId,
                    'revision_kind' => $kind,
                    'revision_remarks' => $remarks,
                    'revision_images' => RisRevisionImages::encode($images),
                    'revision_requested_by' => Auth::id(),
                    'revision_created_at' => now(),
                ]);
                unset(self::$latest[self::cacheKey($type, $documentId, $kind)]);
            }

            return $result;
        } catch (\Throwable $e) {
            RisRevisionImages::delete($images);
            throw $e;
        }
    }

    public static function latest(string $type, $documentId, string $kind = self::KIND_REVISION): ?object
    {
        $documentId = (int) $documentId;
        if ($documentId <= 0 || !self::supported()) {
            return null;
        }

        $key = self::cacheKey($type, $documentId, $kind);
        if (!array_key_exists($key, self::$latest)) {
            try {
                self::$latest[$key] = DB::table(self::TABLE)
                    ->where('document_type', $type)
                    ->where('document_id', $documentId)
                    ->where('revision_kind', $kind)
                    ->orderByDesc('document_revision_id')
                    ->first();
            } catch (\Throwable $e) {
                self::$latest[$key] = null;
            }
        }

        return self::$latest[$key];
    }

    public static function imagesSuffix(array $images): string
    {
        $count = count($images);

        return $count === 0 ? '' : ' ('.$count.' image'.($count === 1 ? '' : 's').' attached)';
    }

    public static function find($revisionId): ?object
    {
        if (!self::supported()) {
            return null;
        }

        return DB::table(self::TABLE)->where('document_revision_id', (int) $revisionId)->first();
    }

    public static function documentFor(object $revision): ?object
    {
        $config = self::DOCUMENTS[$revision->document_type] ?? null;
        if (!$config || !Schema::hasTable($config['table'])) {
            return null;
        }

        return DB::table($config['table'])->where($config['key'], (int) $revision->document_id)->first();
    }

    private static function cacheKey(string $type, int $documentId, string $kind): string
    {
        return $type.':'.$documentId.':'.$kind;
    }
}
