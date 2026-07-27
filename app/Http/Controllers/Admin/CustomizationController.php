<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Services\ApprovalRuleService;
use App\Services\BatchRecallService;
use App\Services\CustomFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Custom fields, approval rules, and batch recall (M16 / M17).
 */
class CustomizationController extends Controller
{
    public function __construct(
        protected CustomFieldService $customFields,
        protected ApprovalRuleService $approvals,
        protected BatchRecallService $recalls
    ) {}

    public function customFields(): View
    {
        return view('admin.customization.custom-fields', [
            'definitions' => $this->customFields->definitions(),
        ]);
    }

    public function storeCustomField(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string', 'max:60'],
            'field_key' => ['required', 'string', 'max:60'],
            'label' => ['required', 'string', 'max:120'],
            'field_type' => ['required', 'in:text,number,date,select,boolean'],
            'options' => ['nullable', 'array'],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $definition = $this->customFields->createDefinition($data);

        return response()->json([
            'status' => true,
            'message' => 'Custom field created.',
            'data' => $definition,
        ], 201);
    }

    public function destroyCustomField(int $customField): JsonResponse
    {
        $this->customFields->deleteDefinition($customField);

        return response()->json(['status' => true, 'message' => 'Custom field deleted.']);
    }

    public function approvalRules(): View
    {
        return view('admin.customization.approval-rules', [
            'rules' => $this->approvals->all(),
        ]);
    }

    public function storeApprovalRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'document_type' => ['required', 'string', 'max:60'],
            'condition_field' => ['nullable', 'string', 'max:60'],
            'condition_operator' => ['nullable', 'in:gte,gt,lte,lt'],
            'condition_value' => ['required', 'numeric', 'min:0'],
            'approver_permission' => ['required', 'string', 'max:100'],
            'steps' => ['nullable'],
            'approval_mode' => ['nullable', 'in:sequential,parallel'],
            'escalation_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'auto_approve_below' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $rule = $this->approvals->create($data);

        return response()->json([
            'status' => true,
            'message' => 'Approval rule created.',
            'data' => $rule,
        ], 201);
    }

    public function destroyApprovalRule(int $approvalRule): JsonResponse
    {
        $this->approvals->delete($approvalRule);

        return response()->json(['status' => true, 'message' => 'Approval rule deleted.']);
    }

    public function recallBatch(Request $request, Batch $batch): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $recalled = $this->recalls->recall($batch->id, $data['reason']);

            return response()->json([
                'status' => true,
                'message' => 'Batch recalled.',
                'data' => $recalled,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
