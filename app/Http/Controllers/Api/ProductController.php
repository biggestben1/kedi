<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category:id,name,slug')
            ->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate($request->input('per_page', 15));

        $products->getCollection()->transform(function (Product $product) {
            return $this->productResource($product);
        });

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->is_active) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->load('category:id,name,slug');

        return response()->json(['data' => $this->productResource($product)]);
    }

    private function productResource(Product $product): array
    {
        $user = auth()->user();
        $price = $product->getPriceForUser($user);

        return [
            'id' => $product->id,
            'item_code' => $product->item_code,
            'name' => $product->name,
            'pack_size' => $product->pack_size,
            'display_name' => $product->display_name,
            'category_id' => $product->category_id,
            'category' => $product->relationLoaded('category') ? [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
                'slug' => $product->category?->slug,
            ] : null,
            'price' => round($price, 2),
            'bv' => (float) $product->bv,
            'pv' => (float) $product->pv,
            'stock' => $product->stock,
            'image' => $product->image ? asset('storage/' . $product->image) : null,
        ];
    }
}
