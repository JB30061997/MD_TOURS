<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, reactive } from "vue";

const props = defineProps({
    plannings: { type: Object, required: true },
    rows: { type: Array, default: () => [] },
    showNewRow: { type: Boolean, default: false },
    newPlanning: { type: Object, required: true },
    searchInputs: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    loadingSave: { type: Boolean, default: false },

    supplierVehicules: { type: Array, default: () => [] },
    supplierClients: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },

    selectedClientsObjects: { type: Array, default: () => [] },
    formatDateOnly: { type: Function, required: true },
});

const emit = defineEmits([
    "sync-supplier-vehicule-id",
    "sync-driver-id",
    "sync-guide-id",
    "sync-service-id",
    "sync-destination-id",
    "sync-vehicule-id",
    "add-client-from-search",
    "remove-client",
    "open-modal",
    "save-planning",
    "cancel-new-row",
    "open-clients-modal",
    "destroy-planning",
]);

const open = reactive({
    depart: false,
    destination: false,
    supplierClient: false,
    supplierVehicule: false,
    vehicule: false,
    driver: false,
    guide: false,
    service: false,
    client: false,
});

const search = reactive({
    depart: "",
    destination: "",
    supplierClient: "",
    supplierVehicule: "",
    vehicule: "",
    driver: "",
    guide: "",
    service: "",
    client: "",
});

const normalize = (v) =>
    String(v || "")
        .toLowerCase()
        .trim();

const filterBy = (items, key, term) => {
    const q = normalize(term);
    if (!q) return items;

    return items.filter((item) => normalize(item[key]).includes(q));
};

const filteredDeparts = computed(() =>
    filterBy(props.destinations, "name", search.depart),
);

const filteredDestinations = computed(() =>
    filterBy(props.destinations, "name", search.destination),
);

const filteredSupplierClients = computed(() =>
    filterBy(props.supplierClients, "name", search.supplierClient),
);

const filteredSupplierVehicules = computed(() =>
    filterBy(props.supplierVehicules, "name", search.supplierVehicule),
);

const filteredDrivers = computed(() =>
    filterBy(props.drivers, "name", search.driver),
);

const filteredGuides = computed(() =>
    filterBy(props.guides, "name", search.guide),
);

const filteredServices = computed(() =>
    filterBy(props.services, "designation", search.service),
);

const filteredVehicules = computed(() => {
    let list = props.vehicules || [];

    return filterBy(list, "matricule", search.vehicule);
});

const filteredClients = computed(() => {
    let list = props.clients || [];

    if (props.newPlanning.supplier_client_id) {
        list = list.filter(
            (client) =>
                Number(client.supplier_client_id) ===
                Number(props.newPlanning.supplier_client_id),
        );
    }

    return filterBy(list, "full_name", search.client);
});

const closeAll = () => {
    Object.keys(open).forEach((key) => {
        open[key] = false;
    });
};

const selectDepart = (item) => {
    props.newPlanning.point_depart = item.name;
    search.depart = item.name;
    closeAll();
};

const selectDestination = (item) => {
    props.newPlanning.destination_id = item.id;
    props.searchInputs.destination = item.name;
    search.destination = item.name;
    closeAll();
    emit("sync-destination-id");
};

const selectSupplierClient = (item) => {
    props.newPlanning.supplier_client_id = item.id;
    search.supplierClient = item.name;
    props.searchInputs.client = "";
    props.newPlanning.client_ids = [];
    closeAll();
};

const selectSupplierVehicule = (item) => {
    props.newPlanning.supplier_vehicule_id = item.id;
    props.searchInputs.supplierVehicule = item.name;
    search.supplierVehicule = item.name;
    closeAll();
    emit("sync-supplier-vehicule-id");
};

const selectVehicule = (item) => {
    props.newPlanning.vehicule_id = item.id;
    props.searchInputs.vehicule = item.matricule;
    search.vehicule = item.matricule;
    closeAll();
    emit("sync-vehicule-id");
};

const selectDriver = (item) => {
    props.newPlanning.driver_id = item.id;
    props.searchInputs.driver = item.name;
    search.driver = item.name;
    closeAll();
    emit("sync-driver-id");
};

