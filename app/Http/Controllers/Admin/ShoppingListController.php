<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShoppingListController extends Controller
{
    public function index(): View
    {
        $shoppingLists = ShoppingList::latest()->paginate(15);

        return view('admin.shopping-lists.index', compact('shoppingLists'));
    }

    public function show(ShoppingList $shoppingList): View
    {
        return view('admin.shopping-lists.show', [
            'shoppingList' => $shoppingList,
            'statuses' => ShoppingList::statuses(),
        ]);
    }

    public function update(Request $request, ShoppingList $shoppingList): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(ShoppingList::statuses()))],
            'estimated_total' => ['nullable', 'integer', 'min:0'],
        ]);

        $shoppingList->update($data + [
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('status', 'Shopping list updated.');
    }

    public function download(ShoppingList $shoppingList): StreamedResponse
    {
        abort_unless(Storage::exists($shoppingList->file_path), 404);

        return Storage::download($shoppingList->file_path, $shoppingList->original_filename);
    }
}
