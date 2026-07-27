<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand work centres into the M11 asset register.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_centres', function (Blueprint $table) {
            $table->string('asset_type', 20)->default('machine')->after('name')->index();
            $table->string('status', 30)->default('active')->after('asset_type')->index();
            $table->string('make_model', 150)->nullable()->after('status');
            $table->string('serial_no', 100)->nullable()->after('make_model');
            $table->date('purchase_date')->nullable()->after('serial_no');
            $table->decimal('purchase_value', 15, 2)->nullable()->after('purchase_date');
            $table->string('location', 150)->nullable()->after('purchase_value');
            $table->string('department', 100)->nullable()->after('location');
            $table->string('capacity', 100)->nullable()->after('department')->comment('Rated capacity description');
            $table->unsignedTinyInteger('cavity_count')->nullable()->after('capacity');
            $table->decimal('cycle_time_seconds', 12, 2)->nullable()->after('cavity_count');
            $table->unsignedBigInteger('life_cycles')->nullable()->after('cycle_time_seconds');
            $table->unsignedBigInteger('cycles_used')->default(0)->after('life_cycles');
            $table->decimal('running_hours', 12, 2)->default(0)->after('cycles_used');
            $table->unsignedInteger('service_interval_days')->nullable()->after('running_hours');
            $table->decimal('service_interval_hours', 12, 2)->nullable()->after('service_interval_days');
            $table->unsignedBigInteger('service_interval_cycles')->nullable()->after('service_interval_hours');
            $table->timestamp('last_service_at')->nullable()->after('service_interval_cycles');
            $table->unsignedBigInteger('cycles_at_last_service')->default(0)->after('last_service_at');
            $table->decimal('hours_at_last_service', 12, 2)->default(0)->after('cycles_at_last_service');
            $table->date('next_service_due_on')->nullable()->index()->after('hours_at_last_service');
            $table->text('notes')->nullable()->after('next_service_due_on');
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');

            $table->foreign('created_by', 'wc_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'wc_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_centres', function (Blueprint $table) {
            $table->dropForeign('wc_created_by_fk');
            $table->dropForeign('wc_updated_by_fk');
            $table->dropColumn([
                'asset_type',
                'status',
                'make_model',
                'serial_no',
                'purchase_date',
                'purchase_value',
                'location',
                'department',
                'capacity',
                'cavity_count',
                'cycle_time_seconds',
                'life_cycles',
                'cycles_used',
                'running_hours',
                'service_interval_days',
                'service_interval_hours',
                'service_interval_cycles',
                'last_service_at',
                'cycles_at_last_service',
                'hours_at_last_service',
                'next_service_due_on',
                'notes',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
