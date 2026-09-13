@extends('website.layout')

@section('title', 'EduKit - School Supply Marketplace')

@section('content')
    @php
        $heroSlides = [
            [
                'eyebrow' => "Uganda's school supply platform",
                'title' => 'Everything for their education, delivered with care.',
                'accent' => 'delivered with care.',
                'copy' => 'Buy school supplies, uniforms, books and more from trusted suppliers. We source, package and deliver to schools, homes or pickup points.',
                'image' => '/images/website/edukit-hero.png',
                'primary' => ['label' => 'Shop Now', 'route' => route('website.products.index')],
                'secondary' => ['label' => 'Upload Shopping List', 'route' => route('website.upload-list')],
            ],
            [
                'eyebrow' => 'Term-ready bundles',
                'title' => 'Books, stationery and bags prepared from one list.',
                'accent' => 'one list.',
                'copy' => 'Send your school list and EduKit turns it into a reviewed basket using the live product catalogue.',
                'image' => '/images/products/hero1.jpeg',
                'primary' => ['label' => 'Upload List', 'route' => route('website.upload-list')],
                'secondary' => ['label' => 'Browse Products', 'route' => route('website.products.index')],
            ],
            [
                'eyebrow' => 'Verified suppliers',
                'title' => 'Suppliers, schools and delivery teams working together.',
                'accent' => 'working together.',
                'copy' => 'EduKit helps connect approved suppliers with school supply demand and fulfilment workflows across Uganda.',
                'image' => '/images/products/school_equipment.jpeg',
                'primary' => ['label' => 'Become a Supplier', 'route' => route('website.suppliers')],
                'secondary' => ['label' => 'How It Works', 'route' => '#how-it-works'],
            ],
        ];

        $categoryArtwork = [
            'School Uniforms' => '/images/products/school_uniform.jpeg',
            'Books' => '/images/products/exercise-books.jpg',
            'Stationery' => '/images/products/school_equipment.jpeg',
            'Shoes' => '/images/products/black-school-shoes.svg',
            'Bags' => '/images/products/school-backpack.jpg',
            'Bedding and Linen' => '/images/products/school_material.jpeg',
            'Toiletries' => '/images/products/toiletries-pack.svg',
            'School Equipment' => '/images/products/school_equipment.jpeg',
            'Other Supplies' => '/images/products/shoopinggcart.jpeg',
        ];

        $howItWorks = [
            ['step' => '1', 'title' => 'Shop or Upload List', 'copy' => 'Browse products or upload your school list.', 'icon' => 'M4 6h2l2 9h8l2-6H7'],
            ['step' => '2', 'title' => 'Checkout and Pay', 'copy' => 'Select delivery option and pay securely.', 'icon' => 'M4 7h16v10H4z M4 10h16'],
            ['step' => '3', 'title' => 'We Fulfil and Deliver', 'copy' => 'We source from trusted suppliers and deliver.', 'icon' => 'M3 7h11v9H3z M14 10h4l3 3v3h-7z'],
            ['step' => '4', 'title' => 'Receive and Confirm', 'copy' => 'Your school, home or pickup point confirms receipt.', 'icon' => 'M20 6 9 17l-5-5'],
        ];

        $whyChoose = [
            ['reason' => 'Wide Range of School Products', 'icon' => 'M12 3 3 8l9 5 9-5-9-5Z M5 10v5l7 4 7-4v-5'],
            ['reason' => 'Verified Ugandan Suppliers', 'icon' => 'M12 3a9 9 0 1 0 9 9 M12 3a9 9 0 0 1 0 18 M3 12h18'],
            ['reason' => 'Convenient Delivery Options', 'icon' => 'M3 7h11v9H3z M14 10h4l3 3v3h-7z'],
            ['reason' => 'Secure Payment Flow', 'icon' => 'M12 3l7 3v5c0 5-3.5 8-7 10-3.5-2-7-5-7-10V6l7-3Z'],
            ['reason' => 'Built for Schools and Families', 'icon' => 'M7 11a4 4 0 1 1 8 0 M3 21a8 8 0 0 1 18 0'],
            ['reason' => 'Better Education for Brighter Futures', 'icon' => 'M12 21s-7-4.4-7-10a4 4 0 0 1 7-2 4 4 0 0 1 7 2c0 5.6-7 10-7 10Z'],
        ];

        $featuredTabs = $featuredProducts->pluck('category.name')->filter()->unique()->take(5);
    @endphp

    @include('website.partials.home.hero', ['heroSlides' => $heroSlides, 'categories' => $categories])
    @include('website.partials.home.categories', [
        'categories' => $categories,
        'categoryArtwork' => $categoryArtwork,
        'categoryImages' => $categoryImages,
    ])
    @include('website.partials.home.promo-panels')
    @include('website.partials.home.featured-products', [
        'featuredProducts' => $featuredProducts,
        'featuredTabs' => $featuredTabs,
    ])
    @include('website.partials.home.how-it-works', ['howItWorks' => $howItWorks])
    @include('website.partials.home.why-choose', ['whyChoose' => $whyChoose])
    @include('website.partials.home.newsletter')
@endsection

@push('scripts')
    @include('website.partials.home.hero-scripts')
@endpush
