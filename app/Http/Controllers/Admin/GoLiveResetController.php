<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoLiveResetService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GoLiveResetController extends Controller
{
    public function index(GoLiveResetService $reset): View
    {
        return view('admin.inventory.go-live-reset', [
            'preview' => $reset->preview(),
            'resetEnabled' => (bool) config('edukit.go_live_reset_enabled'),
        ]);
    }

    public function store(Request $request, GoLiveResetService $reset): RedirectResponse
    {
        abort_unless((bool) config('edukit.go_live_reset_enabled'), 404);

        $request->validate([
            'confirm_all_orders_are_test' => ['required', 'accepted'],
            'confirmation_phrase' => ['required', 'in:RESET PRE-LAUNCH TEST DATA'],
            'current_password' => ['required', 'current_password'],
        ]);

        $summary = $reset->reset((int) $request->user()->id);

        return to_route('admin.inventory.go-live-reset')->with(
            'status',
            'Go-live reset completed. The catalogue was retained; all products are inactive with zero stock and prices until reviewed.',
        )->with('reset_summary', $summary);
    }
}
