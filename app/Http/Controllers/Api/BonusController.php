<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BonusCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BonusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bonuses = BonusCollection::where('user_id', $request->user()->id)
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 30));

        $totalBonus = BonusCollection::where('user_id', $request->user()->id)->sum('total');

        return response()->json([
            'status' => 'success',
            'data' => $bonuses,
            'total_bonus' => (float) $totalBonus,
        ]);
    }
}
