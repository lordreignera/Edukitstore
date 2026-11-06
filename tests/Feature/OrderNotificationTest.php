<?php

namespace Tests\Feature;

use App\Mail\OrderReceivedMail;
use App\Models\Driver;
use App\Models\ShoppingList;
use App\Models\User;
use App\Notifications\NewOrderReceivedNotification;
use App\Services\OrderNotificationService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_sends_customer_confirmation_and_stores_admin_alert(): void
    {
        Mail::fake();
        $admin = $this->adminWithOrderPermission();
        $push = new class extends PushNotificationService
        {
            public array $sent = [];

            public function sendTo(User $user, array $payload): void
            {
                $this->sent[] = ['user_id' => $user->id, 'payload' => $payload];
            }
        };
        $this->app->instance(PushNotificationService::class, $push);
        $customer = User::factory()->create(['email' => 'order-customer@example.test']);
        $order = $this->orderFor($customer);

        app(OrderNotificationService::class)->orderReceived($order);

        Mail::assertSent(OrderReceivedMail::class, fn (OrderReceivedMail $mail): bool => $mail->order->is($order)
            && $mail->hasTo($customer->email));
        $this->assertDatabaseHas('notifications', [
            'type' => NewOrderReceivedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);
        $this->assertSame($admin->id, $push->sent[0]['user_id']);
        $this->assertSame('New EduKit order', $push->sent[0]['payload']['title']);
        $this->assertSame(route('admin.invoices.show', $order), $push->sent[0]['payload']['url']);
    }

    public function test_admin_can_review_only_their_own_order_notifications(): void
    {
        $admin = $this->adminWithOrderPermission();
        $anotherAdmin = $this->adminWithOrderPermission();
        $customer = User::factory()->create();
        $order = $this->orderFor($customer);
        $nextOrder = $this->orderFor($customer);
        $admin->notify(new NewOrderReceivedNotification($order));
        $admin->notify(new NewOrderReceivedNotification($nextOrder));
        $notificationId = $admin->notifications()->where('data->reference', $order->reference)->firstOrFail()->id;

        $this->actingAs($anotherAdmin)
            ->post(route('admin.notifications.open', $notificationId))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee($order->reference)
            ->assertSee($nextOrder->reference)
            ->assertSee('data-push-notifications', false);

        $this->post(route('admin.notifications.open', $notificationId))
            ->assertRedirect(route('admin.invoices.show', $order));

        $this->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertDontSee($order->reference)
            ->assertSee($nextOrder->reference);

        $this->assertNotNull($admin->notifications()->findOrFail($notificationId)->read_at);
        $this->assertNull($admin->notifications()->where('data->reference', $nextOrder->reference)->firstOrFail()->read_at);
    }

    public function test_push_subscription_accepts_known_https_provider_and_rejects_private_endpoint(): void
    {
        config([
            'services.webpush.public_key' => 'test-public-key',
            'services.webpush.private_key' => 'test-private-key',
            'services.webpush.subject' => 'mailto:alerts@example.test',
        ]);
        $admin = $this->adminWithOrderPermission();
        $endpoint = 'https://fcm.googleapis.com/fcm/send/test-device-token';
        $subscription = [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'test-device-public-key', 'auth' => 'test-device-auth-token'],
        ];

        $this->actingAs($admin)
            ->postJson(route('push-subscriptions.store'), $subscription)
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $admin->id,
            'endpoint_hash' => hash('sha256', $endpoint),
            'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($admin)
            ->postJson(route('push-subscriptions.store'), array_replace($subscription, [
                'endpoint' => 'https://127.0.0.1/private-service',
            ]))
            ->assertUnprocessable();
    }

    public function test_driver_can_enable_push_notifications(): void
    {
        config([
            'services.webpush.public_key' => 'test-public-key',
            'services.webpush.private_key' => 'test-private-key',
            'services.webpush.subject' => 'mailto:alerts@example.test',
        ]);
        $driver = User::factory()->create(['is_active' => true]);
        $driver->assignRole(Role::findOrCreate('delivery-person', 'web'));
        $endpoint = 'https://fcm.googleapis.com/fcm/send/driver-device-token';

        $this->actingAs($driver)
            ->postJson(route('push-subscriptions.store'), [
                'endpoint' => $endpoint,
                'keys' => ['p256dh' => 'driver-public-key', 'auth' => 'driver-auth-token'],
            ])
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $driver->id,
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);
    }

    public function test_driver_receives_a_push_when_a_paid_order_is_assigned(): void
    {
        $admin = $this->adminWithOrderPermission();
        $admin->givePermissionTo(Permission::findOrCreate('invoices.update', 'web'));
        $driverUser = User::factory()->create(['is_active' => true]);
        $driverUser->assignRole(Role::findOrCreate('delivery-person', 'web'));
        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'name' => 'Driver Test',
            'email' => $driverUser->email,
            'is_approved' => true,
            'is_available' => true,
        ]);
        $order = $this->orderFor(User::factory()->create());
        $order->update(['payment_status' => ShoppingList::PAYMENT_PAID]);
        $push = new class extends PushNotificationService
        {
            public array $sent = [];

            public function sendTo(User $user, array $payload): void
            {
                $this->sent[] = ['user' => $user, 'payload' => $payload];
            }
        };
        $this->app->instance(PushNotificationService::class, $push);

        $this->actingAs($admin)
            ->patch(route('admin.invoices.update', $order), [
                'status' => ShoppingList::STATUS_QUOTED,
                'assigned_driver_id' => $driver->id,
            ])
            ->assertRedirect();

        $this->assertCount(1, $push->sent);
        $this->assertSame($driverUser->id, $push->sent[0]['user']->id);
        $this->assertSame('New delivery assigned', $push->sent[0]['payload']['title']);
        $this->assertSame(route('driver.deliveries.index', ['status' => 'ready']), $push->sent[0]['payload']['url']);
    }

    private function adminWithOrderPermission(): User
    {
        $role = Role::findOrCreate('super-admin', 'web');
        $role->givePermissionTo(Permission::findOrCreate('invoices.view', 'web'));
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function orderFor(User $customer): ShoppingList
    {
        return ShoppingList::create([
            'customer_user_id' => $customer->id,
            'parent_name' => 'Customer Test',
            'phone' => '+256700000001',
            'email' => $customer->email,
            'source' => ShoppingList::SOURCE_CART,
            'reference' => 'EDU-TEST-'.strtoupper(str()->random(8)),
            'delivery_preference' => 'pickup',
            'items_subtotal' => 10000,
            'delivery_fee' => 0,
            'estimated_total' => 10000,
            'status' => ShoppingList::STATUS_QUOTED,
            'payment_status' => ShoppingList::PAYMENT_UNPAID,
        ]);
    }
}
