<?php

namespace App\Http\Controllers;

use App\Support\DocumentRevisionNotes;
use App\Support\PurchaserDocumentAccess;
use App\Support\RisRevisionImages;
use Illuminate\Support\Facades\Storage;

class DocumentRevisionImageController extends Controller
{
    public function show($revisionId, $index)
    {
        $revision = DocumentRevisionNotes::find($revisionId);
        abort_if(!$revision, 404);

        $document = DocumentRevisionNotes::documentFor($revision);
        abort_if(!$document, 404);

        // Staff portals (Accounting / Receiving) are role-gated by middleware; purchasers only see their own documents.
        if (request()->routeIs('accounting.*')) {
            abort_unless(in_array($revision->document_type, ['ATP', 'PO', 'RFC', 'LIQ'], true), 404);
        } elseif (request()->routeIs('receiving.*')) {
            abort_unless($revision->document_type === 'RR', 404);
        } else {
            PurchaserDocumentAccess::assertOwns($document, DocumentRevisionNotes::DOCUMENTS[$revision->document_type]['owner']);
        }

        $image = RisRevisionImages::decode($revision)[(int) $index] ?? null;
        abort_if(!$image || !Storage::disk('public')->exists($image['path']), 404);

        return Storage::disk('public')->response($image['path'], $image['name'] ?: basename($image['path']));
    }
}
