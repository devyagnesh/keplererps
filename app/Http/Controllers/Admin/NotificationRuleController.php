<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NotificationRuleRequest;
use App\Models\NotificationRule;
use App\Services\NotificationRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Notification rule catalogue screens (M16).
 */
class NotificationRuleController extends Controller
{
    public function __construct(protected NotificationRuleService $service) {}

    public function index(): View
    {
        return view('admin.notification-rules.index', [
            'rules' => $this->service->all(),
            'lookups' => $this->service->formLookups(),
        ]);
    }

    public function store(NotificationRuleRequest $request): JsonResponse
    {
        try {
            $rule = $this->service->create($request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Notification rule created.',
                'data' => $rule,
                'redirect' => route('admin.notification-rules.index'),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(NotificationRuleRequest $request, NotificationRule $notificationRule): JsonResponse
    {
        try {
            $rule = $this->service->update($notificationRule->id, $request->validated());

            return response()->json([
                'status' => true,
                'message' => 'Notification rule updated.',
                'data' => $rule,
                'redirect' => route('admin.notification-rules.index'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function toggle(NotificationRule $notificationRule): JsonResponse
    {
        $rule = $this->service->toggle($notificationRule->id);

        return response()->json([
            'status' => true,
            'message' => $rule->is_active ? 'Rule enabled.' : 'Rule disabled.',
            'data' => $rule,
        ]);
    }

    public function destroy(NotificationRule $notificationRule): JsonResponse
    {
        try {
            $this->service->delete($notificationRule->id);

            return response()->json(['status' => true, 'message' => 'Notification rule deleted.']);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
