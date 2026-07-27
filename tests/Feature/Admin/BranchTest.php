<?php

namespace Tests\Feature\Admin;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\StateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for branch master (M01).
 */
class BranchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Branches can be created via AJAX.
     */
    public function test_branch_can_be_created(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)
            ->postJson(route('admin.branches.store'), [
                'code' => 'HO01',
                'name' => 'Head Office',
                'is_head_office' => 1,
                'is_active' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('branches', [
            'code' => 'HO01',
            'name' => 'Head Office',
            'is_head_office' => 1,
        ]);
    }

    /**
     * DataTables endpoint returns JSON payload.
     */
    public function test_branch_datatable_returns_data(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        Branch::factory()->create(['code' => 'BR001', 'name' => 'West Branch']);

        $this->actingAs($user)
            ->postJson(route('admin.branches.data'), [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ])
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonFragment(['code' => 'BR001']);
    }

    /**
     * Branch with warehouses cannot be deleted.
     */
    public function test_branch_with_warehouses_cannot_be_deleted(): void
    {
        $this->seed(StateSeeder::class);
        $user = User::factory()->superAdmin()->create();
        $branch = Branch::factory()->create();
        $branch->warehouses()->create([
            'code' => 'PLANT1',
            'name' => 'Main Plant',
            'level' => 'plant',
            'depth' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('admin.branches.destroy', $branch))
            ->assertStatus(422);

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
    }
}
