<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Guide;
use App\Models\Service;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function myHistory()
    {
        $audits = Audit::query()
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(20)
            ->through(function ($audit) {
                return [
                    'id' => $audit->id,
                    'event' => $audit->event,
                    'auditable_type' => class_basename($audit->auditable_type),
                    'auditable_id' => $audit->auditable_id,
                    'old_values' => $this->transformAuditValues($audit->old_values ?? []),
                    'new_values' => $this->transformAuditValues($audit->new_values ?? []),
                    'url' => $audit->url,
                    'ip_address' => $audit->ip_address,
                    'user_agent' => $audit->user_agent,
                    'created_at' => optional($audit->created_at)->format('Y-m-d H:i:s'),
                ];
            });

        return Inertia::render('Audits/History', [
            'audits' => $audits,
        ]);
    }

    private function transformAuditValues($values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $supplierClientMap = SupplierClient::pluck('name', 'id')->toArray();
        $supplierVehiculeMap = SupplierVehicule::pluck('name', 'id')->toArray();
        $driverMap = Driver::pluck('name', 'id')->toArray();
        $guideMap = Guide::pluck('name', 'id')->toArray();
        $serviceMap = Service::pluck('designation', 'id')->toArray();

        $result = [];

        foreach ($values as $key => $value) {
            if ($key === 'supplier_client_id') {
                $result['fournisseur_client'] = $supplierClientMap[$value] ?? $value;
                continue;
            }

            if ($key === 'supplier_vehicule_id') {
                $result['fournisseur_vehicule'] = $supplierVehiculeMap[$value] ?? $value;
                continue;
            }

            if ($key === 'driver_id') {
                $result['chauffeur'] = $driverMap[$value] ?? $value;
                continue;
            }

            if ($key === 'guide_id') {
                $result['guide'] = $guideMap[$value] ?? $value;
                continue;
            }

            if ($key === 'service_id') {
                $result['service'] = $serviceMap[$value] ?? $value;
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
