<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employee master, shifts, daily attendance and monthly payroll (M14).
 *
 * Uses hasTable guards so a partially applied run (tables created before the
 * migrations row was written) can finish cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 20)->unique()->comment('Short shift code, e.g. A, B, GEN');
                $table->string('name', 60);
                $table->time('start_time');
                $table->time('end_time')->comment('May be earlier than start_time for night shifts');
                $table->unsignedSmallInteger('break_minutes')->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table): void {
                $table->id();
                $table->string('employee_code', 30)->unique()->comment('System-generated employee number');
                $table->string('full_name', 120);
                $table->string('designation', 80)->nullable();
                $table->string('department', 80)->nullable();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('mobile', 20)->nullable();
                $table->string('email', 120)->nullable();
                $table->date('date_of_joining');
                $table->date('date_of_exit')->nullable();
                $table->string('status', 20)->default('active')->index()->comment('EmploymentStatus enum value');
                $table->decimal('monthly_gross', 15, 2)->default(0.00)->comment('Full monthly gross before attendance proration');
                $table->decimal('basic_percent', 5, 2)->default(50.00)->comment('Share of gross treated as basic pay');
                $table->decimal('fixed_deduction', 15, 2)->default(0.00)->comment('Recurring monthly deduction, e.g. advance recovery');
                $table->decimal('overtime_rate_per_hour', 15, 2)->default(0.00);
                $table->string('bank_account_no', 30)->nullable();
                $table->string('ifsc_code', 15)->nullable();
                $table->string('pan', 10)->nullable();
                $table->string('remarks', 255)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'date_of_joining']);
            });
        }

        if (! Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('attendance_date')->index();
                $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
                $table->string('status', 20)->default('present')->index()->comment('AttendanceStatus enum value');
                $table->decimal('worked_hours', 6, 2)->default(0.00);
                $table->decimal('overtime_hours', 6, 2)->default(0.00);
                $table->string('remarks', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['employee_id', 'attendance_date']);
                $table->index(['attendance_date', 'status']);
            });
        }

        if (! Schema::hasTable('salary_runs')) {
            Schema::create('salary_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('document_no', 50)->unique()->comment('System-generated payroll run number');
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month')->comment('1-12');
                $table->date('period_start');
                $table->date('period_end');
                $table->date('payment_date');
                $table->string('status', 20)->default('draft')->index()->comment('SalaryRunStatus enum value');
                $table->unsignedSmallInteger('employee_count')->default(0);
                $table->decimal('gross_total', 15, 2)->default(0.00);
                $table->decimal('deduction_total', 15, 2)->default(0.00);
                $table->decimal('net_total', 15, 2)->default(0.00);
                $table->string('remarks', 255)->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                // A period can be re-run after cancellation, so uniqueness of the open run is enforced in the service.
                $table->index(['period_year', 'period_month']);
            });
        }

        if (! Schema::hasTable('salary_slips')) {
            Schema::create('salary_slips', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('salary_run_id')->constrained('salary_runs')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->decimal('payable_days', 8, 2)->default(0.00)->comment('Sum of attendance payable fractions for the period');
                $table->unsignedSmallInteger('period_days')->default(0)->comment('Calendar days in the payroll period');
                $table->decimal('overtime_hours', 8, 2)->default(0.00);
                $table->decimal('basic_amount', 15, 2)->default(0.00);
                $table->decimal('allowance_amount', 15, 2)->default(0.00);
                $table->decimal('overtime_amount', 15, 2)->default(0.00);
                $table->decimal('gross_amount', 15, 2)->default(0.00);
                $table->decimal('deduction_amount', 15, 2)->default(0.00);
                $table->decimal('net_amount', 15, 2)->default(0.00);
                $table->string('remarks', 255)->nullable();
                $table->timestamps();

                $table->unique(['salary_run_id', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
        Schema::dropIfExists('salary_runs');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('shifts');
    }
};
