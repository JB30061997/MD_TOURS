<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, reactive, watch } from "vue";

const props = defineProps({
    plannings: { type: Object, required: true },
    rows: { type: Array, default: () => [] },
    showNewRow: { type: Boolean, default: false },
    editingId: { type: [Number, String, null], default: null },

    newPlanning: { type: Object, required: true },
    editPlanning: { type: Object, required: true },
    searchInputs: { type: Object, required: true },
    editSearchInputs: { type: Object, required: true },

    errors: { type: Object, default: () => ({}) },
    editErrors: { type: Object, default: () => ({}) },
    loadingSave: { type: Boolean, default: false },
    loadingUpdate: { type: Boolean, default: false },

    supplierVehicules: { type: Array, default: () => [] },
    supplierClients: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },

    selectedClientsObjects: { type: Array, default: () => [] },
    selectedEditClientsObjects: { type: Array, default: () => [] },
    formatDateOnly: { type: Function, required: true },
});

const emit = defineEmits([
    "sync-supplier-vehicule-id",
    "sync-driver-id",
    "sync-guide-id",
    "sync-service-id",
    "sync-destination-id",
    "sync-vehicule-id",

    "sync-edit-supplier-vehicule-id",
    "sync-edit-driver-id",
    "sync-edit-guide-id",
    "sync-edit-service-id",
    "sync-edit-destination-id",
    "sync-edit-vehicule-id",

    "add-client-from-search",
    "add-edit-client-from-search",
    "remove-client",
    "remove-edit-client",
    "open-modal",
    "save-planning",
    "update-planning",
    "cancel-new-row",
    "open-edit-row",
    "cancel-edit-row",
    "open-clients-modal",
    "destroy-planning",
]);

const open = reactive({});
const search = reactive({});

const fields = [
    "new_depart",
    "new_destination",
    "new_supplierClient",
    "new_supplierVehicule",
    "new_vehicule",
    "new_driver",
    "new_guide",
    "new_service",
    "new_client",
    "edit_depart",
    "edit_destination",
    "edit_supplierClient",
    "edit_supplierVehicule",
    "edit_vehicule",
    "edit_driver",
    "edit_guide",
    "edit_service",
    "edit_client",
];

fields.forEach((key) => {
    open[key] = false;
    search[key] = "";
});

watch(
    () => props.showNewRow,
    (value) => {
        if (value) {
            search.new_depart = props.newPlanning.point_depart || "";
            search.new_destination = props.searchInputs.destination || "";
            search.new_supplierClient = props.searchInputs.supplierClient || "";
            search.new_supplierVehicule =
                props.searchInputs.supplierVehicule || "";
            search.new_vehicule = props.searchInputs.vehicule || "";
            search.new_driver = props.searchInputs.driver || "";
            search.new_guide = props.searchInputs.guide || "";
            search.new_service = props.searchInputs.service || "";
            search.new_client = "";
        }
    },
);

watch(
    () => props.editingId,
    (value) => {
        if (value) {
            search.edit_depart = props.editPlanning.point_depart || "";
            search.edit_destination = props.editSearchInputs.destination || "";
            search.edit_supplierClient =
                props.editSearchInputs.supplierClient || "";
            search.edit_supplierVehicule =
                props.editSearchInputs.supplierVehicule || "";
            search.edit_vehicule = props.editSearchInputs.vehicule || "";
            search.edit_driver = props.editSearchInputs.driver || "";
            search.edit_guide = props.editSearchInputs.guide || "";
            search.edit_service = props.editSearchInputs.service || "";
            search.edit_client = "";
        }
    },
);

const normalize = (v) =>
    String(v || "")
        .toLowerCase()
        .trim();

const filterBy = (items, key, term) => {
    const q = normalize(term);
    if (!q) return items;

    return items.filter((item) => normalize(item[key]).includes(q));
};

