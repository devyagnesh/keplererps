<?php

namespace Tests\Feature\Admin;

use App\Enums\GstType;
use App\Enums\PartyType;
use App\Jobs\ProcessPartyImportJob;
use App\Models\PartyImport;
use App\Models\User;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for party CSV import.
 */
class PartyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_can_be_downloaded(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->get(route('admin.parties.import.template'))
            ->assertOk();
    }

    public function test_preview_and_commit_import(): void
    {
        Storage::fake('local');
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();

        $csv = implode("\n", [
            'party_name,party_type,gst_type,gstin,pan,billing_line1,billing_line2,billing_city,billing_state_code,billing_pin_code,billing_country,credit_limit,unlimited_credit,credit_days,status,contact_name,contact_mobile,contact_email,contact_designation,whatsapp_opt_in',
            'Valid Party,'.PartyType::Customer->value.','.GstType::Unregistered->value.',,,Line 1,,Ahmedabad,24,380001,India,0,0,30,active,Ramesh,9876543210,a@b.com,Owner,1',
            'Bad Party,'.PartyType::Customer->value.','.GstType::Unregistered->value.',,,Line 1,,Ahmedabad,24,000001,India,0,0,30,active,Ramesh,123,a@b.com,Owner,0',
        ]);

        $file = UploadedFile::fake()->createWithContent('parties.csv', $csv);

        $preview = $this->actingAs($user)
            ->postJson(route('admin.parties.import.preview'), ['file' => $file])
            ->assertOk()
            ->assertJsonPath('status', true);

        $importId = $preview->json('data.id');
        $import = PartyImport::query()->findOrFail($importId);
        $this->assertSame(1, $import->valid_rows);
        $this->assertSame(1, $import->invalid_rows);

        Queue::fake();

        $this->actingAs($user)
            ->postJson(route('admin.parties.import.commit', $import))
            ->assertOk()
            ->assertJsonPath('status', true);

        Queue::assertPushed(ProcessPartyImportJob::class);
    }
}
