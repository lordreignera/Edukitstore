<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProfitReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $source = in_array($request->query('source'), ['edukit', 'supplier'], true)
            ? (string) $request->query('source')
            : '';
        $from = $request->date('from');
        $to = $request->date('to');

        $base = ShoppingListItem::query()
            ->whereHas('shoppingList', fn ($query) => $query->where('payment_status', ShoppingList::PAYMENT_PAID))
            ->when($source !== '', fn ($query) => $query->where('fulfilment_source', $source))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));

        $summary = (clone $base)
            ->selectRaw('fulfilment_source, SUM(quantity) as units, SUM(line_total) as revenue, SUM(cost_total) as cost, SUM(profit_total) as profit, SUM(supplier_payable) as supplier_payable')
            ->groupBy('fulfilment_source')
            ->get()
            ->keyBy('fulfilment_source');

        $supplierSettlement = (clone $base)
            ->where('fulfilment_source', 'supplier')
            ->selectRaw('settlement_status, SUM(supplier_payable) as total')
            ->groupBy('settlement_status')
            ->pluck('total', 'settlement_status');

        $items = (clone $base)
            ->with('shoppingList', 'supplier', 'product')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.profit', compact('items', 'summary', 'supplierSettlement', 'source', 'from', 'to'));
    }
}
