<?php

namespace App\Http\Controllers;

use App\Services\DocumentWorkflowService;
use App\Support\AccountingHandoff;
use App\Support\DvFormNumber;
use App\Support\ProcurementRequestNumber;
use App\Support\ProcurementReviewPortal;
use App\Support\RfcAtpLinks;
use App\Support\ReviewerAssignment;
use App\Support\RisWorkflow;
use App\Support\UserSignatureLibrary;
use App\Support\WorkflowNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DisbursementVoucherController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('disbursement_vouchers_table'), 404);

        $filter = trim((string) $request->query('status', 'incoming'));
        $focusRfc = (int) $request->query('rfc', 0);
        $search = trim((string) $request->query('search', ''));

        $packagesQuery = DB::table('request_check_table');
        AccountingHandoff::scopeIncomingPackagesForAccounting($packagesQuery);
        $packagesQuery
            ->leftJoin('disbursement_vouchers_table as dv', function ($join) {
                $join->on('dv.dv_request_check_id', '=', 'request_check_table.request_check_id')
                    ->where(function ($q) {
                        $q->whereNull('dv.dv_is_archived')->orWhere('dv.dv_is_archived', 0);
                    });
            })
            ->select(
                'request_check_table.*',
                'dv.dv_id',
                'dv.dv_status',
                'dv.dv_form_number',
                'dv.dv_request_no as linked_dv_request_no',
                'dv.dv_amount as linked_dv_amount'
            )
            ->orderByDesc('request_check_table.request_check_sent_to_accounting_at');

        if ($filter === 'incoming') {
            $packagesQuery->where(function ($q) {
                $q->whereNull('dv.dv_id')
                    ->orWhereIn('dv.dv_status', ['Draft', 'Revision']);
            });
        } elseif ($filter === 'submitted') {
            $packagesQuery->where('dv.dv_status', 'Pending');
        } elseif ($filter === 'approved') {
            $packagesQuery->where('dv.dv_status', 'Approved');
        } elseif ($filter === 'with_president') {
            $packagesQuery->where('dv.dv_status', 'With President');
        } elseif ($filter === 'ready_release') {
            $packagesQuery->where('dv.dv_status', 'President Approved');
        } elseif ($filter === 'released') {
            $packagesQuery->where('dv.dv_status', 'Check Released');
        }

        if ($focusRfc > 0) {
            $packagesQuery->where('request_check_table.request_check_id', $focusRfc);
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $packagesQuery->where(function ($q) use ($like, $search) {
                $q->where('request_check_table.request_check_form_number', 'like', $like)
                    ->orWhere('request_check_table.request_check_payee', 'like', $like)
                    ->orWhere('dv.dv_form_number', 'like', $like)
                    ->orWhere('dv.dv_status', 'like', $like);

                if (Schema::hasColumn('request_check_table', 'request_check_request_no')) {
                    $q->orWhere('request_check_table.request_check_request_no', 'like', $like);
                }
                if (Schema::hasColumn('disbursement_vouchers_table', 'dv_request_no')) {
                    $q->orWhere('dv.dv_request_no', 'like', $like);
                }

                if (ctype_digit($search)) {
                    $q->orWhere('request_check_table.request_check_id', (int) $search)
                        ->orWhere('dv.dv_id', (int) $search);
                }
            });
        }

        $packages = $packagesQuery->paginate(20)->withQueryString();

        $this->attachPackageRefs($packages->getCollection());

        $base = DB::table('request_check_table');
        AccountingHandoff::scopeIncomingPackagesForAccounting($base);
        $base->leftJoin('disbursement_vouchers_table as dv', function ($join) {
            $join->on('dv.dv_request_check_id', '=', 'request_check_table.request_check_id')
                ->where(function ($q) {
                    $q->whereNull('dv.dv_is_archived')->orWhere('dv.dv_is_archived', 0);
                });
        });

        $counts = [
            'incoming' => (clone $base)->where(function ($q) {
                $q->whereNull('dv.dv_id')->orWhereIn('dv.dv_status', ['Draft', 'Revision']);
            })->count(),
            'submitted' => (clone $base)->where('dv.dv_status', 'Pending')->count(),
            'approved' => (clone $base)->where('dv.dv_status', 'Approved')->count(),
            'with_president' => (clone $base)->where('dv.dv_status', 'With President')->count(),
            'ready_release' => (clone $base)->where('dv.dv_status', 'President Approved')->count(),
            'released' => (clone $base)->where('dv.dv_status', 'Check Released')->count(),
        ];

        $editDvId = (int) $request->query('edit_dv', 0);
        $viewDvId = (int) $request->query('view_dv', 0);
        if ($request->session()->getOldInput() && ! $editDvId) {
            $editDvId = (int) $request->session()->getOldInput('_dv_id', 0);
        }

        $modalDvIds = $packages->getCollection()
            ->pluck('dv_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
        if ($editDvId > 0) {
            $modalDvIds[] = $editDvId;
        }
        if ($viewDvId > 0) {
            $modalDvIds[] = $viewDvId;
        }
        $modalDvIds = array_values(array_unique(array_filter($modalDvIds)));

        $modalDvs = collect();
        $modalPackages = [];
        if ($modalDvIds !== [] && Schema::hasTable('disbursement_vouchers_table')) {
            $modalDvs = DB::table('disbursement_vouchers_table')
                ->whereIn('dv_id', $modalDvIds)
                ->get()
                ->keyBy('dv_id');
            foreach ($modalDvs as $dv) {
                $modalPackages[(int) $dv->dv_id] = $this->packageContext((int) $dv->dv_request_check_id);
            }
        }

        return view('accounting.disbursement-vouchers.index', [
            'packages' => $packages,
            'filter' => $filter,
            'search' => $search,
            'counts' => $counts,
            'focusRfc' => $focusRfc ?: null,
            'editDvId' => $editDvId ?: null,
            'viewDvId' => $viewDvId ?: null,
            'modalDvs' => $modalDvs,
            'modalPackages' => $modalPackages,
            'savedSignatures' => UserSignatureLibrary::forUser((int) Auth::id()),
        ]);
    }

    public function show($id)
    {
        $dv = $this->findDv($id);
        abort_if(! $dv, 404);

        $editable = in_array((string) ($dv->dv_status ?? ''), ['Draft', 'Revision'], true);

        return redirect()->route('accounting.dv.index', [
            $editable ? 'edit_dv' : 'view_dv' => (int) $id,
            'status' => $editable ? 'incoming' : 'all',
        ]);
    }

    public function createFromPackage(Request $request)
    {
        $validated = $request->validate([
            'request_check_id' => ['required', 'integer'],
        ]);

        $rfcId = (int) $validated['request_check_id'];
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        abort_if(! $rfc, 404);

        if (! AccountingHandoff::rfcIsSentToAccounting($rfc)) {
            return back()->with('error', 'Only requests submitted by the Purchaser can become a Disbursement Voucher.');
        }

        $existing = AccountingHandoff::dvForRfc($rfcId);
        if ($existing) {
            return redirect()
                ->route('accounting.dv.index', [
                    'edit_dv' => $existing->dv_id,
                    'status' => 'incoming',
                ])
                ->with('success', 'A Disbursement Voucher already exists for this request.');
        }

        $atpIds = RfcAtpLinks::atpIdsFor($rfcId, isset($rfc->request_check_authority_purchase_id)
            ? (int) $rfc->request_check_authority_purchase_id
            : null);
        $atpId = $atpIds[0] ?? null;
        $risId = null;
        if ($atpId) {
            $risId = DB::table('authority_to_purchase_table')
                ->where('authority_purchase_id', $atpId)
                ->value('authority_purchase_ris_id');
        }

        $amount = $rfc->request_check_amount_figures ?? null;
        $payload = [
            'dv_request_check_id' => $rfcId,
            'dv_ris_id' => $risId ? (int) $risId : null,
            'dv_authority_purchase_id' => $atpId,
            'dv_status' => 'Draft',
            'dv_payee' => $rfc->request_check_payee ?? null,
            'dv_amount' => $amount,
            'dv_remarks' => null,
            'dv_created_by' => Auth::id(),
            'dv_is_archived' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_date')) {
            $payload['dv_date'] = now()->toDateString();
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_particulars')) {
            $payload['dv_particulars'] = $rfc->request_check_particulars_purpose ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_prepared_by_name')) {
            $payload['dv_prepared_by_name'] = null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_accountant_signed_name')) {
            $name = preg_replace('/[^A-Za-z0-9 ]/', '', trim((string) (Auth::user()->user_full_name ?? ''))) ?? '';
            $payload['dv_accountant_signed_name'] = $name !== '' ? substr($name, 0, 26) : null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_account_lines')) {
            $payload['dv_account_lines'] = json_encode([
                ['account' => '', 'debit' => $amount, 'credit' => null],
                ['account' => 'Cash In Bank', 'debit' => null, 'credit' => $amount],
            ]);
        }

        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_request_no')) {
            $requestNo = ProcurementRequestNumber::resolveFromRfc($rfcId)
                ?: ProcurementRequestNumber::resolveFromRis($risId ? (int) $risId : null);
            if ($requestNo) {
                $payload['dv_request_no'] = $requestNo;
            }
        }

        $dvId = DB::table('disbursement_vouchers_table')->insertGetId($payload);

        return redirect()
            ->route('accounting.dv.index', [
                'edit_dv' => $dvId,
                'status' => 'incoming',
            ])
            ->with('success', 'Disbursement Voucher draft created. Complete the form in the modal and submit to the School Administrator.');
    }

    public function update(Request $request, $id)
    {
        $dv = $this->findDv($id);
        abort_if(! $dv, 404);

        if (! in_array((string) $dv->dv_status, ['Draft', 'Revision'], true)) {
            return back()->with('error', 'Only draft or revision Disbursement Vouchers can be edited.');
        }

        $request->merge([
            'dv_amount' => $this->normalizeMoneyInput($request->input('dv_amount')),
        ]);
        $accountLines = $request->input('account_lines');
        if (is_array($accountLines)) {
            foreach ($accountLines as $i => $line) {
                if (! is_array($line)) {
                    continue;
                }
                $accountLines[$i]['debit'] = $this->normalizeMoneyInput($line['debit'] ?? null);
                $accountLines[$i]['credit'] = $this->normalizeMoneyInput($line['credit'] ?? null);
            }
            $request->merge(['account_lines' => $accountLines]);
        }

        try {
            $validated = $request->validate([
                'dv_payee' => ['nullable', 'string', 'max:255'],
                'dv_date' => ['nullable', 'date'],
                'dv_particulars' => [
                    'nullable',
                    'string',
                    'max:1200',
                    function (string $attribute, mixed $value, \Closure $fail) {
                        $text = (string) $value;
                        if (substr_count($text, "\n") > 4) {
                            $fail('Particulars may only have up to 5 lines.');
                        }
                    },
                ],
                'dv_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d{1,10}(\.\d{1,2})?$/'],
                'dv_prepared_by_name' => ['nullable', 'string', 'max:26', 'regex:/^[A-Za-z0-9 ]{0,26}$/'],
                'dv_checked_by_name' => ['nullable', 'string', 'max:26', 'regex:/^[A-Za-z0-9 ]{0,26}$/'],
                'dv_cdv_number' => ['nullable', 'string', 'max:26', 'regex:/^[A-Za-z0-9 ]{0,26}$/'],
                'dv_check_number' => ['nullable', 'string', 'max:16', 'regex:/^[0-9]{0,16}$/'],
                'dv_received_by_name' => ['nullable', 'string', 'max:26', 'regex:/^[A-Za-z0-9 ]{0,26}$/'],
                'dv_received_date' => ['nullable', 'date'],
                'dv_accountant_signed_name' => ['nullable', 'string', 'max:26', 'regex:/^[A-Za-z0-9 ]{0,26}$/'],
                'dv_accountant_signature' => ['nullable', 'string'],
                'account_lines' => ['nullable', 'array'],
                'account_lines.*.account' => ['nullable', 'string', 'max:255'],
                'account_lines.*.debit' => ['nullable', 'numeric', 'min:0'],
                'account_lines.*.credit' => ['nullable', 'numeric', 'min:0'],
                'save_action' => ['nullable', 'in:draft,submit'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->route('accounting.dv.index', ['edit_dv' => $id, 'status' => 'incoming'])
                ->withInput(array_merge($request->all(), ['_dv_id' => $id]))
                ->withErrors($e->errors());
        }

        $isSubmit = ($validated['save_action'] ?? 'draft') === 'submit';

        $accountLines = collect($validated['account_lines'] ?? [])
            ->map(function ($line) {
                $account = trim((string) ($line['account'] ?? ''));
                $debit = isset($line['debit']) && $line['debit'] !== '' ? (float) $line['debit'] : null;
                $credit = isset($line['credit']) && $line['credit'] !== '' ? (float) $line['credit'] : null;

                return [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            })
            ->filter(fn ($line) => $line['account'] !== '' || $line['debit'] !== null || $line['credit'] !== null)
            ->values()
            ->all();

        $payload = [
            'dv_payee' => $validated['dv_payee'] ?? null,
            'dv_amount' => $validated['dv_amount'] ?? null,
            'dv_remarks' => $validated['dv_particulars'] ?? null,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_date')) {
            $payload['dv_date'] = $validated['dv_date'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_particulars')) {
            $payload['dv_particulars'] = $validated['dv_particulars'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_account_lines')) {
            $payload['dv_account_lines'] = json_encode($accountLines);
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_prepared_by_name')) {
            $payload['dv_prepared_by_name'] = $validated['dv_prepared_by_name'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_checked_by_name')) {
            $payload['dv_checked_by_name'] = $validated['dv_checked_by_name'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_cdv_number')) {
            $payload['dv_cdv_number'] = $validated['dv_cdv_number'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_check_number')) {
            $payload['dv_check_number'] = $validated['dv_check_number'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_received_by_name')) {
            $payload['dv_received_by_name'] = $validated['dv_received_by_name'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_received_date')) {
            $payload['dv_received_date'] = $validated['dv_received_date'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_accountant_signed_name')) {
            $payload['dv_accountant_signed_name'] = $validated['dv_accountant_signed_name'] ?? null;
        }
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_accountant_signature')) {
            $sig = $validated['dv_accountant_signature'] ?? null;
            $payload['dv_accountant_signature'] = \App\Support\RisWorkflow::isDrawnSignature((string) $sig) ? $sig : null;
        }

        if ($isSubmit) {
            if (blank($payload['dv_payee']) || $payload['dv_amount'] === null || blank($payload['dv_particulars'] ?? null)) {
                return redirect()
                    ->route('accounting.dv.index', ['edit_dv' => $id, 'status' => 'incoming'])
                    ->withInput(array_merge($request->all(), ['_dv_id' => $id]))
                    ->with('error', 'Payee, date, particulars, and amount are required before submitting.');
            }
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_date') && blank($payload['dv_date'] ?? null)) {
                return redirect()
                    ->route('accounting.dv.index', ['edit_dv' => $id, 'status' => 'incoming'])
                    ->withInput(array_merge($request->all(), ['_dv_id' => $id]))
                    ->with('error', 'Date is required before submitting.');
            }

            try {
                $reviewerId = ReviewerAssignment::resolve($request, WorkflowNotifier::ROLE_SCHOOL_ADMIN);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return redirect()
                    ->route('accounting.dv.index', ['edit_dv' => $id, 'status' => 'incoming'])
                    ->withInput(array_merge($request->all(), ['_dv_id' => $id]))
                    ->withErrors($e->errors());
            }

            $formNumber = DvFormNumber::allocateOnSubmit($dv->dv_form_number ?? null);

            $payload = array_merge($payload, [
                'dv_form_number' => $formNumber,
                'dv_status' => 'Pending',
                'dv_submitted_by' => Auth::id(),
                'dv_submitted_at' => now(),
                'dv_assigned_reviewer_id' => $reviewerId,
                'dv_revision_reason' => null,
            ]);

            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_cdv_number')
                && blank($payload['dv_cdv_number'] ?? null)) {
                $payload['dv_cdv_number'] = $formNumber;
            }

            DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update($payload);

            DocumentWorkflowService::notifySubmitted(
                WorkflowNotifier::ROLE_SCHOOL_ADMIN,
                'DV submitted for review',
                $formNumber.' is waiting for School Administrator review.',
                'dv_submitted',
                'DV',
                (int) $id,
                ProcurementReviewPortal::disbursementVoucher((int) $id),
                $reviewerId
            );

            return redirect()
                ->route('accounting.dv.index', ['status' => 'submitted'])
                ->with('success', $formNumber.' submitted to the School Administrator.');
        }

        DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update($payload);

        return redirect()
            ->route('accounting.dv.index', ['edit_dv' => $id, 'status' => 'incoming'])
            ->with('success', 'Disbursement Voucher draft saved.');
    }

    public function submit(Request $request, $id)
    {
        $request->merge(['save_action' => 'submit']);

        return $this->update($request, $id);
    }

    /** School Admin queue */
    public function schoolAdminIndex(Request $request)
    {
        abort_unless(Schema::hasTable('disbursement_vouchers_table'), 404);

        $filter = trim((string) $request->query('status', 'incoming'));

        $query = DB::table('disbursement_vouchers_table')
            ->leftJoin('request_check_table', 'disbursement_vouchers_table.dv_request_check_id', '=', 'request_check_table.request_check_id')
            ->where(function ($q) {
                $q->whereNull('disbursement_vouchers_table.dv_is_archived')
                    ->orWhere('disbursement_vouchers_table.dv_is_archived', 0);
            })
            ->select(
                'disbursement_vouchers_table.*',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_request_no'
            );

        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($query, 'disbursement_vouchers_table.dv_assigned_reviewer_id');
        }

        if ($filter === 'incoming') {
            $query->where('dv_status', 'Pending');
        } elseif ($filter === 'approved') {
            $query->where('dv_status', 'Approved');
        } elseif ($filter === 'revision') {
            $query->where('dv_status', 'Revision');
        }
        // status=all → no status filter

        $records = $query->orderByDesc('dv_submitted_at')->orderByDesc('dv_id')->paginate(20)->withQueryString();

        $base = DB::table('disbursement_vouchers_table')
            ->where(function ($q) {
                $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
            });
        if (Schema::hasColumn('disbursement_vouchers_table', 'dv_assigned_reviewer_id')) {
            ReviewerAssignment::applyQueueFilter($base, 'dv_assigned_reviewer_id');
        }

        $counts = [
            'incoming' => (clone $base)->where('dv_status', 'Pending')->count(),
            'revision' => (clone $base)->where('dv_status', 'Revision')->count(),
            'approved' => (clone $base)->where('dv_status', 'Approved')->count(),
        ];

        return view('accounting.disbursement-vouchers.school-admin-index', [
            'records' => $records,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    public function schoolAdminShow($id)
    {
        $dv = $this->findDv($id);
        abort_if(! $dv, 404);

        $package = $this->packageContext((int) $dv->dv_request_check_id);
        $reviewable = ($dv->dv_status ?? '') === 'Pending' && ! blank($dv->dv_submitted_at);

        return view('accounting.disbursement-vouchers.school-admin-show', [
            'dv' => $dv,
            'package' => $package,
            'reviewable' => $reviewable,
            'chain' => $this->relatedDocsChainFromPackage($package, $dv),
        ]);
    }

    public function approve(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $dv = DB::table('disbursement_vouchers_table')->where('dv_id', $id)->lockForUpdate()->first();
            abort_if(! $dv, 404);

            ReviewerAssignment::assertCanAct(
                isset($dv->dv_assigned_reviewer_id) ? (int) $dv->dv_assigned_reviewer_id : null,
                'DV'
            );

            if (($dv->dv_status ?? '') !== 'Pending' || blank($dv->dv_submitted_at)) {
                return back()->with('error', 'Only submitted Disbursement Vouchers can be approved.');
            }

            $approvePayload = [
                'dv_status' => 'Approved',
                'dv_approved_by' => Auth::id(),
                'dv_approved_at' => now(),
                'dv_revision_reason' => null,
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_admin_signed_name')) {
                $approvePayload['dv_admin_signed_name'] = Auth::user()->user_full_name ?? 'School Administrator';
            }
            DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update($approvePayload);

            $label = $dv->dv_form_number ?: ((($dv->dv_status ?? '') === 'Draft') ? 'Draft DV' : 'Disbursement Voucher');
            $creatorId = $dv->dv_submitted_by ?: $dv->dv_created_by;

            WorkflowNotifier::toUser(
                $creatorId,
                WorkflowNotifier::ROLE_ACCOUNTING,
                'DV approved by School Administrator',
                $label.' was approved. Submit it to the President for final signature, then release the check.',
                'dv_approved',
                'DV',
                (int) $id,
                '/accounting/disbursement-vouchers?status=approved&view_dv='.$id,
                'school-admin'
            );

            return redirect(ProcurementReviewPortal::disbursementVoucher((int) $id))
                ->with('success', 'Disbursement Voucher approved. Accounting can now submit it to the President.');
        });
    }

    /** Accounting → President */
    public function submitToPresident(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $dv = DB::table('disbursement_vouchers_table')->where('dv_id', $id)->lockForUpdate()->first();
            abort_if(! $dv, 404);

            if (($dv->dv_status ?? '') !== 'Approved') {
                return back()->with('error', 'Only School Administrator–approved Disbursement Vouchers can be sent to the President.');
            }

            DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update([
                'dv_status' => 'With President',
                'dv_president_submitted_at' => now(),
                'dv_president_submitted_by' => Auth::id(),
                'dv_president_revision_reason' => null,
                'updated_at' => now(),
            ]);

            $label = $dv->dv_form_number ?: ((($dv->dv_status ?? '') === 'Draft') ? 'Draft DV' : 'Disbursement Voucher');
            $requestNo = $dv->dv_request_no ?? null;
            $msg = $label.' is waiting for President signature.'
                .($requestNo ? ' Request No. '.$requestNo.'.' : '');

            WorkflowNotifier::toRole(
                WorkflowNotifier::ROLE_PRESIDENT,
                'DV awaiting President approval',
                $msg,
                'dv_president_pending',
                'DV',
                (int) $id,
                '/president/disbursement-vouchers/'.$id,
                'accounting'
            );

            return redirect()
                ->route('accounting.dv.index', ['status' => 'with_president', 'view_dv' => $id])
                ->with('success', $label.' submitted to the President.');
        });
    }

    /** Accounting releases check after President approval */
    public function releaseCheck(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $dv = DB::table('disbursement_vouchers_table')->where('dv_id', $id)->lockForUpdate()->first();
            abort_if(! $dv, 404);

            if (($dv->dv_status ?? '') !== 'President Approved') {
                return back()->with('error', 'Only President-approved Disbursement Vouchers can release a check.');
            }

            DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update([
                'dv_status' => 'Check Released',
                'dv_check_released_at' => now(),
                'dv_check_released_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            $rfcId = (int) ($dv->dv_request_check_id ?? 0);
            if ($rfcId > 0 && Schema::hasTable('request_check_table')) {
                $rfcPayload = [];
                if (Schema::hasColumn('request_check_table', 'request_check_funds_released_at')) {
                    $rfcPayload['request_check_funds_released_at'] = now();
                }
                if (Schema::hasColumn('request_check_table', 'request_check_funds_released_by')) {
                    $rfcPayload['request_check_funds_released_by'] = Auth::id();
                }
                if ($rfcPayload !== []) {
                    DB::table('request_check_table')->where('request_check_id', $rfcId)->update($rfcPayload);
                }
            }

            $label = $dv->dv_form_number ?: ((($dv->dv_status ?? '') === 'Draft') ? 'Draft DV' : 'Disbursement Voucher');
            $requestNo = $dv->dv_request_no ?? null;
            $purchaserId = null;
            $rfc = null;
            if ($rfcId > 0) {
                $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
                $purchaserId = $rfc->request_check_created_by
                    ?? $rfc->request_check_submitted_by
                    ?? null;
            }

            $isCashAdvance = ($rfc->request_check_funding_type ?? null) === 'cash_advance';
            $message = $label.' check is ready for pickup at the office.'
                .($requestNo ? ' Request No. '.$requestNo.'.' : '')
                .' Collect the check, then proceed to purchase and file the Receiving Report'
                .($isCashAdvance ? ' and Liquidation Report.' : '.');

            if ($purchaserId) {
                WorkflowNotifier::toUser(
                    $purchaserId,
                    WorkflowNotifier::ROLE_PURCHASER,
                    'Check released — ready for pickup',
                    $message,
                    'dv_check_released',
                    'DV',
                    (int) $id,
                    '/purchaser/request-check?view_rfc='.$rfcId,
                    'accounting'
                );
            } else {
                WorkflowNotifier::toRole(
                    WorkflowNotifier::ROLE_PURCHASER,
                    'Check released — ready for pickup',
                    $message,
                    'dv_check_released',
                    'DV',
                    (int) $id,
                    '/purchaser/request-check',
                    'accounting'
                );
            }

            return redirect()
                ->route('accounting.dv.index', ['status' => 'released', 'view_dv' => $id])
                ->with('success', $label.' check marked released. Purchaser has been notified.');
        });
    }

    /** President queue */
    public function presidentIndex(Request $request)
    {
        abort_unless(Schema::hasTable('disbursement_vouchers_table'), 404);

        $filter = trim((string) $request->query('status', 'incoming'));

        $query = DB::table('disbursement_vouchers_table')
            ->leftJoin('request_check_table', 'disbursement_vouchers_table.dv_request_check_id', '=', 'request_check_table.request_check_id')
            ->where(function ($q) {
                $q->whereNull('disbursement_vouchers_table.dv_is_archived')
                    ->orWhere('disbursement_vouchers_table.dv_is_archived', 0);
            })
            ->select(
                'disbursement_vouchers_table.*',
                'request_check_table.request_check_form_number',
                'request_check_table.request_check_payee',
                'request_check_table.request_check_request_no as rfc_request_no'
            )
            ->orderByDesc('disbursement_vouchers_table.dv_president_submitted_at')
            ->orderByDesc('disbursement_vouchers_table.dv_id');

        if ($filter === 'incoming') {
            $query->where('disbursement_vouchers_table.dv_status', 'With President');
        } elseif ($filter === 'approved') {
            $query->whereIn('disbursement_vouchers_table.dv_status', ['President Approved', 'Check Released']);
        } elseif ($filter === 'revision') {
            $query->where('disbursement_vouchers_table.dv_status', 'Approved')
                ->whereNotNull('disbursement_vouchers_table.dv_president_revision_reason');
        }

        $rows = $query->paginate(20)->withQueryString();

        $base = DB::table('disbursement_vouchers_table')
            ->where(function ($q) {
                $q->whereNull('dv_is_archived')->orWhere('dv_is_archived', 0);
            });

        $counts = [
            'incoming' => (clone $base)->where('dv_status', 'With President')->count(),
            'approved' => (clone $base)->whereIn('dv_status', ['President Approved', 'Check Released'])->count(),
            'revision' => (clone $base)->where('dv_status', 'Approved')->whereNotNull('dv_president_revision_reason')->count(),
        ];

        return view('president.disbursement-vouchers.index', [
            'rows' => $rows,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    public function presidentShow($id)
    {
        $dv = $this->findDv($id);
        abort_if(! $dv, 404);
        abort_unless(in_array((string) ($dv->dv_status ?? ''), [
            'With President', 'President Approved', 'Check Released', 'Approved',
        ], true), 404);

        $package = $this->packageContext((int) $dv->dv_request_check_id);
        $reviewable = ($dv->dv_status ?? '') === 'With President';

        return view('president.disbursement-vouchers.show', [
            'dv' => $dv,
            'package' => $package,
            'reviewable' => $reviewable,
        ]);
    }

    public function presidentApprove(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            $dv = DB::table('disbursement_vouchers_table')->where('dv_id', $id)->lockForUpdate()->first();
            abort_if(! $dv, 404);

            if (($dv->dv_status ?? '') !== 'With President') {
                return back()->with('error', 'Only Disbursement Vouchers with the President can be approved here.');
            }

            $payload = [
                'dv_status' => 'President Approved',
                'dv_president_approved_at' => now(),
                'dv_president_approved_by' => Auth::id(),
                'dv_president_revision_reason' => null,
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('disbursement_vouchers_table', 'dv_president_signed_name')) {
                $payload['dv_president_signed_name'] = Auth::user()->user_full_name ?? 'President';
            }

            DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update($payload);

            $label = $dv->dv_form_number ?: ((($dv->dv_status ?? '') === 'Draft') ? 'Draft DV' : 'Disbursement Voucher');
            $creatorId = $dv->dv_president_submitted_by ?: $dv->dv_submitted_by ?: $dv->dv_created_by;

            WorkflowNotifier::toUser(
                $creatorId,
                WorkflowNotifier::ROLE_ACCOUNTING,
                'DV approved by President',
                $label.' was signed by the President. You may now release the check to the Purchaser.',
                'dv_president_approved',
                'DV',
                (int) $id,
                '/accounting/disbursement-vouchers?status=ready_release&view_dv='.$id,
                'president'
            );

            return redirect()
                ->route('president.dv.show', $id)
                ->with('success', 'Disbursement Voucher approved. Accounting has been notified to release the check.');
        });
    }

    public function presidentRevise(Request $request, $id)
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        $dv = DB::table('disbursement_vouchers_table')->where('dv_id', $id)->first();
        abort_if(! $dv, 404);

        if (($dv->dv_status ?? '') !== 'With President') {
            return back()->with('error', 'Only Disbursement Vouchers with the President can be sent back.');
        }

        DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update([
            'dv_status' => 'Approved',
            'dv_president_submitted_at' => null,
            'dv_president_revision_reason' => $validated['remarks'],
            'updated_at' => now(),
        ]);

        $label = $dv->dv_form_number ?: 'Disbursement Voucher';
        $creatorId = $dv->dv_president_submitted_by ?: $dv->dv_submitted_by ?: $dv->dv_created_by;

        WorkflowNotifier::toUser(
            $creatorId,
            WorkflowNotifier::ROLE_ACCOUNTING,
            'DV returned by President',
            $label.': '.$validated['remarks'],
            'dv_president_revision',
            'DV',
            (int) $id,
            '/accounting/disbursement-vouchers?status=approved&view_dv='.$id,
            'president'
        );

        return redirect()
            ->route('president.dv.index')
            ->with('success', 'Disbursement Voucher returned to Accounting.');
    }

    public function revise(Request $request, $id)
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        $dv = DB::table('disbursement_vouchers_table')->where('dv_id', $id)->first();
        abort_if(! $dv, 404);

        ReviewerAssignment::assertCanAct(
            isset($dv->dv_assigned_reviewer_id) ? (int) $dv->dv_assigned_reviewer_id : null,
            'DV'
        );

        if (($dv->dv_status ?? '') !== 'Pending' || blank($dv->dv_submitted_at)) {
            return back()->with('error', 'Only submitted Disbursement Vouchers can be sent back.');
        }

        DB::table('disbursement_vouchers_table')->where('dv_id', $id)->update([
            'dv_status' => 'Revision',
            'dv_submitted_at' => null,
            'dv_revision_reason' => $validated['remarks'],
            'updated_at' => now(),
        ]);

        $label = $dv->dv_form_number ?: 'Disbursement Voucher';
        $creatorId = $dv->dv_submitted_by ?: $dv->dv_created_by;

        WorkflowNotifier::toUser(
            $creatorId,
            WorkflowNotifier::ROLE_ACCOUNTING,
            'DV revision required',
            $label.': '.$validated['remarks'],
            'dv_revision',
            'DV',
            (int) $id,
            '/accounting/disbursement-vouchers/'.$id,
            'school-admin'
        );

        return redirect(ProcurementReviewPortal::disbursementVoucher((int) $id))
            ->with('success', 'Disbursement Voucher returned to Accounting for revision.');
    }

    private function findDv($id): ?object
    {
        if (! Schema::hasTable('disbursement_vouchers_table')) {
            return null;
        }

        return DB::table('disbursement_vouchers_table')->where('dv_id', $id)->first();
    }

    /**
     * SA-facing related-docs chain for DV show (RIS → ATP → RFC → Funds → DV).
     *
     * @param  array{rfc:?object,atps:\Illuminate\Support\Collection,risList:\Illuminate\Support\Collection}  $package
     */
    private function relatedDocsChainFromPackage(array $package, object $dv): array
    {
        $chain = [
            'ris' => null,
            'atp' => null,
            'rfc' => null,
            'funds' => null,
            'dv' => null,
            'rr' => null,
            'liq' => null,
        ];

        $ris = collect($package['risList'] ?? [])->first();
        if ($ris) {
            $risId = (int) $ris->ris_id;
            $chain['ris'] = [
                'label' => RisWorkflow::formNumber($ris, $risId),
                'url' => ProcurementReviewPortal::ris($risId),
                'status' => $ris->ris_status ?? null,
            ];
        }

        $atp = collect($package['atps'] ?? [])->first();
        if ($atp) {
            $atpId = (int) $atp->authority_purchase_id;
            $chain['atp'] = [
                'label' => $atp->authority_purchase_form_number ?: ('ATP #'.$atpId),
                'url' => ProcurementReviewPortal::atp($atpId),
                'status' => $atp->authority_purchase_status ?? null,
            ];
        }

        $rfc = $package['rfc'] ?? null;
        if ($rfc) {
            $rfcId = (int) $rfc->request_check_id;
            $chain['rfc'] = [
                'label' => $rfc->request_check_form_number ?: ('RFC #'.$rfcId),
                'url' => ProcurementReviewPortal::requestCheck($rfcId),
                'status' => $rfc->request_check_status ?? null,
            ];
            $chain['funds'] = [
                'label' => ! empty($rfc->request_check_funds_released_at) ? 'Released' : 'Not released',
                'url' => ProcurementReviewPortal::requestCheck($rfcId).'?view=funds',
                'status' => ! empty($rfc->request_check_funds_released_at) ? 'Released' : 'Pending',
            ];
        }

        $dvId = (int) ($dv->dv_id ?? 0);
        $chain['dv'] = [
            'label' => $dv->dv_form_number ?: ('DV #'.$dvId),
            'url' => ProcurementReviewPortal::disbursementVoucher($dvId),
            'status' => $dv->dv_status ?? null,
        ];

        return $chain;
    }

    private function packageContext(int $rfcId): array
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        $atpIds = RfcAtpLinks::atpIdsFor(
            $rfcId,
            isset($rfc->request_check_authority_purchase_id) ? (int) $rfc->request_check_authority_purchase_id : null
        );

        $atps = $atpIds === []
            ? collect()
            : DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_id', $atpIds)
                ->get();

        $risIds = $atps->pluck('authority_purchase_ris_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();
        $risList = $risIds === []
            ? collect()
            : DB::table('requisition_issue_slip_table')->whereIn('ris_id', $risIds)->get();

        return [
            'rfc' => $rfc,
            'atps' => $atps,
            'risList' => $risList,
        ];
    }

    private function attachPackageRefs($rows): void
    {
        $rfcIds = $rows->pluck('request_check_id')->map(fn ($id) => (int) $id)->all();
        $primary = $rows->mapWithKeys(fn ($r) => [
            (int) $r->request_check_id => isset($r->request_check_authority_purchase_id)
                ? (int) $r->request_check_authority_purchase_id
                : null,
        ])->all();

        $atpMap = RfcAtpLinks::atpIdsForMany($primary);
        $allAtpIds = collect($atpMap)->flatten()->unique()->filter()->values()->all();
        $atps = $allAtpIds === []
            ? collect()
            : DB::table('authority_to_purchase_table')
                ->whereIn('authority_purchase_id', $allAtpIds)
                ->get()
                ->keyBy('authority_purchase_id');

        foreach ($rows as $row) {
            $rfcId = (int) $row->request_check_id;
            $ids = $atpMap[$rfcId] ?? [];
            $row->atp_ids = $ids;
            $row->atp_labels = collect($ids)
                ->map(fn ($id) => optional($atps->get($id))->authority_purchase_form_number ?: 'ATP')
                ->implode(', ');
            $firstAtp = $ids[0] ?? null;
            $row->ris_id = $firstAtp ? optional($atps->get($firstAtp))->authority_purchase_ris_id : null;
            $risIds = collect($ids)
                ->map(fn ($id) => optional($atps->get($id))->authority_purchase_ris_id)
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
            $row->ris_ids = $risIds;

            // Surface / backfill Request No. for older packages created before the ticket existed.
            $requestNo = $row->linked_dv_request_no
                ?? ($row->request_check_request_no ?? null)
                ?? ProcurementRequestNumber::resolveFromRfc($rfcId);
            if ($requestNo && blank($row->request_check_request_no ?? null)
                && Schema::hasColumn('request_check_table', 'request_check_request_no')) {
                DB::table('request_check_table')
                    ->where('request_check_id', $rfcId)
                    ->where(function ($q) {
                        $q->whereNull('request_check_request_no')->orWhere('request_check_request_no', '');
                    })
                    ->update(['request_check_request_no' => $requestNo]);
            }
            if ($requestNo && ! empty($row->dv_id)
                && blank($row->linked_dv_request_no ?? null)
                && Schema::hasColumn('disbursement_vouchers_table', 'dv_request_no')) {
                DB::table('disbursement_vouchers_table')
                    ->where('dv_id', (int) $row->dv_id)
                    ->where(function ($q) {
                        $q->whereNull('dv_request_no')->orWhere('dv_request_no', '');
                    })
                    ->update(['dv_request_no' => $requestNo]);
                $row->linked_dv_request_no = $requestNo;
            }
            $row->request_check_request_no = $requestNo ?: ($row->request_check_request_no ?? null);
        }
    }

    public function viewPackageRfc($rfcId)
    {
        $rfc = $this->assertAccountingPackageRfc((int) $rfcId);

        return view('accounting.disbursement-vouchers.package-reference', [
            'docType' => 'rfc',
            'title' => $rfc->request_check_form_number ?: 'Request for Check',
            'subtitle' => 'Request for Check — request reference for DV',
            'rfc' => $rfc,
            'rfcId' => (int) $rfc->request_check_id,
            'package' => $this->packageContext((int) $rfc->request_check_id),
            'attachments' => Schema::hasTable('request_check_attachments_table')
                ? DB::table('request_check_attachments_table')
                    ->where('request_check_id', $rfc->request_check_id)
                    ->orderBy('request_check_attachment_id')
                    ->get()
                : collect(),
        ]);
    }

    public function viewPackageAtp($rfcId, $atpId)
    {
        $rfc = $this->assertAccountingPackageRfc((int) $rfcId);
        $package = $this->packageContext((int) $rfc->request_check_id);
        abort_unless(
            collect($package['atps'])->contains(fn ($row) => (int) $row->authority_purchase_id === (int) $atpId),
            404
        );

        $atpQuery = DB::table('authority_to_purchase_table')
            ->where('authority_to_purchase_table.authority_purchase_id', $atpId)
            ->select('authority_to_purchase_table.*');

        if (Schema::hasTable('suppliers_table')) {
            $atpQuery
                ->leftJoin('suppliers_table', 'authority_to_purchase_table.authority_purchase_supplier_id', '=', 'suppliers_table.supplier_id')
                ->addSelect('suppliers_table.supplier_store_type');
            if (Schema::hasTable('physical_suppliers_table')) {
                $atpQuery
                    ->leftJoin('physical_suppliers_table', 'suppliers_table.supplier_id', '=', 'physical_suppliers_table.supplier_id')
                    ->addSelect('physical_suppliers_table.company_name');
            }
            if (Schema::hasTable('online_suppliers_table')) {
                $atpQuery
                    ->leftJoin('online_suppliers_table', 'suppliers_table.supplier_id', '=', 'online_suppliers_table.supplier_id')
                    ->addSelect('online_suppliers_table.shop_name');
            }
        }

        $atp = $atpQuery->first();
        abort_if(! $atp, 404);

        $items = Schema::hasTable('authority_to_purchase_items_table')
            ? DB::table('authority_to_purchase_items_table')
                ->where('authority_purchase_id', $atpId)
                ->orderBy('atp_item_id')
                ->get()
            : collect();

        return view('accounting.disbursement-vouchers.package-reference', [
            'docType' => 'atp',
            'title' => $atp->authority_purchase_form_number ?: 'Authority to Purchase',
            'subtitle' => 'Authority to Purchase — request reference for DV',
            'rfc' => $rfc,
            'rfcId' => (int) $rfc->request_check_id,
            'package' => $package,
            'atp' => $atp,
            'items' => $items,
        ]);
    }

    public function viewPackageRis($rfcId, $risId)
    {
        $rfc = $this->assertAccountingPackageRfc((int) $rfcId);
        $package = $this->packageContext((int) $rfc->request_check_id);
        $ris = collect($package['risList'])->first(fn ($row) => (int) $row->ris_id === (int) $risId);
        abort_if(! $ris, 404);

        $risItems = Schema::hasTable('requisition_issue_slip_items_table')
            ? DB::table('requisition_issue_slip_items_table')
                ->where('ris_id', $risId)
                ->orderBy('ris_item_id')
                ->get()
            : collect();

        $attachments = Schema::hasTable('ris_attachments_table')
            ? DB::table('ris_attachments_table')
                ->where('ris_id', $risId)
                ->orderBy('ris_attachment_original_name')
                ->get()
            : collect();

        return view('accounting.disbursement-vouchers.package-reference', [
            'docType' => 'ris',
            'title' => RisWorkflow::formNumber($ris),
            'subtitle' => 'Canvas + RIS — request reference for DV',
            'rfc' => $rfc,
            'rfcId' => (int) $rfc->request_check_id,
            'package' => $package,
            'ris' => $ris,
            'risItems' => $risItems,
            'attachments' => $attachments,
        ]);
    }

    public function downloadPackageRisAttachment($rfcId, $risId, $attachmentId): BinaryFileResponse
    {
        $this->assertAccountingPackageRfc((int) $rfcId);
        $package = $this->packageContext((int) $rfcId);
        abort_unless(
            collect($package['risList'])->contains(fn ($row) => (int) $row->ris_id === (int) $risId),
            404
        );
        abort_unless(Schema::hasTable('ris_attachments_table'), 404);

        $file = DB::table('ris_attachments_table')
            ->where('ris_id', $risId)
            ->where('ris_attachment_id', $attachmentId)
            ->first();
        abort_if(! $file, 404);

        $path = storage_path('app/public/'.$file->ris_attachment_path);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="'.$file->ris_attachment_original_name.'"',
        ]);
    }

    private function assertAccountingPackageRfc(int $rfcId): object
    {
        $rfc = DB::table('request_check_table')->where('request_check_id', $rfcId)->first();
        abort_if(! $rfc, 404);
        abort_unless(AccountingHandoff::rfcIsSentToAccounting($rfc), 404);

        return $rfc;
    }

    private function normalizeMoneyInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' || $normalized === '-' || $normalized === '.' ? null : $normalized;
    }
}
