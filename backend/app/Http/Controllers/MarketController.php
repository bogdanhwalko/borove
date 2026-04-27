<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['shop:id,user_id,name', 'shop.user:id,nickname,first_name,last_name'])
            ->withCount('purchaseRequests')
            ->latest();

        if ($request->filled('shop_id')) {
            $query->where('shop_id', (int) $request->input('shop_id'));
        }

        $perPage = min((int) $request->input('per_page', 12), 200);
        $paged   = $query->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function showShop(int $id): JsonResponse
    {
        $shop = Shop::with('user:id,nickname,first_name,last_name')
            ->withCount('products')
            ->findOrFail($id);

        return response()->json($shop);
    }

    public function buyRequest(Request $request, int $id): JsonResponse
    {
        $product = Product::with('shop')->findOrFail($id);

        if ($product->shop->user_id === $request->user()->id) {
            return response()->json(['message' => 'Не можна надіслати запит на власний товар'], 422);
        }

        $request->validate(['message' => 'nullable|string|max:300']);

        PurchaseRequest::updateOrCreate(
            ['product_id' => $id, 'buyer_id' => $request->user()->id],
            ['message' => $request->input('message')]
        );

        return response()->json(['ok' => true], 201);
    }
}
