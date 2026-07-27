<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Final SRS completeness: RFQ, dual UOM columns, statutory fields, scheduled reports, scans, e-invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_rfqs')) {
            Schema::create('purchase_rfqs', function (Blueprint $table): void {
                $table->id();
                $table->string('document_no', 50)->unique();
                $table->date('document_date')->index();
                $table->date('valid_until')->nullable();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('purchase_indent_id')->nullable()->constrained('purchase_indents')->nullOnDelete();
                $table->string('status', 30)->default('draft')->index();
                $table->string('remarks', 500)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('purchase_rfq_items')) {
            Schema::create('purchase_rfq_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchase_rfq_id')->constrained('purchase_rfqs')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
                $table->foreignId('uom_id')->constrained('uoms')->restrictOnDelete();
                $table->decimal('quantity', 18, 4);
                $table->decimal('base_qty', 18, 4)->nullable()->comment('Qty in stock UOM');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('purchase_rfq_quotes')) {
            Schema::create('purchase_rfq_quotes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchase_rfq_id')->constrained('purchase_rfqs')->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained('parties')->restrictOnDelete();
                $table->date('quote_date')->nullable();
                $table->decimal('freight_amount', 15, 2)->default(0);
                $table->unsignedSmallInteger('lead_time_days')->nullable();
                $table->boolean('is_selected')->default(false)->index();
                $table->string('award_reason', 255)->nullable();
                $table->string('remarks', 500)->nullable();
                $table->timestamps();

                $table->unique(['purchase_rfq_id', 'supplier_id'], 'rfq_supplier_uq');
            });
        }

        if (! Schema::hasTable('purchase_rfq_quote_items')) {
            Schema::create('purchase_rfq_quote_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('purchase_rfq_quote_id')->constrained('purchase_rfq_quotes')->cascadeOnDelete();
                $table->foreignId('purchase_rfq_item_id')->constrained('purchase_rfq_items')->cascadeOnDelete();
                $table->decimal('rate', 15, 4)->default(0);
                $table->decimal('gst_rate', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['purchase_rfq_quote_id', 'purchase_rfq_item_id'], 'rfq_quote_item_uq');
            });
        }

        foreach (['purchase_order_items', 'sales_order_items', 'purchase_indent_items'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'base_qty')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                    $table->decimal('base_qty', 18, 4)->nullable()->after('quantity')
                        ->comment('Quantity converted to stock UOM');
                    if ($tableName !== 'purchase_indent_items' && ! Schema::hasColumn($tableName, 'secondary_uom_id')) {
                        // secondary optional later
                    }
                });
            }
        }

        if (Schema::hasTable('purchase_orders') && ! Schema::hasColumn('purchase_orders', 'purchase_indent_id')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                $table->foreignId('purchase_indent_id')->nullable()->after('warehouse_id')
                    ->constrained('purchase_indents')->nullOnDelete();
                $table->foreignId('purchase_rfq_id')->nullable()->after('purchase_indent_id')
                    ->constrained('purchase_rfqs')->nullOnDelete();
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table): void {
                if (! Schema::hasColumn('employees', 'pf_applicable')) {
                    $table->boolean('pf_applicable')->default(true)->after('overtime_rate_per_hour');
                }
                if (! Schema::hasColumn('employees', 'esi_applicable')) {
                    $table->boolean('esi_applicable')->default(false)->after('pf_applicable');
                }
                if (! Schema::hasColumn('employees', 'pt_state')) {
                    $table->string('pt_state', 40)->nullable()->after('esi_applicable');
                }
                if (! Schema::hasColumn('employees', 'piece_rate')) {
                    $table->decimal('piece_rate', 15, 4)->nullable()->after('pt_state');
                }
            });
        }

        if (Schema::hasTable('shifts') && ! Schema::hasColumn('shifts', 'ot_after_hours')) {
            Schema::table('shifts', function (Blueprint $table): void {
                $table->decimal('ot_after_hours', 5, 2)->nullable()->after('break_minutes')
                    ->comment('Hours after which OT starts; null = use durationHours');
                $table->decimal('ot_multiplier', 4, 2)->default(1.5)->after('ot_after_hours');
            });
        }

        if (! Schema::hasTable('scheduled_reports')) {
            Schema::create('scheduled_reports', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->string('register_key', 60)->index()->comment('sales|purchase|day-book|...');
                $table->string('frequency', 20)->default('daily')->index()->comment('daily|weekly|monthly');
                $table->string('recipient_emails', 500);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('last_run_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('scan_exceptions')) {
            Schema::create('scan_exceptions', function (Blueprint $table): void {
                $table->id();
                $table->string('scan_code', 64)->index();
                $table->string('context', 40)->default('package')->index();
                $table->string('reason', 60)->index()->comment('unknown|wrong_location|offline_replay|duplicate');
                $table->string('device_id', 80)->nullable();
                $table->json('payload')->nullable();
                $table->string('status', 20)->default('open')->index();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('einvoice_logs')) {
            Schema::create('einvoice_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
                $table->string('status', 20)->default('queued')->index();
                $table->string('irn', 64)->nullable()->index();
                $table->string('ack_no', 64)->nullable();
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dashboard_role_widgets')) {
            Schema::create('dashboard_role_widgets', function (Blueprint $table): void {
                $table->id();
                $table->string('role_name', 80)->index();
                $table->json('widget_keys');
                $table->timestamps();

                $table->unique('role_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_role_widgets');
        Schema::dropIfExists('einvoice_logs');
        Schema::dropIfExists('scan_exceptions');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('purchase_rfq_quote_items');
        Schema::dropIfExists('purchase_rfq_quotes');
        Schema::dropIfExists('purchase_rfq_items');
        Schema::dropIfExists('purchase_rfqs');

        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                if (Schema::hasColumn('purchase_orders', 'purchase_rfq_id')) {
                    $table->dropConstrainedForeignId('purchase_rfq_id');
                }
                if (Schema::hasColumn('purchase_orders', 'purchase_indent_id')) {
                    $table->dropConstrainedForeignId('purchase_indent_id');
                }
            });
        }

        foreach (['purchase_order_items', 'sales_order_items', 'purchase_indent_items'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'base_qty')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropColumn('base_qty');
                });
            }
        }
    }
};
