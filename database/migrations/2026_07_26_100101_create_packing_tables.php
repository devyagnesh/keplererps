<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Packing masters and scannable package labels (M17).
 *
 * Idempotent: a previous partial run may have left packing_units without finishing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packing_units')) {
            Schema::create('packing_units', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 30)->unique()->comment('Short packing unit code, e.g. BOX-50');
                $table->string('name', 100);
                $table->foreignId('item_id')->nullable()
                    ->comment('Null for a generic packing unit usable by any item')
                    ->constrained('items')->nullOnDelete();
                $table->foreignId('parent_id')->nullable()
                    ->comment('Nesting parent, e.g. a carton holding boxes')
                    ->constrained('packing_units')->nullOnDelete();
                $table->foreignId('uom_id')->constrained('uoms');
                $table->decimal('quantity', 15, 4)->default(1.0000)
                    ->comment('Quantity of the parent unit, or of the base UOM when there is no parent');
                $table->boolean('is_active')->default(true)->index();
                $table->string('remarks', 255)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['item_id', 'is_active']);
            });
        } elseif (! $this->hasForeignKey('packing_units', 'packing_units_uom_id_foreign')) {
            Schema::table('packing_units', function (Blueprint $table): void {
                $table->foreign('uom_id', 'packing_units_uom_id_foreign')
                    ->references('id')
                    ->on('uoms');
            });
        }

        if (! Schema::hasTable('package_labels')) {
            Schema::create('package_labels', function (Blueprint $table): void {
                $table->id();
                $table->string('label_no', 50)->unique()->comment('System-generated package number printed on the label');
                $table->string('qr_payload', 255)->comment('Pipe-delimited payload encoded into the QR code');
                $table->foreignId('packing_unit_id')->constrained('packing_units');
                $table->foreignId('item_id')->constrained('items');
                $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses');
                $table->foreignId('delivery_challan_id')->nullable()->constrained('delivery_challans')->nullOnDelete();
                $table->foreignId('delivery_challan_item_id')->nullable()->constrained('delivery_challan_items')->nullOnDelete();
                $table->decimal('quantity', 15, 4)->comment('Contents in the item stock UOM');
                $table->string('status', 20)->default('packed')->index()->comment('PackageStatus enum value');
                $table->timestamp('packed_at')->nullable();
                $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['delivery_challan_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_labels');
        Schema::dropIfExists('packing_units');
    }

    /**
     * Whether a named foreign key already exists on a table.
     */
    protected function hasForeignKey(string $table, string $constraint): bool
    {
        $database = Schema::getConnection()->getDatabaseName();

        $row = Schema::getConnection()->selectOne(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS
             where TABLE_SCHEMA = ? and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = ?',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};
