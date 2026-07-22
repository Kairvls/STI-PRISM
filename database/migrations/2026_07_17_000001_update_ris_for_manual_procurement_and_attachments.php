<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {

            // =====================================================
            // RIS SUBMISSION TRACKING
            // =====================================================

            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_submitted_by')) {
                $table->unsignedBigInteger('ris_submitted_by')
                    ->nullable()
                    ->after('ris_requested_by_date');
            }

            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_submitted_at')) {
                $table->dateTime('ris_submitted_at')
                    ->nullable()
                    ->after('ris_submitted_by');
            }

            // =====================================================
            // ADMIN APPROVAL
            // =====================================================

            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_rejection_reason')) {
                $table->text('ris_rejection_reason')
                    ->nullable()
                    ->after('ris_approved_by_date');
            }

            // =====================================================
            // UPDATED TIMESTAMP
            // =====================================================

            if (!Schema::hasColumn('requisition_issue_slip_table', 'ris_updated_at')) {
                $table->dateTime('ris_updated_at')
                    ->nullable()
                    ->after('ris_created_at');
            }
        });

        // =====================================================
        // MATCH USER_ID TYPE BEFORE ADDING FOREIGN KEY
        // =====================================================

        DB::statement('ALTER TABLE requisition_issue_slip_table MODIFY ris_submitted_by BIGINT NULL');

        // =====================================================
        // FOREIGN KEY
        // =====================================================

        $this->addForeignIfMissing(
            'requisition_issue_slip_table',
            'fk_ris_submitted_by',
            'ris_submitted_by',
            'users_table',
            'user_id'
        );

        // =====================================================
        // INDEXES
        // =====================================================

        $this->addIndexIfMissing(
            'requisition_issue_slip_table',
            'idx_ris_status',
            'ris_status'
        );

        $this->addIndexIfMissing(
            'requisition_issue_slip_table',
            'idx_ris_submitted_at',
            'ris_submitted_at'
        );

        // =====================================================
        // RIS ATTACHMENTS TABLE
        // =====================================================

        if (!Schema::hasTable('ris_attachments_table')) {

            Schema::create('ris_attachments_table', function (Blueprint $table) {

                $table->bigIncrements('ris_attachment_id');

                $table->unsignedBigInteger('ris_id');

                $table->string('ris_attachment_original_name');

                $table->string('ris_attachment_path');

                $table->string('ris_attachment_mime_type')
                    ->nullable();

                $table->unsignedBigInteger('ris_attachment_size')
                    ->nullable();

                $table->unsignedBigInteger('ris_attachment_uploaded_by')
                    ->nullable();

                $table->dateTime('ris_attachment_created_at')
                    ->useCurrent();

                $table->index(
                    'ris_id',
                    'idx_ris_attachment_ris_id'
                );

                $table->index(
                    'ris_attachment_uploaded_by',
                    'idx_ris_attachment_uploaded_by'
                );
            });
        }

        $this->addForeignIfMissing(
            'ris_attachments_table',
            'fk_ris_attachment_ris',
            'ris_id',
            'requisition_issue_slip_table',
            'ris_id',
            'CASCADE'
        );

        $this->addForeignIfMissing(
            'ris_attachments_table',
            'fk_ris_attachment_uploaded_by',
            'ris_attachment_uploaded_by',
            'users_table',
            'user_id'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ris_attachments_table');

        Schema::table('requisition_issue_slip_table', function (Blueprint $table) {

            foreach ([
                'fk_ris_submitted_by',
            ] as $foreignKey) {

                try {
                    $table->dropForeign($foreignKey);
                } catch (Throwable $exception) {
                    //
                }
            }

            foreach ([
                'idx_ris_status',
                'idx_ris_submitted_at',
            ] as $indexName) {

                try {
                    $table->dropIndex($indexName);
                } catch (Throwable $exception) {
                    //
                }
            }

            foreach ([
                'ris_submitted_by',
                'ris_submitted_at',
                'ris_rejection_reason',
                'ris_updated_at',
            ] as $column) {

                if (Schema::hasColumn('requisition_issue_slip_table', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addIndexIfMissing(
        string $table,
        string $indexName,
        string $column
    ): void {

        $exists = collect(
            DB::select(
                "SHOW INDEX FROM {$table} WHERE Key_name = ?",
                [$indexName]
            )
        )->isNotEmpty();

        if (!$exists) {
            DB::statement(
                "ALTER TABLE {$table} ADD INDEX {$indexName} ({$column})"
            );
        }
    }

    private function addForeignIfMissing(
        string $table,
        string $foreignName,
        string $column,
        string $referencesTable,
        string $referencesColumn,
        ?string $onDelete = null
    ): void {

        $exists = collect(DB::select(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?',
            [$table, $foreignName]
        ))->isNotEmpty();

        if ($exists) {
            return;
        }

        $sql = "ALTER TABLE {$table}
                ADD CONSTRAINT {$foreignName}
                FOREIGN KEY ({$column})
                REFERENCES {$referencesTable} ({$referencesColumn})";

        $sql .= $onDelete
            ? " ON DELETE {$onDelete}"
            : " ON DELETE SET NULL";

        DB::statement($sql);
    }
};