const lists = (prefix, planning) => ({
    departs: filterBy(props.destinations, "name", search[`${prefix}_depart`]),
    destinations: filterBy(
        props.destinations,
        "name",
        search[`${prefix}_destination`],
    ),
    supplierClients: filterBy(
        props.supplierClients,
        "name",
        search[`${prefix}_supplierClient`],
    ),
    supplierVehicules: filterBy(
        props.supplierVehicules,
        "name",
        search[`${prefix}_supplierVehicule`],
    ),
    drivers: filterBy(props.drivers, "name", search[`${prefix}_driver`]),
    guides: filterBy(props.guides, "name", search[`${prefix}_guide`]),
    services: filterBy(
        props.services,
        "designation",
        search[`${prefix}_service`],
    ),
    vehicules: filterBy(
        props.vehicules,
        "matricule",
        search[`${prefix}_vehicule`],
    ),
    clients: filterBy(
        planning.supplier_client_id
            ? props.clients.filter(
                  (client) =>
                      Number(client.supplier_client_id) ===
                      Number(planning.supplier_client_id),
              )
            : props.clients,
        "full_name",
        search[`${prefix}_client`],
    ),
});

const closeAll = () => {
    Object.keys(open).forEach((key) => {
        open[key] = false;
    });
};

const selectItem = (prefix, type, item) => {
    const planning = prefix === "new" ? props.newPlanning : props.editPlanning;
    const inputs =
        prefix === "new" ? props.searchInputs : props.editSearchInputs;
    const emitName = (base) =>
        prefix === "new" ? base : base.replace("sync-", "sync-edit-");

    if (type === "depart") {
        planning.point_depart = item.name;
        search[`${prefix}_depart`] = item.name;
    }

    if (type === "destination") {
        planning.destination_id = item.id;
        inputs.destination = item.name;
        search[`${prefix}_destination`] = item.name;
        emit(emitName("sync-destination-id"));
    }

    if (type === "supplierClient") {
        planning.supplier_client_id = item.id;
        inputs.supplierClient = item.name;
        search[`${prefix}_supplierClient`] = item.name;
        inputs.client = "";
        planning.client_ids = [];
    }

    if (type === "supplierVehicule") {
        planning.supplier_vehicule_id = item.id;
        inputs.supplierVehicule = item.name;
        search[`${prefix}_supplierVehicule`] = item.name;
        emit(emitName("sync-supplier-vehicule-id"));
    }

    if (type === "vehicule") {
        planning.vehicule_id = item.id;
        inputs.vehicule = item.matricule;
        search[`${prefix}_vehicule`] = item.matricule;
        emit(emitName("sync-vehicule-id"));
    }

    if (type === "driver") {
        planning.driver_id = item.id;
        inputs.driver = item.name;
        search[`${prefix}_driver`] = item.name;
        emit(emitName("sync-driver-id"));
    }

    if (type === "guide") {
        planning.guide_id = item.id;
        inputs.guide = item.name;
        search[`${prefix}_guide`] = item.name;
        emit(emitName("sync-guide-id"));
    }

    if (type === "service") {
        planning.service_id = item.id;
        inputs.service = item.designation;
        search[`${prefix}_service`] = item.designation;
        emit(emitName("sync-service-id"));
    }

    if (type === "client") {
        inputs.client = item.full_name;
        search[`${prefix}_client`] = "";
        emit(
            prefix === "new"
                ? "add-client-from-search"
                : "add-edit-client-from-search",
        );
    }

    closeAll();
};

const getClients = (planning) => {
    return planning.planning_clients || planning.planningClients || [];
};

const rowConfig = (prefix) => {
    const isNew = prefix === "new";

    return {
        prefix,
        planning: isNew ? props.newPlanning : props.editPlanning,
        inputs: isNew ? props.searchInputs : props.editSearchInputs,
        errors: isNew ? props.errors : props.editErrors,
        selectedClients: isNew
            ? props.selectedClientsObjects
            : props.selectedEditClientsObjects,
        saveEvent: isNew ? "save-planning" : "update-planning",
        cancelEvent: isNew ? "cancel-new-row" : "cancel-edit-row",
        saving: isNew ? props.loadingSave : props.loadingUpdate,
        saveText: isNew ? "Save" : "Update",
    };
};
</script>

