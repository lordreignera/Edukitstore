<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Services\CustomerOrderClaimService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CustomerOrderClaimService $orderClaims): View
    {
        $customer = $request->user();
        $orderClaims->claimVerifiedGuestOrders($customer);
        $ordersQuery = $customer->shoppingLists();

        $orders = (clone $ordersQuery)
            ->with('assignedDriver', 'school')
            ->latest()
            ->paginate(10);

        $stats = [
            'total' => (clone $ordersQuery)->count(),
            'awaiting_payment' => (clone $ordersQuery)
                ->where('status', ShoppingList::STATUS_QUOTED)
                ->where('payment_status', '!=', ShoppingList::PAYMENT_PAID)
                ->count(),
            'in_progress' => (clone $ordersQuery)
                ->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->whereNotIn('status', [ShoppingList::STATUS_FULFILLED, ShoppingList::STATUS_CANCELLED, ShoppingList::STATUS_REJECTED])
                ->count(),
            'completed' => (clone $ordersQuery)->where('status', ShoppingList::STATUS_FULFILLED)->count(),
        ];

        return view('customer.dashboard', compact('customer', 'orders', 'stats'));
    }
}
