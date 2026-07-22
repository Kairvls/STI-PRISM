<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
    {
        // =====================================================
        // TEMPORARILY SUPPORT OLD + NEW STATUS VALUES HERE
        // =====================================================

        DB::statement("
            ALTER TABLE requisition_issue_slip_table
            MODIFY ris_status ENUM(
                'Pending',
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Approved',
                'Rejected',
                'Archived'
            ) NOT NULL DEFAULT 'Draft'
        ");


        // =====================================================
        // CONVERT OLD FAKE DRAFTS HERE
        // =====================================================

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Pending')
            ->whereNull('ris_submitted_at')
            ->update([
                'ris_status' => 'Draft',
            ]);


        // =====================================================
        // CONVERT OLD SUBMITTED RIS HERE
        // =====================================================

        DB::table('requisition_issue_slip_table')
            ->where('ris_status', 'Pending')
            ->whereNotNull('ris_submitted_at')
            ->update([
                'ris_status' => 'Submitted',
            ]);


        // =====================================================
        // REMOVE OLD PENDING STATUS HERE
        // =====================================================

        DB::statement("
            ALTER TABLE requisition_issue_slip_table
            MODIFY ris_status ENUM(
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
                'Approved',
                'Rejected',
                'Archived'
            ) NOT NULL DEFAULT 'Draft'
        ");


        // =====================================================
        // CREATE RIS REVISION HISTORY TABLE HERE
        // =====================================================

        Schema::create('ris_revision_notes_table', function (Blueprint $table) {

            $table->bigIncrements('ris_revision_id');

            $table->bigInteger('ris_id');

            $table->bigInteger('ris_revision_requested_by')
                ->nullable();

            $table->string('ris_revision_type', 100)
                ->default('Minor Revision');

            $table->text('ris_revision_note');

            $table->dateTime('ris_revision_created_at')
                ->useCurrent();

            $table->dateTime('ris_revision_updated_at')
                ->nullable();

            $table->index(
                'ris_id',
                'idx_ris_revision_ris_id'
            );

            $table->index(
                'ris_revision_requested_by',
                'idx_ris_revision_requested_by'
            );

            $table->foreign('ris_id', 'fk_ris_revision_ris')
                ->references('ris_id')
                ->on('requisition_issue_slip_table')
                ->cascadeOnDelete();

            $table->foreign(
                'ris_revision_requested_by',
                'fk_ris_revision_requested_by'
            )
                ->references('user_id')
                ->on('users_table')
                ->nullOnDelete();
        });

    }


    public function down(): void
    {
        // =====================================================
        // REMOVE REVISION HISTORY TABLE HERE
        // =====================================================

        Schema::dropIfExists('ris_revision_notes_table');


        // =====================================================
        // RESTORE OLD RIS STATUS STRUCTURE HERE
        // =====================================================

        DB::table('requisition_issue_slip_table')
            ->whereIn('ris_status', [
                'Draft',
                'Submitted',
                'Under Review',
                'Minor Revision',
                'Resubmitted',
            ])
            ->update([
                'ris_status' => $isDraft
                    ? 'Draft'
                    : 'Submitted',
            ]);

        DB::statement("
            ALTER TABLE requisition_issue_slip_table
            MODIFY ris_status ENUM(
                'Pending',
                'Approved',
                'Rejected'
            ) NOT NULL DEFAULT 'Pending'
        ");
    }
};