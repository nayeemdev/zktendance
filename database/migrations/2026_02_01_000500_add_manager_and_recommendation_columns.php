<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('recommended_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable()->after('recommended_by');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recommended_by');
            $table->dropColumn('recommended_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
