<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_basic')->default(false);
            $table->boolean('is_taxable')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->string('calculation');
            $table->decimal('value', 12, 2);
            $table->timestamps();
        });

        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_structure_id')->constrained();
            $table->decimal('gross_salary', 12, 2);
            $table->date('effective_from');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('overtime_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('min_minutes')->default(30);
            $table->unsignedSmallInteger('rounding_minutes')->default(15);
            $table->unsignedSmallInteger('max_daily_minutes')->default(240);
            $table->string('rate_base')->default('basic');
            $table->unsignedSmallInteger('monthly_hours_divisor')->default(208);
            $table->decimal('workday_multiplier', 4, 2)->default(2);
            $table->decimal('offday_multiplier', 4, 2)->default(2);
            $table->timestamps();
        });

        Schema::create('tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 14, 2)->nullable();
            $table->decimal('rate', 5, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('installment', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('start_month');
            $table->string('status')->default('active');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->string('type');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('tax_slabs');
        Schema::dropIfExists('overtime_rules');
        Schema::dropIfExists('employee_salaries');
        Schema::dropIfExists('salary_structure_items');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('salary_components');
    }
};
