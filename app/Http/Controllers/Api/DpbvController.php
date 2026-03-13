<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DpbvCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DpbvController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $collections = DpbvCollection::where('user_id', $request->user()->id)
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 30));

        $totalDpbv = DpbvCollection::where('user_id', $request->user()->id)->sum('dpbv');
        // Calculate naira equivalent: (Total DPBV * 0.95) * 990
        $nairaEquivalent = ($totalDpbv * 0.95) * 990;

        return response()->json([
            'status' => 'success',
            'data' => $collections,
            'total_dpbv' => (float) $totalDpbv,
            'naira_equivalent' => (float) $nairaEquivalent,
        ]);
    }
}