<template>
    <div class="planning-table-card card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table align-middle table-hover custom-planning-table mb-0"
                >
                    <thead>
                        <tr>
                            <th>Star date</th>
                            <th>End date</th>
                            <th>Reference</th>
                            <th>Vehicle</th>
                            <th>PAX</th>
                            <th>Type</th>
                            <th>Flight</th>
                            <th>Time</th>
                            <th>Start Point</th>
                            <th>End Point</th>
                            <th>Location</th>
                            <th>Suppliers Clients</th>
                            <th>Vehicle Supplier</th>
                            <th>MD Driver</th>
                            <th>Guide</th>
                            <th>Clients</th>
                            <th>Budget</th>
                            <th>Supplier Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template
                            v-for="cfg in [rowConfig('new')]"
                            :key="'new-row'"
                        >
                            <tr v-if="showNewRow" class="planning-new-row">
                                <td>
                                    <input
                                        v-model="cfg.planning.date_du"
                                        type="date"
                                        class="form-control table-input"
                                    /><small class="text-danger">{{
                                        cfg.errors.date_du
                                    }}</small>
                                </td>
                                <td>
                                    <input
                                        v-model="cfg.planning.date_au"
                                        type="date"
                                        class="form-control table-input"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="cfg.planning.ref_dossier"
                                        type="text"
                                        class="form-control table-input small-input"
                                    /><small class="text-danger">{{
                                        cfg.errors.ref_dossier
                                    }}</small>
                                </td>

                                <td>
                                    <div class="smart-select">
                                        <input
                                            v-model="
                                                search[`${cfg.prefix}_vehicule`]
                                            "
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Vehicle..."
                                            @focus="
                                                closeAll();
                                                open[`${cfg.prefix}_vehicule`] =
                                                    true;
                                            "
                                        />
                                        <div
                                            v-if="
                                                open[`${cfg.prefix}_vehicule`]
                                            "
                                            class="smart-menu"
                                        >
                                            <button
                                                v-for="item in lists(
                                                    cfg.prefix,
                                                    cfg.planning,
                                                ).vehicules"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="
                                                    selectItem(
                                                        cfg.prefix,
                                                        'vehicule',
                                                        item,
                                                    )
                                                "
                                            >
                                                {{ item.matricule }}
                                                <small
                                                    v-if="
                                                        item.marque ||
                                                        item.modele
                                                    "
                                                    class="text-muted"
                                                    >— {{ item.marque }}
                                                    {{ item.modele }}</small
                                                >
                                            </button>
                                            <div
                                                v-if="
                                                    !lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).vehicules.length
                                                "
                                                class="smart-empty"
                                            >
                                                No vehicle found
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <input
                                        v-model="cfg.planning.nbr_personnes"
                                        type="number"
                                        class="form-control table-input tiny-input"
                                    />
                                </td>

                                <td>
                                    <div class="smart-select">
                                        <input
                                            v-model="
                                                search[`${cfg.prefix}_service`]
                                            "
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Type..."
                                            @focus="
                                                closeAll();
                                                open[`${cfg.prefix}_service`] =
                                                    true;
                                            "
                                        />
                                        <div
                                            v-if="open[`${cfg.prefix}_service`]"
                                            class="smart-menu"
                                        >
                                            <button
                                                v-for="item in lists(
                                                    cfg.prefix,
                                                    cfg.planning,
                                                ).services"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="
                                                    selectItem(
                                                        cfg.prefix,
                                                        'service',
                                                        item,
                                                    )
                                                "
                                            >
                                                {{ item.designation }}
                                            </button>
                                            <div
                                                v-if="
                                                    !lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).services.length
                                                "
                                                class="smart-empty"
                                            >
                                                No type found
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <input
                                        v-model="cfg.planning.flight"
                                        type="text"
                                        class="form-control table-input small-input"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="cfg.planning.heure"
                                        type="time"
                                        class="form-control table-input"
                                    />
                                </td>

                                <td>
                                    <div class="smart-select">
                                        <input
                                            v-model="
                                                search[`${cfg.prefix}_depart`]
                                            "
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Start Point..."
                                            @focus="
                                                closeAll();
                                                open[`${cfg.prefix}_depart`] =
                                                    true;
                                            "
                                        />
                                        <div
                                            v-if="open[`${cfg.prefix}_depart`]"
                                            class="smart-menu"
                                        >
                                            <button
                                                v-for="item in lists(
                                                    cfg.prefix,
                                                    cfg.planning,
                                                ).departs"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="
                                                    selectItem(
                                                        cfg.prefix,
                                                        'depart',
                                                        item,
                                                    )
                                                "
                                            >
                                                {{ item.name }}
                                            </button>
                                            <div
                                                v-if="
                                                    !lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).departs.length
                                                "
                                                class="smart-empty"
                                            >
                                                No start point found
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="search-select-box">
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_destination`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="End Point..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_destination`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[
                                                        `${cfg.prefix}_destination`
                                                    ]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).destinations"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'destination',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).destinations.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No destination found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="plus-btn"
                                            @click="
                                                $emit(
                                                    'open-modal',
                                                    'destinationModal',
                                                )
                                            "
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <input
                                        v-model="cfg.planning.site"
                                        type="text"
                                        class="form-control table-input"
                                        placeholder="Location..."
                                    />
                                </td>

                                <td>
                                    <div class="search-select-box">
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_supplierClient`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="Suppliers..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_supplierClient`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[
                                                        `${cfg.prefix}_supplierClient`
                                                    ]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).supplierClients"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'supplierClient',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).supplierClients.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No supplier found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="plus-btn"
                                            @click="
                                                $emit(
                                                    'open-modal',
                                                    'supplierClientModal',
                                                )
                                            "
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <div class="search-select-box">
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_supplierVehicule`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="Vehicle supplier..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_supplierVehicule`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[
                                                        `${cfg.prefix}_supplierVehicule`
                                                    ]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).supplierVehicules"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'supplierVehicule',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).supplierVehicules
                                                            .length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No vehicle supplier found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="plus-btn"
                                            @click="
                                                $emit(
                                                    'open-modal',
                                                    'supplierModal',
                                                )
                                            "
                                        >
                                            +
                                        </button>
                                    </div>
                                </td>

                                <td>
                                    <div class="smart-select">
                                        <input
                                            v-model="
                                                search[`${cfg.prefix}_driver`]
                                            "
                                            type="text"
                                            class="form-control table-input small-input"
                                            placeholder="MD Driver..."
                                            @focus="
                                                closeAll();
                                                open[`${cfg.prefix}_driver`] =
                                                    true;
                                            "
                                        />
                                        <div
                                            v-if="open[`${cfg.prefix}_driver`]"
                                            class="smart-menu"
                                        >
                                            <button
                                                v-for="item in lists(
                                                    cfg.prefix,
                                                    cfg.planning,
                                                ).drivers"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="
                                                    selectItem(
                                                        cfg.prefix,
                                                        'driver',
                                                        item,
                                                    )
                                                "
                                            >
                                                {{ item.name }}
                                            </button>
                                            <div
                                                v-if="
                                                    !lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).drivers.length
                                                "
                                                class="smart-empty"
                                            >
                                                No driver found
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="smart-select">
                                        <input
                                            v-model="
                                                search[`${cfg.prefix}_guide`]
                                            "
                                            type="text"
                                            class="form-control table-input small-input"
                                            placeholder="Guide..."
                                            @focus="
                                                closeAll();
                                                open[`${cfg.prefix}_guide`] =
                                                    true;
                                            "
                                        />
                                        <div
                                            v-if="open[`${cfg.prefix}_guide`]"
                                            class="smart-menu"
                                        >
                                            <button
                                                v-for="item in lists(
                                                    cfg.prefix,
                                                    cfg.planning,
                                                ).guides"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="
                                                    selectItem(
                                                        cfg.prefix,
                                                        'guide',
                                                        item,
                                                    )
                                                "
                                            >
                                                {{ item.name }}
                                            </button>
                                            <div
                                                v-if="
                                                    !lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).guides.length
                                                "
                                                class="smart-empty"
                                            >
                                                No guide found
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="clients-cell-new">
                                    <div class="search-select-box mb-2">
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_client`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="Client..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_client`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[`${cfg.prefix}_client`]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).clients"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'client',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.full_name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).clients.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No client found
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="plus-btn"
                                            @click="
                                                $emit(
                                                    'open-modal',
                                                    'clientModal',
                                                )
                                            "
                                        >
                                            +
                                        </button>
                                    </div>

                                    <div
                                        v-if="cfg.selectedClients.length"
                                        class="client-tags"
                                    >
                                        <span
                                            v-for="client in cfg.selectedClients"
                                            :key="client.id"
                                            class="client-tag client-tag-selected"
                                        >
                                            {{ client.full_name }}
                                            <button
                                                type="button"
                                                class="tag-remove"
                                                @click="
                                                    $emit(
                                                        'remove-client',
                                                        client.id,
                                                    )
                                                "
                                            >
                                                ×
                                            </button>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <input
                                        v-model="cfg.planning.budget"
                                        type="number"
                                        step="0.01"
                                        class="form-control table-input small-input"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="cfg.planning.supplier_price"
                                        type="number"
                                        step="0.01"
                                        class="form-control table-input small-input"
                                    />
                                </td>

                                <td class="actions-cell">
                                    <div class="row-actions">
                                        <button
                                            type="button"
                                            class="btn btn-save-action btn-sm"
                                            @click="$emit(cfg.saveEvent)"
                                            :disabled="cfg.saving"
                                        >
                                            <span
                                                v-if="cfg.saving"
                                                class="spinner-border spinner-border-sm me-1"
                                            ></span>
                                            {{ cfg.saveText }}
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-cancel-action btn-sm"
                                            @click="$emit(cfg.cancelEvent)"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template v-for="planning in rows" :key="planning.id">
                            <tr
                                v-if="Number(editingId) === Number(planning.id)"
                                class="planning-new-row planning-edit-row"
                            >
                                <template
                                    v-for="cfg in [rowConfig('edit')]"
                                    :key="'edit-' + planning.id"
                                >
                                    <td>
                                        <input
                                            v-model="cfg.planning.date_du"
                                            type="date"
                                            class="form-control table-input"
                                        /><small class="text-danger">{{
                                            cfg.errors.date_du
                                        }}</small>
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.date_au"
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.ref_dossier"
                                            type="text"
                                            class="form-control table-input small-input"
                                        /><small class="text-danger">{{
                                            cfg.errors.ref_dossier
                                        }}</small>
                                    </td>
                                    <td>
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_vehicule`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="Vehicle..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_vehicule`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[
                                                        `${cfg.prefix}_vehicule`
                                                    ]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).vehicules"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'vehicule',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.matricule }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).vehicules.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No vehicle found
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.nbr_personnes"
                                            type="number"
                                            class="form-control table-input tiny-input"
                                        />
                                    </td>
                                    <td>
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_service`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="Type..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_service`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[
                                                        `${cfg.prefix}_service`
                                                    ]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).services"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'service',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.designation }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).services.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No type found
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.flight"
                                            type="text"
                                            class="form-control table-input small-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.heure"
                                            type="time"
                                            class="form-control table-input"
                                        />
                                    </td>
                                    <td>
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_depart`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input"
                                                placeholder="Start Point..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_depart`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[`${cfg.prefix}_depart`]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).departs"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'depart',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).departs.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No start point found
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="search-select-box">
                                            <div class="smart-select">
                                                <input
                                                    v-model="
                                                        search[
                                                            `${cfg.prefix}_destination`
                                                        ]
                                                    "
                                                    type="text"
                                                    class="form-control table-input"
                                                    placeholder="End Point..."
                                                    @focus="
                                                        closeAll();
                                                        open[
                                                            `${cfg.prefix}_destination`
                                                        ] = true;
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        open[
                                                            `${cfg.prefix}_destination`
                                                        ]
                                                    "
                                                    class="smart-menu"
                                                >
                                                    <button
                                                        v-for="item in lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).destinations"
                                                        :key="item.id"
                                                        type="button"
                                                        class="smart-item"
                                                        @click="
                                                            selectItem(
                                                                cfg.prefix,
                                                                'destination',
                                                                item,
                                                            )
                                                        "
                                                    >
                                                        {{ item.name }}
                                                    </button>
                                                    <div
                                                        v-if="
                                                            !lists(
                                                                cfg.prefix,
                                                                cfg.planning,
                                                            ).destinations
                                                                .length
                                                        "
                                                        class="smart-empty"
                                                    >
                                                        No destination found
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="plus-btn"
                                                @click="
                                                    $emit(
                                                        'open-modal',
                                                        'destinationModal',
                                                    )
                                                "
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.site"
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Location..."
                                        />
                                    </td>
                                    <td>
                                        <div class="search-select-box">
                                            <div class="smart-select">
                                                <input
                                                    v-model="
                                                        search[
                                                            `${cfg.prefix}_supplierClient`
                                                        ]
                                                    "
                                                    type="text"
                                                    class="form-control table-input"
                                                    placeholder="Suppliers..."
                                                    @focus="
                                                        closeAll();
                                                        open[
                                                            `${cfg.prefix}_supplierClient`
                                                        ] = true;
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        open[
                                                            `${cfg.prefix}_supplierClient`
                                                        ]
                                                    "
                                                    class="smart-menu"
                                                >
                                                    <button
                                                        v-for="item in lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).supplierClients"
                                                        :key="item.id"
                                                        type="button"
                                                        class="smart-item"
                                                        @click="
                                                            selectItem(
                                                                cfg.prefix,
                                                                'supplierClient',
                                                                item,
                                                            )
                                                        "
                                                    >
                                                        {{ item.name }}
                                                    </button>
                                                    <div
                                                        v-if="
                                                            !lists(
                                                                cfg.prefix,
                                                                cfg.planning,
                                                            ).supplierClients
                                                                .length
                                                        "
                                                        class="smart-empty"
                                                    >
                                                        No supplier found
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="plus-btn"
                                                @click="
                                                    $emit(
                                                        'open-modal',
                                                        'supplierClientModal',
                                                    )
                                                "
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="search-select-box">
                                            <div class="smart-select">
                                                <input
                                                    v-model="
                                                        search[
                                                            `${cfg.prefix}_supplierVehicule`
                                                        ]
                                                    "
                                                    type="text"
                                                    class="form-control table-input"
                                                    placeholder="Vehicle supplier..."
                                                    @focus="
                                                        closeAll();
                                                        open[
                                                            `${cfg.prefix}_supplierVehicule`
                                                        ] = true;
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        open[
                                                            `${cfg.prefix}_supplierVehicule`
                                                        ]
                                                    "
                                                    class="smart-menu"
                                                >
                                                    <button
                                                        v-for="item in lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).supplierVehicules"
                                                        :key="item.id"
                                                        type="button"
                                                        class="smart-item"
                                                        @click="
                                                            selectItem(
                                                                cfg.prefix,
                                                                'supplierVehicule',
                                                                item,
                                                            )
                                                        "
                                                    >
                                                        {{ item.name }}
                                                    </button>
                                                    <div
                                                        v-if="
                                                            !lists(
                                                                cfg.prefix,
                                                                cfg.planning,
                                                            ).supplierVehicules
                                                                .length
                                                        "
                                                        class="smart-empty"
                                                    >
                                                        No vehicle supplier
                                                        found
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="plus-btn"
                                                @click="
                                                    $emit(
                                                        'open-modal',
                                                        'supplierModal',
                                                    )
                                                "
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_driver`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input small-input"
                                                placeholder="MD Driver..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_driver`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[`${cfg.prefix}_driver`]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).drivers"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'driver',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).drivers.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No driver found
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="smart-select">
                                            <input
                                                v-model="
                                                    search[
                                                        `${cfg.prefix}_guide`
                                                    ]
                                                "
                                                type="text"
                                                class="form-control table-input small-input"
                                                placeholder="Guide..."
                                                @focus="
                                                    closeAll();
                                                    open[
                                                        `${cfg.prefix}_guide`
                                                    ] = true;
                                                "
                                            />
                                            <div
                                                v-if="
                                                    open[`${cfg.prefix}_guide`]
                                                "
                                                class="smart-menu"
                                            >
                                                <button
                                                    v-for="item in lists(
                                                        cfg.prefix,
                                                        cfg.planning,
                                                    ).guides"
                                                    :key="item.id"
                                                    type="button"
                                                    class="smart-item"
                                                    @click="
                                                        selectItem(
                                                            cfg.prefix,
                                                            'guide',
                                                            item,
                                                        )
                                                    "
                                                >
                                                    {{ item.name }}
                                                </button>
                                                <div
                                                    v-if="
                                                        !lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).guides.length
                                                    "
                                                    class="smart-empty"
                                                >
                                                    No guide found
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="clients-cell-new">
                                        <div class="search-select-box mb-2">
                                            <div class="smart-select">
                                                <input
                                                    v-model="
                                                        search[
                                                            `${cfg.prefix}_client`
                                                        ]
                                                    "
                                                    type="text"
                                                    class="form-control table-input"
                                                    placeholder="Client..."
                                                    @focus="
                                                        closeAll();
                                                        open[
                                                            `${cfg.prefix}_client`
                                                        ] = true;
                                                    "
                                                />
                                                <div
                                                    v-if="
                                                        open[
                                                            `${cfg.prefix}_client`
                                                        ]
                                                    "
                                                    class="smart-menu"
                                                >
                                                    <button
                                                        v-for="item in lists(
                                                            cfg.prefix,
                                                            cfg.planning,
                                                        ).clients"
                                                        :key="item.id"
                                                        type="button"
                                                        class="smart-item"
                                                        @click="
                                                            selectItem(
                                                                cfg.prefix,
                                                                'client',
                                                                item,
                                                            )
                                                        "
                                                    >
                                                        {{ item.full_name }}
                                                    </button>
                                                    <div
                                                        v-if="
                                                            !lists(
                                                                cfg.prefix,
                                                                cfg.planning,
                                                            ).clients.length
                                                        "
                                                        class="smart-empty"
                                                    >
                                                        No client found
                                                    </div>
                                                </div>
                                            </div>
                                            <button
                                                type="button"
                                                class="plus-btn"
                                                @click="
                                                    $emit(
                                                        'open-modal',
                                                        'clientModal',
                                                    )
                                                "
                                            >
                                                +
                                            </button>
                                        </div>
                                        <div
                                            v-if="cfg.selectedClients.length"
                                            class="client-tags"
                                        >
                                            <span
                                                v-for="client in cfg.selectedClients"
                                                :key="client.id"
                                                class="client-tag client-tag-selected"
                                            >
                                                {{ client.full_name }}
                                                <button
                                                    type="button"
                                                    class="tag-remove"
                                                    @click="
                                                        $emit(
                                                            'remove-edit-client',
                                                            client.id,
                                                        )
                                                    "
                                                >
                                                    ×
                                                </button>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <input
                                            v-model="cfg.planning.budget"
                                            type="number"
                                            step="0.01"
                                            class="form-control table-input small-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="
                                                cfg.planning.supplier_price
                                            "
                                            type="number"
                                            step="0.01"
                                            class="form-control table-input small-input"
                                        />
                                    </td>
                                    <td class="actions-cell">
                                        <div class="row-actions">
                                            <button
                                                type="button"
                                                class="btn btn-save-action btn-sm"
                                                @click="
                                                    $emit('update-planning')
                                                "
                                                :disabled="cfg.saving"
                                            >
                                                <span
                                                    v-if="cfg.saving"
                                                    class="spinner-border spinner-border-sm me-1"
                                                ></span>
                                                Update
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-cancel-action btn-sm"
                                                @click="
                                                    $emit('cancel-edit-row')
                                                "
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>

                            <tr v-else>
                                <td>{{ formatDateOnly(planning.date_du) }}</td>
                                <td>{{ formatDateOnly(planning.date_au) }}</td>
                                <td>
                                    <span class="ref-badge">{{
                                        planning.ref_dossier || "-"
                                    }}</span>
                                </td>
                                <td>
                                    {{
                                        planning?.vehicule?.matricule ||
                                        planning.bus ||
                                        "-"
                                    }}
                                </td>
                                <td>{{ planning.nbr_personnes || "-" }}</td>
                                <td>
                                    {{ planning?.service?.designation || "-" }}
                                </td>
                                <td>{{ planning.flight || "-" }}</td>
                                <td>{{ planning.heure || "-" }}</td>
                                <td>{{ planning.point_depart || "-" }}</td>
                                <td>
                                    {{
                                        planning?.destination?.name ||
                                        planning.destination ||
                                        "-"
                                    }}
                                </td>
                                <td>{{ planning.site || "-" }}</td>
                                <td>
                                    {{ planning?.supplier_client?.name || "-" }}
                                </td>
                                <td>
                                    {{
                                        planning?.supplierVehicule?.name ||
                                        planning?.supplier_vehicule?.name ||
                                        "-"
                                    }}
                                </td>
                                <td>{{ planning?.driver?.name || "-" }}</td>
                                <td>{{ planning?.guide?.name || "-" }}</td>

                                <td class="clients-inline-cell">
                                    <div
                                        v-if="getClients(planning).length"
                                        class="client-tags"
                                    >
                                        <span
                                            v-for="clientRel in getClients(
                                                planning,
                                            ).slice(0, 3)"
                                            :key="clientRel.id"
                                            class="client-tag"
                                        >
                                            {{
                                                clientRel?.client?.full_name ||
                                                "-"
                                            }}
                                        </span>

                                        <button
                                            v-if="
                                                getClients(planning).length > 3
                                            "
                                            type="button"
                                            class="btn btn-link p-0 small fw-bold text-danger"
                                            @click="
                                                $emit(
                                                    'open-clients-modal',
                                                    planning,
                                                )
                                            "
                                        >
                                            View more
                                        </button>
                                    </div>
                                    <span v-else class="text-muted">-</span>
                                </td>

                                <td>{{ planning.budget || "-" }}</td>
                                <td>{{ planning.supplier_price || "-" }}</td>

                                <td class="actions-cell">
                                    <div class="row-actions">
                                        <button
                                            type="button"
                                            class="btn btn-edit-action btn-sm"
                                            @click="
                                                $emit('open-edit-row', planning)
                                            "
                                        >
                                            <i class="bx bx-edit me-1"></i>
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-delete-action btn-sm"
                                            @click="
                                                $emit(
                                                    'destroy-planning',
                                                    planning.id,
                                                )
                                            "
                                        >
                                            <i class="bx bx-trash me-1"></i>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="rows.length === 0">
                            <td
                                colspan="19"
                                class="text-center py-5 text-muted"
                            >
                                No planning found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="plannings?.links?.length"
                class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top"
            >
                <div class="text-muted small">
                    Page {{ plannings.current_page }} /
                    {{ plannings.last_page }}
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <Link
                        v-for="(link, index) in plannings.links"
                        :key="index"
                        :href="link.url || ''"
                        v-html="link.label"
                        class="btn btn-sm"
                        :class="
                            link.active
                                ? 'btn-danger-red'
                                : 'btn-outline-secondary'
                        "
                        :disabled="!link.url"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.planning-table-card {
    border-radius: 24px;
    overflow: visible;
    background: #fff;
}

