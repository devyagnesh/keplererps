<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Account code used by control-account settings');
            $table->string('name', 150);
            $table->string('account_type', 20)->index()->comment('asset|liability|equity|income|expense');
            $table->string('account_group', 100)->nullable()->comment('Reporting group shown on statements');
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('party_id')->nullable()->index()->comment('Set for customer/supplier sub-ledgers');
            $table->decimal('opening_balance', 18, 2)->default(0.00);
            $table->string('opening_balance_side', 10)->default('debit')->comment('debit|credit');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false)->comment('Seeded control account; cannot be deleted');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_type', 'is_active']);
            $table->foreign('parent_id', 'ledger_parent_fk')->references('id')->on('ledger_accounts')->nullOnDelete();
            $table->foreign('party_id', 'ledger_party_fk')->references('id')->on('parties')->cascadeOnDelete();
            $table->foreign('created_by', 'ledger_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'ledger_updated_by_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_accounts');
    }
};
