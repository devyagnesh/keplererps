<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receipt/payment allocations and accounting period locks (M13 gap-close).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_voucher_id')->constrained('journal_vouchers')->cascadeOnDelete();
            $table->string('allocatable_type', 120);
            $table->unsignedBigInteger('allocatable_id');
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->index(['allocatable_type', 'allocatable_id'], 'voucher_alloc_doc_idx');
            $table->index(['journal_voucher_id', 'allocatable_type']);
        });

        Schema::create('period_locks', function (Blueprint $table): void {
            $table->id();
            $table->date('locked_to')->unique()->comment('Documents on or before this date are locked');
            $table->string('reason', 255)->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_locks');
        Schema::dropIfExists('voucher_allocations');
    }
};
