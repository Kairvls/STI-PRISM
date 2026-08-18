<?php

namespace Tests\Unit;

use App\Support\RisWorkflow;
use PHPUnit\Framework\TestCase;

class RisWorkflowTest extends TestCase
{
    private function ris(array $attrs): object
    {
        return (object) array_merge([
            'ris_status' => 'Draft',
            'ris_approved_by_signature' => null,
            'ris_issued_by_signature' => null,
        ], $attrs);
    }

    public function test_directly_approved_is_eligible_for_atp(): void
    {
        $this->assertTrue(RisWorkflow::isEligibleForAtp($this->ris([
            'ris_status' => 'Directly Approved',
            'ris_issued_by_signature' => 'Admin Name',
        ])));
    }

    public function test_president_approved_needs_issued_by_for_atp(): void
    {
        $pendingIssued = $this->ris([
            'ris_status' => 'Approved by the President',
            'ris_approved_by_signature' => 'data:image/png;base64,abc',
        ]);
        $this->assertFalse(RisWorkflow::isEligibleForAtp($pendingIssued));

        $released = $this->ris([
            'ris_status' => 'Approved by the President',
            'ris_approved_by_signature' => 'data:image/png;base64,abc',
            'ris_issued_by_signature' => 'Admin Name',
        ]);
        $this->assertTrue(RisWorkflow::isEligibleForAtp($released));
    }

    public function test_legacy_approved_without_issued_by_is_not_eligible(): void
    {
        $this->assertFalse(RisWorkflow::isEligibleForAtp($this->ris([
            'ris_status' => 'Approved',
        ])));

        $this->assertFalse(RisWorkflow::isEligibleForAtp($this->ris([
            'ris_status' => 'Approved',
            'ris_approved_by_signature' => 'Admin Name',
        ])));
    }

    public function test_legacy_approved_with_issued_by_is_eligible(): void
    {
        $this->assertTrue(RisWorkflow::isEligibleForAtp($this->ris([
            'ris_status' => 'Approved',
            'ris_approved_by_signature' => 'data:image/png;base64,abc',
            'ris_issued_by_signature' => 'Admin Name',
        ])));
    }

    public function test_forwarded_ris_awaits_president_and_cannot_create_atp(): void
    {
        $forwarded = $this->ris(['ris_status' => 'Forwarded to President']);
        $this->assertTrue(RisWorkflow::isAwaitingPresident($forwarded));
        $this->assertFalse(RisWorkflow::isEligibleForAtp($forwarded));
        $this->assertSame('Forwarded to President', RisWorkflow::statusLabel($forwarded));
    }

    public function test_president_reject_label_is_normalized(): void
    {
        $this->assertTrue(RisWorkflow::isPresidentRejected($this->ris([
            'ris_status' => 'Rejected by President',
        ])));
        $this->assertSame(
            'Rejected by the President',
            RisWorkflow::statusLabel($this->ris(['ris_status' => 'Rejected by the President']))
        );
    }

    public function test_president_approved_without_issued_by_shows_awaiting_admin(): void
    {
        $this->assertSame('Awaiting Admin', RisWorkflow::statusLabel($this->ris([
            'ris_status' => 'Approved by the President',
            'ris_approved_by_signature' => 'data:image/png;base64,abc',
        ])));
    }

    public function test_replacement_source_sets_request_type_and_purpose(): void
    {
        $this->assertSame('Replacement Procurement', RisWorkflow::requestType(12));
        $this->assertSame('New Procurement', RisWorkflow::requestType(null));

        $source = (object) [
            'equipment_name' => '',
            'report_unlisted_equipment_name' => 'Demo Desktop PC',
            'room_name' => 'Computer Laboratory 1',
            'report_replacement_notes' => 'Motherboard failed.',
            'report_problem_description' => 'Unit no longer boots.',
        ];

        $this->assertSame('Demo Desktop PC', RisWorkflow::equipmentLabel($source));
        $this->assertSame(
            'Replacement of Demo Desktop PC in Computer Laboratory 1. Reason: Motherboard failed.',
            RisWorkflow::replacementPurpose($source)
        );
    }

    public function test_source_label_maps_legacy_manual_request_type(): void
    {
        $this->assertSame('New Procurement', RisWorkflow::sourceLabel((object) [
            'ris_request_type' => 'manual',
        ]));
        $this->assertSame('New Procurement', RisWorkflow::requestTypeLabel((object) [
            'ris_request_type' => 'Manual Procurement',
        ]));
        $this->assertSame('Replacement Procurement', RisWorkflow::requestTypeLabel((object) [
            'ris_request_type' => 'Replacement',
        ]));
        $this->assertSame('Printer toner', RisWorkflow::sourceLabel((object) [
            'ris_request_type' => 'manual',
            'ris_item_names' => 'Printer toner',
        ]));
    }

    public function test_atp_revision_is_distinct_from_draft(): void
    {
        $draft = (object) [
            'authority_purchase_status' => 'Pending',
            'authority_purchase_submitted_at' => null,
            'authority_purchase_rejection_reason' => null,
        ];
        $revision = (object) [
            'authority_purchase_status' => 'Pending',
            'authority_purchase_submitted_at' => null,
            'authority_purchase_rejection_reason' => 'Correct the supplier name.',
        ];
        $submitted = (object) [
            'authority_purchase_status' => 'Pending',
            'authority_purchase_submitted_at' => '2026-08-18 09:00:00',
            'authority_purchase_rejection_reason' => null,
        ];

        $this->assertFalse(RisWorkflow::atpNeedsRevision($draft));
        $this->assertTrue(RisWorkflow::atpNeedsRevision($revision));
        $this->assertSame('Draft', RisWorkflow::atpStatusLabel($draft));
        $this->assertSame('Minor Revision', RisWorkflow::atpStatusLabel($revision));
        $this->assertSame('Submitted', RisWorkflow::atpStatusLabel($submitted));
    }

    public function test_drawn_signature_is_stored_when_present(): void
    {
        $png = 'data:image/png;base64,abc';
        $this->assertTrue(RisWorkflow::isDrawnSignature($png));
        $this->assertFalse(RisWorkflow::isDrawnSignature('Jane Accountant'));
        $this->assertSame($png, RisWorkflow::drawnOrName($png, 'Jane Accountant'));
        $this->assertSame('Jane Accountant', RisWorkflow::drawnOrName('', 'Jane Accountant'));
        $this->assertSame('Jane Accountant', RisWorkflow::drawnOrName(null, 'Jane Accountant'));
    }
}
