<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupplierVehiculeInvoice;
use Illuminate\Http\Request;

class MobileAdminSupplierVehiculeInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $search = trim((string) $request->get('search', ''));

        $query = SupplierVehiculeInvoice::with(['supplierVehicule', 'plannings.service'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('total_amount', 'like', "%{$search}%")
                        ->orWhereHas('supplierVehicule', function ($supplier) use ($search) {
                            $supplier->where('name', 'like', "%{$search}%");
                        });
                });
            });

        $invoices = $query
            ->latest()
            ->paginate((int) $request->get('per_page', 15));

        return response()->json([
            'invoices' => $invoices->items(),
            'stats' => [
                'total_amount' => (clone $query)->sum('total_amount'),
                'total_invoices' => (clone $query)->count(),
            ],
            'pagination' => [
                'current_page' => $invoices->currentPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
                'last_page' => $invoices->lastPage(),
                'has_more' => $invoices->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, SupplierVehiculeInvoice $invoice)
    {
        $this->authorizeAdmin($request);

        $invoice->load(['supplierVehicule', 'plannings.service']);

        if ($invoice->pdf_path) {
            $invoice->pdf_url = asset('storage/' . $invoice->pdf_path);
        }

        return response()->json([
            'invoice' => $invoice,
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();
        $roles = $user && method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];

        abort_unless(in_array('admin', $roles, true) || in_array('administrateur', $roles, true), 403);
    }
}
