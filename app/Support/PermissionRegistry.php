<?php

namespace App\Support;

class PermissionRegistry
{
    public const ACTIONS = [
        'view' => 'Voir',
        'create' => 'Ajouter',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'validate' => 'Valider',
        'import' => 'Importer',
        'export' => 'Exporter',
        'print' => 'Imprimer',
        'email' => 'Envoyer email',
        'manage' => 'Gérer',
    ];

    public const MODULES = [
        'dashboard' => [
            'label' => 'Dashboard',
            'actions' => ['view'],
        ],
        'commandes' => [
            'label' => 'Commandes',
            'actions' => ['view', 'create', 'edit', 'delete', 'export', 'print', 'email'],
        ],
        'generate-boncmd' => [
            'label' => 'Generate BonCMD',
            'actions' => ['view', 'export', 'print'],
        ],
        'supplier-vehicule-tarifs' => [
            'label' => 'Supplier Control',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ],
        'mailbox' => [
            'label' => 'Mailbox',
            'actions' => ['view', 'create', 'edit', 'delete', 'email'],
        ],
        'reservation-drafts' => [
            'label' => 'Planning TH',
            'actions' => ['view', 'validate', 'delete'],
        ],
        'plannings' => [
            'label' => 'Plannings',
            'actions' => ['view', 'create', 'edit', 'delete', 'import', 'export', 'print', 'email'],
        ],
        'road-sheets' => [
            'label' => 'Road Sheets',
            'actions' => ['view', 'create', 'edit', 'delete', 'print', 'email'],
        ],
        'clients' => [
            'label' => 'Clients',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        'reservateurs' => [
            'label' => 'Réservateurs',
            'actions' => ['view', 'create', 'edit', 'delete', 'validate'],
        ],
        'supplier-clients' => [
            'label' => 'Client Suppliers',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ],
        'supplier-vehicules' => [
            'label' => 'Vehicle Suppliers',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ],
        'vehicules' => [
            'label' => 'Vehicles',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        'vehicle-maintenances' => [
            'label' => 'Maintenance Report',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        'drivers' => [
            'label' => 'Drivers',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ],
        'driver-primes' => [
            'label' => 'Primes chauffeurs',
            'actions' => ['view'],
        ],
        'guides' => [
            'label' => 'Guides',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        'services' => [
            'label' => 'Services',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        'destinations' => [
            'label' => 'Destinations',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
        'driver-fuel-invoices' => [
            'label' => 'Fuel Invoices',
            'actions' => ['view', 'create', 'edit', 'delete', 'export', 'print'],
        ],
        'supplier-vehicule-invoices' => [
            'label' => 'Vehicle Supplier Invoices',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage', 'export', 'print'],
        ],
        'all-users' => [
            'label' => 'Users',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ],
        'roles-permissions' => [
            'label' => 'Roles & Permissions',
            'actions' => ['view', 'create', 'edit', 'delete', 'manage'],
        ],
        'type-services' => [
            'label' => 'Service Types',
            'actions' => ['view', 'create', 'edit', 'delete'],
        ],
    ];

    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::MODULES as $module => $config) {
            foreach ($config['actions'] as $action) {
                $permissions[] = "{$module}.{$action}";
            }
        }

        return array_values(array_unique($permissions));
    }

    public static function grouped(): array
    {
        return collect(self::MODULES)
            ->map(function (array $config, string $module) {
                return [
                    'key' => $module,
                    'label' => $config['label'],
                    'permissions' => collect($config['actions'])
                        ->map(fn (string $action) => [
                            'name' => "{$module}.{$action}",
                            'action' => $action,
                            'label' => self::ACTIONS[$action] ?? ucfirst($action),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
