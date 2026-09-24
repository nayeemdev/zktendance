<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtime_rules', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('is_enabled');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('overtime_status')->nullable()->after('overtime_minutes');
            $table->foreignId('overtime_reviewed_by')->nullable()->after('overtime_status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('overtime_reviewed_by');
            $table->dropColumn('overtime_status');
        });

        Schema::table('overtime_rules', function (Blueprint $table) {
            $table->dropColumn('requires_approval');
        });
    }
};
