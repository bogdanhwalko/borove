<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketController extends Controller
{
    private const BUY_REQUEST_COOLDOWN_HOURS = 24;

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
        $products = collect($paged->items());

        $user = $request->user('sanctum');
        if ($user && $products->isNotEmpty()) {
            $purchaseRequests = PurchaseRequest::where('buyer_id', $user->id)
                ->whereIn('product_id', $products->pluck('id'))
                ->get()
                ->keyBy('product_id');

            $products->each(function (Product $product) use ($purchaseRequests): void {
                $purchaseRequest = $purchaseRequests->get($product->id);
                $lockedUntil = $purchaseRequest
                    ? $purchaseRequest->updated_at->copy()->addHours(self::BUY_REQUEST_COOLDOWN_HOURS)
                    : null;

                $product->setAttribute(
                    'viewer_can_request',
                    !$lockedUntil || now()->greaterThanOrEqualTo($lockedUntil)
                );
                $product->setAttribute(
                    'viewer_request_locked_until',
                    $lockedUntil && now()->lessThan($lockedUntil) ? $lockedUntil->toISOString() : null
                );
            });
        }

        return response()->json([
            'data'         => $products->values(),
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

        if ((int) $product->shop->user_id === (int) $request->user()->id) {
            return response()->json(['message' => 'Не можна надіслати запит на власний товар'], 422);
        }

        $request->validate(['message' => 'nullable|string|max:300']);

        $purchaseRequest = PurchaseRequest::where('product_id', $id)
            ->where('buyer_id', $request->user()->id)
            ->first();

        if ($purchaseRequest) {
            $nextAllowedAt = $purchaseRequest->updated_at->copy()->addHours(self::BUY_REQUEST_COOLDOWN_HOURS);

            if (now()->lessThan($nextAllowedAt)) {
                return response()->json([
                    'message' => 'Запит уже надіслано. Повторити можна через день після попереднього запиту.',
                    'can_request_after' => $nextAllowedAt->toISOString(),
                    'retry_after_seconds' => max(1, $nextAllowedAt->getTimestamp() - now()->getTimestamp()),
                ], 429);
            }

            $purchaseRequest->update([
                'message' => $request->input('message'),
                'viewed_at' => null,
            ]);

            return response()->json([
                'ok' => true,
                'can_request_after' => now()->addHours(self::BUY_REQUEST_COOLDOWN_HOURS)->toISOString(),
            ]);
        }

        PurchaseRequest::create([
            'product_id' => $id,
            'buyer_id' => $request->user()->id,
            'message' => $request->input('message'),
            'viewed_at' => null,
        ]);

        return response()->json([
            'ok' => true,
            'can_request_after' => now()->addHours(self::BUY_REQUEST_COOLDOWN_HOURS)->toISOString(),
        ], 201);
    }
}
