<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces a named permission, or derives one from the admin route name.
 */
class EnsureUserHasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $permission ??= $this->resolvePermissionFromRoute($request);

        if ($permission === null) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || ! $user->hasPermissionTo($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    /**
     * Map admin.* route names to module.action permission names.
     */
    protected function resolvePermissionFromRoute(Request $request): ?string
    {
        $name = $request->route()?->getName();
        if ($name === null || ! str_starts_with($name, 'admin.')) {
            return null;
        }

        $parts = explode('.', $name);
        if (count($parts) < 3) {
            return null;
        }

        if (($parts[1] ?? null) === 'parties' && ($parts[2] ?? null) === 'import') {
            $importAction = $parts[3] ?? 'index';

            return match ($importAction) {
                'preview', 'commit' => 'party.create',
                default => 'party.view',
            };
        }

        $module = $this->moduleFromResource($parts[1]);
        $action = $this->permissionActionFromRouteAction($parts[2], $module);

        return $module.'.'.$action;
    }

    protected function moduleFromResource(string $resource): string
    {
        $normalized = str_replace('-', '_', $resource);

        return match ($normalized) {
            'categories' => 'category',
            'branches' => 'branch',
            'settings' => 'setting',
            'uoms' => 'uom',
            'purchase_orders' => 'purchase_order',
            'goods_receipts' => 'goods_receipt',
            'purchase_bills' => 'purchase_bill',
            'purchase_returns' => 'purchase_return',
            'sales_returns' => 'sales_return',
            'purchase_suggestions' => 'purchase_suggestion',
            'purchase_indents' => 'purchase_indent',
            'purchase_rfqs' => 'purchase_rfq',
            'bank_reconciliation' => 'bank_reconciliation',
            'holidays' => 'holiday',
            'leave_balances' => 'holiday',
            'gstr2b' => 'gst_report',
            'backups' => 'backup',
            'recycle_bin' => 'recycle_bin',
            'locale' => 'setting',
            'dashboard_widgets' => 'setting',
            'scheduled_reports' => 'scheduled_report',
            'scan_exceptions' => 'scan_exception',
            'sales_quotations' => 'sales_quotation',
            'sales_orders' => 'sales_order',
            'sales_invoices' => 'sales_invoice',
            'delivery_challans' => 'delivery_challan',
            'work_orders' => 'work_order',
            'production_plans' => 'production_plan',
            'ledger_accounts' => 'ledger_account',
            'journal_vouchers' => 'journal_voucher',
            'voucher_allocations' => 'voucher_allocation',
            'period_locks' => 'period_lock',
            'price_lists' => 'price_list',
            'stock_takes' => 'stock_take',
            'supplier_ratings' => 'supplier_rating',
            'crm_reports' => 'crm_report',
            'qc_reports' => 'qc_report',
            'custom_fields' => 'custom_field',
            'approval_rules' => 'approval_rule',
            'print_templates' => 'print_template',
            'terms_blocks' => 'terms_block',
            'ui_labels' => 'ui_label',
            'industry_profiles' => 'industry_profile',
            'integrations' => 'integration',
            'batches' => 'batch_recall',
            'finance_reports' => 'finance_report',
            'gst_reports' => 'gst_report',
            'production_entries' => 'production_entry',
            'work_centres' => 'work_centre',
            'maintenance_orders' => 'maintenance_order',
            'attendance' => 'attendance',
            'salary_runs' => 'salary_run',
            'notification_rules' => 'notification_rule',
            'activity_logs' => 'activity_log',
            'shop_floor' => 'shop_floor',
            'tally' => 'finance_report',
            default => Str::singular($normalized),
        };
    }

    protected function permissionActionFromRouteAction(string $action, string $module): string
    {
        return match ($action) {
            'new-version' => 'create',
            'explode' => 'view',
            'approve' => 'approve',
            'mark-sent', 'accept', 'convert', 'confirm', 'cancel', 'dispatch', 'mark-delivered', 'release', 'issue-materials', 'complete', 'issue-parts', 'follow-up', 'stage', 'quotation', 'recalculate', 'seed', 'save-lines', 'reprint', 'recall', 'sync', 'whatsapp', 'activate', 'gsp-push', 'reconcile', 'restore', 'add-quote', 'award', 'einvoice', 'from-indent', 'eway-submit' => 'update',
            'pending-lines', 'challan-lines', 'billable-lines', 'returnable-lines', 'eway-payload', 'boms', 'availability', 'due', 'status-board', 'print', 'demand', 'pipeline', 'pack', 'scan-form', 'coa', 'allocate', 'open-documents', 'resolve-rate', 'funnel', 'overdue', 'duplicates', 'pareto', 'download', 'comparative', 'export', 'operator', 'capacity', 'costing' => 'view',
            'scan' => $module === 'package' ? 'scan' : 'update',
            'replay-offline' => $module === 'package' ? 'scan' : 'update',
            'index', 'data', 'show', 'preview', 'summary', 'template', 'errors', 'gstin-lookup', 'permissions' => 'view',
            // Lead status is a pipeline transition; elsewhere `status` is a read-only progress check.
            'status' => $module === 'lead' ? 'update' : 'view',
            'edit' => in_array($module, ['company', 'setting'], true) ? 'view' : 'update',
            'mobile-punch' => 'create',
            'create', 'store', 'import', 'recompute', 'store-from-indent' => 'create',
            'update', 'set-current', 'toggle', 'whatsapp-test', 'resolve' => 'update',
            'copy' => 'create',
            'destroy' => 'delete',
            'post', 'commit', 'generate' => 'post',
            'close' => $module === 'financial_year' ? 'close' : 'update',
            'clear-cache' => 'maintain',
            'override' => 'override',
            'approve' => 'approve',
            default => 'view',
        };
    }
}
