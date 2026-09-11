<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $page): View
    {
        $pages = [
            'upload-list' => [
                'title' => 'Upload School List',
                'heading' => 'Shopping list upload is coming soon',
                'copy' => 'This flow will let parents upload a photo, PDF or document and match it to the EduKit master catalogue before checkout.',
            ],
            'track-order' => [
                'title' => 'Track Order',
                'heading' => 'Order tracking is coming soon',
                'copy' => 'Parents will be able to search by order number and follow payment, sourcing, delivery and confirmation progress.',
            ],
            'suppliers' => [
                'title' => 'For Suppliers',
                'heading' => 'Supplier onboarding is coming soon',
                'copy' => 'Approved suppliers will manage products, stock, order requests, preparation status and payouts.',
            ],
            'schools' => [
                'title' => 'For Schools',
                'heading' => 'School receiving portal is coming soon',
                'copy' => 'School receivers will verify incoming student packages and confirm or dispute deliveries.',
            ],
            'help' => [
                'title' => 'Help',
                'heading' => 'Help centre is coming soon',
                'copy' => 'Support content for parents, suppliers, drivers and schools will live here.',
            ],
        ];

        abort_unless(array_key_exists($page, $pages), 404);

        return view('website.coming-soon', ['page' => $pages[$page]]);
    }
}
