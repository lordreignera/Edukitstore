<?php

namespace App\Services;

use App\Mail\OrderPaidThankYouMail;
use App\Mail\OrderReceivedMail;
use App\Mail\PartnerApplicationReceivedMail;
use App\Mail\PartnerApprovedMail;
use App\Models\ShoppingList;
use App\Models\User;
use App\Notifications\NewOrderReceivedNotification;
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

        $admins = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'admin']))
            ->get();

        if (Permission::query()->where('name', 'invoices.view')->where('guard_name', 'web')->exists()) {
            $admins = $admins->merge(
                User::query()->where('is_active', true)->permission('invoices.view')->get()
            )->unique('id');
        }

        foreach ($admins as $admin) {
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
}
