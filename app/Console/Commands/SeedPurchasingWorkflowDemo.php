<?php

namespace App\Console\Commands;

use App\Support\RisWorkflow;
use App\Support\WorkflowNotifier;
use Database\Seeders\PurchasingWorkflowDemoBootstrapSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedPurchasingWorkflowDemo extends Command
{
    protected $signature = 'demo:seed-purchasing {--count=}';

    protected $description = 'Seed additive demo purchasing workflow chains with mixed statuses';

    public function handle(): int
    {
        $profiles = $this->allProfiles();
        $count = $this->option('count') === null
            ? count($profiles)
            : max(1, (int) $this->option('count'));
        $profiles = array_slice($profiles, 0, $count);

        if ($this->option('count') !== null && (int) $this->option('count') !== count($this->allProfiles())) {
            $this->warn('Seeding '.$count.' chain(s). Full mix is '.count($this->allProfiles()).' statuses.');
        }

        $bootstrap = app(PurchasingWorkflowDemoBootstrapSeeder::class);
        $bootstrap->run();

        $actors = $bootstrap->ensureDemoActors();
        $suppliers = array_values($bootstrap->ensureDemoSuppliers());
        $baseIndex = $this->nextDemoSequence();
        $created = [];

        foreach ($profiles as $offset => $profile) {
            $sequence = $baseIndex + $offset;
            $created[] = DB::transaction(function () use ($actors, $suppliers, $profile, $sequence) {
                return $this->createChain($actors, $suppliers, $profile, $sequence);
            });
        }

        $this->printSummary($created);

        return self::SUCCESS;
    }

    private function allProfiles(): array
    {
        return [
            ['stage' => 'replacement_pending', 'status' => 'Pending', 'history' => []],
            ['stage' => 'replacement_ris_ready', 'status' => 'Approved', 'history' => []],
            ['stage' => 'ris_admin_pending', 'status' => 'Submitted', 'history' => []],
            ['stage' => 'ris_purchaser_revision', 'status' => 'Minor Revision', 'history' => [['level' => 'Admin', 'status' => 'Minor Revision', 'remarks' => 'Please correct the purpose and quantities.']]],
            ['stage' => 'president_queue', 'status' => RisWorkflow::FORWARDED, 'history' => [['level' => 'Admin', 'status' => 'Forwarded to President', 'remarks' => 'Forwarded for presidential review.']]],
            ['stage' => 'president_rejected', 'status' => RisWorkflow::PRESIDENT_REJECTED, 'history' => [['level' => 'Admin', 'status' => 'Forwarded to President', 'remarks' => 'Forwarded for presidential review.'], ['level' => 'President', 'status' => 'Rejected', 'remarks' => 'Budget clarification requested.']]],
            ['stage' => 'admin_cosign_queue', 'status' => RisWorkflow::PRESIDENT_APPROVED, 'history' => [['level' => 'Admin', 'status' => 'Forwarded to President', 'remarks' => 'Forwarded for approval.'], ['level' => 'President', 'status' => 'Approved', 'remarks' => 'Approved by Demo President.']]],
            ['stage' => 'atp_ready_direct', 'status' => RisWorkflow::DIRECTLY_APPROVED, 'history' => [['level' => 'Admin', 'status' => 'Admin Approved', 'remarks' => 'Directly approved. Purchaser may create ATP.']]],
            ['stage' => 'atp_ready_president', 'status' => RisWorkflow::PRESIDENT_APPROVED, 'history' => [['level' => 'Admin', 'status' => 'Forwarded to President', 'remarks' => 'Forwarded for approval.'], ['level' => 'President', 'status' => 'Approved', 'remarks' => 'Approved by Demo President.'], ['level' => 'Admin Co-sign', 'status' => 'Co-signed', 'remarks' => 'Issued by completed. Purchaser may create ATP.']]],
            ['stage' => 'atp_accounting_queue', 'status' => 'Approved', 'history' => [['level' => 'Accounting', 'status' => 'Minor Revision', 'remarks' => 'Supplier quotation mismatch on first submission.']]],
            ['stage' => 'rfc_ready', 'status' => 'Approved', 'history' => []],
            ['stage' => 'rfc_accounting_queue', 'status' => 'Approved', 'history' => []],
            ['stage' => 'rfc_waiting_funds', 'status' => 'Approved', 'history' => [['level' => 'Accounting', 'status' => 'Minor Revision', 'remarks' => 'Payee name corrected before approval.']]],
            ['stage' => 'rr_ready', 'status' => 'Approved', 'history' => []],
            ['stage' => 'rr_receiving_queue', 'status' => 'Approved', 'history' => [['level' => 'Receiving', 'status' => 'Minor Revision', 'remarks' => 'Delivery note revised before final submission.']]],
            ['stage' => 'liq_ready', 'status' => 'Approved', 'history' => []],
            ['stage' => 'liq_accounting_queue', 'status' => 'Approved', 'history' => [['level' => 'Accounting', 'status' => 'Minor Revision', 'remarks' => 'Expense receipt resubmitted before final review.']]],
            ['stage' => 'liq_approved', 'status' => 'Approved', 'history' => []],
        ];
    }

    private function stageGroup(string $stage): string
    {
        return match ($stage) {
            'atp_accounting_queue', 'rfc_ready' => 'atp',
            'rfc_accounting_queue', 'rfc_waiting_funds' => 'rfc',
            'rr_ready' => 'funds',
            'rr_receiving_queue', 'liq_ready' => 'rr',
            'liq_accounting_queue', 'liq_approved' => 'liq',
            default => 'ris',
        };
    }

    private function createChain(array $actors, array $suppliers, array $profile, int $sequence): array
    {
        $supplier = $suppliers[$sequence % count($suppliers)];
        $now = Carbon::now()->subDays(max(0, 20 - $sequence));
        $formSuffix = str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
        $referenceLabel = 'Demo workflow ' . $formSuffix;

        if (in_array($profile['stage'], ['replacement_pending', 'replacement_ris_ready'], true)) {
            return $this->createReplacementSource($actors, $profile, $formSuffix, $now);
        }

        $procurementRequestId = $this->insert('procurement_requests_table', $this->onlyExisting('procurement_requests_table', [
            'procurement_request_supplier_id' => $supplier['supplier_id'],
            'procurement_request_status' => 'Approved',
            'procurement_request_created_by' => $actors['purchaser'],
            'procurement_request_created_at' => $now,
        ]));

        $risId = $this->insert('requisition_issue_slip_table', $this->onlyExisting('requisition_issue_slip_table', [
            'ris_procurement_request_id' => $procurementRequestId,
            'ris_form_number' => 'DEMO-RIS-' . now()->format('Y') . '-' . $formSuffix,
            'ris_manual_title' => $referenceLabel,
            'ris_purpose_description' => 'Procurement for ' . $referenceLabel,
            'ris_supplier_id' => $supplier['supplier_id'],
            'ris_status' => $profile['status'],
            'ris_requested_by_signature' => 'Demo Purchaser',
            'ris_requested_by_date' => $now->toDateString(),
            'ris_submitted_by' => $actors['purchaser'],
            'ris_submitted_at' => $now->copy()->addHour(),
            'ris_created_at' => $now,
            'ris_updated_at' => $now->copy()->addHour(),
        ]));

        $risItems = $this->seedRisItems($risId, $supplier['supplier_id'], $sequence, $now);

        $this->applyRisStage($profile['stage'], $risId, $actors, $now);
        $this->seedRisHistory($risId, $actors, $profile['history'], $now);

        $record = [
            'stage' => $profile['stage'],
            'ris_id' => $risId,
            'ris_form' => 'DEMO-RIS-' . now()->format('Y') . '-' . $formSuffix,
            'atp_form' => null,
            'rfc_form' => null,
            'rr_form' => null,
            'liq_form' => null,
        ];

        if ($this->stageGroup($profile['stage']) === 'ris') {
            $this->seedStageNotifications($profile['stage'], $risId, null, null, null, null);
            return $record;
        }

        $atpId = $this->insert('authority_to_purchase_table', $this->onlyExisting('authority_to_purchase_table', [
            'authority_purchase_ris_id' => $risId,
            'authority_purchase_form_number' => 'DEMO-ATP-' . now()->format('Y') . '-' . $formSuffix,
            'authority_purchase_supplier_id' => $supplier['supplier_id'],
            'authority_purchase_date' => $now->copy()->addDays(1)->toDateString(),
            'authority_purchase_received_by_name' => 'Demo Purchaser',
            'authority_purchase_reference_po_no' => 'DEMO-PO-' . $formSuffix,
            'authority_purchase_status' => $profile['stage'] === 'atp_accounting_queue' ? 'Pending' : 'Approved',
            'authority_purchase_authorized_by_signature' => $profile['stage'] === 'atp_accounting_queue' ? null : 'Demo Accounting',
            'authority_purchase_created_by' => $actors['purchaser'],
            'authority_purchase_submitted_by' => $profile['stage'] === 'atp_accounting_queue' ? $actors['purchaser'] : null,
            'authority_purchase_submitted_at' => $profile['stage'] === 'atp_accounting_queue' ? $now->copy()->addDays(1)->addHour() : null,
            'authority_purchase_updated_at' => $now->copy()->addDays(1)->addHour(),
            'authority_purchase_created_at' => $now->copy()->addDays(1),
        ]));

        $this->seedAtpItems($atpId, $risItems, $now);
        $record['atp_form'] = 'DEMO-ATP-' . now()->format('Y') . '-' . $formSuffix;

        if ($this->stageGroup($profile['stage']) === 'atp') {
            if ($profile['stage'] === 'atp_accounting_queue') {
                $this->log('ATP', $atpId, $actors['purchaser'], 'Submitted', 'ATP submitted for Accounting review.', $now->copy()->addDays(1)->addHour());
                $this->log('ATP', $atpId, $actors['accounting'], 'Minor Revision', 'Initial ATP revision was resolved before this submission.', $now->copy()->addDays(1)->subMinutes(20));
            } else {
                $this->log('ATP', $atpId, $actors['accounting'], 'Approved', 'ATP approved. Purchaser may proceed to Request Check.', $now->copy()->addDays(2));
            }
            $this->seedStageNotifications($profile['stage'], $risId, $atpId, null, null, null);
            return $record;
        }

        $this->log('ATP', $atpId, $actors['accounting'], 'Approved', 'ATP approved. Purchaser may proceed to Request Check.', $now->copy()->addDays(2));

        $totalAmount = array_sum(array_column($risItems, 'amount'));
        $rfcId = $this->insert('request_check_table', $this->onlyExisting('request_check_table', [
            'request_check_authority_purchase_id' => $atpId,
            'request_check_form_number' => 'DEMO-RFC-' . now()->format('Y') . '-' . $formSuffix,
            'request_check_date' => $now->copy()->addDays(3)->toDateString(),
            'request_check_payee' => $supplier['display_name'],
            'request_check_amount_words' => 'Demo amount for workflow testing',
            'request_check_amount_figures' => $totalAmount,
            'request_check_particulars_purpose' => 'Funds for ' . $referenceLabel,
            'request_check_requested_by' => 'Demo Purchaser',
            'request_check_requested_by_user_id' => $actors['purchaser'],
            'request_check_status' => $profile['stage'] === 'rfc_accounting_queue' ? 'Submitted' : 'Approved',
            'request_check_review_stage' => 'accounting',
            'request_check_submitted_by' => $actors['purchaser'],
            'request_check_submitted_at' => $now->copy()->addDays(3),
            'request_check_approved_by_user_id' => $profile['stage'] === 'rfc_accounting_queue' ? null : $actors['accounting'],
            'request_check_approved_at' => $profile['stage'] === 'rfc_accounting_queue' ? null : $now->copy()->addDays(4),
            'request_check_approved_by_signature' => $profile['stage'] === 'rfc_accounting_queue' ? null : 'Demo Accounting',
            'request_check_approved_by_admin' => $profile['stage'] === 'rfc_accounting_queue' ? null : 'Demo Accounting',
            'request_check_accounting_verified_by' => $profile['stage'] === 'rfc_accounting_queue' ? null : $actors['accounting'],
            'request_check_accounting_verified_at' => $profile['stage'] === 'rfc_accounting_queue' ? null : $now->copy()->addDays(4),
            'request_check_updated_at' => $now->copy()->addDays(4),
            'request_check_created_at' => $now->copy()->addDays(3),
        ]));
        $record['rfc_form'] = 'DEMO-RFC-' . now()->format('Y') . '-' . $formSuffix;

        $this->log('RFC', $rfcId, $actors['purchaser'], 'Submitted', 'Request Check submitted to Accounting.', $now->copy()->addDays(3));

        if ($profile['stage'] === 'rfc_accounting_queue') {
            $this->seedStageNotifications($profile['stage'], $risId, $atpId, $rfcId, null, null);
            return $record;
        }

        $this->log('RFC', $rfcId, $actors['accounting'], 'Approved', 'Request Check approved.', $now->copy()->addDays(4));

        if ($profile['stage'] === 'rfc_waiting_funds') {
            $this->seedStageNotifications($profile['stage'], $risId, $atpId, $rfcId, null, null);
            return $record;
        }

        $this->update('request_check_table', 'request_check_id', $rfcId, $this->onlyExisting('request_check_table', [
            'request_check_funds_released_at' => $now->copy()->addDays(5),
            'request_check_funds_released_by' => $actors['accounting'],
            'request_check_updated_at' => $now->copy()->addDays(5),
        ]));
        $this->log('RFC', $rfcId, $actors['accounting'], 'Approved', 'Funds released for collection.', $now->copy()->addDays(5));

        if ($profile['stage'] === 'rr_ready') {
            $this->seedStageNotifications($profile['stage'], $risId, $atpId, $rfcId, null, null);
            return $record;
        }

        $rrId = $this->insert('receiving_reports_table', $this->onlyExisting('receiving_reports_table', [
            'receiving_report_request_check_id' => $rfcId,
            'receiving_report_procurement_request_id' => $procurementRequestId,
            'receiving_report_atp_id' => $atpId,
            'receiving_report_ris_id' => $risId,
            'receiving_report_form_number' => 'DEMO-RR-' . now()->format('Y') . '-' . $formSuffix,
            'receiving_report_supplier_id' => $supplier['supplier_id'],
            'receiving_report_supplier_address_override' => $supplier['address'],
            'receiving_report_received_from' => $supplier['display_name'],
            'receiving_report_date' => $now->copy()->addDays(6)->toDateString(),
            'receiving_report_delivery_date' => $now->copy()->addDays(6)->toDateString(),
            'receiving_report_invoice_no' => 'DEMO-INV-' . $formSuffix,
            'receiving_report_dr_no' => 'DEMO-DR-' . $formSuffix,
            'receiving_report_received_by_signature' => 'Demo Purchaser',
            'receiving_report_status' => $profile['stage'] === 'rr_receiving_queue' ? 'Submitted' : 'Completed',
            'receiving_report_submitted_by' => $actors['purchaser'],
            'receiving_report_submitted_at' => $now->copy()->addDays(6),
            'receiving_report_second_count_by' => $profile['stage'] === 'rr_receiving_queue' ? null : 'Demo Receiving',
            'receiving_report_second_count_by_user_id' => $profile['stage'] === 'rr_receiving_queue' ? null : $actors['receiving'],
            'receiving_report_second_count_at' => $profile['stage'] === 'rr_receiving_queue' ? null : $now->copy()->addDays(7),
            'receiving_report_second_count_signature' => $profile['stage'] === 'rr_receiving_queue' ? null : 'Demo Receiving',
            'receiving_report_updated_at' => $profile['stage'] === 'rr_receiving_queue' ? $now->copy()->addDays(6) : $now->copy()->addDays(7),
            'receiving_report_created_at' => $now->copy()->addDays(6),
        ]));
        $record['rr_form'] = 'DEMO-RR-' . now()->format('Y') . '-' . $formSuffix;

        $this->seedReceivingItems($rrId, $risItems);
        $this->log('RR', $rrId, $actors['purchaser'], 'Submitted', 'Receiving Report submitted to Receiving.', $now->copy()->addDays(6));

        if ($profile['stage'] === 'rr_receiving_queue') {
            $this->seedStageNotifications($profile['stage'], $risId, $atpId, $rfcId, $rrId, null);
            return $record;
        }

        $this->log('RR', $rrId, $actors['receiving'], 'Approved', 'Items accepted after second count.', $now->copy()->addDays(7));
        $this->update('requisition_issue_slip_table', 'ris_id', $risId, $this->onlyExisting('requisition_issue_slip_table', [
            'ris_received_by_signature' => 'Demo Receiving',
            'ris_received_by_date' => $now->copy()->addDays(7)->toDateString(),
        ]));
        $this->seedInventoryFromRr($rrId, $supplier, $now->copy()->addDays(7));

        if ($profile['stage'] === 'liq_ready') {
            $this->seedStageNotifications($profile['stage'], $risId, $atpId, $rfcId, $rrId, null);
            return $record;
        }

        $liqId = $this->insert('liquidation_reports_table', $this->onlyExisting('liquidation_reports_table', [
            'liquidation_report_receiving_report_id' => $rrId,
            'liquidation_report_procurement_request_id' => $procurementRequestId,
            'liquidation_report_form_number' => 'DEMO-LIQ-' . now()->format('Y') . '-' . $formSuffix,
            'liquidation_report_employee_name' => 'Demo Purchaser',
            'liquidation_report_cheque_number' => 'DEMO-CHK-' . $formSuffix,
            'liquidation_report_purpose' => 'Liquidation for ' . $referenceLabel,
            'liquidation_report_amount_advance' => $totalAmount,
            'liquidation_report_date_released' => $now->copy()->addDays(5)->toDateString(),
            'liquidation_report_activity_end_date' => $now->copy()->addDays(7)->toDateString(),
            'liquidation_report_submission_deadline' => $now->copy()->addDays(10)->toDateString(),
            'liquidation_report_date_submitted' => $now->copy()->addDays(8)->toDateString(),
            'liquidation_report_days_lapse' => 0,
            'liquidation_report_summary_amt_advanced' => $totalAmount,
            'liquidation_report_summary_actual_expense' => $totalAmount,
            'liquidation_report_summary_balance' => 0,
            'liquidation_report_cash_returned_or_no' => 'No',
            'liquidation_report_submitted_by_signature' => 'Demo Purchaser',
            'liquidation_report_submitted_by_date' => $now->copy()->addDays(8)->toDateString(),
            'liquidation_report_status' => $profile['stage'] === 'liq_accounting_queue' ? 'Submitted' : 'Approved',
            'liquidation_report_review_stage' => $profile['stage'] === 'liq_accounting_queue' ? 'accounting' : 'completed',
            'liquidation_report_submitted_by' => $actors['purchaser'],
            'liquidation_report_submitted_at' => $now->copy()->addDays(8),
            'liquidation_report_checked_by_accountant' => $profile['stage'] === 'liq_accounting_queue' ? null : 'Demo Accounting',
            'liquidation_report_checked_by_date' => $profile['stage'] === 'liq_accounting_queue' ? null : $now->copy()->addDays(9)->toDateString(),
            'liquidation_report_updated_at' => $profile['stage'] === 'liq_accounting_queue' ? $now->copy()->addDays(8) : $now->copy()->addDays(9),
            'liquidation_report_created_at' => $now->copy()->addDays(8),
        ]));
        $record['liq_form'] = 'DEMO-LIQ-' . now()->format('Y') . '-' . $formSuffix;

        $this->seedLiquidationItems($liqId, $risItems, $formSuffix);
        $this->log('LIQ', $liqId, $actors['purchaser'], 'Submitted', 'Liquidation Report submitted to Accounting.', $now->copy()->addDays(8));

        if ($profile['stage'] !== 'liq_accounting_queue') {
            $this->log('LIQ', $liqId, $actors['accounting'], 'Approved', 'Liquidation approved.', $now->copy()->addDays(9));
            $this->update('procurement_requests_table', 'procurement_request_id', $procurementRequestId, $this->onlyExisting('procurement_requests_table', [
                'procurement_request_status' => 'Completed',
            ]));
        }

        $this->seedStageNotifications($profile['stage'], $risId, $atpId, $rfcId, $rrId, $liqId);

        return $record;
    }

    private function applyRisStage(string $stage, int $risId, array $actors, Carbon $now): void
    {
        if ($stage === 'ris_admin_pending') {
            $this->log('RIS', $risId, $actors['purchaser'], 'Submitted', 'RIS submitted for Admin review.', $now->copy()->addHour());
            return;
        }

        if ($stage === 'ris_purchaser_revision') {
            $this->log('RIS', $risId, $actors['admin'], 'Minor Revision', 'Returned to Purchaser for correction.', $now->copy()->addHours(2));
            return;
        }

        if ($stage === 'president_queue') {
            $this->log('RIS', $risId, $actors['admin'], 'Forwarded to President', 'RIS forwarded to President.', $now->copy()->addHours(2));
            return;
        }

        if ($stage === 'president_rejected') {
            $this->update('requisition_issue_slip_table', 'ris_id', $risId, $this->onlyExisting('requisition_issue_slip_table', [
                'ris_status' => RisWorkflow::PRESIDENT_REJECTED,
                'ris_approved_by_signature' => null,
                'ris_issued_by_signature' => null,
                'ris_updated_at' => $now->copy()->addDays(1),
            ]));
            $this->log('RIS', $risId, $actors['president'], 'Rejected', 'Rejected by the President. Admin may return it for revision.', $now->copy()->addDays(1));
            return;
        }

        if ($stage === 'admin_cosign_queue') {
            $this->update('requisition_issue_slip_table', 'ris_id', $risId, $this->onlyExisting('requisition_issue_slip_table', [
                'ris_status' => RisWorkflow::PRESIDENT_APPROVED,
                'ris_approved_by_signature' => $this->demoSignature(),
                'ris_approved_by_date' => $now->copy()->addDays(1)->toDateString(),
                'ris_updated_at' => $now->copy()->addDays(1),
            ]));
            return;
        }

        if ($stage === 'atp_ready_president') {
            $this->update('requisition_issue_slip_table', 'ris_id', $risId, $this->onlyExisting('requisition_issue_slip_table', [
                'ris_status' => RisWorkflow::PRESIDENT_APPROVED,
                'ris_approved_by_signature' => $this->demoSignature(),
                'ris_approved_by_date' => $now->copy()->addDays(1)->toDateString(),
                'ris_issued_by_signature' => 'Demo Admin',
                'ris_issued_by_date' => $now->copy()->addDays(1)->toDateString(),
                'ris_updated_at' => $now->copy()->addDays(1)->addHour(),
            ]));
            $this->log('RIS', $risId, $actors['admin'], 'Co-signed', 'Issued by completed. Purchaser may create ATP.', $now->copy()->addDays(1)->addHour());
            return;
        }

        $this->update('requisition_issue_slip_table', 'ris_id', $risId, $this->onlyExisting('requisition_issue_slip_table', [
            'ris_status' => RisWorkflow::DIRECTLY_APPROVED,
            'ris_issued_by_signature' => 'Demo Admin',
            'ris_issued_by_date' => $now->copy()->addDays(1)->toDateString(),
            'ris_updated_at' => $now->copy()->addDays(1),
        ]));
        $this->log('RIS', $risId, $actors['admin'], 'Admin Approved', 'RIS directly approved and released to Purchaser.', $now->copy()->addDays(1));
    }

    private function seedRisHistory(int $risId, array $actors, array $history, Carbon $now): void
    {
        foreach ($history as $index => $entry) {
            $userId = match ($entry['level']) {
                'Admin', 'Admin Approval', 'Admin Co-sign', 'Admin Return' => $actors['admin'],
                'President' => $actors['president'],
                'Accounting' => $actors['accounting'],
                'Receiving' => $actors['receiving'],
                default => $actors['purchaser'],
            };

            $this->log('RIS', $risId, $userId, $entry['status'], $entry['remarks'], $now->copy()->subHours(count($history) - $index));
        }
    }

    private function seedRisItems(int $risId, int $supplierId, int $sequence, Carbon $now): array
    {
        $items = [
            [
                'name' => 'Demo Laptop ' . $sequence,
                'quantity' => 1,
                'unit_cost' => 32500.00 + ($sequence * 100),
            ],
            [
                'name' => 'Demo Office Chair ' . $sequence,
                'quantity' => 2,
                'unit_cost' => 2500.00 + ($sequence * 25),
            ],
        ];

        foreach ($items as &$item) {
            $item['amount'] = $item['quantity'] * $item['unit_cost'];
            $this->insert('requisition_issue_slip_items_table', $this->onlyExisting('requisition_issue_slip_items_table', [
                'ris_id' => $risId,
                'ris_item_name_description' => $item['name'],
                'ris_quantity_requested' => $item['quantity'],
                'ris_quantity_issued' => $item['quantity'],
                'ris_unit_cost' => $item['unit_cost'],
                'ris_total_amount' => $item['amount'],
                'ris_item_supplier_id' => $supplierId,
                'ris_item_created_at' => $now,
                'ris_item_updated_at' => $now,
            ]));
        }

        return $items;
    }

    private function seedAtpItems(int $atpId, array $risItems, Carbon $now): void
    {
        foreach ($risItems as $item) {
            $this->insert('authority_to_purchase_items_table', $this->onlyExisting('authority_to_purchase_items_table', [
                'authority_purchase_id' => $atpId,
                'atp_quantity' => $item['quantity'],
                'atp_unit' => 'unit',
                'atp_description' => $item['name'],
                'atp_unit_price' => $item['unit_cost'],
                'atp_amount' => $item['amount'],
                'atp_item_created_at' => $now,
                'atp_item_updated_at' => $now,
            ]));
        }
    }

    private function seedReceivingItems(int $rrId, array $risItems): void
    {
        foreach ($risItems as $item) {
            $this->insert('receiving_report_items_table', $this->onlyExisting('receiving_report_items_table', [
                'receiving_report_id' => $rrId,
                'receiving_report_item_quantity' => $item['quantity'],
                'receiving_report_item_unit' => 'unit',
                'receiving_report_item_article' => $item['name'],
                'receiving_report_item_unit_price' => $item['unit_cost'],
                'receiving_report_item_amount' => $item['amount'],
            ]));
        }
    }

    private function seedInventoryFromRr(int $rrId, array $supplier, Carbon $acquiredAt): void
    {
        if (!Schema::hasTable('equipment_table') || !Schema::hasTable('receiving_report_items_table')) {
            return;
        }

        $items = DB::table('receiving_report_items_table')
            ->where('receiving_report_id', $rrId)
            ->orderBy('receiving_report_item_id')
            ->get();

        foreach ($items as $item) {
            if (
                Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')
                && !empty($item->receiving_report_item_equipment_id)
            ) {
                continue;
            }

            $equipmentId = $this->insert('equipment_table', $this->onlyExisting('equipment_table', [
                'equipment_name' => $item->receiving_report_item_article ?: 'Demo received item',
                'equipment_quantity' => (int) ($item->receiving_report_item_quantity ?: 1),
                'equipment_supplier_id' => $supplier['supplier_id'] ?? null,
                'equipment_tracking_mode' => 'Bulk',
                'equipment_condition_status' => 'Good',
                'equipment_inventory_status' => 'Active',
                'equipment_purchase_date' => $acquiredAt->toDateString(),
                'equipment_purchase_cost' => $item->receiving_report_item_amount ?? null,
                'equipment_acquired_date' => $acquiredAt->toDateString(),
                'equipment_created_at' => $acquiredAt,
            ]));

            if ($equipmentId && Schema::hasColumn('receiving_report_items_table', 'receiving_report_item_equipment_id')) {
                DB::table('receiving_report_items_table')
                    ->where('receiving_report_item_id', $item->receiving_report_item_id)
                    ->update(['receiving_report_item_equipment_id' => $equipmentId]);
            }
        }
    }

    private function seedLiquidationItems(int $liqId, array $risItems, string $suffix): void
    {
        foreach ($risItems as $index => $item) {
            $this->insert('liquidation_report_items_table', $this->onlyExisting('liquidation_report_items_table', [
                'liquidation_report_id' => $liqId,
                'liquidation_item_particulars' => $item['name'],
                'liquidation_item_particulars_amount' => $item['amount'],
                'liquidation_item_actual_breakdown_amount' => $item['amount'],
                'liquidation_item_actual_total_amount' => $item['amount'],
                'liquidation_item_variance' => 0,
                'liquidation_item_ref_no' => 'DEMO-REF-' . $suffix . '-' . ($index + 1),
            ]));
        }
    }

    private function seedStageNotifications(string $stage, int $risId, ?int $atpId, ?int $rfcId, ?int $rrId, ?int $liqId): void
    {
        $risForm = (string) DB::table('requisition_issue_slip_table')->where('ris_id', $risId)->value('ris_form_number');

        match ($stage) {
            'ris_admin_pending' => WorkflowNotifier::toRole(WorkflowNotifier::ROLE_ADMIN, 'New RIS submitted', $risForm . ' is waiting for Admin review.', 'ris_submitted', 'RIS', $risId, '/admin/procurement-review'),
            'ris_purchaser_revision' => WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PURCHASER, 'RIS returned for revision', $risForm . ' needs correction before resubmission.', 'ris_revision', 'RIS', $risId, '/purchaser/ris'),
            'president_queue' => WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PRESIDENT, 'RIS ready for presidential review', $risForm . ' was forwarded by Admin.', 'ris_forwarded', 'RIS', $risId, '/president/approvals'),
            'president_rejected' => WorkflowNotifier::toRole(WorkflowNotifier::ROLE_ADMIN, 'President rejected an RIS', $risForm . ' was rejected. Return it to Purchaser for revision.', 'ris_president_rejected', 'RIS', $risId, '/admin/digital-signatures/sign-ris'),
            'admin_cosign_queue' => WorkflowNotifier::toRole(WorkflowNotifier::ROLE_ADMIN, 'President approved an RIS', $risForm . ' is waiting for Admin Sign RIS.', 'ris_president_approved', 'RIS', $risId, '/admin/digital-signatures/sign-ris'),
            'atp_ready_direct', 'atp_ready_president' => WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PURCHASER, 'RIS released', $risForm . ' is ready. Create an Authority to Purchase.', 'ris_released', 'RIS', $risId, '/purchaser/ris'),
            'atp_accounting_queue' => $atpId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_ACCOUNTING, 'ATP submitted for review', (string) DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->value('authority_purchase_form_number') . ' is waiting for Accounting review.', 'atp_submitted', 'ATP', $atpId, '/accounting/authority-to-purchase/' . $atpId) : null,
            'rfc_ready' => $atpId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PURCHASER, 'ATP approved', (string) DB::table('authority_to_purchase_table')->where('authority_purchase_id', $atpId)->value('authority_purchase_form_number') . ' was approved. Create a Request for Check.', 'atp_approved', 'ATP', $atpId, '/purchaser/request-check?selected_atp=' . $atpId) : null,
            'rfc_accounting_queue' => $rfcId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_ACCOUNTING, 'Request Check submitted', (string) DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_form_number') . ' is waiting for Accounting review.', 'rfc_submitted', 'RFC', $rfcId, '/accounting/request-check/' . $rfcId) : null,
            'rfc_waiting_funds' => $rfcId ? WorkflowNotifier::toUser((int) DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_requested_by_user_id'), WorkflowNotifier::ROLE_PURCHASER, 'Request Check approved', (string) DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_form_number') . ' was approved. Wait for Accounting to release funds, then create a Receiving Report.', 'rfc_approved', 'RFC', $rfcId, '/purchaser/request-check') : null,
            'rr_ready' => $rfcId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PURCHASER, 'Funds released', (string) DB::table('request_check_table')->where('request_check_id', $rfcId)->value('request_check_form_number') . ' funds were released. Create a Receiving Report.', 'rfc_funds_released', 'RFC', $rfcId, '/purchaser/receiving-reports') : null,
            'rr_receiving_queue' => $rrId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_RECEIVING, 'Receiving Report submitted', (string) DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->value('receiving_report_form_number') . ' is waiting for inspection.', 'rr_submitted', 'RR', $rrId, '/receiving/reports') : null,
            'liq_ready' => $rrId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PURCHASER, 'Receiving completed', (string) DB::table('receiving_reports_table')->where('receiving_report_id', $rrId)->value('receiving_report_form_number') . ' was accepted. Create a Liquidation Report.', 'rr_completed', 'RR', $rrId, '/purchaser/liquidation-reports') : null,
            'liq_accounting_queue' => $liqId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_ACCOUNTING, 'Liquidation Report submitted', (string) DB::table('liquidation_reports_table')->where('liquidation_report_id', $liqId)->value('liquidation_report_form_number') . ' is waiting for Accounting review.', 'liq_submitted', 'LIQ', $liqId, '/accounting/liquidation-reports/' . $liqId) : null,
            'liq_approved' => $liqId ? WorkflowNotifier::toRole(WorkflowNotifier::ROLE_PURCHASER, 'Liquidation approved', (string) DB::table('liquidation_reports_table')->where('liquidation_report_id', $liqId)->value('liquidation_report_form_number') . ' was approved. This purchasing chain is complete.', 'liq_approved', 'LIQ', $liqId, '/purchaser/liquidation-reports') : null,
            default => null,
        };
    }

    private function createReplacementSource(array $actors, array $profile, string $formSuffix, Carbon $now): array
    {
        $approved = $profile['stage'] === 'replacement_ris_ready';
        $equipmentName = 'Demo Desktop PC ' . $formSuffix;
        $roomId = Schema::hasTable('rooms_table')
            ? DB::table('rooms_table')->orderBy('room_id')->value('room_id')
            : null;
        $reporterId = Schema::hasTable('reporters_table')
            ? (
                DB::table('reporters_table')->where('reporter_employee_id', 'OMC0129F')->value('reporter_employee_id')
                ?: DB::table('reporters_table')->value('reporter_employee_id')
            )
            : null;

        $reportId = $this->insert('reports_table', $this->onlyExisting('reports_table', [
            'report_reporter_employee_id' => $reporterId,
            'report_room_id' => $roomId,
            'report_equipment_id' => null,
            'report_unlisted_equipment_name' => $equipmentName,
            'report_problem_description' => 'Unit no longer boots. Recommended for replacement.',
            'report_suggested_issue' => 'Hardware Failure',
            'report_urgency_level' => 'Non-Urgent',
            'report_current_status' => 'For Replacement',
            'report_assigned_purchaser_id' => $actors['purchaser'],
            'report_purchaser_assigned_at' => $now,
            'report_replacement_notes' => 'Motherboard failed. Replace with a new desktop unit.',
            'report_replacement_submitted_to_purchaser' => 1,
            'report_is_archived' => 0,
            'report_submitted_at' => $now,
            'report_updated_at' => $now,
        ]));

        $procurementRequestId = $this->insert('procurement_requests_table', $this->onlyExisting('procurement_requests_table', [
            'procurement_request_report_id' => $reportId,
            'procurement_request_status' => $approved ? 'Approved' : 'Pending',
            'procurement_request_created_by' => $actors['purchaser'],
            'procurement_request_created_at' => $now,
            'procurement_request_is_archived' => 0,
        ]));

        $label = $approved
            ? 'Replacement #' . $procurementRequestId . ' (Create RIS)'
            : 'Replacement #' . $procurementRequestId . ' (Pending approve)';

        if ($approved) {
            WorkflowNotifier::toRole(
                WorkflowNotifier::ROLE_PURCHASER,
                'Replacement approved',
                $equipmentName . ' is approved. Create an RIS to start purchasing.',
                'replacement_approved',
                'PROC',
                $procurementRequestId,
                '/purchaser/procurement/replacement-requests'
            );
        } else {
            WorkflowNotifier::toRole(
                WorkflowNotifier::ROLE_PURCHASER,
                'Replacement request pending',
                $equipmentName . ' is waiting for Purchaser approval.',
                'replacement_pending',
                'PROC',
                $procurementRequestId,
                '/purchaser/procurement/replacement-requests'
            );
        }

        return [
            'stage' => $profile['stage'],
            'ris_id' => null,
            'ris_form' => $label,
            'atp_form' => null,
            'rfc_form' => null,
            'rr_form' => null,
            'liq_form' => null,
        ];
    }

    private function printSummary(array $created): void
    {
        $this->newLine();
        $this->info('Seeded purchasing workflow demo data.');

        $counts = [];
        foreach ($created as $row) {
            $counts[$row['stage']] = ($counts[$row['stage']] ?? 0) + 1;
        }

        foreach ($counts as $stage => $count) {
            $this->line(str_pad($stage, 26) . ' ' . $count);
        }

        $this->newLine();
        $this->line('Sample references:');
        foreach ($created as $row) {
            $parts = array_filter([
                $row['ris_form'],
                $row['atp_form'],
                $row['rfc_form'],
                $row['rr_form'],
                $row['liq_form'],
            ]);

            $this->line('- ' . $row['stage'] . ': ' . implode(' -> ', $parts));
        }
    }

    private function nextDemoSequence(): int
    {
        $prefix = 'DEMO-RIS-' . now()->format('Y') . '-';
        $records = DB::table('requisition_issue_slip_table')
            ->where('ris_form_number', 'like', $prefix . '%')
            ->pluck('ris_form_number');

        $max = 0;
        foreach ($records as $number) {
            $suffix = (int) substr((string) $number, strlen($prefix));
            $max = max($max, $suffix);
        }

        return $max + 1;
    }

    private function demoSignature(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn0L0UAAAAASUVORK5CYII=';
    }

    private function log(string $type, int $referenceId, int $userId, string $status, string $remarks, Carbon $approvedAt): void
    {
        if (!Schema::hasTable('approval_logs_table')) {
            return;
        }

        $level = match ($type) {
            'RIS' => match ($status) {
                'Forwarded to President', 'Minor Revision' => 'Admin',
                'Admin Approved' => 'Admin Approval',
                'Co-signed' => 'Admin Co-sign',
                'Approved', 'Rejected' => 'President',
                default => 'Admin',
            },
            'ATP', 'RFC', 'LIQ' => 'Accounting',
            'RR' => 'Receiving',
            default => 'Admin',
        };

        DB::table('approval_logs_table')->insert($this->onlyExisting('approval_logs_table', [
            'approval_log_reference_type' => $type,
            'approval_log_reference_id' => $referenceId,
            'approval_log_approved_by' => $userId,
            'approval_log_level' => $level,
            'approval_log_approval_status' => $status,
            'approval_log_approval_remarks' => $remarks,
            'approval_log_approved_at' => $approvedAt,
        ]));
    }

    private function insert(string $table, array $payload): int
    {
        return (int) DB::table($table)->insertGetId($payload);
    }

    private function update(string $table, string $key, int $id, array $payload): void
    {
        DB::table($table)->where($key, $id)->update($payload);
    }

    private function onlyExisting(string $table, array $payload): array
    {
        $filtered = [];

        foreach ($payload as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}
