<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $promos = PromoCollection::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 30));

        return response()->json([
            'status' => 'success',
            'data' => $promos,
        ]);
    }
}
