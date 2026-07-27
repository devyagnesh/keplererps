<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remaining priority tables: print templates, document shares, terms, labels, portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('print_templates')) {
            Schema::create('print_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('document_type', 60)->index()->comment('sales_quotation, sales_invoice, purchase_order, delivery_challan');
                $table->string('header_html', 2000)->nullable();
                $table->string('footer_html', 2000)->nullable();
                $table->boolean('show_hsn')->default(true);
                $table->boolean('show_tax_breakup')->default(true);
                $table->boolean('is_default')->default(false)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('document_shares')) {
            Schema::create('document_shares', function (Blueprint $table): void {
                $table->id();
                $table->string('token', 64)->unique();
                $table->string('document_type', 60)->index();
                $table->unsignedBigInteger('document_id')->index();
                $table->string('document_no', 50)->nullable();
                $table->string('channel', 20)->default('link')->comment('link|whatsapp|email');
                $table->string('recipient', 40)->nullable();
                $table->string('storage_path', 255)->nullable();
                $table->string('public_url', 500)->nullable();
                $table->string('status', 20)->default('ready')->index();
                $table->json('meta')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('terms_blocks')) {
            Schema::create('terms_blocks', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->string('document_type', 60)->nullable()->index();
                $table->text('body');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('ui_labels')) {
            Schema::create('ui_labels', function (Blueprint $table): void {
                $table->id();
                $table->string('locale', 10)->default('en')->index();
                $table->string('label_key', 120);
                $table->string('label_value', 255);
                $table->timestamps();

                $table->unique(['locale', 'label_key'], 'ui_labels_locale_key_uq');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'party_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('party_id')->nullable()->after('branch_id')->constrained('parties')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'party_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('party_id');
            });
        }

        Schema::dropIfExists('ui_labels');
        Schema::dropIfExists('terms_blocks');
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('print_templates');
    }
};
