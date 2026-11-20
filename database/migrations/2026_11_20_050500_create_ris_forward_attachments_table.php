<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ris_forward_attachments_table')) {
            Schema::create('ris_forward_attachments_table', function (Blueprint $table) {
                $table->bigIncrements('ris_forward_attachment_id');
                $table->unsignedBigInteger('ris_id');
                $table->string('ris_forward_attachment_name');
                $table->string('ris_forward_attachment_path', 500);
                $table->unsignedBigInteger('ris_forward_attachment_size')->nullable();
                $table->dateTime('ris_forward_attachment_created_at')->useCurrent();
                $table->index('ris_id', 'idx_ris_forward_attachment_ris_id');
            });
        }

        if (
            ! Schema::hasTable('requisition_issue_slip_table')
            || ! Schema::hasColumn('requisition_issue_slip_table', 'ris_forward_attachment_path')
        ) {
            return;
        }

        $existing = DB::table('requisition_issue_slip_table')
            ->whereNotNull('ris_forward_attachment_path')
            ->where('ris_forward_attachment_path', '!=', '')
            ->get(['ris_id', 'ris_forward_attachment_path', 'ris_forward_attachment_name']);

        foreach ($existing as $ris) {
            $path = trim((string) $ris->ris_forward_attachment_path);
            if ($path === '') {
                continue;
            }
            $already = DB::table('ris_forward_attachments_table')
                ->where('ris_id', $ris->ris_id)
                ->where('ris_forward_attachment_path', $path)
                ->exists();
            if ($already) {
                continue;
            }
            DB::table('ris_forward_attachments_table')->insert([
                'ris_id' => $ris->ris_id,
                'ris_forward_attachment_path' => $path,
                'ris_forward_attachment_name' => trim((string) ($ris->ris_forward_attachment_name ?? '')) ?: basename($path),
                'ris_forward_attachment_created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ris_forward_attachments_table');
    }
};
