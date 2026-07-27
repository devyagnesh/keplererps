<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lists and restores soft-deleted masters / documents (M16 recycle bin).
 */
class RecycleBinService
{
    /**
     * Soft-delete capable models exposed in the recycle bin.
     *
     * @return array<string, class-string<Model>>
     */
    public function catalog(): array
    {
        return [
            'party' => \App\Models\Party::class,
            'item' => \App\Models\Item::class,
            'purchase_order' => \App\Models\PurchaseOrder::class,
            'sales_order' => \App\Models\SalesOrder::class,
            'sales_invoice' => \App\Models\SalesInvoice::class,
            'journal_voucher' => \App\Models\JournalVoucher::class,
            'lead' => \App\Models\Lead::class,
            'purchase_indent' => \App\Models\PurchaseIndent::class,
            'employee' => \App\Models\Employee::class,
        ];
    }

    /**
     * @return list<array{type: string, id: int, label: string, deleted_at: string|null}>
     */
    public function list(?string $type = null, int $limit = 100): array
    {
        $rows = [];
        $catalog = $this->catalog();

        foreach ($catalog as $key => $class) {
            if ($type !== null && $type !== $key) {
                continue;
            }

            /** @var Model $model */
            $model = new $class;
            if (! method_exists($model, 'trashed')) {
                continue;
            }

            $query = $class::onlyTrashed()->latest('deleted_at')->limit($limit);
            foreach ($query->get() as $record) {
                $rows[] = [
                    'type' => $key,
                    'id' => (int) $record->getKey(),
                    'label' => $this->labelFor($record),
                    'deleted_at' => $record->deleted_at?->toDateTimeString(),
                ];
            }
        }

        usort($rows, fn (array $a, array $b): int => strcmp((string) $b['deleted_at'], (string) $a['deleted_at']));

        return array_slice($rows, 0, $limit);
    }

    public function restore(string $type, int $id): Model
    {
        $class = $this->catalog()[$type] ?? null;
        if ($class === null) {
            abort(404, 'Unknown recycle bin type.');
        }

        $record = $class::onlyTrashed()->findOrFail($id);
        $record->restore();

        return $record;
    }

    protected function labelFor(Model $record): string
    {
        foreach (['document_no', 'party_name', 'item_name', 'full_name', 'name', 'code', 'email'] as $attr) {
            if (isset($record->{$attr}) && filled($record->{$attr})) {
                return (string) $record->{$attr};
            }
        }

        return Str::headline(class_basename($record)).' #'.$record->getKey();
    }
}
