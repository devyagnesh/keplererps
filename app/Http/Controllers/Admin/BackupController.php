<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

/**
 * Manual backup download utility (M16).
 */
class BackupController extends Controller
{
    public function __construct(protected BackupService $service) {}

    public function index(): View
    {
        return view('admin.system.backups', [
            'backups' => $this->service->recent(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $log = $this->service->create($data['notes'] ?? '');

        return response()->json([
            'status' => $log->status === 'ready',
            'message' => $log->status === 'ready' ? 'Backup created.' : 'Backup failed.',
            'data' => $log,
        ], $log->status === 'ready' ? 201 : 500);
    }

    public function download(BackupLog $backup): BinaryFileResponse
    {
        abort_unless($backup->status === 'ready', 404);

        return response()->download($this->service->absolutePath($backup), basename($backup->disk_path));
    }

    public function restore(Request $request, BackupLog $backup): JsonResponse
    {
        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:255'],
        ]);

        try {
            $log = $this->service->restore($backup, $data['confirmation']);

            return response()->json([
                'status' => true,
                'message' => 'Backup restored successfully.',
                'data' => $log,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
