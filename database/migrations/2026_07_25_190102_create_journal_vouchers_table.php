<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 40)->unique()->comment('System-generated voucher number');
            $table->date('document_date')->index();
            $table->unsignedBigInteger('financial_year_id')->nullable()->index();
            $table->string('voucher_type', 20)->default('journal')->index()->comment('journal|sales|purchase|receipt|payment|contra');
            $table->string('status', 20)->default('draft')->index();
            $table->string('reference_no', 60)->nullable()->comment('Cheque / UTR / source document reference');
            $table->string('source_type', 100)->nullable()->comment('Source document class for auto-posted vouchers');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('total_debit', 18, 2)->default(0.00);
            $table->decimal('total_credit', 18, 2)->default(0.00);
            $table->text('narration')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->unsignedBigInteger('posted_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'document_date']);
            $table->index(['source_type', 'source_id'], 'jv_source_index');
            $table->foreign('financial_year_id', 'jv_fy_fk')->references('id')->on('financial_years')->nullOnDelete();
            $table->foreign('posted_by', 'jv_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'jv_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'jv_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('journal_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_voucher_id')->index();
            $table->unsignedBigInteger('ledger_account_id')->index();
            $table->unsignedBigInteger('party_id')->nullable()->index()->comment('Sub-ledger party for AR/AP lines');
            $table->decimal('debit', 18, 2)->default(0.00);
            $table->decimal('credit', 18, 2)->default(0.00);
            $table->string('narration', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('journal_voucher_id', 'jvl_voucher_fk')->references('id')->on('journal_vouchers')->cascadeOnDelete();
            $table->foreign('ledger_account_id', 'jvl_account_fk')->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->foreign('party_id', 'jvl_party_fk')->references('id')->on('parties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_voucher_lines');
        Schema::dropIfExists('journal_vouchers');
    }
};
