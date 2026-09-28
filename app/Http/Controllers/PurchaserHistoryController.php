<?php

namespace App\Http\Controllers;

use App\Support\PurchaserHistory;
use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaserHistoryController extends Controller
{
    private const TABS = ['items', 'documents', 'suppliers'];

    public function index(Request $request)
    {
        $viewer = Auth::user();
        $viewerId = (int) $viewer->user_id;
        $isAdmin = RoleAccess::isAdmin($viewer);

        $accounts = PurchaserHistory::accounts();
        if (! $accounts->contains('user_id', $viewerId)) {
            $self = PurchaserHistory::describeAccount($viewer);
            $self->role_label = RoleAccess::primaryRoleLabel($viewer);
            $accounts->prepend($self);
        }

        // Each purchaser sees only their own history; administrators may open any purchaser's.
        $subjectId = $viewerId;
        if ($isAdmin && $request->filled('purchaser')) {
            $requested = (int) $request->query('purchaser');
            if ($accounts->contains('user_id', $requested)) {
                $subjectId = $requested;
            }
        }
        $subject = $accounts->firstWhere('user_id', $subjectId);
        $isOwnHistory = $subjectId === $viewerId;

        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'items';

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(PurchaserHistory::STATUS_FILTERS))],
            'type' => ['nullable', 'in:'.implode(',', array_keys(PurchaserHistory::DOCUMENT_TYPES))],
        ]);

        $filters = [
            'search' => $validated['search'] ?? null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
            'status' => $validated['status'] ?? null,
            'type' => $validated['type'] ?? null,
        ];
        $periodFilters = ['from' => $filters['from'], 'to' => $filters['to']];

        $summary = PurchaserHistory::summary($subjectId, $periodFilters);
        $monthlySpend = PurchaserHistory::monthlySpend($subjectId);
        $documentCounts = PurchaserHistory::documentCounts($subjectId);

        $items = $tab === 'items' ? PurchaserHistory::purchasedItems($subjectId, $filters) : null;
        $documents = $tab === 'documents' ? PurchaserHistory::documents($subjectId, $filters) : null;
        $suppliers = $tab === 'suppliers' ? PurchaserHistory::suppliers($subjectId, $periodFilters) : null;

        return view('purchaser.history.index', [
            'accounts' => $accounts,
            'subject' => $subject,
            'isAdmin' => $isAdmin,
            'isOwnHistory' => $isOwnHistory,
            'tab' => $tab,
            'filters' => $filters,
            'summary' => $summary,
            'monthlySpend' => $monthlySpend,
            'documentCounts' => $documentCounts,
            'items' => $items,
            'documents' => $documents,
            'suppliers' => $suppliers,
        ]);
    }
}
