<?php

namespace App\Services;

use App\Enums\InspectionType;
use App\Enums\QcParameterType;
use App\Enums\SamplingPlanType;
use App\Models\QcTemplate;
use App\Models\QcTemplateParameter;
use App\Repositories\Interfaces\QcTemplateRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * QC template CRUD (M10).
 */
class QcTemplateService
{
    public function __construct(protected QcTemplateRepositoryInterface $repository) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): QcTemplate
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): QcTemplate
    {
        return DB::transaction(function () use ($data): QcTemplate {
            $parameters = $data['parameters'] ?? [];
            unset($data['parameters']);
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            $template = $this->repository->create($data);
            $this->syncParameters($template, $parameters);

            return $this->repository->findById($template->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): QcTemplate
    {
        return DB::transaction(function () use ($id, $data): QcTemplate {
            $parameters = $data['parameters'] ?? [];
            unset($data['parameters']);
            $data['updated_by'] = Auth::id();
            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            $this->repository->update($id, $data);
            $template = $this->repository->findById($id);
            $template->parameters()->delete();
            $this->syncParameters($template, $parameters);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * Resolve best matching active template for item + stage.
     */
    public function resolveForItem(int $itemId, InspectionType $type, ?int $categoryId = null): ?QcTemplate
    {
        $itemMatch = QcTemplate::query()
            ->with('parameters')
            ->where('is_active', true)
            ->where('inspection_type', $type->value)
            ->where('item_id', $itemId)
            ->first();

        if ($itemMatch) {
            return $itemMatch;
        }

        if ($categoryId) {
            $categoryMatch = QcTemplate::query()
                ->with('parameters')
                ->where('is_active', true)
                ->where('inspection_type', $type->value)
                ->where('category_id', $categoryId)
                ->whereNull('item_id')
                ->first();

            if ($categoryMatch) {
                return $categoryMatch;
            }
        }

        return QcTemplate::query()
            ->with('parameters')
            ->where('is_active', true)
            ->where('inspection_type', $type->value)
            ->whereNull('item_id')
            ->whereNull('category_id')
            ->first();
    }

    public function suggestSampleSize(QcTemplate $template, float $lotQty): float
    {
        $lotQty = max(0, $lotQty);
        $size = match ($template->sampling_plan) {
            SamplingPlanType::Fixed => (float) ($template->sampling_value ?: 1),
            SamplingPlanType::Percentage => round($lotQty * ((float) ($template->sampling_value ?: 10) / 100), 4),
            SamplingPlanType::SqrtPlusOne => round(sqrt($lotQty) + 1, 4),
        };

        $size = max(1, $size);

        return min($size, $lotQty > 0 ? $lotQty : $size);
    }

    /** @param  list<array<string, mixed>>  $parameters */
    protected function syncParameters(QcTemplate $template, array $parameters): void
    {
        if ($parameters === []) {
            throw ValidationException::withMessages(['parameters' => 'Add at least one QC parameter.']);
        }

        foreach (array_values($parameters) as $index => $parameter) {
            if (empty($parameter['name'])) {
                continue;
            }
            $type = $parameter['parameter_type'] ?? QcParameterType::Numeric->value;
            QcTemplateParameter::query()->create([
                'qc_template_id' => $template->id,
                'name' => $parameter['name'],
                'parameter_type' => $type,
                'uom' => $parameter['uom'] ?? null,
                'min_value' => $parameter['min_value'] ?? null,
                'max_value' => $parameter['max_value'] ?? null,
                'target_value' => $parameter['target_value'] ?? null,
                'is_critical' => (bool) ($parameter['is_critical'] ?? false),
                'test_method' => $parameter['test_method'] ?? null,
                'sort_order' => $index,
            ]);
        }

        if ($template->parameters()->count() === 0) {
            throw ValidationException::withMessages(['parameters' => 'Add at least one QC parameter.']);
        }
    }
}
