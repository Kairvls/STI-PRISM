<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_roles_table')) {
            Schema::create('user_roles_table', function (Blueprint $table) {
                $table->id('user_role_row_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamp('assigned_at')->useCurrent();

                $table->unique(['user_id', 'role_id']);
                $table->index('role_id');
            });
        }

        if (Schema::hasTable('users_table') && Schema::hasTable('user_roles_table')) {
            $users = DB::table('users_table')
                ->select('user_id', 'user_role_id')
                ->whereNotNull('user_role_id')
                ->get();

            foreach ($users as $user) {
                $exists = DB::table('user_roles_table')
                    ->where('user_id', $user->user_id)
                    ->where('role_id', $user->user_role_id)
                    ->exists();

                if (! $exists) {
                    DB::table('user_roles_table')->insert([
                        'user_id' => $user->user_id,
                        'role_id' => $user->user_role_id,
                        'assigned_at' => now(),
                    ]);
                }
            }

            // Maintenance accounts with procurement flag also get Purchaser role.
            if (Schema::hasColumn('users_table', 'user_can_procurement')) {
                $withProc = DB::table('users_table')
                    ->where('user_role_id', 2)
                    ->where('user_can_procurement', 1)
                    ->pluck('user_id');

                foreach ($withProc as $userId) {
                    $exists = DB::table('user_roles_table')
                        ->where('user_id', $userId)
                        ->where('role_id', 3)
                        ->exists();

                    if (! $exists) {
                        DB::table('user_roles_table')->insert([
                            'user_id' => $userId,
                            'role_id' => 3,
                            'assigned_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles_table');
    }
};
