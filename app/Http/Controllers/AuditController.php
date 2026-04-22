<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Guide;
use App\Models\Service;
use App\Models\Supplier;
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

        $supplierMap = Supplier::pluck('name', 'id')->toArray();
        $driverMap   = Driver::pluck('name', 'id')->toArray();
        $guideMap    = Guide::pluck('name', 'id')->toArray();
        $serviceMap  = Service::pluck('designation', 'id')->toArray();

        $result = [];

        foreach ($values as $key => $value) {
            if ($key === 'supplier_id') {
                $result['supplier'] = $supplierMap[$value] ?? $value;
                continue;
            }

            if ($key === 'driver_id') {
                $result['driver'] = $driverMap[$value] ?? $value;
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