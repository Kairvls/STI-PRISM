<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_signatures_table')) {
            return;
        }

        Schema::create('user_signatures_table', function (Blueprint $table) {
            $table->bigIncrements('user_signature_id');
            // users_table.user_id is signed BIGINT
            $table->bigInteger('user_id')->index();
            $table->string('user_signature_label', 120)->nullable();
            $table->string('user_signature_path', 500);
            $table->boolean('user_signature_is_default')->default(false);
            $table->timestamp('user_signature_created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users_table')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_signatures_table');
    }
};
