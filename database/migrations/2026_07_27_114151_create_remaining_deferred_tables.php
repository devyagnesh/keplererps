<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deferred remaining: DomPDF path, multi-step approvals, industry packs, GSP logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_shares') && ! Schema::hasColumn('document_shares', 'pdf_storage_path')) {
            Schema::table('document_shares', function (Blueprint $table): void {
                $table->string('pdf_storage_path', 255)->nullable()->after('storage_path')
                    ->comment('DomPDF binary snapshot path');
            });
        }

        if (Schema::hasTable('approval_rules')) {
            Schema::table('approval_rules', function (Blueprint $table): void {
                if (! Schema::hasColumn('approval_rules', 'approval_mode')) {
                    $table->string('approval_mode', 20)->default('sequential')->after('approver_permission')
                        ->comment('sequential|parallel');
                }
                if (! Schema::hasColumn('approval_rules', 'escalation_hours')) {
                    $table->unsignedSmallInteger('escalation_hours')->nullable()->after('approval_mode')
                        ->comment('Escalate pending approval after N hours');
                }
                if (! Schema::hasColumn('approval_rules', 'auto_approve_below')) {
                    $table->decimal('auto_approve_below', 15, 2)->nullable()->after('escalation_hours')
                        ->comment('Skip workflow when amount is below this');
                }
                if (! Schema::hasColumn('approval_rules', 'steps')) {
                    $table->json('steps')->nullable()->after('auto_approve_below')
                        ->comment('Ordered approver permissions [{permission,label}]');
                }
            });
        }

        if (! Schema::hasTable('document_approval_actions')) {
            Schema::create('document_approval_actions', function (Blueprint $table): void {
                $table->id();
                $table->string('document_type', 60)->index();
                $table->unsignedBigInteger('document_id')->index();
                $table->foreignId('approval_rule_id')->constrained('approval_rules')->cascadeOnDelete();
                $table->unsignedTinyInteger('step_index')->default(0)->comment('Zero-based step in rule.steps');
                $table->string('required_permission', 100);
                $table->string('status', 20)->default('pending')->index()->comment('pending|approved|escalated|skipped');
                $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('acted_at')->nullable();
                $table->timestamp('due_at')->nullable()->index();
                $table->timestamp('escalated_at')->nullable();
                $table->string('remarks', 255)->nullable();
                $table->timestamps();

                $table->index(['document_type', 'document_id', 'status'], 'doc_approval_doc_status_idx');
            });
        }

        if (! Schema::hasTable('industry_profiles')) {
            Schema::create('industry_profiles', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name', 120);
                $table->json('modules')->nullable()->comment('Feature flags map');
                $table->json('uom')->nullable();
                $table->json('costing')->nullable();
                $table->json('item_attributes')->nullable();
                $table->json('qc_templates')->nullable();
                $table->json('reports')->nullable();
                $table->json('print_templates')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('gsp_filing_logs')) {
            Schema::create('gsp_filing_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('return_type', 20)->index()->comment('gstr1|gstr3b');
                $table->date('period_from');
                $table->date('period_to');
                $table->string('status', 20)->default('queued')->index()->comment('queued|dry_run|pushed|failed');
                $table->unsignedInteger('row_count')->default(0);
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['period_from', 'period_to']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gsp_filing_logs');
        Schema::dropIfExists('industry_profiles');
        Schema::dropIfExists('document_approval_actions');

        if (Schema::hasTable('approval_rules')) {
            Schema::table('approval_rules', function (Blueprint $table): void {
                foreach (['steps', 'auto_approve_below', 'escalation_hours', 'approval_mode'] as $column) {
                    if (Schema::hasColumn('approval_rules', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('document_shares') && Schema::hasColumn('document_shares', 'pdf_storage_path')) {
            Schema::table('document_shares', function (Blueprint $table): void {
                $table->dropColumn('pdf_storage_path');
            });
        }
    }
};
