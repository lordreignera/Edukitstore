<?php

namespace App\Services;

use App\Mail\OrderPaidThankYouMail;
use App\Mail\OrderReceivedMail;
use App\Mail\OrderFulfilmentUpdateMail;
use App\Mail\PartnerApplicationReceivedMail;
use App\Mail\PartnerApprovedMail;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\User;
use App\Notifications\NewOrderReceivedNotification;
use App\Notifications\OrderWorkflowNotification;
use App\Notifications\ProductAvailableNotification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Throwable;

class OrderNotificationService
{
    public function orderReceived(ShoppingList $order): void
    {
        $order->loadMissing('customer');
        $customerEmail = $order->email ?: $order->customer?->email;

        if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $this->sendMail($customerEmail, new OrderReceivedMail($order), [
                'event' => 'order_received',
                'reference' => $order->reference,
            ]);
        }

        foreach ($this->adminRecipients() as $admin) {
            try {
                $admin->notify(new NewOrderReceivedNotification($order));
                app(PushNotificationService::class)->sendTo($admin, [
                    'title' => 'New EduKit order',
                    'body' => "Order {$order->reference} is ready for review.",
                    'url' => route('admin.invoices.show', $order),
                    'tag' => 'edukit-order-'.$order->id,
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function paymentConfirmed(ShoppingList $order): void
    {
        $order->loadMissing('customer');
        $email = $order->email ?: $order->customer?->email;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendMail($email, new OrderPaidThankYouMail($order), [
                'event' => 'order_payment_confirmed',
                'reference' => $order->reference,
            ]);
        }
    }

    public function productAvailable(Product $product): void
    {
        if (! $product->is_active || $product->marketplace_stock_quantity < 1) {
            return;
        }

        // A saved browser subscription is the customer's explicit opt-in; never broadcast to every account.
        $customers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'customer'))
            ->whereHas('pushSubscriptions')
            ->get();

        foreach ($customers as $customer) {
            try {
                $customer->notify(new ProductAvailableNotification($product));
                app(PushNotificationService::class)->sendTo($customer, [
                    'title' => 'New product available',
                    'body' => "{$product->name} is now available in the EduKit store.",
                    'url' => route('website.products.show', $product),
                    'tag' => "edukit-product-{$product->id}",
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    public function orderInTransit(ShoppingList $order): void
    {
        $this->notifyCustomer(
            $order,
            'order_in_transit',
            'Your order is on the way',
            "Order {$order->reference} has left with the driver.",
        );
    }

    public function orderArrived(ShoppingList $order): void
    {
        $this->notifyCustomer(
            $order,
            'order_arrived',
            'Your order has arrived',
            "The driver has reached the destination for order {$order->reference}. Please check the items and confirm receipt.",
        );
    }

    public function itemsUnavailable(ShoppingList $order): void
    {
        $order->loadMissing('customer', 'lineItems');
        $items = $order->lineItems->filter(fn ($item) => (int) $item->unavailable_quantity > 0)
            ->map(fn ($item) => $item->product_name.' ('.(int) $item->unavailable_quantity.' unavailable)')
            ->implode(', ');
        $refund = $order->manualRefundDue();
        $title = $items === '' ? 'Availability update for your order' : 'Unavailable items in your order';
        $message = $items === ''
            ? 'We rechecked your order and all requested products are available. They will continue to delivery; no unavailable-item refund is due.'
            : "We could not include: {$items}. The available products will continue to delivery. A manual refund of UGX ".number_format($refund).' is pending.';
        if ($items !== '' && $order->payment_provider === 'demo') {
            $message .= ' This was a demo payment; no money was collected, so this is a dry-run refund record only.';
        }
        $email = $order->email ?: $order->customer?->email;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendMail($email, new OrderFulfilmentUpdateMail($order, $title, $message), [
                'event' => $items === '' ? 'order_availability_updated' : 'order_items_unavailable', 'reference' => $order->reference,
            ]);
        }

        if ($order->customer?->is_active) {
            $this->notifyUser(
                $order->customer,
                $items === '' ? 'order_availability_updated' : 'order_items_unavailable',
                $title,
                $message,
                $order,
                route('website.quote.show', $order->reference),
                'customer',
            );
        }
    }

    public function manualRefundRecorded(ShoppingList $order): void
    {
        $order->loadMissing('customer');
        $message = $order->payment_provider === 'demo'
            ? 'EduKit recorded the dry-run refund of UGX '.number_format((int) $order->manual_refund_amount)
                .' for order '.$order->reference.'. No money was collected or returned. Test reference: '.$order->manual_refund_reference.'.'
            : 'EduKit recorded your manual refund of UGX '.number_format((int) $order->manual_refund_amount)
                .' for order '.$order->reference.'. Reference: '.$order->manual_refund_reference.'.';
        $email = $order->email ?: $order->customer?->email;

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendMail($email, new OrderFulfilmentUpdateMail($order, 'Your order refund was recorded', $message), [
                'event' => 'order_manual_refund_recorded', 'reference' => $order->reference,
            ]);
        }

        if ($order->customer?->is_active) {
            $this->notifyUser(
                $order->customer,
                'order_manual_refund_recorded',
                'Order refund recorded',
                $message,
                $order,
                route('website.quote.show', $order->reference),
                'customer',
            );
        }
    }

    public function customerConfirmedReceipt(ShoppingList $order): void
    {
        $this->notifyAdmins(
            $order,
            'customer_confirmed_receipt',
            'Customer confirmed delivery',
            "The customer confirmed receipt of order {$order->reference}.",
        );
    }

    public function applicationReceived(string $email, string $name, string $type): void
    {
        $this->sendMail($email, new PartnerApplicationReceivedMail($name, $type), [
            'event' => 'partner_application_received',
            'partner_type' => $type,
        ]);
    }

    public function partnerApproved(User $user, string $type): void
    {
        $this->sendMail($user->email, new PartnerApprovedMail($user->name, $type), [
            'event' => 'partner_approved',
            'partner_type' => $type,
            'user_id' => $user->id,
        ]);
    }

    private function sendMail(string $email, Mailable $mail, array $context): void
    {
        try {
            Mail::to($email)->send($mail);
        } catch (Throwable $exception) {
            Log::error('EduKit transactional email could not be delivered.', $context + [
                'exception' => $exception::class,
            ]);
        }
    }

    private function notifyCustomer(ShoppingList $order, string $event, string $title, string $body): void
    {
        $order->loadMissing('customer');
        $customer = $order->customer;

        // Guest orders have no customer user_id, so they must not receive another account's private alert.
        if (! $customer || ! $customer->is_active) {
            return;
        }

        $this->notifyUser(
            $customer,
            $event,
            $title,
            $body,
            $order,
            route('website.quote.show', $order->reference),
            'customer',
        );
    }

    private function notifyAdmins(ShoppingList $order, string $event, string $title, string $body): void
    {
        foreach ($this->adminRecipients() as $admin) {
            $this->notifyUser(
                $admin,
                $event,
                $title,
                $body,
                $order,
                route('admin.invoices.show', $order),
                'admin',
            );
        }
    }

    private function notifyUser(User $user, string $event, string $title, string $body, ShoppingList $order, string $url, string $audience): void
    {
        try {
            $user->notify(new OrderWorkflowNotification($event, $title, $body, $order->reference, $url));
            app(PushNotificationService::class)->sendTo($user, [
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'tag' => "edukit-{$audience}-{$event}-{$order->id}",
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function adminRecipients()
    {
        // Store each alert against the individual active user who can review invoices.
        $admins = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'admin']))
            ->get();

        if (Permission::query()->where('name', 'invoices.view')->where('guard_name', 'web')->exists()) {
            $admins = $admins->merge(
                User::query()->where('is_active', true)->permission('invoices.view')->get()
            );
        }

        return $admins->unique('id');
    }
}