.table-responsive {
    overflow-x: auto;
    overflow-y: visible;
}

.custom-planning-table {
    min-width: 2500px;
    border-collapse: separate;
    border-spacing: 0;
}

.custom-planning-table thead th {
    background: #fff3f4;
    color: #9f101d;
    font-size: 14px;
    font-weight: 900;
    padding: 18px 16px;
    border-bottom: 1px solid #f3d4d7;
    white-space: nowrap;
    vertical-align: middle;
}

.custom-planning-table tbody td {
    padding: 16px 14px;
    border-bottom: 1px solid #eef1f5;
    background: #fff;
    vertical-align: top;
    min-width: 130px;
}

.planning-new-row td,
.planning-edit-row td {
    background: #fffafa !important;
}

.table-input {
    min-width: 150px;
    height: 46px;
    border-radius: 14px;
    border: 1px solid #d9e1ec;
    background: #fff;
    box-shadow: none;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}

.table-input:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
}

.small-input {
    min-width: 110px;
}

.tiny-input {
    min-width: 80px;
}

.search-select-box {
    display: flex;
    gap: 8px;
    align-items: start;
}

.smart-select {
    position: relative;
    width: 100%;
}

.smart-menu {
    position: absolute;
    z-index: 9999;
    top: calc(100% + 6px);
    left: 0;
    min-width: 100%;
    max-height: 260px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    padding: 8px;
}

