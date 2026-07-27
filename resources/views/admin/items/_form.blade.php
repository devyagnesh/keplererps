@php
    $lockedByTxn = (bool) ($item?->has_transactions);
    $lockedByStock = (bool) ($item?->has_stock);
    $uomConversions = old('uom_conversions', $item?->uomConversions?->toArray() ?? [['from_uom_id' => '', 'to_uom_id' => '', 'factor' => '']]);
    $warehouseSettings = old('warehouse_settings', $item?->warehouseSettings?->toArray() ?? [['warehouse_id' => '', 'reorder_level' => '', 'reorder_qty' => '', 'min_stock' => '', 'max_stock' => '']]);
    $substitutes = old('substitutes', $item?->substitutes?->map(fn ($s) => [
        'substitute_item_id' => $s->substitute_item_id,
        'conversion_ratio' => $s->conversion_ratio,
        'is_active' => $s->is_active ? 1 : 0,
    ])->toArray() ?? [['substitute_item_id' => '', 'conversion_ratio' => '1', 'is_active' => 1]]);
@endphp
<div class="card custom-card">
    <div class="card-body">
        <form id="itemForm" action="{{ $action }}" method="POST" novalidate>
            @csrf
            <input type="hidden" name="_method" value="{{ $method }}">
            <div class="row gy-3">
                <div class="col-md-3">
                    <label class="form-label">Item Code</label>
                    <input type="text" class="form-control text-uppercase" name="item_code" value="{{ old('item_code', $item?->item_code) }}" placeholder="Auto-generated" {{ ($item === null || $lockedByTxn) ? 'readonly' : '' }}>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Item Name *</label>
                    <input type="text" class="form-control" name="item_name" value="{{ old('item_name', $item?->item_name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Item Type *</label>
                    <select name="item_type" class="form-select" {{ $lockedByStock ? 'disabled' : '' }} required>
                        @foreach ($itemTypes as $type)
                            <option value="{{ $type->value }}" @selected(old('item_type', $item?->item_type?->value) === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                    @if ($lockedByStock)
                        <input type="hidden" name="item_type" value="{{ $item->item_type->value }}">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category *</label>
                    <select name="category_id" class="form-select select2" required>
                        <option value="">Select</option>
                        @foreach ($categories->whereNull('parent_id') as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $item?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sub Category</label>
                    <select name="sub_category_id" class="form-select select2">
                        <option value="">None</option>
                        @foreach ($categories->whereNotNull('parent_id') as $category)
                            <option value="{{ $category->id }}" @selected((string) old('sub_category_id', $item?->sub_category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Barcode</label>
                    <input type="text" class="form-control" name="barcode" value="{{ old('barcode', $item?->barcode) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stock UOM *</label>
                    <select name="stock_uom_id" class="form-select select2" {{ $lockedByStock ? 'disabled' : '' }} required>
                        <option value="">Select</option>
                        @foreach ($uoms as $uom)
                            <option value="{{ $uom->id }}" @selected((string) old('stock_uom_id', $item?->stock_uom_id) === (string) $uom->id)>{{ $uom->code }} — {{ $uom->name }}</option>
                        @endforeach
                    </select>
                    @if ($lockedByStock)
                        <input type="hidden" name="stock_uom_id" value="{{ $item->stock_uom_id }}">
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchase UOM</label>
                    <select name="purchase_uom_id" class="form-select select2">
                        <option value="">Same as stock</option>
                        @foreach ($uoms as $uom)
                            <option value="{{ $uom->id }}" @selected((string) old('purchase_uom_id', $item?->purchase_uom_id) === (string) $uom->id)>{{ $uom->code }} — {{ $uom->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sales UOM</label>
                    <select name="sales_uom_id" class="form-select select2">
                        <option value="">Same as stock</option>
                        @foreach ($uoms as $uom)
                            <option value="{{ $uom->id }}" @selected((string) old('sales_uom_id', $item?->sales_uom_id) === (string) $uom->id)>{{ $uom->code }} — {{ $uom->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">HSN / SAC *</label>
                    <select name="hsn_code_id" id="hsn_code_id" class="form-select select2" required>
                        <option value="">Select</option>
                        @foreach ($hsnCodes as $hsn)
                            <option value="{{ $hsn->id }}" data-gst="{{ $hsn->default_gst_rate }}" @selected((string) old('hsn_code_id', $item?->hsn_code_id) === (string) $hsn->id)>{{ $hsn->code }} — {{ $hsn->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">GST %</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="gst_rate" id="gst_rate" value="{{ old('gst_rate', $item?->gst_rate ?? 18) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cess %</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="cess_rate" value="{{ old('cess_rate', $item?->cess_rate ?? 0) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tracking *</label>
                    <select name="tracking_type" class="form-select" {{ $lockedByStock ? 'disabled' : '' }} required>
                        @foreach ($trackingTypes as $tracking)
                            <option value="{{ $tracking->value }}" @selected(old('tracking_type', $item?->tracking_type?->value ?? 'none') === $tracking->value)>{{ $tracking->label() }}</option>
                        @endforeach
                    </select>
                    @if ($lockedByStock)
                        <input type="hidden" name="tracking_type" value="{{ $item->tracking_type->value }}">
                    @endif
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input type="hidden" name="expiry_tracking" value="0">
                        <input class="form-check-input" type="checkbox" name="expiry_tracking" id="expiry_tracking" value="1" {{ old('expiry_tracking', $item?->expiry_tracking) ? 'checked' : '' }}>
                        <label class="form-check-label" for="expiry_tracking">Expiry tracking</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Shelf life (days)</label>
                    <input type="number" min="1" class="form-control" name="shelf_life_days" id="shelf_life_days" value="{{ old('shelf_life_days', $item?->shelf_life_days) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Standard cost</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="standard_cost" value="{{ old('standard_cost', $item?->standard_cost ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Selling price</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="selling_price" value="{{ old('selling_price', $item?->selling_price) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min selling price</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="minimum_selling_price" value="{{ old('minimum_selling_price', $item?->minimum_selling_price) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min stock</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="min_stock" value="{{ old('min_stock', $item?->min_stock) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max stock</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="max_stock" value="{{ old('max_stock', $item?->max_stock) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Lead time (days)</label>
                    <input type="number" min="0" class="form-control" name="lead_time_days" value="{{ old('lead_time_days', $item?->lead_time_days) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default warehouse</label>
                    <select name="default_warehouse_id" class="form-select select2">
                        <option value="">None</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('default_warehouse_id', $item?->default_warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Weight / unit</label>
                    <input type="number" step="0.0001" min="0" class="form-control" name="weight_per_unit" value="{{ old('weight_per_unit', $item?->weight_per_unit) }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="2">{{ old('description', $item?->description) }}</textarea>
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_purchasable" value="0">
                        <input class="form-check-input" type="checkbox" name="is_purchasable" value="1" {{ old('is_purchasable', $item?->is_purchasable ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Purchasable</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_sellable" value="0">
                        <input class="form-check-input" type="checkbox" name="is_sellable" value="1" {{ old('is_sellable', $item?->is_sellable) ? 'checked' : '' }}>
                        <label class="form-check-label">Sellable</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_manufacturable" value="0">
                        <input class="form-check-input" type="checkbox" name="is_manufacturable" value="1" {{ old('is_manufacturable', $item?->is_manufacturable) ? 'checked' : '' }}>
                        <label class="form-check-label">Manufacturable</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="requires_inspection" value="0">
                        <input class="form-check-input" type="checkbox" name="requires_inspection" value="1" {{ old('requires_inspection', $item?->requires_inspection) ? 'checked' : '' }}>
                        <label class="form-check-label">Requires QC inspection</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $item?->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">UOM Conversions</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddUomConversion">Add row</button>
            </div>
            <div id="uomConversionRows">
                @foreach ($uomConversions as $index => $row)
                    <div class="row g-2 mb-2 repeater-row" data-repeater="uom">
                        <div class="col-md-4">
                            <select name="uom_conversions[{{ $index }}][from_uom_id]" class="form-select">
                                <option value="">From UOM</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) ($row['from_uom_id'] ?? '') === (string) $uom->id)>{{ $uom->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="uom_conversions[{{ $index }}][to_uom_id]" class="form-select">
                                <option value="">To UOM</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}" @selected((string) ($row['to_uom_id'] ?? '') === (string) $uom->id)>{{ $uom->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.000001" min="0" class="form-control" name="uom_conversions[{{ $index }}][factor]" value="{{ $row['factor'] ?? '' }}" placeholder="Factor">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger-light btn-remove-row"><i class="ri-close-line"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Warehouse Reorder Levels</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddWarehouseSetting">Add row</button>
            </div>
            <div id="warehouseSettingRows">
                @foreach ($warehouseSettings as $index => $row)
                    <div class="row g-2 mb-2 repeater-row" data-repeater="warehouse">
                        <div class="col-md-3">
                            <select name="warehouse_settings[{{ $index }}][warehouse_id]" class="form-select">
                                <option value="">Warehouse</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" @selected((string) ($row['warehouse_id'] ?? '') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[{{ $index }}][reorder_level]" value="{{ $row['reorder_level'] ?? '' }}" placeholder="Reorder lvl"></div>
                        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[{{ $index }}][reorder_qty]" value="{{ $row['reorder_qty'] ?? '' }}" placeholder="Reorder qty"></div>
                        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[{{ $index }}][min_stock]" value="{{ $row['min_stock'] ?? '' }}" placeholder="Min"></div>
                        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[{{ $index }}][max_stock]" value="{{ $row['max_stock'] ?? '' }}" placeholder="Max"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-row"><i class="ri-close-line"></i></button></div>
                    </div>
                @endforeach
            </div>

            <hr class="my-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Substitutes</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddSubstitute">Add row</button>
            </div>
            <div id="substituteRows">
                @foreach ($substitutes as $index => $row)
                    <div class="row g-2 mb-2 repeater-row" data-repeater="substitute">
                        <div class="col-md-6">
                            <select name="substitutes[{{ $index }}][substitute_item_id]" class="form-select">
                                <option value="">Substitute item</option>
                                @foreach ($substituteItems as $option)
                                    @if (! $item || $option->id !== $item->id)
                                        <option value="{{ $option->id }}" @selected((string) ($row['substitute_item_id'] ?? '') === (string) $option->id)>{{ $option->item_code }} — {{ $option->item_name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.000001" min="0" class="form-control" name="substitutes[{{ $index }}][conversion_ratio]" value="{{ $row['conversion_ratio'] ?? 1 }}" placeholder="Ratio">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check form-switch mt-2">
                                <input type="hidden" name="substitutes[{{ $index }}][is_active]" value="0">
                                <input class="form-check-input" type="checkbox" name="substitutes[{{ $index }}][is_active]" value="1" {{ ($row['is_active'] ?? 1) ? 'checked' : '' }}>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-row"><i class="ri-close-line"></i></button></div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <button class="btn btn-primary" type="submit">Save</button>
                <a href="{{ route('admin.items.index') }}" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<template id="tplUomConversion">
    <div class="row g-2 mb-2 repeater-row" data-repeater="uom">
        <div class="col-md-4">
            <select name="uom_conversions[__INDEX__][from_uom_id]" class="form-select">
                <option value="">From UOM</option>
                @foreach ($uoms as $uom)
                    <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <select name="uom_conversions[__INDEX__][to_uom_id]" class="form-select">
                <option value="">To UOM</option>
                @foreach ($uoms as $uom)
                    <option value="{{ $uom->id }}">{{ $uom->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><input type="number" step="0.000001" min="0" class="form-control" name="uom_conversions[__INDEX__][factor]" placeholder="Factor"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-row"><i class="ri-close-line"></i></button></div>
    </div>
</template>

<template id="tplWarehouseSetting">
    <div class="row g-2 mb-2 repeater-row" data-repeater="warehouse">
        <div class="col-md-3">
            <select name="warehouse_settings[__INDEX__][warehouse_id]" class="form-select">
                <option value="">Warehouse</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[__INDEX__][reorder_level]" placeholder="Reorder lvl"></div>
        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[__INDEX__][reorder_qty]" placeholder="Reorder qty"></div>
        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[__INDEX__][min_stock]" placeholder="Min"></div>
        <div class="col-md-2"><input type="number" step="0.0001" class="form-control" name="warehouse_settings[__INDEX__][max_stock]" placeholder="Max"></div>
        <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-row"><i class="ri-close-line"></i></button></div>
    </div>
</template>

<template id="tplSubstitute">
    <div class="row g-2 mb-2 repeater-row" data-repeater="substitute">
        <div class="col-md-6">
            <select name="substitutes[__INDEX__][substitute_item_id]" class="form-select">
                <option value="">Substitute item</option>
                @foreach ($substituteItems as $option)
                    @if (! $item || $option->id !== $item->id)
                        <option value="{{ $option->id }}">{{ $option->item_code }} — {{ $option->item_name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><input type="number" step="0.000001" min="0" class="form-control" name="substitutes[__INDEX__][conversion_ratio]" value="1" placeholder="Ratio"></div>
        <div class="col-md-2">
            <div class="form-check form-switch mt-2">
                <input type="hidden" name="substitutes[__INDEX__][is_active]" value="0">
                <input class="form-check-input" type="checkbox" name="substitutes[__INDEX__][is_active]" value="1" checked>
                <label class="form-check-label">Active</label>
            </div>
        </div>
        <div class="col-md-1"><button type="button" class="btn btn-danger-light btn-remove-row"><i class="ri-close-line"></i></button></div>
    </div>
</template>
