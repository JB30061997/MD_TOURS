<?php

namespace App\Http\Controllers;

use App\Models\Guide;
use App\Support\DeleteProtection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuideController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $guides = Guide::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Guides/Index', [
            'guides' => $guides,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Guides/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $guide = Guide::create($data);

        return redirect()->back()->with([
            'success' => 'Guide ajouté avec succès.',
            'lastCreatedGuide' => $guide,
        ]);
    }

    public function edit($id)
    {
        return Inertia::render('Guides/Edit', [
            'guide' => Guide::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        Guide::findOrFail($id)->update($data);

        return redirect()->route('guides.index')->with('success', 'Guide modifié avec succès.');
    }

    public function destroy($id)
    {
        $guide = Guide::findOrFail($id);

        $message = DeleteProtection::blockingMessage('ce guide', [
            ['table' => 'plannings', 'column' => 'guide_id', 'value' => $guide->id, 'label' => 'planning(s)'],
            ['table' => 'commandes', 'column' => 'guide_id', 'value' => $guide->id, 'label' => 'bon(s) de commande'],
        ]);

        if ($message) {
            return redirect()->back()->with('error', $message);
        }

        try {
            $guide->delete();
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ce guide', $exception));
        }

        return redirect()->back()->with('success', 'Guide supprimé avec succès.');
    }
}
