<?php

namespace App\Modules\Directory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Directory\Models\ClientDirectoryEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The one unauthenticated hop in the whole flow: app -> client code ->
 * endpoint. Everything after this uses the returned endpoint directly; this
 * controller never sees or returns anything about the tenant itself.
 */
class ResolveClientController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $entry = ClientDirectoryEntry::query()
            ->where('client_code', $validated['code'])
            ->where('is_active', true)
            ->first();

        if (! $entry) {
            return response()->json([
                'message' => 'Unknown client code.',
            ], 404);
        }

        return response()->json([
            'country' => $entry->country,
            'endpoint' => $entry->endpoint_url,
        ]);
    }
}
