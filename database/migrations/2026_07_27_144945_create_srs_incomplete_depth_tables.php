<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Close remaining SRS depth gaps: piece-rate, punch geo, package nesting, DOB, e-way logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salary_slips')) {
            Schema::table('salary_slips', function (Blueprint $table): void {
                if (! Schema::hasColumn('salary_slips', 'pieces')) {
                    $table->decimal('pieces', 18, 4)->default(0)->after('overtime_hours');
                }
                if (! Schema::hasColumn('salary_slips', 'piece_amount')) {
                    $table->decimal('piece_amount', 15, 2)->default(0)->after('overtime_amount');
                }
            });
        }

        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'piece_rate')) {
            Schema::table('items', function (Blueprint $table): void {
                $table->decimal('piece_rate', 15, 4)->nullable()->after('selling_price')
                    ->comment('Operator piece-rate for good production qty');
            });
        }

        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'date_of_birth')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->date('date_of_birth')->nullable()->after('date_of_joining');
            });
        }

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table): void {
                if (! Schema::hasColumn('attendance_records', 'source')) {
                    $table->string('source', 20)->default('manual')->after('remarks')->index();
                }
                if (! Schema::hasColumn('attendance_records', 'punch_in_at')) {
                    $table->timestamp('punch_in_at')->nullable()->after('source');
                }
                if (! Schema::hasColumn('attendance_records', 'punch_out_at')) {
                    $table->timestamp('punch_out_at')->nullable()->after('punch_in_at');
                }
                if (! Schema::hasColumn('attendance_records', 'latitude')) {
                    $table->decimal('latitude', 10, 7)->nullable()->after('punch_out_at');
                }
                if (! Schema::hasColumn('attendance_records', 'longitude')) {
                    $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                }
            });
        }

        if (Schema::hasTable('package_labels')) {
            Schema::table('package_labels', function (Blueprint $table): void {
                if (! Schema::hasColumn('package_labels', 'parent_package_label_id')) {
                    $table->foreignId('parent_package_label_id')->nullable()->after('packing_unit_id')
                        ->constrained('package_labels')->nullOnDelete();
                }
                if (! Schema::hasColumn('package_labels', 'secondary_quantity')) {
                    $table->decimal('secondary_quantity', 18, 4)->nullable()->after('quantity')
                        ->comment('Secondary UOM qty for dual-UOM items');
                }
            });
        }

        if (! Schema::hasTable('eway_submission_logs')) {
            Schema::create('eway_submission_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('delivery_challan_id')->constrained('delivery_challans')->cascadeOnDelete();
                $table->string('status', 20)->default('queued')->index();
                $table->string('eway_bill_number', 20)->nullable()->index();
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('install_locks')) {
            Schema::create('install_locks', function (Blueprint $table): void {
                $table->id();
                $table->string('install_key_hash', 64)->nullable();
                $table->boolean('is_installed')->default(false)->index();
                $table->timestamp('installed_at')->nullable();
                $table->string('app_version', 40)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('install_locks');
        Schema::dropIfExists('eway_submission_logs');

        if (Schema::hasTable('package_labels')) {
            Schema::table('package_labels', function (Blueprint $table): void {
                if (Schema::hasColumn('package_labels', 'parent_package_label_id')) {
                    $table->dropConstrainedForeignId('parent_package_label_id');
                }
                if (Schema::hasColumn('package_labels', 'secondary_quantity')) {
                    $table->dropColumn('secondary_quantity');
                }
            });
        }
    }
};
