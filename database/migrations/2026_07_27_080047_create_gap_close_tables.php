<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema for remaining module gap-close slices (M05–M17 / M16 C1–C3).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price_lists')) {
            Schema::create('price_lists', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 30)->unique();
                $table->string('name', 100);
                $table->date('valid_from')->nullable();
                $table->date('valid_to')->nullable();
                $table->boolean('is_default')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('price_list_items')) {
            Schema::create('price_list_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
                $table->decimal('min_qty', 15, 4)->default(1);
                $table->decimal('rate', 15, 4);
                $table->timestamps();

                $table->unique(['price_list_id', 'item_id', 'min_qty'], 'price_list_item_qty_uq');
            });
        }

        if (! Schema::hasTable('party_price_lists')) {
            Schema::create('party_price_lists', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->foreignId('price_list_id')->constrained('price_lists')->cascadeOnDelete();
                $table->unsignedSmallInteger('priority')->default(1);
                $table->timestamps();

                $table->unique(['party_id', 'price_list_id']);
            });
        }

        if (! Schema::hasTable('supplier_ratings')) {
            Schema::create('supplier_ratings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
                $table->date('period_from');
                $table->date('period_to');
                $table->unsignedInteger('po_count')->default(0);
                $table->unsignedInteger('on_time_count')->default(0);
                $table->unsignedInteger('qc_fail_count')->default(0);
                $table->decimal('otif_score', 5, 2)->default(0);
                $table->decimal('quality_score', 5, 2)->default(0);
                $table->decimal('overall_score', 5, 2)->default(0);
                $table->timestamp('computed_at')->nullable();
                $table->timestamps();

                $table->unique(['party_id', 'period_from', 'period_to'], 'supplier_rating_period_uq');
            });
        }

        if (! Schema::hasTable('stock_takes')) {
            Schema::create('stock_takes', function (Blueprint $table): void {
                $table->id();
                $table->string('document_no', 50)->unique();
                $table->unsignedBigInteger('warehouse_id');
                $table->string('status', 20)->default('draft')->index();
                $table->date('document_date');
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('remarks', 255)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('warehouse_id', 'stock_takes_warehouse_id_fk')
                    ->references('id')
                    ->on('warehouses');
                $table->index('warehouse_id', 'stock_takes_warehouse_id_idx');
            });
        }

        if (! Schema::hasTable('stock_take_lines')) {
            Schema::create('stock_take_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('stock_take_id')->constrained('stock_takes')->cascadeOnDelete();
                $table->foreignId('item_id')->constrained('items');
                $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
                $table->decimal('system_qty', 15, 4)->default(0);
                $table->decimal('counted_qty', 15, 4)->default(0);
                $table->decimal('variance_qty', 15, 4)->default(0);
                $table->string('scanned_code', 100)->nullable();
                $table->timestamps();

                $table->unique(['stock_take_id', 'item_id', 'batch_id'], 'stock_take_line_uq');
                $table->index('item_id', 'stock_take_lines_item_id_idx');
            });
        }

        if (! Schema::hasTable('package_label_reprints')) {
            Schema::create('package_label_reprints', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('package_label_id')->constrained('package_labels')->cascadeOnDelete();
                $table->foreignId('reprinted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason', 255)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('custom_field_definitions')) {
            Schema::create('custom_field_definitions', function (Blueprint $table): void {
                $table->id();
                $table->string('entity_type', 60)->index()->comment('Model morph alias, e.g. party, item, lead');
                $table->string('field_key', 60);
                $table->string('label', 120);
                $table->string('field_type', 20)->default('text')->comment('text|number|date|select|boolean');
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['entity_type', 'field_key']);
            });
        }

        if (! Schema::hasTable('custom_field_values')) {
            Schema::create('custom_field_values', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('custom_field_definition_id')->constrained('custom_field_definitions')->cascadeOnDelete();
                $table->string('entity_type', 120);
                $table->unsignedBigInteger('entity_id');
                $table->text('value')->nullable();
                $table->timestamps();

                $table->unique(['custom_field_definition_id', 'entity_type', 'entity_id'], 'custom_field_value_uq');
                $table->index(['entity_type', 'entity_id']);
            });
        }

        if (! Schema::hasTable('approval_rules')) {
            Schema::create('approval_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('document_type', 60)->index()->comment('sales_order, purchase_order, etc.');
                $table->string('condition_field', 60)->default('grand_total');
                $table->string('condition_operator', 10)->default('gte');
                $table->decimal('condition_value', 15, 2)->default(0);
                $table->string('approver_permission', 100)->comment('Permission required to approve');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('batches') && ! Schema::hasColumn('batches', 'recall_reason')) {
            Schema::table('batches', function (Blueprint $table): void {
                $table->timestamp('recalled_at')->nullable()->after('is_active');
                $table->string('recall_reason', 255)->nullable()->after('recalled_at');
                $table->foreignId('recalled_by')->nullable()->after('recall_reason')->constrained('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('qc_inspections') && ! Schema::hasColumn('qc_inspections', 'public_token')) {
            Schema::table('qc_inspections', function (Blueprint $table): void {
                $table->string('public_token', 64)->nullable()->unique()->after('document_no');
            });
        }

        if (Schema::hasTable('work_order_operations') && ! Schema::hasColumn('work_order_operations', 'requires_qc')) {
            Schema::table('work_order_operations', function (Blueprint $table): void {
                $table->boolean('requires_qc')->default(false)->after('sequence');
            });
        }

        if (Schema::hasTable('qc_inspection_readings') && ! Schema::hasColumn('qc_inspection_readings', 'defect_reason_id')) {
            Schema::table('qc_inspection_readings', function (Blueprint $table): void {
                $table->foreignId('defect_reason_id')->nullable()->after('result')->constrained('defect_reasons')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('qc_inspection_readings') && Schema::hasColumn('qc_inspection_readings', 'defect_reason_id')) {
            Schema::table('qc_inspection_readings', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('defect_reason_id');
            });
        }

        if (Schema::hasTable('work_order_operations') && Schema::hasColumn('work_order_operations', 'requires_qc')) {
            Schema::table('work_order_operations', function (Blueprint $table): void {
                $table->dropColumn('requires_qc');
            });
        }

        if (Schema::hasTable('qc_inspections') && Schema::hasColumn('qc_inspections', 'public_token')) {
            Schema::table('qc_inspections', function (Blueprint $table): void {
                $table->dropUnique(['public_token']);
                $table->dropColumn('public_token');
            });
        }

        if (Schema::hasTable('batches') && Schema::hasColumn('batches', 'recall_reason')) {
            Schema::table('batches', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('recalled_by');
                $table->dropColumn(['recalled_at', 'recall_reason']);
            });
        }

        Schema::dropIfExists('approval_rules');
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_field_definitions');
        Schema::dropIfExists('package_label_reprints');
        Schema::dropIfExists('stock_take_lines');
        Schema::dropIfExists('stock_takes');
        Schema::dropIfExists('supplier_ratings');
        Schema::dropIfExists('party_price_lists');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
    }
};
