<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Meta WhatsApp webhook (no auth — verified by token).
 */
class WhatsAppWebhookController extends Controller
{
    public function __construct(protected WhatsAppWebhookService $service) {}

    public function verify(Request $request): Response|JsonResponse
    {
        $challenge = $this->service->verify(
            (string) ($request->query('hub.mode') ?? $request->query('hub_mode', '')),
            (string) ($request->query('hub.verify_token') ?? $request->query('hub_verify_token', '')),
            (string) ($request->query('hub.challenge') ?? $request->query('hub_challenge', ''))
        );

        if ($challenge === null) {
            return response()->json(['message' => 'Verification failed.'], 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request): JsonResponse
    {
        $result = $this->service->handle($request->all());

        return response()->json($result);
    }
}
