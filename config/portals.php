<?php

return [
    'supplier_navigation' => [
        ['label' => 'Dashboard', 'route' => 'supplier.dashboard', 'pattern' => 'supplier.dashboard', 'icon' => 'home'],
        ['label' => 'My Products', 'route' => 'supplier.offers.index', 'pattern' => 'supplier.offers.*', 'icon' => 'products'],
        ['label' => 'EduKit Stock Records', 'route' => 'supplier.stock.index', 'pattern' => 'supplier.stock.*', 'icon' => 'warehouse'],
        ['label' => 'Sales & Earnings', 'route' => 'supplier.earnings.index', 'pattern' => 'supplier.earnings.*', 'icon' => 'invoice'],
    ],
];
