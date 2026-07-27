<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Work centre / machine / mould / die asset register (M04 costing + M11).
 */
class WorkCentre extends Model
{
    /** @use HasFactory<\Database\Factories\WorkCentreFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'asset_type',
        'status',
        'make_model',
        'serial_no',
        'purchase_date',
        'purchase_value',
        'location',
        'department',
        'capacity',
        'machine_rate_per_hour',
        'labour_rate_per_hour',
        'cavity_count',
        'cycle_time_seconds',
        'life_cycles',
        'cycles_used',
        'running_hours',
        'service_interval_days',
        'service_interval_hours',
        'service_interval_cycles',
        'last_service_at',
        'cycles_at_last_service',
        'hours_at_last_service',
        'next_service_due_on',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'asset_type' => AssetType::class,
            'status' => AssetStatus::class,
            'purchase_date' => 'date',
            'purchase_value' => 'decimal:2',
            'machine_rate_per_hour' => 'decimal:2',
            'labour_rate_per_hour' => 'decimal:2',
            'cycle_time_seconds' => 'decimal:2',
            'running_hours' => 'decimal:2',
            'service_interval_hours' => 'decimal:2',
            'hours_at_last_service' => 'decimal:2',
            'last_service_at' => 'datetime',
            'next_service_due_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function maintenanceOrders(): HasMany
    {
        return $this->hasMany(MaintenanceOrder::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Whether preventive maintenance is at or past the alert threshold (default 90%).
     */
    public function isMaintenanceDue(float $thresholdPercent = 90.0): bool
    {
        $ratio = $this->maintenanceUsageRatio();

        return $ratio !== null && $ratio >= ($thresholdPercent / 100);
    }

    /**
     * Highest usage ratio across configured interval dimensions (0–1+), or null if none configured.
     */
    public function maintenanceUsageRatio(): ?float
    {
        $ratios = [];

        if ($this->service_interval_days && $this->last_service_at) {
            $daysSince = max(0, $this->last_service_at->diffInDays(now()));
            $ratios[] = $daysSince / max(1, (int) $this->service_interval_days);
        } elseif ($this->service_interval_days && $this->next_service_due_on) {
            $total = max(1, (int) $this->service_interval_days);
            $remaining = now()->startOfDay()->diffInDays($this->next_service_due_on, false);
            $ratios[] = 1 - ($remaining / $total);
        }

        if ($this->service_interval_hours && (float) $this->service_interval_hours > 0) {
            $hoursSince = max(0, (float) $this->running_hours - (float) $this->hours_at_last_service);
            $ratios[] = $hoursSince / (float) $this->service_interval_hours;
        }

        if ($this->service_interval_cycles && (int) $this->service_interval_cycles > 0) {
            $cyclesSince = max(0, (int) $this->cycles_used - (int) $this->cycles_at_last_service);
            $ratios[] = $cyclesSince / (int) $this->service_interval_cycles;
        }

        if ($ratios === []) {
            return null;
        }

        return max($ratios);
    }
}
