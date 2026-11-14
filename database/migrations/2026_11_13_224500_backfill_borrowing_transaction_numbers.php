<?php

use App\Support\BorrowingSlipNumber;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        BorrowingSlipNumber::backfillMissing();
    }

    public function down(): void
    {
        // Slip numbers assigned to older borrows stay in place.
    }
};
