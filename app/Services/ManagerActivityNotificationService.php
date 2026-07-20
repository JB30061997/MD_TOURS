<?php

namespace App\Services;

use App\Models\Commande;
use App\Models\Driver;
use App\Models\Guide;
use App\Models\MobileDeviceToken;
use App\Models\Planning;
use App\Models\PlanningClient;
use App\Models\RoadSheet;
use App\Models\SupplierClient;
use App\Models\SupplierVehicule;
use App\Models\SupplierVehiculeInvoice;
use App\Models\User;
use App\Models\Vehicule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Contracts\Audit;
use OwenIt\Auditing\Contracts\Auditable;

class ManagerActivityNotificationService
{
    public const NOTIFIED_ROLES = ['admin', 'assistant_admin', 'super_admin'];

    public function __construct(private MobilePushNotificationService $push)
    {
    }

    public function notifyAudited(Auditable $model, ?Audit $audit): array
    {
        if (!$audit || !$model instanceof Model || $this->shouldIgnore($model)) {
            return ['sent' => 0, 'failed' => 0, 'reason' => 'ignored'];
        }

        try {
            $actor = $this->actorName($audit);
            $subject = $this->subjectLabel($model);
            $event = (string) ($audit->event ?? $model->auditEvent ?? 'updated');
            $route = $this->routePayload($model, $audit);

            return $this->push->sendToUsers(
                $this->recipientUserIds(),
                $this->titleForEvent($event),
                trim("{$actor} {$this->verbForEvent($event)} {$subject}."),
                [
                    'type' => 'manager_activity',
                    'event' => $event,
                    'audit_id' => (string) ($audit->id ?? ''),
                    'auditable_type' => class_basename($model),
                    'auditable_id' => (string) $model->getKey(),
                    'route_name' => $route['name'],
                    'route_params' => json_encode($route['params'], JSON_UNESCAPED_UNICODE),
                    'web_url' => (string) ($audit->url ?? ''),
                    'actor_id' => (string) ($audit->user_id ?? ''),
                    'actor_name' => $actor,
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('Manager activity notification failed.', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'audit_id' => $audit->id ?? null,
                'error' => $exception->getMessage(),
            ]);

            return ['sent' => 0, 'failed' => 1];
        }
    }

    public function recipientUserIds(): array
    {
        return User::query()
            ->where('active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', self::NOTIFIED_ROLES))
            ->distinct()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function shouldIgnore(Model $model): bool
    {
        return $model instanceof MobileDeviceToken;
    }

    private function actorName(Audit $audit): string
    {
        $userId = $audit->user_id ?? null;

        if (!$userId) {
            return 'System';
        }

        return User::find($userId)?->name ?: 'Utilisateur #' . $userId;
    }

    private function titleForEvent(string $event): string
    {
        return match ($event) {
            'created' => 'Nouvelle action MD Tours',
            'updated' => 'Modification MD Tours',
            'deleted' => 'Suppression MD Tours',
            'restored' => 'Restauration MD Tours',
            default => 'Activité MD Tours',
        };
    }

    private function verbForEvent(string $event): string
    {
        return match ($event) {
            'created' => 'a ajouté',
            'updated' => 'a modifié',
            'deleted' => 'a supprimé',
            'restored' => 'a restauré',
            default => 'a effectué une action sur',
        };
    }

    private function subjectLabel(Model $model): string
    {
        $type = $this->friendlyType($model);
        $value = $model->name
            ?? $model->designation
            ?? $model->ref_dossier
            ?? $model->voucher_number
            ?? $model->matricule
            ?? null;

        return trim($type . ($value ? ' ' . $value : ' #' . $model->getKey()));
    }

    private function friendlyType(Model $model): string
    {
        return match (true) {
            $model instanceof Planning => 'planning',
            $model instanceof Driver => 'driver',
            $model instanceof Guide => 'guide',
            $model instanceof Vehicule => 'vehicule',
            $model instanceof SupplierClient => 'supplier client',
            $model instanceof SupplierVehicule => 'vehicle supplier',
            $model instanceof SupplierVehiculeInvoice => 'facture fournisseur vehicule',
            $model instanceof Commande => 'bon de commande',
            $model instanceof RoadSheet => 'road sheet',
            default => strtolower(class_basename($model)),
        };
    }

    private function routePayload(Model $model, Audit $audit): array
    {
        if ($model instanceof Planning) {
            return $this->route('PlanningDetails', ['planningId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof Driver) {
            return $this->route('AdminDriverDetails', ['driverId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof Guide) {
            return $this->route('AdminGuideDetails', ['guideId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof Vehicule) {
            return $this->route('AdminVehicleDetails', ['vehicleId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof SupplierClient) {
            return $this->route('AdminSupplierClientDetails', ['supplierClientId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof SupplierVehicule) {
            return $this->route('AdminSupplierVehiculeDetails', ['supplierVehiculeId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof SupplierVehiculeInvoice) {
            return $this->route('AdminSupplierVehiculeInvoiceDetails', ['invoiceId' => $model->getKey(), 'id' => $model->getKey()]);
        }

        if ($model instanceof Commande && $model->planning_id) {
            return $this->route('PlanningDetails', ['planningId' => $model->planning_id, 'id' => $model->planning_id]);
        }

        if ($model instanceof RoadSheet && $model->planning_id) {
            return $this->route('RoadSheetForm', ['planningId' => $model->planning_id, 'id' => $model->planning_id]);
        }

        if ($model instanceof PlanningClient) {
            $planningId = $model->planning_id ?: data_get($audit->new_values, 'planning_id') ?: data_get($audit->old_values, 'planning_id');

            if ($planningId) {
                return $this->route('PlanningDetails', ['planningId' => $planningId, 'id' => $planningId]);
            }
        }

        return $this->route('Dashboard');
    }

    private function route(string $name, array $params = []): array
    {
        return [
            'name' => $name,
            'params' => $params,
        ];
    }
}
