<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesQuotation;
use App\Services\DocumentShareService;
use App\Services\PrintTemplateService;
use App\Services\TermsBlockService;
use App\Services\UiLabelService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Remaining customization + document share / WhatsApp actions.
 */
class RemainingPriorityController extends Controller
{
    public function __construct(
        protected PrintTemplateService $printTemplates,
        protected TermsBlockService $terms,
        protected UiLabelService $labels,
        protected DocumentShareService $shares,
        protected WhatsAppService $whatsApp
    ) {}

    public function printTemplates(): View
    {
        return view('admin.customization.print-templates', [
            'templates' => $this->printTemplates->all(),
        ]);
    }

    public function storePrintTemplate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'document_type' => ['required', 'string', 'max:60'],
            'header_html' => ['nullable', 'string', 'max:2000'],
            'footer_html' => ['nullable', 'string', 'max:2000'],
            'show_hsn' => ['sometimes', 'boolean'],
            'show_tax_breakup' => ['sometimes', 'boolean'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $template = $this->printTemplates->create($data);

        return response()->json([
            'status' => true,
            'message' => 'Print template saved.',
            'data' => $template,
        ], 201);
    }

    public function destroyPrintTemplate(int $printTemplate): JsonResponse
    {
        $this->printTemplates->delete($printTemplate);

        return response()->json(['status' => true, 'message' => 'Print template deleted.']);
    }

    public function termsBlocks(): View
    {
        return view('admin.customization.terms-blocks', [
            'blocks' => $this->terms->all(),
        ]);
    }

    public function storeTermsBlock(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'document_type' => ['nullable', 'string', 'max:60'],
            'body' => ['required', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $block = $this->terms->create($data);

        return response()->json([
            'status' => true,
            'message' => 'Terms block saved.',
            'data' => $block,
        ], 201);
    }

    public function destroyTermsBlock(int $termsBlock): JsonResponse
    {
        $this->terms->delete($termsBlock);

        return response()->json(['status' => true, 'message' => 'Terms block deleted.']);
    }

    public function uiLabels(): View
    {
        return view('admin.customization.ui-labels', [
            'labels' => $this->labels->all(),
        ]);
    }

    public function storeUiLabel(Request $request): JsonResponse
    {
        $data = $request->validate([
            'locale' => ['nullable', 'string', 'max:10'],
            'label_key' => ['required', 'string', 'max:120'],
            'label_value' => ['required', 'string', 'max:255'],
        ]);

        $label = $this->labels->upsert($data);

        return response()->json([
            'status' => true,
            'message' => 'Label saved.',
            'data' => $label,
        ], 201);
    }

    public function sendQuotationWhatsApp(Request $request, SalesQuotation $salesQuotation): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
        ]);

        try {
            $share = $this->shares->sendWhatsApp('sales_quotation', $salesQuotation->id, $data['mobile']);

            return response()->json([
                'status' => true,
                'message' => $share->status === 'failed'
                    ? 'WhatsApp send failed.'
                    : 'Quotation link prepared for WhatsApp.',
                'data' => $share,
            ], $share->status === 'failed' ? 422 : 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $this->whatsApp->sendText(
            $data['mobile'],
            $data['message'] ?? 'Kepler ERP WhatsApp test message.'
        );

        return response()->json([
            'status' => true,
            'message' => 'WhatsApp test '.$result['status'].'.',
            'data' => $result,
        ]);
    }
}
