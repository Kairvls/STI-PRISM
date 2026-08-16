<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AccountingController extends Controller
{
    public function dashboard(): View
    {
        return view('accounting.dashboard');
    }

    public function requestCheck(Request $request)
    {
        $filter = $request->query('status', 'submitted');

        $query = RequestForCheckController::reviewBaseQuery()
            ->where(function ($q) {
                $q->whereNull('request_check_table.request_check_is_archived')
                    ->orWhere('request_check_table.request_check_is_archived', 0);
            });

        if ($filter === 'submitted') {
            $query->where(function ($q) {
                $q->whereIn('request_check_table.request_check_status', ['Submitted', 'Resubmitted'])
                    ->orWhere(function ($inner) {
                        $inner->where('request_check_table.request_check_status', 'Under Review')
                            ->where('request_check_table.request_check_review_stage', 'accounting');
                    });
            });
        } elseif ($filter === 'forwarded') {
            $query->whereIn('request_check_table.request_check_status', ['Pending Admin Approval', 'Approved']);
        } elseif ($filter === 'release') {
            $query->where('request_check_table.request_check_status', 'Approved');
            if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                $query->whereNull('request_check_table.request_check_funds_released_at');
            }
        } elseif ($filter === 'rejected') {
            $query->where('request_check_table.request_check_status', 'Rejected');
        }

        $rfcs = $query
            ->orderByDesc('request_check_table.request_check_submitted_at')
            ->orderByDesc('request_check_table.request_check_id')
            ->paginate(10)
            ->withQueryString();

        $rfcIds = $rfcs->getCollection()->pluck('request_check_id');
        $attachments = $rfcIds->isEmpty()
            ? collect()
            : DB::table('request_check_attachments_table')
                ->whereIn('request_check_id', $rfcIds)
                ->get()
                ->groupBy('request_check_id');

        $counts = [
            'submitted' => RequestForCheckController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
                })
                ->where(function ($q) {
                    $q->whereIn('request_check_status', ['Submitted', 'Resubmitted'])
                        ->orWhere(function ($inner) {
                            $inner->where('request_check_status', 'Under Review')
                                ->where('request_check_review_stage', 'accounting');
                        });
                })
                ->count(),
            'forwarded' => RequestForCheckController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
                })
                ->whereIn('request_check_status', ['Pending Admin Approval', 'Approved'])
                ->count(),
            'release' => $this->rfcReadyToReleaseCount(),
            'rejected' => RequestForCheckController::reviewBaseQuery()
                ->where(function ($q) {
                    $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
                })
                ->where('request_check_status', 'Rejected')
                ->count(),
        ];

        return view('accounting.request-check.index', compact('rfcs', 'attachments', 'filter', 'counts'));
    }

    public function startRfcReview($id)
    {
        return DB::transaction(function () use ($id) {
            $rfc = $this->lockAccountingRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            if (in_array($rfc->request_check_status, ['Submitted', 'Resubmitted'], true)) {
                DB::table('request_check_table')->where('request_check_id', $id)->update([
                    'request_check_status' => 'Under Review',
                    'request_check_review_stage' => 'accounting',
                    'request_check_updated_at' => now(),
                ]);
            }

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'Under Review']);
            }

            return back();
        });
    }

    public function verifyRfc($id)
    {
        return DB::transaction(function () use ($id) {
            $rfc = $this->lockAccountingRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_status' => 'Pending Admin Approval',
                'request_check_review_stage' => 'admin',
                'request_check_accounting_verified_by' => Auth::id(),
                'request_check_accounting_verified_at' => now(),
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Request for Check verified and forwarded to Admin.');
        });
    }

    public function rejectRfc(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);

        return DB::transaction(function () use ($request, $id) {
            $rfc = $this->lockAccountingRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_status' => 'Rejected',
                'request_check_rejection_reason' => $request->input('remarks'),
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Request for Check rejected.');
        });
    }

    public function reviseRfc(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);

        return DB::transaction(function () use ($request, $id) {
            $rfc = $this->lockAccountingRfc($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_status' => 'Minor Revision',
                'request_check_review_stage' => null,
                'request_check_revision_notes' => $request->input('remarks'),
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Request for Check returned to Purchaser for revision.');
        });
    }

    public function releaseRfcFunds($id)
    {
        return DB::transaction(function () use ($id) {
            $rfc = $this->lockRfcForFundsRelease($id);
            if (!is_object($rfc)) {
                return $rfc;
            }

            DB::table('request_check_table')->where('request_check_id', $id)->update([
                'request_check_funds_released_at' => now(),
                'request_check_funds_released_by' => Auth::id(),
                'request_check_updated_at' => now(),
            ]);

            return back()->with('success', 'Funds released. The purchaser can now collect the check and create a Receiving Report.');
        });
    }

    public function authorityToPurchase(Request $request)
    {
        $filter = $request->query('status', 'submitted');

        $query = $this->atpBaseQuery();

        if ($filter === 'submitted') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at');
        } elseif ($filter === 'approved') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Approved');
        } elseif ($filter === 'rejected') {
            $query->where('authority_to_purchase_table.authority_purchase_status', 'Rejected');
        }

        $query->where(function ($q) {
            $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
        });

        $atps = $query
            ->orderByDesc('authority_to_purchase_table.authority_purchase_submitted_at')
            ->orderByDesc('authority_to_purchase_table.authority_purchase_id')
            ->paginate(10)
            ->withQueryString();

        $atpItems = DB::table('authority_to_purchase_items_table')
            ->whereIn('authority_purchase_id', $atps->getCollection()->pluck('authority_purchase_id'))
            ->orderBy('atp_item_id')
            ->get()
            ->groupBy('authority_purchase_id');

        $counts = [
            'submitted' => $this->atpBaseQuery()
                ->where('authority_to_purchase_table.authority_purchase_status', 'Pending')
                ->whereNotNull('authority_to_purchase_table.authority_purchase_submitted_at')
                ->where(function ($q) {
                    $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                        ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
                })
                ->count(),
            'approved' => $this->atpBaseQuery()
                ->where('authority_to_purchase_table.authority_purchase_status', 'Approved')
                ->where(function ($q) {
                    $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                        ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
                })
                ->count(),
            'rejected' => $this->atpBaseQuery()
                ->where('authority_to_purchase_table.authority_purchase_status', 'Rejected')
                ->where(function ($q) {
                    $q->whereNull('authority_to_purchase_table.authority_purchase_is_archived')
                        ->orWhere('authority_to_purchase_table.authority_purchase_is_archived', 0);
                })
                ->count(),
        ];

        return view('accounting.authority-to-purchase.index', compact('atps', 'atpItems', 'filter', 'counts'));
    }

    public function approveAtp($id)
    {
        return DB::transaction(function () use ($id) {
            $atp = $this->lockSubmittedAtp($id);
            if (!is_object($atp)) {
                return $atp;
            }

            $approverName = Auth::user()->user_full_name ?? 'Accounting';

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_status' => 'Approved',
                    'authority_purchase_authorized_by_signature' => $approverName,
                    'authority_purchase_rejection_reason' => null,
                    'authority_purchase_updated_at' => now(),
                ]);

            return back()->with('success', 'Authority to Purchase approved.');
        });
    }

    public function rejectAtp(Request $request, $id)
    {
        $request->validate([
            'remarks' => ['required', 'string', 'max:5000'],
        ]);

        return DB::transaction(function () use ($request, $id) {
            $atp = $this->lockSubmittedAtp($id);
            if (!is_object($atp)) {
                return $atp;
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_status' => 'Rejected',
                    'authority_purchase_rejection_reason' => $request->input('remarks'),
                    'authority_purchase_updated_at' => now(),
                ]);

            return back()->with('success', 'Authority to Purchase rejected.');
        });
    }

    public function reviseAtp(Request $request, $id)
    {
        $request->validate([
            'remarks' => ['required', 'string', 'max:5000'],
        ]);

        return DB::transaction(function () use ($request, $id) {
            $atp = $this->lockSubmittedAtp($id);
            if (!is_object($atp)) {
                return $atp;
            }

            DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $id)
                ->update([
                    'authority_purchase_status' => 'Pending',
                    'authority_purchase_submitted_by' => null,
                    'authority_purchase_submitted_at' => null,
                    'authority_purchase_rejection_reason' => $request->input('remarks'),
                    'authority_purchase_updated_at' => now(),
                ]);

            return back()->with('success', 'ATP returned to the Purchaser for revision.');
        });
    }

    public function financialRecords(): View
    {
        return view('accounting.financial-records.index');
    }

    public function liquidationReports(Request $request)
    {
        $filter = $request->query('status', 'submitted');
        $query = \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()
            ->where(function ($q) {
                $q->whereNull('liquidation_reports_table.liquidation_report_is_archived')
                    ->orWhere('liquidation_reports_table.liquidation_report_is_archived', 0);
            });

        if ($filter === 'submitted') {
            $query->where(function ($q) {
                $q->whereIn('liquidation_report_status', ['Submitted', 'Resubmitted'])
                    ->orWhere(function ($inner) {
                        $inner->where('liquidation_report_status', 'Under Review')->where('liquidation_report_review_stage', 'accounting');
                    });
            });
        } elseif ($filter === 'forwarded') {
            $query->whereIn('liquidation_report_status', ['Pending Admin Approval', 'Approved']);
        } elseif ($filter === 'rejected') {
            $query->where('liquidation_report_status', 'Rejected');
        }

        $reports = $query->orderByDesc('liquidation_report_submitted_at')->paginate(10)->withQueryString();
        $ids = $reports->getCollection()->pluck('liquidation_report_id');
        $items = $ids->isEmpty() ? collect() : DB::table('liquidation_report_items_table')->whereIn('liquidation_report_id', $ids)->orderBy('liquidation_item_id')->get()->groupBy('liquidation_report_id');

        $counts = [
            'submitted' => \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            })->where(function ($q) {
                $q->whereIn('liquidation_report_status', ['Submitted', 'Resubmitted'])
                    ->orWhere(function ($inner) {
                        $inner->where('liquidation_report_status', 'Under Review')->where('liquidation_report_review_stage', 'accounting');
                    });
            })->count(),
            'forwarded' => \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            })->whereIn('liquidation_report_status', ['Pending Admin Approval', 'Approved'])->count(),
            'rejected' => \App\Http\Controllers\LiquidationReportController::reviewBaseQuery()->where(function ($q) {
                $q->whereNull('liquidation_report_is_archived')->orWhere('liquidation_report_is_archived', 0);
            })->where('liquidation_report_status', 'Rejected')->count(),
        ];

        return view('accounting.liquidation-reports.index', compact('reports', 'items', 'filter', 'counts'));
    }

    public function startLiqReview($id)
    {
        return DB::transaction(function () use ($id) {
            $liq = $this->lockAccountingLiq($id);
            if (!is_object($liq)) return $liq;
            if (in_array($liq->liquidation_report_status, ['Submitted', 'Resubmitted'], true)) {
                DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                    'liquidation_report_status' => 'Under Review',
                    'liquidation_report_review_stage' => 'accounting',
                    'liquidation_report_updated_at' => now(),
                ]);
            }
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 'Under Review']);
            }
            return back();
        });
    }

    public function checkLiq($id)
    {
        return DB::transaction(function () use ($id) {
            $liq = $this->lockAccountingLiq($id);
            if (!is_object($liq)) return $liq;
            $name = Auth::user()->user_full_name ?? 'Accountant';
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Pending Admin Approval',
                'liquidation_report_review_stage' => 'admin',
                'liquidation_report_checked_by_accountant' => $name,
                'liquidation_report_checked_by_date' => now()->toDateString(),
                'liquidation_report_updated_at' => now(),
            ]);
            return back()->with('success', 'Liquidation Report checked and forwarded to Admin.');
        });
    }

    public function rejectLiq(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);
        return DB::transaction(function () use ($request, $id) {
            $liq = $this->lockAccountingLiq($id);
            if (!is_object($liq)) return $liq;
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Rejected',
                'liquidation_report_rejection_reason' => $request->input('remarks'),
                'liquidation_report_updated_at' => now(),
            ]);
            return back()->with('success', 'Liquidation Report rejected.');
        });
    }

    public function reviseLiq(Request $request, $id)
    {
        $request->validate(['remarks' => ['required', 'string', 'max:5000']]);
        return DB::transaction(function () use ($request, $id) {
            $liq = $this->lockAccountingLiq($id);
            if (!is_object($liq)) return $liq;
            DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->update([
                'liquidation_report_status' => 'Minor Revision',
                'liquidation_report_review_stage' => null,
                'liquidation_report_revision_notes' => $request->input('remarks'),
                'liquidation_report_updated_at' => now(),
            ]);
            return back()->with('success', 'Liquidation Report returned to Purchaser for revision.');
        });
    }

    public function notifications(): View
    {
        return view('accounting.notifications.index');
    }

    private function atpBaseQuery()
    {
        return DB::table('authority_to_purchase_table')
            ->leftJoin(
                'requisition_issue_slip_table',
                'authority_to_purchase_table.authority_purchase_ris_id',
                '=',
                'requisition_issue_slip_table.ris_id'
            )
            ->leftJoin(
                'suppliers_table',
                'authority_to_purchase_table.authority_purchase_supplier_id',
                '=',
                'suppliers_table.supplier_id'
            )
            ->leftJoin(
                'physical_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'physical_suppliers_table.supplier_id'
            )
            ->leftJoin(
                'online_suppliers_table',
                'suppliers_table.supplier_id',
                '=',
                'online_suppliers_table.supplier_id'
            )
            ->select(
                'authority_to_purchase_table.*',
                'requisition_issue_slip_table.ris_form_number',
                'suppliers_table.supplier_store_type',
                'physical_suppliers_table.company_name',
                'online_suppliers_table.shop_name'
            );
    }

    private function lockSubmittedAtp($id)
    {
        $atp = DB::table('authority_to_purchase_table')
            ->where('authority_purchase_id', $id)
            ->lockForUpdate()
            ->first();

        if (!$atp) {
            return back()->with('error', 'Authority to Purchase not found.');
        }

        if ((int) ($atp->authority_purchase_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived ATP cannot be reviewed.');
        }

        if ($atp->authority_purchase_status !== 'Pending' || blank($atp->authority_purchase_submitted_at)) {
            return back()->with('error', 'Only submitted ATP records can be reviewed.');
        }

        return $atp;
    }

    private function lockAccountingRfc($id)
    {
        $rfc = DB::table('request_check_table')
            ->where('request_check_id', $id)
            ->lockForUpdate()
            ->first();

        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        if ((int) ($rfc->request_check_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived Request for Check cannot be reviewed.');
        }

        $reviewable = in_array($rfc->request_check_status, ['Submitted', 'Resubmitted', 'Under Review'], true)
            && ($rfc->request_check_review_stage === 'accounting' || in_array($rfc->request_check_status, ['Submitted', 'Resubmitted'], true));

        if (!$reviewable) {
            return back()->with('error', 'Only Request for Check records in the Accounting queue can be reviewed.');
        }

        return $rfc;
    }

    private function lockRfcForFundsRelease($id)
    {
        $rfc = DB::table('request_check_table')
            ->where('request_check_id', $id)
            ->lockForUpdate()
            ->first();

        if (!$rfc) {
            return back()->with('error', 'Request for Check not found.');
        }

        if ((int) ($rfc->request_check_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived Request for Check cannot be updated.');
        }

        if ($rfc->request_check_status !== 'Approved') {
            return back()->with('error', 'Funds can only be released after Admin approves the Request for Check.');
        }

        if (!empty($rfc->request_check_funds_released_at)) {
            return back()->with('error', 'Funds for this Request for Check have already been released.');
        }

        return $rfc;
    }

    private function rfcReadyToReleaseCount(): int
    {
        $query = RequestForCheckController::reviewBaseQuery()
            ->where(function ($q) {
                $q->whereNull('request_check_is_archived')->orWhere('request_check_is_archived', 0);
            })
            ->where('request_check_status', 'Approved');

        if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
            $query->whereNull('request_check_funds_released_at');
        }

        return $query->count();
    }

    private function lockAccountingLiq($id)
    {
        $liq = DB::table('liquidation_reports_table')->where('liquidation_report_id', $id)->lockForUpdate()->first();
        if (!$liq) {
            return back()->with('error', 'Liquidation Report not found.');
        }
        if ((int) ($liq->liquidation_report_is_archived ?? 0) === 1) {
            return back()->with('error', 'Archived Liquidation Reports cannot be reviewed.');
        }
        $reviewable = in_array($liq->liquidation_report_status, ['Submitted', 'Resubmitted', 'Under Review'], true)
            && ($liq->liquidation_report_review_stage === 'accounting' || in_array($liq->liquidation_report_status, ['Submitted', 'Resubmitted'], true));
        if (!$reviewable) {
            return back()->with('error', 'Only Liquidation Reports in the Accounting queue can be reviewed.');
        }
        return $liq;
    }
}
