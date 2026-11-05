<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceGuideController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $guide = $this->guideFor($user);
        $guide['navigation'] = $this->navigationFor($user);

        return view('guides.index', [
            'guide' => $guide,
        ]);
    }

    public function download(): StreamedResponse
    {
        $guide = $this->guideFor(auth()->user());

        return response()->streamDownload(
            fn () => print view('guides.download', compact('guide'))->render(),
            'edukit-'.$guide['slug'].'-user-guide.html',
            ['Content-Type' => 'text/html; charset=UTF-8'],
        );
    }

    private function guideFor(User $user): array
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return [
                'slug' => 'administrator',
                'layout' => 'admin',
                'portalName' => 'Administrator',
                'eyebrow' => 'Administrator workflow',
                'title' => 'How to run EduKit',
                'intro' => 'Work through the queues in order: keep the catalogue and stock accurate, review partner and customer requests, then monitor fulfilment and settlement.',
                'steps' => [
                    ['title' => 'Maintain the catalogue', 'description' => 'Create categories and products before stock or customer orders are processed.', 'route' => 'admin.products.index', 'action' => 'Open products', 'permission' => 'products.view'],
                    ['title' => 'Keep stock accurate', 'description' => 'Record opening stock, intake and transfers, then watch low-stock and display-stock indicators.', 'route' => 'admin.inventory.index', 'action' => 'Open inventory', 'permission' => 'inventory.view'],
                    ['title' => 'Approve partners', 'description' => 'Review supplier and driver applications before their accounts can be used for fulfilment.', 'route' => 'admin.suppliers.index', 'action' => 'Review suppliers', 'permission' => 'suppliers.view'],
                    ['title' => 'Review supplier offers', 'description' => 'Approve or reject supplier products and stock proposals before they become available for customer orders.', 'route' => 'admin.supplier-offers.index', 'action' => 'Review offers', 'permission' => 'supplier-products.view'],
                    ['title' => 'Process customer requests', 'description' => 'Review shopping lists and quotes, verify payment or exceptions, and resolve paid stock before delivery.', 'route' => 'admin.shopping-lists.index', 'action' => 'Open shopping lists', 'permission' => 'shopping-lists.view'],
                    ['title' => 'Monitor orders', 'description' => 'Use Orders & Fulfilment to review payment, assign drivers and follow deliveries through customer receipt.', 'route' => 'admin.invoices.index', 'action' => 'Open orders', 'permission' => 'invoices.view'],
                    ['title' => 'Manage access and results', 'description' => 'Use users, roles and permissions to control access, then use the profit report for performance review.', 'route' => 'admin.users.index', 'action' => 'Open users', 'permission' => 'users.view'],
                ],
                'notes' => [
                    'Supplier and driver logins become usable after their application or account is approved and active.',
                    'Do not treat a quote as ready for delivery until payment and stock checks have been completed.',
                ],
            ];
        }

        if ($user->hasRole('finance-admin')) {
            return [
                'slug' => 'finance',
                'layout' => 'portal',
                'portalName' => 'Finance Portal',
                'eyebrow' => 'Finance workflow',
                'title' => 'How to read the finance report',
                'intro' => 'This workspace is read-only. Use the filters and settlement columns to reconcile paid sales without mixing EduKit warehouse stock with supplier-direct stock.',
                'steps' => [
                    ['title' => 'Set the reporting period', 'description' => 'Choose a source and date range, or leave the filters broad to review all paid sales.', 'route' => 'finance.dashboard', 'action' => 'Refresh report'],
                    ['title' => 'Separate stock owners', 'description' => 'EduKit warehouse sales show stock cost and gross profit; supplier-direct sales show supplier payable and EduKit margin.'],
                    ['title' => 'Check settlement', 'description' => 'Supplier-direct earnings become earned after delivery; use the settlement column to identify pending or reserved amounts.'],
                    ['title' => 'Reconcile each line', 'description' => 'Use the order reference, product, quantity, revenue and cost/payable columns to trace a paid sale back to its source.'],
                ],
                'notes' => [
                    'Finance does not approve products, stock, users or deliveries from this workspace.',
                    'Operational corrections belong in the administrator workspace and should be followed by a report refresh.',
                ],
            ];
        }

        if ($user->hasRole('supplier')) {
            return [
                'slug' => 'supplier',
                'layout' => 'portal',
                'portalName' => 'Supplier Portal',
                'eyebrow' => 'Supplier workflow',
                'title' => 'How to supply through EduKit',
                'intro' => 'Submit products or stock for review, wait for approval, then monitor the batches that EduKit receives and the earnings released after delivery.',
                'steps' => [
                    ['title' => 'Submit a product or stock', 'description' => 'Use My Products to choose an existing catalogue product or propose a new one, then enter your price and quantity.', 'route' => 'supplier.offers.create', 'action' => 'Submit an offer'],
                    ['title' => 'Wait for approval', 'description' => 'New products and stock are pending until the EduKit team reviews them. Rejected offers show the review note.'],
                    ['title' => 'Restock approved products', 'description' => 'For an approved product, submit additional quantity from My Products. The new quantity is reviewed before it is available.'],
                    ['title' => 'Check received batches', 'description' => 'Use Stock Records to confirm quantities recorded by the EduKit warehouse and see what remains available.', 'route' => 'supplier.stock.index', 'action' => 'View stock records'],
                    ['title' => 'Review earnings', 'description' => 'Sales, supplier payable and settlement status are shown separately; earnings are released after the customer receives the order.', 'route' => 'supplier.earnings.index', 'action' => 'View earnings'],
                ],
                'notes' => [
                    'Your customer price and availability are controlled through the EduKit approval process.',
                    'A supplier account must be approved and active before this workspace can be used.',
                ],
            ];
        }

        if ($user->hasRole('delivery-person')) {
            return [
                'slug' => 'delivery',
                'layout' => 'portal',
                'portalName' => 'Delivery Partner',
                'eyebrow' => 'Delivery workflow',
                'title' => 'How to complete an assigned trip',
                'intro' => 'Only start a journey when the order is paid, stock is ready and no payment exception is shown. The customer completes the final receipt confirmation.',
                'steps' => [
                    ['title' => 'Set your availability', 'description' => 'Mark yourself available when you can accept trips, and add a note when you need to explain an unavailable status.'],
                    ['title' => 'Open My Deliveries', 'description' => 'Use the ready and active filters to find assigned trips and check the customer, destination and order status.', 'route' => 'driver.deliveries.index', 'action' => 'Open deliveries'],
                    ['title' => 'Start the journey', 'description' => 'Start only after payment is verified and the trip is not under a payment exception.'],
                    ['title' => 'Record arrival', 'description' => 'When you reach the destination, mark that you have reached and add useful delivery notes.'],
                    ['title' => 'Hand over and wait for confirmation', 'description' => 'Let the customer inspect the order and confirm receipt. You do not mark the final customer receipt yourself.'],
                ],
                'notes' => [
                    'If payment is pending or under review, wait for the administrator to resolve it instead of starting the trip.',
                    'Assigned trips remain visible even when you switch your availability to unavailable.',
                ],
            ];
        }

        if ($user->hasAnyRole(['school-admin', 'school-receiver'])) {
            $isSchoolAdmin = $user->hasRole('school-admin');

            return [
                'slug' => $isSchoolAdmin ? 'school-administrator' : 'school-receiver',
                'layout' => 'portal',
                'portalName' => $isSchoolAdmin ? 'School Administrator' : 'School Receiver',
                'eyebrow' => 'School workflow',
                'title' => $isSchoolAdmin ? 'How to request school supplies' : 'How to receive a school delivery',
                'intro' => $isSchoolAdmin
                    ? 'Browse the catalogue or send a shopping list for quotation. Sign in with your EduKit account to keep each order in your history.'
                    : 'Use your EduKit account to reopen an order, check progress and complete receipt.',
                'steps' => $isSchoolAdmin ? [
                    ['title' => 'Browse the catalogue', 'description' => 'Review the current EduKit products before deciding whether to shop directly or send a full list.', 'route' => 'website.products.index', 'action' => 'Browse products'],
                    ['title' => 'Send a shopping list', 'description' => 'Upload the required items and quantities so EduKit can prepare a quotation.', 'route' => 'website.upload-list', 'action' => 'Upload a list'],
                    ['title' => 'Review the quote', 'description' => 'Open My Orders to review the itemized quote and complete payment when ready.'],
                    ['title' => 'Track the order', 'description' => 'Sign in to My Orders to follow payment, driver assignment and delivery progress.', 'route' => 'customer.dashboard', 'action' => 'Open my orders'],
                    ['title' => 'Confirm receipt', 'description' => 'After delivery, inspect the items and acknowledge receipt from the order page.'],
                ] : [
                    ['title' => 'Get the order page', 'description' => 'Ask the signed-in order contact to open the order from their My Orders dashboard.'],
                    ['title' => 'Track the delivery', 'description' => 'Use the order page to view current delivery status.', 'route' => 'website.track-order', 'action' => 'Order tracking'],
                    ['title' => 'Check the delivered items', 'description' => 'When the driver arrives, compare the delivery with the order before accepting it.'],
                    ['title' => 'Confirm receipt', 'description' => 'Acknowledge receipt from the order page after the delivery has been checked.'],
                ],
                'notes' => [
                    'Only the signed-in account that placed an order can open its private order details.',
                ],
            ];
        }

        return [
            'slug' => 'customer-checkout',
            'layout' => 'portal',
            'portalName' => 'Customer checkout',
            'eyebrow' => 'Customer account workflow',
            'title' => 'How to place and follow an order',
            'intro' => 'Create an account or sign in before checkout. Your orders then stay together in the My Orders dashboard.',
            'steps' => [
                ['title' => 'Shop supplies', 'description' => 'Browse the catalogue and add the supplies you need to your cart.', 'route' => 'website.products.index', 'action' => 'Browse products'],
                ['title' => 'Create an account or sign in', 'description' => 'Use your email address and password so EduKit can keep your orders together.', 'route' => 'login', 'action' => 'Sign in'],
                ['title' => 'Enter your order contact', 'description' => 'Provide your name and the active MTN MoMo or Airtel Money number you intend to pay with. The number is saved with the order.'],
                ['title' => 'Send a list for a quote', 'description' => 'Upload your school list while signed in; EduKit will prepare the quote in your account.', 'route' => 'website.upload-list', 'action' => 'Upload a list'],
                ['title' => 'Review, pay and track', 'description' => 'Open My Orders any time to review the quote, pay and follow delivery progress.', 'route' => 'customer.dashboard', 'action' => 'Open my orders'],
                ['title' => 'Confirm receipt', 'description' => 'Inspect the delivery and acknowledge receipt from the order page once everything is received.'],
            ],
            'notes' => [
                'The Mobile Money number is stored on each order. Private order details are available only after signing in to the account that placed the order.',
            ],
        ];
    }

    private function navigationFor(User $user): array
    {
        if ($user->hasRole('finance-admin')) {
            return [
                ['label' => 'Profit report', 'route' => 'finance.dashboard', 'pattern' => 'finance.dashboard', 'icon' => 'chart'],
            ];
        }

        if ($user->hasRole('supplier')) {
            return config('portals.supplier_navigation');
        }

        if ($user->hasRole('delivery-person')) {
            return [
                ['label' => 'Dashboard', 'route' => 'driver.dashboard', 'pattern' => 'driver.dashboard', 'icon' => 'home'],
                ['label' => 'My Deliveries', 'route' => 'driver.deliveries.index', 'pattern' => 'driver.deliveries.*', 'icon' => 'drivers'],
            ];
        }

        if ($user->hasAnyRole(['school-admin', 'school-receiver'])) {
            $navigation = [
                ['label' => 'Dashboard', 'route' => 'school.dashboard', 'pattern' => 'school.dashboard', 'icon' => 'home'],
                ['label' => 'Order tracking help', 'route' => 'website.track-order', 'pattern' => 'website.track-order', 'icon' => 'drivers'],
            ];

            if ($user->hasRole('school-admin')) {
                $navigation[] = ['label' => 'Browse products', 'route' => 'website.products.index', 'pattern' => 'website.products.*', 'icon' => 'products'];
                $navigation[] = ['label' => 'Upload a list', 'route' => 'website.upload-list', 'pattern' => 'website.upload-list', 'icon' => 'upload'];
            }

            return $navigation;
        }

        return [
            ['label' => 'Shop supplies', 'route' => 'website.products.index', 'pattern' => 'website.products.*', 'icon' => 'products'],
            ['label' => 'Upload a list', 'route' => 'website.upload-list', 'pattern' => 'website.upload-list', 'icon' => 'upload'],
            ['label' => 'Order tracking help', 'route' => 'website.track-order', 'pattern' => 'website.track-order', 'icon' => 'drivers'],
        ];
    }
}
