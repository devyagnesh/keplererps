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
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50)->index()->comment('industry|localisation|inventory|system');
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->string('value_type', 20)->default('string')->comment('string|integer|boolean|json');
            $table->string('label', 150);
            $table->boolean('is_locked')->default(false)->comment('Locked settings cannot be changed from UI');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
