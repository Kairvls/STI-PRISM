<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reporter_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 100);
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('full_name', 255);
            $table->string('email', 255);
            $table->string('contact', 50);
            $table->string('employment_type', 50);
            $table->string('status', 30)->default('pending');
            $table->unsignedBigInteger('invite_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporter_approval_requests');
    }
};
