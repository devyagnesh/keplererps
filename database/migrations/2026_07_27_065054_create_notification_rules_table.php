<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catalogue of in-app notification rules (M16 shell polish).
 *
 * Rules describe which business event notifies which audience; WhatsApp/email
 * channels are stored for future use but only in_app is dispatched in v1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique()->comment('Stable key used by seeders and dispatch');
            $table->string('name', 120);
            $table->string('event', 60)->index()->comment('NotificationEvent value');
            $table->string('channel', 20)->default('in_app')->index()->comment('NotificationChannel value');
            $table->string('recipient_type', 20)->comment('role or permission');
            $table->string('recipient_value', 100)->comment('Role slug or permission name');
            $table->string('subject_template', 200);
            $table->string('body_template', 500);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_system')->default(false)->comment('Seeded rules cannot be deleted');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event', 'is_active', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
