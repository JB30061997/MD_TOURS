<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\SupplierClient;
use App\Models\User;
use App\Support\DeleteProtection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SupplierClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $supplierClients = SupplierClient::with('user')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SupplierClients/Index', [
            'supplierClients' => $supplierClients,
            'allSupplierClients' => SupplierClient::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('SupplierClients/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        DB::transaction(function () use (&$validated) {

            // Ila email makaynch, kan generiw email automatiquement
            $email = $validated['email'] ?? null;

            if (!$email) {
                $baseEmail = Str::slug($validated['name']) . '@md-tours.local';
                $email = $baseEmail;

                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = Str::slug($validated['name']) . '-' . $counter . '@md-tours.local';
                    $counter++;
                }
            }

            // Password howa email
            $password = $email;

            // Ila user_id makaynch, kancreyiw user automatiquement
            if (empty($validated['user_id'])) {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $validated['name'],
                        'password' => Hash::make($password),
                    ]
                );

                // Role supplier_client
                if (method_exists($user, 'assignRole')) {
                    $role = Role::firstOrCreate([
                        'name' => 'supplier_client',
                        'guard_name' => 'web',
                    ]);

                    if (!$user->hasRole('supplier_client')) {
                        $user->assignRole($role);
                    }
                }

                $validated['user_id'] = $user->id;
            }

            $validated['email'] = $email;

            SupplierClient::create([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'],
                'address' => $validated['address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => $validated['is_active'],
            ]);
        });

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', 'Client fournisseur créé avec succès. User créé automatiquement.');
    }
    public function edit($id)
    {
        return Inertia::render('SupplierClients/Edit', [
            'supplierClient' => SupplierClient::findOrFail($id),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplierClient = SupplierClient::findOrFail($id);

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $supplierClient->update($request->only([
            'user_id',
            'name',
            'phone',
            'email',
            'address',
            'notes',
            'is_active',
        ]));

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', 'Client fournisseur modifié avec succès.');
    }

    public function replace(Request $request, SupplierClient $supplierClient)
    {
        $request->merge([
            'old_id' => $supplierClient->id,
        ]);

        $request->validate([
            'new_supplier_client_id' => 'required|exists:supplier_clients,id|different:old_id',
        ]);

        $updatedClients = Client::where('supplier_client_id', $supplierClient->id)
            ->update([
                'supplier_client_id' => $request->new_supplier_client_id,
            ]);

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', "Client supplier remplacé avec succès. {$updatedClients} client(s) mis à jour.");
    }

    public function destroy($id)
    {
        $supplierClient = SupplierClient::findOrFail($id);

        $message = DeleteProtection::blockingMessage('ce supplier client', [
            ['count' => fn () => Client::where('supplier_client_id', $supplierClient->id)->count(), 'label' => 'client(s)'],
            ['table' => 'plannings', 'column' => 'supplier_client_id', 'value' => $supplierClient->id, 'label' => 'planning(s)'],
            ['table' => 'commandes', 'column' => 'supplier_client_id', 'value' => $supplierClient->id, 'label' => 'bon(s) de commande'],
        ]);

        if ($message) {
            return redirect()->back()->with('error', $message);
        }

        try {
            $supplierClient->delete();
        } catch (QueryException $exception) {
            return redirect()->back()->with('error', DeleteProtection::foreignKeyMessage('ce supplier client', $exception));
        }

        return redirect()
            ->route('supplier-clients.index')
            ->with('success', 'Client fournisseur supprimé avec succès.');
    }
}
