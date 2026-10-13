<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reporters_table') && ! Schema::hasColumn('reporters_table', 'reporter_pin_hash')) {
            Schema::table('reporters_table', function (Blueprint $table) {
                // Only the bcrypt hash is stored; the plain PIN is shown once when it is generated or set.
                $table->string('reporter_pin_hash', 255)->nullable();
                $table->timestamp('reporter_pin_set_at')->nullable();
            });
        }

        if (! Schema::hasTable('reporter_pin_resets')) {
            Schema::create('reporter_pin_resets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reporter_id')->index();
                $table->string('token_hash', 64)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reporter_pin_resets');

        if (Schema::hasTable('reporters_table') && Schema::hasColumn('reporters_table', 'reporter_pin_hash')) {
            Schema::table('reporters_table', function (Blueprint $table) {
                $table->dropColumn(['reporter_pin_hash', 'reporter_pin_set_at']);
            });
        }
    }
};
