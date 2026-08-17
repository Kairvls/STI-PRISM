<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporter_registration_invites', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporter_registration_invites');
    }
};
