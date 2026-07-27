<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('party_code', 30)->unique();
            $table->string('party_name', 150)->index();
            $table->string('party_type', 20)->index()->comment('customer|supplier|both');
            $table->string('gst_type', 20)->index();
            $table->string('gstin', 15)->nullable()->unique();
            $table->string('pan', 10)->nullable()->index();
            $table->string('billing_line1', 150);
            $table->string('billing_line2', 150)->nullable();
            $table->string('billing_city', 100);
            $table->foreignId('billing_state_id')->constrained('states')->restrictOnDelete();
            $table->string('billing_pin_code', 6);
            $table->string('billing_country', 100)->default('India');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->boolean('unlimited_credit')->default(false);
            $table->unsignedSmallInteger('credit_days')->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('bank_account_number', 18)->nullable();
            $table->string('bank_ifsc', 11)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('has_transactions')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['party_type', 'status']);
            $table->index(['party_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parties');
    }
};
