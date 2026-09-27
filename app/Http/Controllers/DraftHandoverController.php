<?php

namespace App\Http\Controllers;

use App\Support\DraftHandover;
use App\Support\ProcurementPortal;
use App\Support\PurchaseOrderBasket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DraftHandoverController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'in:'.implode(',', DraftHandover::TYPES)],
            'document_id' => ['required', 'integer', 'min:1'],
            'to_user_id' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'to_user_id.required' => 'Choose the co-worker who should take over this draft.',
        ]);

        try {
            DraftHandover::request(
                $validated['document_type'],
                (int) $validated['document_id'],
                (int) $validated['to_user_id'],
                $validated['note'] ?? null
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Draft sent to '.DraftHandover::userName((int) $validated['to_user_id']).'. It stays with you until they accept it.'
        );
    }

    public function accept(int $handoverId): RedirectResponse
    {
        try {
            $result = DraftHandover::accept($handoverId);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $this->redirectToDocument($result->type, $result->id)
            ->with('success', $result->label.' is now yours. Review it, sign, and submit when ready.');
    }

    public function decline(Request $request, int $handoverId): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = DraftHandover::decline($handoverId, $validated['reason'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'You declined '.$result->label.'. It stays with the sender.');
    }

    public function cancel(int $handoverId): RedirectResponse
    {
        try {
            $result = DraftHandover::cancel($handoverId);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Handover of '.$result->label.' cancelled. The draft stays with you.');
    }

    public function dismiss(int $handoverId): RedirectResponse
    {
        try {
            DraftHandover::dismiss($handoverId);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back();
    }

    /**
     * Read-only view of a draft that is waiting to be accepted.
     */
    public function preview(int $handoverId): View
    {
        try {
            $handover = DraftHandover::forPreview($handoverId);
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        $type = (string) $handover->handover_document_type;
        $docId = (int) $handover->handover_document_id;
        $loader = app(AdminOperationsController::class);

        if ($type === 'po') {
            $order = DraftHandover::document('po', $docId);
            abort_if(! $order, 404);

            $documents = collect(PurchaseOrderBasket::atpIdsForPo($docId))
                ->map(fn ($atpId) => ['type' => 'atp'] + $loader->loadDocumentViewPayload('atp', (int) $atpId))
                ->all();
            $title = PurchaseOrderBasket::displayNumber($order);
        } else {
            $payload = ['type' => $type] + $loader->loadDocumentViewPayload($type, $docId);
            $documents = [$payload];
            $title = $payload['title'] ?? DraftHandover::typeLabel($type);
        }

        return view('purchaser.handovers.preview', [
            'title' => $title,
            'handover' => $handover,
            'documents' => $documents,
        ]);
    }

    private function redirectToDocument(string $type, int $id): RedirectResponse
    {
        return match ($type) {
            'ris' => ProcurementPortal::redirect('ris.index', ['view_ris' => $id, 'status' => 'Draft']),
            'atp' => ProcurementPortal::redirect('atp.index', ['view_atp' => $id]),
            default => ProcurementPortal::redirect('purchase-orders.index'),
        };
    }
}
