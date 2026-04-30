<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserShopController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $shop = $request->user()->shop()->with('products')->first();
        return response()->json($shop);
    }

    public function createOrUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
        ]);

        $shop = Shop::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json($shop->load('products'), $shop->wasRecentlyCreated ? 201 : 200);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'price'       => 'nullable|numeric|min:0|max:9999999',
            'photo'       => 'nullable|image|max:10240',
        ]);

        $user = $request->user();
        $fallbackName = trim((string) $user->nickname) !== ''
            ? $user->nickname
            : 'Моя палатка';

        $shop = Shop::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $fallbackName]
        );

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('products', 'public');
        }

        $product = Product::create([
            'shop_id'     => $shop->id,
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'price'       => $request->input('price'),
            'photo_path'  => $photoPath,
        ]);

        return response()->json($product, 201);
    }

    public function destroyProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if ($product->shop->user_id !== $request->user()->id && !$request->user()->is_admin) {
            abort(403);
        }

        if ($product->photo_path) {
            Storage::disk('public')->delete($product->photo_path);
        }

        $product->delete();

        return response()->json(['ok' => true]);
    }

    public function requests(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;
        if (!$shop) return response()->json([]);

        $requests = PurchaseRequest::whereHas('product', fn ($q) => $q->where('shop_id', $shop->id))
            ->with([
                'product:id,title,photo_path',
                'buyer:id,nickname,first_name,last_name,phone',
            ])
            ->orderByRaw('viewed_at IS NULL DESC')
            ->latest()
            ->get();

        return response()->json($requests);
    }

    public function markRequestViewed(Request $request, int $id): JsonResponse
    {
        $shop = $request->user()->shop;
        if (!$shop) abort(403);

        $req = PurchaseRequest::with('product:id,shop_id')->findOrFail($id);
        if (!$req->product || $req->product->shop_id !== $shop->id) {
            abort(403);
        }

        if (!$req->viewed_at) {
            $req->update(['viewed_at' => now()]);
        }

        return response()->json(['ok' => true, 'viewed_at' => $req->viewed_at]);
    }

    public function markAllRequestsViewed(Request $request): JsonResponse
    {
        $shop = $request->user()->shop;
        if (!$shop) return response()->json(['ok' => true, 'updated' => 0]);

        $updated = PurchaseRequest::whereHas('product', fn ($q) => $q->where('shop_id', $shop->id))
            ->whereNull('viewed_at')
            ->update(['viewed_at' => now()]);

        return response()->json(['ok' => true, 'updated' => $updated]);
    }
}
