<?php

namespace Database\Seeders;

use App\Services\IndustryProfileService;
use Illuminate\Database\Seeder;

/**
 * Seeds the eleven industry profile packs from the SRS.
 */
class IndustryProfileSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(IndustryProfileService::class);

        foreach ($this->profiles() as $profile) {
            $service->upsert($profile);
        }

        if ($service->active() === null) {
            $service->activate('pvc_pipes');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function profiles(): array
    {
        return [
            [
                'code' => 'plastic_products',
                'name' => 'Plastic Products',
                'modules' => [
                    'production' => true, 'quality' => true, 'maintenance' => true,
                    'batch_tracking' => true, 'serial_tracking' => false, 'mould_die_register' => true,
                ],
                'uom' => ['default_stock_uom' => 'PCS', 'allow_dual_uom' => true, 'secondary_uom' => 'KG'],
                'costing' => ['method' => 'weighted_average', 'include_scrap_credit' => true],
                'item_attributes' => [
                    ['key' => 'mould_code', 'label' => 'Mould Code', 'type' => 'text'],
                    ['key' => 'cavity_count', 'label' => 'Cavity Count', 'type' => 'number'],
                    ['key' => 'cycle_time_sec', 'label' => 'Cycle Time (sec)', 'type' => 'decimal'],
                ],
                'qc_templates' => ['incoming_resin', 'inprocess_moulding', 'final_dimension'],
                'reports' => ['shift_output', 'scrap_percentage'],
                'print_templates' => ['carton_label'],
            ],
            [
                'code' => 'pvc_pipes',
                'name' => 'PVC Pipes & Fittings',
                'modules' => [
                    'production' => true, 'quality' => true, 'maintenance' => true,
                    'batch_tracking' => true, 'serial_tracking' => false, 'mould_die_register' => true,
                ],
                'uom' => [
                    'default_stock_uom' => 'MTR', 'allow_dual_uom' => true,
                    'secondary_uom' => 'KG', 'conversion_mode' => 'item_specific_factor',
                ],
                'costing' => ['method' => 'weighted_average', 'include_scrap_credit' => true],
                'item_attributes' => [
                    ['key' => 'outer_diameter_mm', 'label' => 'Outer Diameter (mm)', 'type' => 'decimal', 'required' => true],
                    ['key' => 'wall_thickness_mm', 'label' => 'Wall Thickness (mm)', 'type' => 'decimal', 'required' => true],
                    ['key' => 'pressure_class', 'label' => 'Pressure Class', 'type' => 'select', 'options' => ['PN4', 'PN6', 'PN10', 'PN16']],
                ],
                'qc_templates' => ['incoming_resin', 'inprocess_extrusion', 'final_pipe_dimension', 'hydraulic_pressure_test'],
                'reports' => ['metre_wise_production', 'line_efficiency', 'scrap_percentage'],
                'print_templates' => ['pipe_bundle_label', 'tax_invoice_with_metres'],
            ],
            [
                'code' => 'packaging_materials',
                'name' => 'Packaging Materials',
                'modules' => [
                    'production' => true, 'quality' => true, 'jobwork' => true,
                    'batch_tracking' => true, 'serial_tracking' => false,
                ],
                'uom' => ['default_stock_uom' => 'PCS', 'allow_dual_uom' => true, 'secondary_uom' => 'KG'],
                'costing' => ['method' => 'weighted_average'],
                'item_attributes' => [
                    ['key' => 'ply_count', 'label' => 'Ply Count', 'type' => 'number'],
                    ['key' => 'gsm', 'label' => 'GSM', 'type' => 'decimal'],
                    ['key' => 'flute_type', 'label' => 'Flute Type', 'type' => 'text'],
                ],
                'qc_templates' => ['bursting_strength', 'print_registration'],
                'reports' => ['wastage_percentage', 'jobwork_status'],
                'print_templates' => ['reel_label'],
            ],
            [
                'code' => 'rubber_products',
                'name' => 'Rubber Products',
                'modules' => [
                    'production' => true, 'quality' => true, 'batch_tracking' => true,
                    'serial_tracking' => false, 'fefo_issue' => true,
                ],
                'uom' => ['default_stock_uom' => 'PCS', 'allow_dual_uom' => true, 'secondary_uom' => 'KG'],
                'costing' => ['method' => 'batch_specific'],
                'item_attributes' => [
                    ['key' => 'compound_code', 'label' => 'Compound Code', 'type' => 'text'],
                    ['key' => 'hardness_shore_a', 'label' => 'Hardness (Shore A)', 'type' => 'decimal'],
                ],
                'qc_templates' => ['compound_hardness', 'final_dimension'],
                'reports' => ['compound_expiry', 'scrap_percentage'],
                'print_templates' => ['batch_label'],
            ],
            [
                'code' => 'furniture',
                'name' => 'Furniture',
                'modules' => [
                    'production' => true, 'quality' => true, 'serial_tracking' => true,
                    'batch_tracking' => false, 'made_to_order' => true,
                ],
                'uom' => ['default_stock_uom' => 'PCS', 'allow_dual_uom' => false],
                'costing' => ['method' => 'fifo'],
                'item_attributes' => [
                    ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
                    ['key' => 'finish', 'label' => 'Finish', 'type' => 'text'],
                    ['key' => 'upholstery_code', 'label' => 'Upholstery Code', 'type' => 'text'],
                ],
                'qc_templates' => ['final_finish', 'assembly_check'],
                'reports' => ['mto_backlog'],
                'print_templates' => ['serial_label', 'job_card'],
            ],
            [
                'code' => 'electrical_components',
                'name' => 'Electrical Components',
                'modules' => [
                    'production' => true, 'quality' => true, 'serial_tracking' => true, 'batch_tracking' => true,
                ],
                'uom' => ['default_stock_uom' => 'PCS', 'allow_dual_uom' => false],
                'costing' => ['method' => 'fifo'],
                'item_attributes' => [
                    ['key' => 'voltage_rating', 'label' => 'Voltage Rating', 'type' => 'text'],
                    ['key' => 'ip_rating', 'label' => 'IP Rating', 'type' => 'text'],
                    ['key' => 'certification', 'label' => 'Certification', 'type' => 'text'],
                ],
                'qc_templates' => ['electrical_safety', 'final_functional'],
                'reports' => ['warranty_serials'],
                'print_templates' => ['serial_label'],
            ],
            [
                'code' => 'auto_parts',
                'name' => 'Auto Parts',
                'modules' => [
                    'production' => true, 'quality' => true, 'serial_tracking' => true,
                    'batch_tracking' => true, 'delivery_schedules' => true,
                ],
                'uom' => ['default_stock_uom' => 'PCS', 'allow_dual_uom' => false],
                'costing' => ['method' => 'standard_cost'],
                'item_attributes' => [
                    ['key' => 'oem_part_number', 'label' => 'OEM Part Number', 'type' => 'text'],
                    ['key' => 'drawing_revision', 'label' => 'Drawing Revision', 'type' => 'text'],
                    ['key' => 'vehicle_model', 'label' => 'Vehicle Model', 'type' => 'text'],
                ],
                'qc_templates' => ['incoming_ppap', 'inprocess_control', 'final_dimension'],
                'reports' => ['schedule_adherence', 'ppap_status'],
                'print_templates' => ['oem_label'],
            ],
            [
                'code' => 'steel_products',
                'name' => 'Steel Products',
                'modules' => [
                    'production' => true, 'quality' => true, 'batch_tracking' => true,
                    'heat_number' => true, 'serial_tracking' => false,
                ],
                'uom' => ['default_stock_uom' => 'KG', 'allow_dual_uom' => true, 'secondary_uom' => 'MTR'],
                'costing' => ['method' => 'weighted_average'],
                'item_attributes' => [
                    ['key' => 'grade', 'label' => 'Grade', 'type' => 'text'],
                    ['key' => 'heat_number', 'label' => 'Heat Number', 'type' => 'text'],
                    ['key' => 'thickness_mm', 'label' => 'Thickness (mm)', 'type' => 'decimal'],
                ],
                'qc_templates' => ['chemical_composition', 'dimensional'],
                'reports' => ['heat_traceability', 'remnant_stock'],
                'print_templates' => ['heat_label'],
            ],
            [
                'code' => 'aluminium_products',
                'name' => 'Aluminium Products',
                'modules' => [
                    'production' => true, 'quality' => true, 'batch_tracking' => true,
                    'mould_die_register' => true, 'serial_tracking' => false,
                ],
                'uom' => ['default_stock_uom' => 'KG', 'allow_dual_uom' => true, 'secondary_uom' => 'MTR'],
                'costing' => ['method' => 'weighted_average'],
                'item_attributes' => [
                    ['key' => 'alloy_temper', 'label' => 'Alloy / Temper', 'type' => 'text'],
                    ['key' => 'section_code', 'label' => 'Section Code', 'type' => 'text'],
                    ['key' => 'die_code', 'label' => 'Die Code', 'type' => 'text'],
                ],
                'qc_templates' => ['dimensional', 'anodising_check'],
                'reports' => ['die_life', 'metre_to_kg'],
                'print_templates' => ['bundle_label'],
            ],
            [
                'code' => 'textile',
                'name' => 'Textile',
                'modules' => [
                    'production' => true, 'quality' => true, 'jobwork' => true,
                    'batch_tracking' => true, 'shade_lot' => true,
                ],
                'uom' => ['default_stock_uom' => 'MTR', 'allow_dual_uom' => true, 'secondary_uom' => 'KG'],
                'costing' => ['method' => 'batch_specific'],
                'item_attributes' => [
                    ['key' => 'gsm', 'label' => 'GSM', 'type' => 'decimal'],
                    ['key' => 'width_mm', 'label' => 'Width (mm)', 'type' => 'decimal'],
                    ['key' => 'shade_lot', 'label' => 'Shade / Lot', 'type' => 'text'],
                ],
                'qc_templates' => ['shade_match', 'shrinkage'],
                'reports' => ['jobwork_status', 'shade_lot_stock'],
                'print_templates' => ['roll_label'],
            ],
            [
                'code' => 'chemical',
                'name' => 'Chemical',
                'modules' => [
                    'production' => true, 'quality' => true, 'batch_tracking' => true,
                    'serial_tracking' => false, 'fefo_issue' => true,
                ],
                'uom' => ['default_stock_uom' => 'KG', 'allow_dual_uom' => true, 'secondary_uom' => 'LTR'],
                'costing' => ['method' => 'batch_specific'],
                'item_attributes' => [
                    ['key' => 'concentration', 'label' => 'Concentration', 'type' => 'text'],
                    ['key' => 'hazard_class', 'label' => 'Hazard Class', 'type' => 'text'],
                    ['key' => 'shelf_life_days', 'label' => 'Shelf Life (days)', 'type' => 'number'],
                ],
                'qc_templates' => ['incoming_coa', 'inprocess_assay', 'final_coa'],
                'reports' => ['batch_coa', 'expiry_stock'],
                'print_templates' => ['drum_label', 'coa'],
            ],
        ];
    }
}
