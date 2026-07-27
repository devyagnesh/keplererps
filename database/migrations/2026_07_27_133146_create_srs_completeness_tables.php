<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SRS completeness tables: indents, bank recon, leave, GSTR-2B, backups.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_indents')) {
            Schema::create('purchase_indents', function (Blueprint $table): void {
                $table->id();
                $table->string('document_no', 50)->unique();
                $table->date('document_date')->index();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->string('status', 30)->default('draft')->index();
                $table->string('remarks', 500)->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('purchase_indent_items')) {
            Schema::create('purchase_indent_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchase_indent_id')->constrained('purchase_indents')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
                $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->decimal('ordered_qty', 18, 4)->default(0);
                $table->string('source', 40)->nullable()->comment('reorder|shortage|manual');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['purchase_indent_id', 'item_id']);
            });
        }

        if (Schema::hasTable('journal_voucher_lines')) {
            Schema::table('journal_voucher_lines', function (Blueprint $table): void {
                if (! Schema::hasColumn('journal_voucher_lines', 'reconciled_at')) {
                    $table->timestamp('reconciled_at')->nullable()->after('sort_order')->index();
                }
                if (! Schema::hasColumn('journal_voucher_lines', 'bank_date')) {
                    $table->date('bank_date')->nullable()->after('reconciled_at')->index();
                }
                if (! Schema::hasColumn('journal_voucher_lines', 'reconciled_by')) {
                    $table->foreignId('reconciled_by')->nullable()->after('bank_date')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table): void {
                $table->id();
                $table->date('holiday_date')->unique();
                $table->string('name', 120);
                $table->boolean('is_optional')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leave_balances')) {
            Schema::create('leave_balances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->unsignedSmallInteger('year')->index();
                $table->string('leave_type', 40)->default('paid')->comment('paid|casual|sick');
                $table->decimal('opening_days', 8, 2)->default(0);
                $table->decimal('availed_days', 8, 2)->default(0);
                $table->timestamps();

                $table->unique(['employee_id', 'year', 'leave_type'], 'leave_balances_emp_year_type_uq');
            });
        }

        if (! Schema::hasTable('gstr2b_imports')) {
            Schema::create('gstr2b_imports', function (Blueprint $table): void {
                $table->id();
                $table->string('period', 7)->index()->comment('YYYY-MM');
                $table->string('original_filename', 255);
                $table->string('storage_path', 255);
                $table->unsignedInteger('row_count')->default(0);
                $table->unsignedInteger('matched_count')->default(0);
                $table->unsignedInteger('mismatch_count')->default(0);
                $table->json('summary')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('backup_logs')) {
            Schema::create('backup_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('disk_path', 500);
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('status', 20)->default('ready')->index();
                $table->string('notes', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('gstr2b_imports');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('purchase_indent_items');
        Schema::dropIfExists('purchase_indents');

        if (Schema::hasTable('journal_voucher_lines')) {
            Schema::table('journal_voucher_lines', function (Blueprint $table): void {
                if (Schema::hasColumn('journal_voucher_lines', 'reconciled_by')) {
                    $table->dropConstrainedForeignId('reconciled_by');
                }
                foreach (['bank_date', 'reconciled_at'] as $column) {
                    if (Schema::hasColumn('journal_voucher_lines', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
