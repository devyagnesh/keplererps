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
        Schema::create('hsn_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique()->comment('HSN 4/6/8 digits or SAC 6 digits');
            $table->string('code_type', 10)->default('hsn')->index()->comment('hsn|sac');
            $table->string('description', 255);
            $table->decimal('default_gst_rate', 5, 2)->default(18);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
