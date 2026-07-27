<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DashboardRoleWidget;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Role-specific dashboard widget configuration (M16).
 */
class DashboardWidgetController extends Controller
{
    /**
     * @var list<string>
     */
    protected const WIDGET_KEYS = [
        'sales',
        'purchase',
        'inventory',
        'production',
        'maintenance',
        'finance',
        'approvals',
        'crm',
    ];

    public function index(): View
    {
        $roles = Role::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $packs = DashboardRoleWidget::query()->get()->keyBy('role_name');

        return view('admin.settings.dashboard-widgets', [
            'roles' => $roles,
            'packs' => $packs,
            'widgetKeys' => self::WIDGET_KEYS,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role_name' => ['required', 'string', 'max:100'],
            'widget_keys' => ['nullable', 'array'],
            'widget_keys.*' => ['string', 'in:'.implode(',', self::WIDGET_KEYS)],
        ]);

        $pack = DashboardRoleWidget::query()->updateOrCreate(
            ['role_name' => $data['role_name']],
            ['widget_keys' => array_values($data['widget_keys'] ?? [])]
        );

        return response()->json([
            'status' => true,
            'message' => 'Dashboard widgets saved for '.$data['role_name'].'.',
            'data' => $pack,
        ]);
    }
}
