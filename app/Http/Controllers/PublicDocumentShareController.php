<?php

namespace App\Http\Controllers;

use App\Services\DocumentShareService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Public signed document share viewer (HTML or PDF).
 */
class PublicDocumentShareController extends Controller
{
    public function __construct(protected DocumentShareService $shares) {}

    public function show(Request $request, string $token): Response
    {
        try {
            $share = $this->shares->findValid($token);
        } catch (ValidationException $e) {
            abort(410, collect($e->errors())->flatten()->first() ?: 'Expired');
        }

        $wantsPdf = $request->string('format')->toString() === 'pdf'
            || $request->boolean('download');

        if ($wantsPdf) {
            $filename = str_replace(' ', '_', (string) ($share->document_no ?: 'document')).'.pdf';

            return response($this->shares->pdfFor($share), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]);
        }

        return response($this->shares->htmlFor($share), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }
}
