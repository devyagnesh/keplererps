<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartyImportRequest;
use App\Models\PartyImport;
use App\Services\PartyImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Party CSV import screens (US-M01-05).
 */
class PartyImportController extends Controller
{
    public function __construct(protected PartyImportService $service) {}

    public function index(): View
    {
        return view('admin.parties.import.index');
    }

    public function template(): StreamedResponse
    {
        return $this->service->downloadTemplate();
    }

    public function preview(PartyImportRequest $request): JsonResponse
    {
        $import = $this->service->preview($request->file('file'), (int) $request->user()->id);

        return response()->json([
            'status' => true,
            'message' => "Preview ready: {$import->valid_rows} valid, {$import->invalid_rows} invalid of {$import->total_rows}.",
            'data' => $import,
            'redirect' => route('admin.parties.import.show', $import),
        ]);
    }

    public function show(PartyImport $import): View
    {
        abort_unless($import->user_id === auth()->id() || auth()->user()?->hasRole('super-admin'), 403);

        return view('admin.parties.import.show', ['import' => $import]);
    }

    public function commit(PartyImport $import): JsonResponse
    {
        abort_unless($import->user_id === auth()->id() || auth()->user()?->hasRole('super-admin'), 403);
        $import = $this->service->commit($import);

        return response()->json([
            'status' => true,
            'message' => 'Import queued. Results will update when processing finishes.',
            'data' => $import,
        ]);
    }

    public function downloadErrors(PartyImport $import)
    {
        abort_unless($import->user_id === auth()->id() || auth()->user()?->hasRole('super-admin'), 403);
        abort_unless($import->error_file_path && Storage::disk('local')->exists($import->error_file_path), 404);

        return Storage::disk('local')->download($import->error_file_path, 'party-import-errors.csv');
    }

    public function status(PartyImport $import): JsonResponse
    {
        abort_unless($import->user_id === auth()->id() || auth()->user()?->hasRole('super-admin'), 403);

        return response()->json([
            'status' => true,
            'message' => 'Import status loaded.',
            'data' => $import->fresh(),
        ]);
    }
}
