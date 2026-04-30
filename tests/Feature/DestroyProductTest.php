<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DestroyProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_can_delete_own_product_with_purchase_requests(): void
    {
        Storage::fake('public');

        $owner = $this->user('owner', '0501112233');
        $buyer = $this->user('buyer', '0501112244');
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Owner shop']);
        Storage::disk('public')->put('products/test.jpg', 'image');
        $product = Product::create([
            'shop_id' => $shop->id,
            'title' => 'Test product',
            'photo_path' => 'products/test.jpg',
        ]);
        PurchaseRequest::create(['product_id' => $product->id, 'buyer_id' => $buyer->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/my/shop/products/{$product->id}");

        $response->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Товар видалено.']);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('purchase_requests', ['product_id' => $product->id]);
        Storage::disk('public')->assertMissing('products/test.jpg');
    }

    public function test_non_owner_gets_readable_error_when_deleting_product(): void
    {
        $owner = $this->user('owner', '0501112233');
        $other = $this->user('other', '0501112244');
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Owner shop']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Test product']);

        $response = $this->actingAs($other, 'sanctum')
            ->deleteJson("/api/my/shop/products/{$product->id}");

        $response->assertForbidden()
            ->assertJson(['message' => 'Можна видаляти лише товари зі свого магазину.']);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    private function user(string $nickname, string $phone): User
    {
        return User::create([
            'last_name' => 'Test',
            'first_name' => 'User',
            'patronymic' => 'Example',
            'street' => 'Main',
            'nickname' => $nickname,
            'phone' => $phone,
            'password' => Hash::make('password'),
        ]);
    }
}
