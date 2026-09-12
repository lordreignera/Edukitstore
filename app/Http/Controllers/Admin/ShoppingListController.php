<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShoppingList;
use App\Support\DocumentStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShoppingListController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $delivery = (string) $request->query('delivery');

        $shoppingLists = ShoppingList::query()
            ->where('source', ShoppingList::SOURCE_UPLOAD)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($listQuery) use ($search) {
                    $listQuery->where('parent_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('school_name', 'like', "%{$search}%")
                        ->orWhere('learner_name', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, ShoppingList::statuses()), fn ($query) => $query->where('status', $status))
            ->when(in_array($delivery, ['school', 'home'], true), fn ($query) => $query->where('delivery_preference', $delivery))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.shopping-lists.index', [
            'shoppingLists' => $shoppingLists,
            'statuses' => ShoppingList::statuses(),
            'search' => $search,
            'selectedStatus' => $status,
            'delivery' => $delivery,
        ]);
    }

    public function show(ShoppingList $shoppingList): View
    {
        return view('admin.shopping-lists.show', [
            'shoppingList' => $shoppingList,
        ]);
    }

    public function download(ShoppingList $shoppingList): StreamedResponse
    {
        $disk = DocumentStorage::disk();

        abort_unless($shoppingList->file_path, 404);
        abort_unless(Storage::disk($disk)->exists($shoppingList->file_path), 404);

        return Storage::disk($disk)->download($shoppingList->file_path, $shoppingList->original_filename);
    }

}