const selectGuide = (item) => {
    props.newPlanning.guide_id = item.id;
    props.searchInputs.guide = item.name;
    search.guide = item.name;
    closeAll();
    emit("sync-guide-id");
};

const selectService = (item) => {
    props.newPlanning.service_id = item.id;
    props.searchInputs.service = item.designation;
    search.service = item.designation;
    closeAll();
    emit("sync-service-id");
};

const selectClient = (item) => {
    props.searchInputs.client = item.full_name;
    search.client = "";
    closeAll();
    emit("add-client-from-search");
};

const getClients = (planning) => {
    return planning.planning_clients || planning.planningClients || [];
};
</script>

<template>
    <div class="planning-table-card card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle table-hover custom-planning-table mb-0">
                    <thead>
                        <tr>
                            <th>DU</th>
                            <th>AU</th>
                            <th>Réf</th>
                            <th>Véhicule</th>
                            <th>N/P</th>
                            <th>Type</th>
                            <th>Flight</th>
                            <th>Time</th>
                            <th>Start Point</th>
                            <th>End Point</th>
                            <th>Location</th>
                            <th>Suppliers</th>
                            <th>Fournisseur Véhicule</th>
                            <th>MD Driver</th>
                            <th>Guide</th>
                            <th>Clients Name</th>
                            <th>budget</th>
                            <th>supplier's price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-if="showNewRow" class="planning-new-row">
                            <td>
                                <input v-model="newPlanning.date_du" type="date" class="form-control table-input" />
                                <small class="text-danger">{{ errors.date_du }}</small>
                            </td>

                            <td>
                                <input v-model="newPlanning.date_au" type="date" class="form-control table-input" />
                            </td>

                            <td>
                                <input v-model="newPlanning.ref_dossier" type="text" class="form-control table-input small-input" />
                                <small class="text-danger">{{ errors.ref_dossier }}</small>
                            </td>

                            <td>
                                <div class="smart-select">
                                    <input
                                        v-model="search.vehicule"
                                        type="text"
                                        class="form-control table-input"
                                        placeholder="Véhicule..."
                                        @focus="closeAll(); open.vehicule = true;"
                                    />

                                    <div v-if="open.vehicule" class="smart-menu">
                                        <button
                                            v-for="item in filteredVehicules"
                                            :key="item.id"
                                            type="button"
                                            class="smart-item"
                                            @click="selectVehicule(item)"
                                        >
                                            {{ item.matricule }}
                                            <small v-if="item.marque || item.modele" class="text-muted">
                                                — {{ item.marque }} {{ item.modele }}
                                            </small>
                                        </button>

                                        <div v-if="!filteredVehicules.length" class="smart-empty">
                                            Aucun véhicule
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <input v-model="newPlanning.nbr_personnes" type="number" class="form-control table-input tiny-input" />
                            </td>

                            <td>
                                <div class="smart-select">
                                    <input
                                        v-model="search.service"
                                        type="text"
                                        class="form-control table-input"
                                        placeholder="Type..."
                                        @focus="closeAll(); open.service = true;"
                                    />

                                    <div v-if="open.service" class="smart-menu">
                                        <button
                                            v-for="item in filteredServices"
                                            :key="item.id"
                                            type="button"
                                            class="smart-item"
                                            @click="selectService(item)"
                                        >
                                            {{ item.designation }}
                                        </button>

                                        <div v-if="!filteredServices.length" class="smart-empty">
                                            Aucun type
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <input v-model="newPlanning.flight" type="text" class="form-control table-input small-input" />
                            </td>

                            <td>
                                <input v-model="newPlanning.heure" type="time" class="form-control table-input" />
                            </td>

                            <td>
                                <div class="smart-select">
                                    <input
                                        v-model="search.depart"
                                        type="text"
                                        class="form-control table-input"
                                        placeholder="Start Point..."
                                        @focus="closeAll(); open.depart = true;"
                                    />

                                    <div v-if="open.depart" class="smart-menu">
                                        <button
                                            v-for="item in filteredDeparts"
                                            :key="item.id"
                                            type="button"
                                            class="smart-item"
                                            @click="selectDepart(item)"
                                        >
                                            {{ item.name }}
                                        </button>

                                        <div v-if="!filteredDeparts.length" class="smart-empty">
                                            Aucun départ
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="search-select-box">
                                    <div class="smart-select">
                                        <input
                                            v-model="search.destination"
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="End Point..."
                                            @focus="closeAll(); open.destination = true;"
                                        />

                                        <div v-if="open.destination" class="smart-menu">
                                            <button
                                                v-for="item in filteredDestinations"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="selectDestination(item)"
                                            >
                                                {{ item.name }}
                                            </button>

                                            <div v-if="!filteredDestinations.length" class="smart-empty">
                                                Aucune destination
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="plus-btn" @click="$emit('open-modal', 'destinationModal')">
                                        +
                                    </button>
                                </div>
                            </td>

                            <td>
                                <input v-model="newPlanning.site" type="text" class="form-control table-input" placeholder="Location..." />
                            </td>

                            <td>
                                <div class="search-select-box">
                                    <div class="smart-select">
                                        <input
                                            v-model="search.supplierClient"
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Suppliers..."
                                            @focus="closeAll(); open.supplierClient = true;"
                                        />

                                        <div v-if="open.supplierClient" class="smart-menu">
                                            <button
                                                v-for="item in filteredSupplierClients"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="selectSupplierClient(item)"
                                            >
                                                {{ item.name }}
                                            </button>

                                            <div v-if="!filteredSupplierClients.length" class="smart-empty">
                                                Aucun supplier
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="plus-btn" @click="$emit('open-modal', 'supplierClientModal')">
                                        +
                                    </button>
                                </div>
                            </td>

                            <td>
                                <div class="search-select-box">
                                    <div class="smart-select">
                                        <input
                                            v-model="search.supplierVehicule"
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Fournisseur véhicule..."
                                            @focus="closeAll(); open.supplierVehicule = true;"
                                        />

                                        <div v-if="open.supplierVehicule" class="smart-menu">
                                            <button
                                                v-for="item in filteredSupplierVehicules"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="selectSupplierVehicule(item)"
                                            >
                                                {{ item.name }}
                                            </button>

                                            <div v-if="!filteredSupplierVehicules.length" class="smart-empty">
                                                Aucun fournisseur véhicule
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="plus-btn" @click="$emit('open-modal', 'supplierModal')">
                                        +
                                    </button>
                                </div>
                            </td>

                            <td>
                                <div class="smart-select">
                                    <input
                                        v-model="search.driver"
                                        type="text"
                                        class="form-control table-input small-input"
                                        placeholder="MD Driver..."
                                        @focus="closeAll(); open.driver = true;"
                                    />

                                    <div v-if="open.driver" class="smart-menu">
                                        <button
                                            v-for="item in filteredDrivers"
                                            :key="item.id"
                                            type="button"
                                            class="smart-item"
                                            @click="selectDriver(item)"
                                        >
                                            {{ item.name }}
                                        </button>

                                        <div v-if="!filteredDrivers.length" class="smart-empty">
                                            Aucun chauffeur
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="smart-select">
                                    <input
                                        v-model="search.guide"
                                        type="text"
                                        class="form-control table-input small-input"
                                        placeholder="Guide..."
                                        @focus="closeAll(); open.guide = true;"
                                    />

                                    <div v-if="open.guide" class="smart-menu">
                                        <button
                                            v-for="item in filteredGuides"
                                            :key="item.id"
                                            type="button"
                                            class="smart-item"
                                            @click="selectGuide(item)"
                                        >
                                            {{ item.name }}
                                        </button>

                                        <div v-if="!filteredGuides.length" class="smart-empty">
                                            Aucun guide
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="clients-cell-new">
                                <div class="search-select-box mb-2">
                                    <div class="smart-select">
                                        <input
                                            v-model="search.client"
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Client..."
                                            @focus="closeAll(); open.client = true;"
                                        />

                                        <div v-if="open.client" class="smart-menu">
                                            <button
                                                v-for="item in filteredClients"
                                                :key="item.id"
                                                type="button"
                                                class="smart-item"
                                                @click="selectClient(item)"
                                            >
                                                {{ item.full_name }}
                                            </button>

                                            <div v-if="!filteredClients.length" class="smart-empty">
                                                Aucun client
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="plus-btn" @click="$emit('open-modal', 'clientModal')">
                                        +
                                    </button>
                                </div>

                                <div v-if="selectedClientsObjects.length" class="client-tags">
                                    <span
                                        v-for="client in selectedClientsObjects"
                                        :key="client.id"
                                        class="client-tag client-tag-selected"
                                    >
                                        {{ client.full_name }}
                                        <button type="button" class="tag-remove" @click="$emit('remove-client', client.id)">
                                            ×
                                        </button>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <input v-model="newPlanning.budget" type="number" step="0.01" class="form-control table-input small-input" />
                            </td>

                            <td>
                                <input v-model="newPlanning.supplier_price" type="number" step="0.01" class="form-control table-input small-input" />
                            </td>

                            <td class="actions-cell">
                                <div class="row-actions">
                                    <button type="button" class="btn btn-save-action btn-sm" @click="$emit('save-planning')" :disabled="loadingSave">
                                        <span v-if="loadingSave" class="spinner-border spinner-border-sm me-1"></span>
                                        Enregistrer
                                    </button>

                                    <button type="button" class="btn btn-cancel-action btn-sm" @click="$emit('cancel-new-row')">
                                        Annuler
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-for="planning in rows" :key="planning.id">
                            <td>{{ formatDateOnly(planning.date_du) }}</td>
                            <td>{{ formatDateOnly(planning.date_au) }}</td>
                            <td><span class="ref-badge">{{ planning.ref_dossier || "-" }}</span></td>
                            <td>{{ planning?.vehicule?.matricule || planning.bus || "-" }}</td>
                            <td>{{ planning.nbr_personnes || "-" }}</td>
                            <td>{{ planning?.service?.designation || "-" }}</td>
                            <td>{{ planning.flight || "-" }}</td>
                            <td>{{ planning.heure || "-" }}</td>
                            <td>{{ planning.point_depart || "-" }}</td>
                            <td>{{ planning?.destination?.name || planning.destination || "-" }}</td>
                            <td>{{ planning.site || "-" }}</td>
                            <td>{{ planning?.supplier_client?.name || "-" }}</td>
                            <td>{{ planning?.supplierVehicule?.name || planning?.supplier_vehicule?.name || "-" }}</td>
                            <td>{{ planning?.driver?.name || "-" }}</td>
                            <td>{{ planning?.guide?.name || "-" }}</td>

                            <td class="clients-inline-cell">
                                <div v-if="getClients(planning).length" class="client-tags">
                                    <span
                                        v-for="clientRel in getClients(planning).slice(0, 3)"
                                        :key="clientRel.id"
                                        class="client-tag"
                                    >
                                        {{ clientRel?.client?.full_name || "-" }}
                                    </span>

                                    <button
                                        v-if="getClients(planning).length > 3"
                                        type="button"
                                        class="btn btn-link p-0 small fw-bold text-danger"
                                        @click="$emit('open-clients-modal', planning)"
                                    >
                                        Voir +
                                    </button>
                                </div>

                                <span v-else class="text-muted">-</span>
                            </td>

                            <td>{{ planning.budget || "-" }}</td>
                            <td>{{ planning.supplier_price || "-" }}</td>

                            <td class="actions-cell">
                                <div class="row-actions">
                                    <button type="button" class="btn btn-delete-action btn-sm" @click="$emit('destroy-planning', planning.id)">
                                        <i class="bx bx-trash me-1"></i>
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="rows.length === 0">
                            <td colspan="19" class="text-center py-5 text-muted">
                                Aucun planning trouvé.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="plannings?.links?.length" class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top">
                <div class="text-muted small">
                    Page {{ plannings.current_page }} / {{ plannings.last_page }}
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <Link
                        v-for="(link, index) in plannings.links"
                        :key="index"
                        :href="link.url || ''"
                        v-html="link.label"
                        class="btn btn-sm"
                        :class="link.active ? 'btn-danger-red' : 'btn-outline-secondary'"
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
    min-width: 2400px;
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

.planning-new-row td {
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
    min-width: 220px;
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

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-delete-action:hover,
.btn-save-action:hover {
    color: #fff;
}
</style>
