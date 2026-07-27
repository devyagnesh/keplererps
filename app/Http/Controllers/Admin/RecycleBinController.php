<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RecycleBinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Soft-delete recycle bin (M16).
 */
class RecycleBinController extends Controller
{
    public function __construct(protected RecycleBinService $service) {}

    public function index(Request $request): View
    {
        $type = $request->string('type')->toString() ?: null;

        return view('admin.system.recycle-bin', [
            'types' => array_keys($this->service->catalog()),
            'type' => $type,
            'rows' => $this->service->list($type),
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
        ]);

        $record = $this->service->restore($data['type'], (int) $data['id']);

        return response()->json([
            'status' => true,
            'message' => 'Record restored.',
            'data' => ['id' => $record->getKey()],
        ]);
    }
}
