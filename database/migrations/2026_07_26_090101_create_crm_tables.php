<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM pipeline: leads, opportunities and their follow-up log (M05).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('lead_no', 50)->unique()->comment('System-generated lead number');
            $table->date('lead_date')->index();
            $table->string('company_name', 150)->index();
            $table->string('contact_person', 120);
            $table->string('mobile', 20)->index();
            $table->string('email', 120)->nullable();
            $table->string('city', 80)->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('industry', 100)->nullable();
            $table->string('source', 30)->index()->comment('LeadSource enum value');
            $table->string('status', 20)->default('new')->index()->comment('LeadStatus enum value');
            $table->text('requirement')->nullable()->comment('Free-text enquiry summary');
            $table->decimal('estimated_value', 15, 2)->default(0.00);
            $table->date('next_follow_up_date')->nullable()->index();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->string('lost_reason', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'lead_date']);
            $table->index(['assigned_user_id', 'status']);
        });

        Schema::create('opportunities', function (Blueprint $table): void {
            $table->id();
            $table->string('opportunity_no', 50)->unique()->comment('System-generated opportunity number');
            $table->date('opportunity_date')->index();
            $table->string('title', 150);
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->string('stage', 20)->default('qualification')->index()->comment('OpportunityStage enum value');
            $table->decimal('expected_value', 15, 2)->default(0.00);
            $table->unsignedTinyInteger('probability_percent')->default(25);
            $table->date('expected_close_date')->nullable()->index();
            $table->date('next_follow_up_date')->nullable()->index();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('sales_quotations')->nullOnDelete();
            $table->string('lost_reason', 255)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'expected_close_date']);
        });

        Schema::create('crm_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->string('followupable_type', 120)->comment('Lead or Opportunity model class');
            $table->unsignedBigInteger('followupable_id');
            $table->date('follow_up_date')->index();
            $table->string('mode', 20)->comment('FollowUpMode enum value');
            $table->text('summary')->comment('What was discussed');
            $table->string('outcome', 255)->nullable();
            $table->date('next_follow_up_date')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['followupable_type', 'followupable_id'], 'crm_follow_ups_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_follow_ups');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
    }
};