.smart-item {
    width: 100%;
    border: 0;
    background: transparent;
    text-align: left;
    padding: 11px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}

.smart-item:hover {
    background: #fff1f2;
    color: #be123c;
}

.smart-empty {
    padding: 12px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 700;
}

.plus-btn {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #111827;
    font-size: 20px;
    font-weight: 900;
}

.plus-btn:hover {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}

.clients-inline-cell,
.clients-cell-new {
    min-width: 320px !important;
    width: 320px;
}

.client-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    max-width: 100%;
}

.client-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f8fbff, #eef4ff);
    border: 1px solid #dbe7ff;
    color: #1d4ed8;
    font-size: 13px;
    font-weight: 800;
    line-height: 1.4;
    white-space: normal;
    text-align: center;
    max-width: 220px;
    min-height: 46px;
    box-shadow: 0 6px 18px rgba(59, 130, 246, 0.08);
    transition: 0.2s ease;
}

.client-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 24px rgba(59, 130, 246, 0.14);
    border-color: #bfdbfe;
}

.client-tag-selected {
    background: linear-gradient(135deg, #fff5f5, #fff0f0);
    border: 1px solid #fecaca;
    color: #b91c1c;
    box-shadow: 0 6px 18px rgba(239, 68, 68, 0.08);
}

.client-tag-selected:hover {
    box-shadow: 0 10px 24px rgba(239, 68, 68, 0.14);
}

.tag-remove {
    margin-left: 8px;
    border: 0;
    background: transparent;
    color: inherit;
    font-size: 16px;
    font-weight: 900;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.ref-badge {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.06);
    color: #111827;
    font-weight: 900;
    white-space: nowrap;
}

.actions-cell {
    min-width: 260px;
}

.row-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
}

.btn-save-action {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-cancel-action {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-edit-action {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-delete-action:hover,
.btn-save-action:hover,
.btn-edit-action:hover {
    color: #fff;
}
</style>
