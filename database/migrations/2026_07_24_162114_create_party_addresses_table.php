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
        Schema::create('party_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('address_type', 20)->index()->comment('billing|shipping|factory');
            $table->string('label', 100)->nullable();
            $table->string('line1', 150);
            $table->string('line2', 150)->nullable();
            $table->string('city', 100);
            $table->foreignId('state_id')->constrained('states')->restrictOnDelete();
            $table->string('pin_code', 6);
            $table->string('country', 100)->default('India');
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();

            $table->index(['party_id', 'address_type', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_addresses');
    }
};
