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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name', 150);
            $table->string('trade_name', 150)->nullable();
            $table->boolean('is_gst_registered')->default(false)->index();
            $table->string('gstin', 15)->nullable()->unique();
            $table->string('pan', 10)->index();
            $table->string('cin', 21)->nullable();
            $table->string('registered_address', 250);
            $table->foreignId('state_id')->constrained('states')->restrictOnDelete();
            $table->string('pin_code', 6);
            $table->string('phone', 20);
            $table->string('email', 100);
            $table->string('logo_path', 255)->nullable()->comment('Stored logo path under storage');
            $table->unsignedTinyInteger('fy_start_month')->default(4)->comment('Financial year start month 1-12');
            $table->unsignedTinyInteger('fy_start_day')->default(1)->comment('Financial year start day');
            $table->string('base_currency', 3)->default('INR');
            $table->unsignedTinyInteger('amount_decimals')->default(2);
            $table->unsignedTinyInteger('quantity_decimals')->default(3);
            $table->boolean('has_transactions')->default(false)->comment('Locks FY and currency once true');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
