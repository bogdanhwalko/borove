<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BuyRequestCooldownTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_buyer_can_repeat_purchase_request_only_after_one_day(): void
    {
        $start = Carbon::parse('2026-05-01 10:00:00');
        Carbon::setTestNow($start);

        [$buyer, $product] = $this->buyerAndProduct();

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/products/{$product->id}/buy-request", ['message' => 'first'])
            ->assertCreated()
            ->assertJsonStructure(['ok', 'can_request_after']);

        Carbon::setTestNow($start->copy()->addHours(23));

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/products/{$product->id}/buy-request", ['message' => 'second'])
            ->assertStatus(429)
            ->assertJson([
                'message' => 'Запит уже надіслано. Повторити можна через день після попереднього запиту.',
            ]);

        $this->assertDatabaseCount('purchase_requests', 1);
        $this->assertSame('first', PurchaseRequest::first()->message);

        Carbon::setTestNow($start->copy()->addDay()->addSecond());

        $this->actingAs($buyer, 'sanctum')
            ->postJson("/api/products/{$product->id}/buy-request", ['message' => 'second'])
            ->assertOk()
            ->assertJsonStructure(['ok', 'can_request_after']);

        $this->assertDatabaseCount('purchase_requests', 1);
        $this->assertSame('second', PurchaseRequest::first()->message);
    }

    public function test_product_index_marks_recent_buyer_request_as_locked(): void
    {
        $start = Carbon::parse('2026-05-01 10:00:00');
        Carbon::setTestNow($start);

        [$buyer, $product] = $this->buyerAndProduct();
        PurchaseRequest::create([
            'product_id' => $product->id,
            'buyer_id' => $buyer->id,
            'message' => 'first',
        ]);

        $this->actingAs($buyer, 'sanctum')
            ->getJson('/api/products?per_page=9')
            ->assertOk()
            ->assertJsonPath('data.0.viewer_can_request', false)
            ->assertJsonPath('data.0.viewer_request_locked_until', $start->copy()->addDay()->toISOString());

        Carbon::setTestNow($start->copy()->addDay()->addSecond());

        $this->actingAs($buyer, 'sanctum')
            ->getJson('/api/products?per_page=9')
            ->assertOk()
            ->assertJsonPath('data.0.viewer_can_request', true)
            ->assertJsonPath('data.0.viewer_request_locked_until', null);
    }

    /**
     * @return array{0: User, 1: Product}
     */
    private function buyerAndProduct(): array
    {
        $owner = $this->user('owner', '0501112233');
        $buyer = $this->user('buyer', '0501112244');
        $shop = Shop::create(['user_id' => $owner->id, 'name' => 'Owner shop']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Test product']);

        return [$buyer, $product];
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
