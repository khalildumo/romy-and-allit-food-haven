<?php

namespace Tests\Feature;

use App\Models\MenuProduct;
use App\Models\EmailOtp;
use App\Models\EmailVerificationCode;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FoodHavenTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_loads_and_displays_seeded_menu_products(): void
    {
        MenuProduct::query()->create([
            'name' => 'Chicken Adobo',
            'category' => 'mains',
            'description' => 'A Filipino favorite',
            'price' => 40,
            'image_path' => 'assets/Dishes/Chicken Adobo.jpeg',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Chicken Adobo')
            ->assertSee('₱40.00');
    }

    public function test_database_seeder_creates_categories_profile_and_initial_menu(): void
    {
        $this->seed();

        $this->assertDatabaseCount('product_categories', 4);
        $this->assertDatabaseCount('menu_products', 12);
        $this->assertDatabaseHas('canteen_profile', ['id' => 1]);
    }

    public function test_customer_can_authenticate_with_the_legacy_username_and_password_fields(): void
    {
        $user = User::factory()->create(['password_hash' => 'password']);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_registration_requires_email_verification(): void
    {
        Mail::fake();

        $this->post('/register', [
            'full_name' => 'New Customer',
            'username' => 'new_customer',
            'institution_id' => 'ISPSC-12',
            'email' => 'new@example.com',
            'phone' => '09123456789',
            'password' => 'foodhaven-password',
            'password_confirmation' => 'foodhaven-password',
        ])->assertRedirect('/verify-email');

        $user = User::query()->where('username', 'new_customer')->firstOrFail();
        $this->assertFalse($user->email_verified);
        $this->assertDatabaseHas('email_verification_codes', ['user_id' => $user->id]);

        EmailVerificationCode::query()->whereKey($user->id)->update([
            'code_hash' => Hash::make('123456'),
        ]);
        $this->withSession(['pending_email_verification_user_id' => $user->id])
            ->post('/verify-email', ['code' => '123456'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email_verified' => true]);
    }

    public function test_standalone_email_otp_can_be_sent_and_verified_once(): void
    {
        Mail::fake();

        $this->post('/send-otp', ['email' => 'guest@example.com'])
            ->assertRedirect('/verify-otp');

        $otp = EmailOtp::query()->firstOrFail();
        $otp->update(['otp_hash' => Hash::make('123456')]);

        $this->withSession([
            'otp_email' => $otp->email,
            'otp_last_id' => $otp->id,
        ])->post('/verify-otp', ['otp' => '123456'])
            ->assertRedirect('/otp-success');

        $this->assertNotNull($otp->fresh()->used_at);
        $this->get('/otp-success')->assertOk()->assertSee('guest@example.com');
    }

    public function test_checkout_uses_database_prices_and_decrements_limited_stock(): void
    {
        $product = MenuProduct::query()->create([
            'name' => 'Pork Adobo',
            'category' => 'mains',
            'description' => 'Comfort food',
            'price' => 40,
            'stock_enabled' => true,
            'stock_quantity' => 5,
            'is_available' => true,
        ]);

        $this->postJson('/orders', [
            'student_name' => 'Test Customer',
            'student_id' => 'ISPSC-12',
            'payment_method' => 'cash',
            'fulfillment_type' => 'pickup',
            'items' => [['id' => $product->id, 'quantity' => 2, 'price' => 0.01]],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', '80.00');

        $this->assertDatabaseHas('orders', [
            'student_name' => 'Test Customer',
            'total_amount' => 80,
            'payment_status' => 'pay_on_pickup',
        ]);
        $this->assertDatabaseHas('menu_products', ['id' => $product->id, 'stock_quantity' => 3]);
    }

    public function test_checkout_rejects_products_that_are_out_of_stock(): void
    {
        $product = MenuProduct::query()->create([
            'name' => 'Sold out dish',
            'category' => 'mains',
            'price' => 40,
            'stock_enabled' => true,
            'stock_quantity' => 0,
            'is_available' => true,
        ]);

        $this->postJson('/orders', [
            'student_name' => 'Test Customer',
            'payment_method' => 'cash',
            'fulfillment_type' => 'pickup',
            'items' => [['id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_order_status_must_follow_the_allowed_transition_sequence(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::query()->create([
            'order_number' => 'AW-261029-ABC123',
            'student_name' => 'Test Customer',
            'payment_method' => 'cash',
            'payment_status' => 'pay_on_pickup',
            'fulfillment_type' => 'pickup',
            'order_status' => 'pending',
            'total_amount' => 40,
            'items_json' => [],
        ]);

        $this->actingAs($admin)
            ->patchJson("/admin/orders/{$order->id}/status", ['status' => 'preparing'])
            ->assertOk()
            ->assertJsonPath('order_status', 'preparing');

        $this->patchJson("/admin/orders/{$order->id}/status", ['status' => 'completed'])
            ->assertUnprocessable();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'preparing']);
    }

    public function test_only_pending_gcash_payments_can_be_marked_paid_by_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::query()->create([
            'order_number' => 'AW-261029-GCA123',
            'student_name' => 'Test Customer',
            'payment_method' => 'gcash',
            'payment_status' => 'pending',
            'fulfillment_type' => 'pickup',
            'order_status' => 'pending',
            'total_amount' => 40,
            'items_json' => [],
        ]);

        $this->actingAs($admin)
            ->patchJson("/admin/orders/{$order->id}/payment")
            ->assertOk()
            ->assertJsonPath('payment_status', 'paid');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
    }

    public function test_admin_routes_are_not_available_to_customers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_admin_dashboard_renders_for_an_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Order overview.');
        $this->get('/admin/products')->assertOk()->assertSee('Manage products');
        $this->get('/admin/settings')->assertOk()->assertSee('Canteen settings');
    }
}
