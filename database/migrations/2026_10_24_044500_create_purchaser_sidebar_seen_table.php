<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchaser_sidebar_seen_table')) {
            return;
        }

        Schema::create('purchaser_sidebar_seen_table', function (Blueprint $table) {
            $table->id('seen_id');
            $table->unsignedBigInteger('seen_user_id');
            $table->string('seen_kind', 20);
            $table->unsignedBigInteger('seen_record_id');
            $table->string('seen_status', 40)->default('');
            $table->timestamp('seen_at')->nullable();

            $table->unique(['seen_user_id', 'seen_kind', 'seen_record_id'], 'purchaser_sidebar_seen_unique');
            $table->index(['seen_user_id', 'seen_kind'], 'purchaser_sidebar_seen_user_kind_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchaser_sidebar_seen_table');
    }
};
