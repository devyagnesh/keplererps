<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statutory identifiers and biometric device code for employees (M14 depth).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('uan', 20)->nullable()->after('pan')->comment('EPFO Universal Account Number');
            $table->string('pf_number', 30)->nullable()->after('uan')->comment('PF member / establishment reference');
            $table->string('esi_number', 30)->nullable()->after('pf_number')->comment('ESIC insurance number');
            $table->string('aadhaar_last4', 4)->nullable()->after('esi_number')->comment('Last 4 digits only — never store full Aadhaar');
            $table->string('biometric_code', 40)->nullable()->after('aadhaar_last4')
                ->comment('Device punch ID used by biometric CSV import');

            $table->unique('biometric_code');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropUnique(['biometric_code']);
            $table->dropColumn(['uan', 'pf_number', 'esi_number', 'aadhaar_last4', 'biometric_code']);
        });
    }
};
