<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('accrual')->default('yearly')->after('days_per_year');
            $table->string('gender')->nullable()->after('allow_half_day');
            $table->boolean('is_encashable')->default(false)->after('gender');
        });

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->decimal('encashed', 5, 1)->default(0)->after('used');
        });

        Schema::create('leave_encashments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained();
            $table->foreignId('payroll_adjustment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('days', 5, 1);
            $table->decimal('amount', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_encashments');

        Schema::table('leave_balances', function (Blueprint $table) {
            $table->dropColumn('encashed');
        });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['accrual', 'gender', 'is_encashable']);
        });
    }
};
