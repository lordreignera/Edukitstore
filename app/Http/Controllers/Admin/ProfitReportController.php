<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $this->filters($request);
        $base = $this->filteredItems($filters);

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

        $view = $request->routeIs('finance.dashboard')
            ? 'finance.dashboard'
            : 'admin.reports.profit';

        return view($view, [
            'items' => $items,
            'summary' => $summary,
            'supplierSettlement' => $supplierSettlement,
            ...$filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $items = $this->filteredItems($filters)
            ->with('shoppingList', 'supplier')
            ->orderBy('shopping_list_items.id');

        return response()->streamDownload(function () use ($items): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Paid at',
                'Order reference',
                'Payment provider',
                'Product',
                'SKU',
                'Stock owner',
                'Supplier',
                'Quantity',
                'Revenue (UGX)',
                'Cost (UGX)',
                'Supplier payable (UGX)',
                'EduKit gross profit (UGX)',
                'Settlement status',
            ]);

            $items->chunkById(500, function ($chunk) use ($output): void {
                foreach ($chunk as $item) {
                    $order = $item->shoppingList;
                    fputcsv($output, [
                        $order?->paid_at?->format('Y-m-d H:i:s'),
                        $this->safeCsvText($order?->reference),
                        $this->safeCsvText($order?->payment_provider === 'demo' ? 'demo (test)' : $order?->payment_provider),
                        $this->safeCsvText($item->product_name),
                        $this->safeCsvText($item->sku),
                        $item->fulfilment_source === 'supplier' ? 'Supplier direct' : 'EduKit warehouse',
                        $this->safeCsvText($item->supplier?->business_name),
                        (int) $item->quantity,
                        $item->line_total,
                        $item->cost_total,
                        $item->supplier_payable,
                        $item->profit_total,
                        $item->fulfilment_source === 'supplier' ? $item->settlement_status : 'Owned',
                    ]);
                }
            });

            fclose($output);
        }, 'edukit-profit-report-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $isAdminProfitRoute = $request->routeIs('admin.reports.profit')
            || $request->routeIs('admin.reports.profit.export');

        return [
            'source' => in_array($request->query('source'), ['edukit', 'supplier'], true)
                ? (string) $request->query('source')
                : '',
            'includeDemo' => $isAdminProfitRoute && $request->boolean('include_demo'),
            'from' => isset($validated['from']) ? Carbon::parse($validated['from']) : null,
            'to' => isset($validated['to']) ? Carbon::parse($validated['to']) : null,
        ];
    }

    private function filteredItems(array $filters): Builder
    {
        return ShoppingListItem::query()
            ->whereHas('shoppingList', fn ($query) => $query
                ->where('payment_status', ShoppingList::PAYMENT_PAID)
                ->when(! $filters['includeDemo'], fn ($provider) => $provider->where(fn ($normal) => $normal
                    ->whereNull('payment_provider')
                    ->orWhere('payment_provider', '!=', 'demo')))
                ->when($filters['from'], fn ($paid) => $paid->whereDate('paid_at', '>=', $filters['from']))
                ->when($filters['to'], fn ($paid) => $paid->whereDate('paid_at', '<=', $filters['to'])))
            ->when($filters['source'] !== '', fn ($query) => $query->where('fulfilment_source', $filters['source']));
    }

    private function safeCsvText(?string $value): ?string
    {
        if ($value !== null && ! is_numeric($value) && preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
