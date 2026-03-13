<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\ServiceCenterStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $stockUserId = ($user && in_array($user->role?->name ?? '', ['branch', 'service_center', 'annex'])) ? (int) $user->id : null;
        $role = $user?->role?->name ?? '';

        $query = Product::with('category:id,name,slug')
            ->where('is_active', true);

        if ($stockUserId) {
            $productIds = $role === 'branch'
                ? BranchStock::where('branch_user_id', $stockUserId)->where('quantity', '>', 0)->pluck('product_id')
                : ($role === 'service_center'
                    ? \App\Models\ServiceCenterStock::where('service_center_user_id', $stockUserId)->where('quantity', '>', 0)->pluck('product_id')
                    : \App\Models\AnnexStock::where('annex_user_id', $stockUserId)->where('quantity', '>', 0)->pluck('product_id'));
            $query->whereIn('id', $productIds);
        }

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

        $stock = $product->stock;
        if ($user) {
            $role = $user->role?->name ?? '';
            if ($role === 'branch') {
                $stock = BranchStock::getQuantity((int) $user->id, $product->id);
            } elseif ($role === 'service_center') {
                $stock = ServiceCenterStock::getQuantity((int) $user->id, $product->id);
            } elseif ($role === 'annex') {
                $stock = AnnexStock::getQuantity((int) $user->id, $product->id);
            }
        }

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
            'stock' => $stock,
            'image' => $product->image_url,
            'image_url' => $product->image_url,
        ];
    }
}
