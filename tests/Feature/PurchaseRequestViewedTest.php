<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PurchaseRequestViewedTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_owner_can_mark_purchase_request_as_viewed(): void
    {
        [$owner, $request] = $this->purchaseRequestForOwner();

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/my/shop/requests/{$request->id}/view");

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['viewed_at']);

        $this->assertNotNull($request->fresh()->viewed_at);
    }

    public function test_other_shop_owner_gets_readable_error_when_marking_request_viewed(): void
    {
        [, $request] = $this->purchaseRequestForOwner();
        $otherOwner = $this->user('other-owner', '0501112255');
        Shop::create(['user_id' => $otherOwner->id, 'name' => 'Other shop']);

        $response = $this->actingAs($otherOwner, 'sanctum')
            ->postJson("/api/my/shop/requests/{$request->id}/view");

        $response->assertForbidden()
            ->assertJson(['message' => 'Можна переглядати лише заявки свого магазину.']);

        $this->assertNull($request->fresh()->viewed_at);
    }

    /**
     * @return array{0: User, 1: PurchaseRequest}
     */
    private function purchaseRequestForOwner(): array
    {
        $owner = $this->user('owner', '0501112233');
        $buyer = $this->user('buyer', '0501112244');
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Owner shop']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Test product']);
        $request = PurchaseRequest::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
        ]);

        return [$owner, $request];
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
