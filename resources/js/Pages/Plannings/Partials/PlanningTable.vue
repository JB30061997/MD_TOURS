<script setup>
import { Link, router } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";

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
    manualOrderMode: { type: Boolean, default: false },
    savingOrder: { type: Boolean, default: false },

    supplierVehicules: { type: Array, default: () => [] },
    supplierTarifs: { type: Array, default: () => [] },
    supplierClients: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },
    query: { type: Object, required: true },
    columnFilters: { type: Object, default: () => ({}) },

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
    "apply-server-filters",
    "toggle-manual-order-mode",
    "save-planning-order",
]);

const open = reactive({});
const search = reactive({});
const openColumnFilter = ref(null);
const sendingAction = ref(null);
const actionMenuOpen = ref(null);
const filterDraft = reactive({});
const filterSearch = reactive({});
const localRows = ref([]);
const draggedRowIndex = ref(null);
const dragOverRowIndex = ref(null);

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
    () => props.rows,
    (rows) => {
        localRows.value = [...(rows || [])];
    },
    { immediate: true, deep: true },
);

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

const vehicleLabel = (vehicle) => {
    if (!vehicle) return "";

    const model = [vehicle.marque, vehicle.modele].filter(Boolean).join(" ").trim();
    const seats = Number(vehicle.nombre_places || 0);

    return [
        vehicle.matricule || vehicle.name || "-",
        model || null,
        seats ? `${seats} places` : null,
    ]
        .filter(Boolean)
        .join(" — ");
};

const itemSearchText = (item, key) => {
    if (typeof key === "function") {
        return key(item);
    }

    return item?.[key];
};

const filterBy = (items, key, term) => {
    const q = normalize(term);
    if (!q) return items;

    return items.filter((item) => normalize(itemSearchText(item, key)).includes(q));
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
    vehicules: filterBy(props.vehicules, vehicleLabel, search[`${prefix}_vehicule`]),
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

const optionLabel = (item, labelKey) => {
    if (typeof item === "object" && item !== null) {
        if (labelKey === "vehicleLabel") {
            return vehicleLabel(item);
        }

        return item[labelKey] || item.name || item.designation || item.matricule;
    }

    return item;
};

const optionValue = (item) => {
    if (typeof item === "object" && item !== null) {
        return String(item.id);
    }

    return String(item);
};

const formatTarifPrice = (value) =>
    new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

const matchingTarifs = (planning) => {
    const supplierId = Number(planning.supplier_vehicule_id || 0);
    const serviceId = Number(planning.service_id || 0);
    const vehicleId = Number(planning.vehicule_id || 0);
    const vehicle = props.vehicules.find((item) => Number(item.id) === vehicleId);
    const service = props.services.find((item) => Number(item.id) === serviceId);
    const vehicleSeats = Number(vehicle?.nombre_places || 0);
    const typeServiceId = Number(service?.type_service || 0);

    if (!supplierId || !serviceId || !vehicleSeats) return [];

    return props.supplierTarifs.filter(
        (tarif) =>
            Number(tarif.supplier_vehicule_id) === supplierId &&
            Number(tarif.service_id) === serviceId &&
            Number(tarif.vehicle_seats) === vehicleSeats &&
            Number(tarif.type_service_id || 0) === typeServiceId,
    );
};

const tarifSelectPlaceholder = (planning) => {
    if (!planning.supplier_vehicule_id) return "Choisir fournisseur";
    if (!planning.service_id) return "Choisir service";
    if (!planning.vehicule_id) return "Choisir véhicule";

    const vehicle = props.vehicules.find((item) => Number(item.id) === Number(planning.vehicule_id));

    if (!Number(vehicle?.nombre_places || 0)) return "Places véhicule manquantes";
    if (!matchingTarifs(planning).length) return "Aucun tarif configuré";

    return "Choisir tarif";
};

const syncPlanningTarif = (planning) => {
    const tarifs = matchingTarifs(planning);

    if (tarifs.length === 1) {
        planning.supplier_price = String(tarifs[0].price ?? "");
        return;
    }

    const currentIsStillValid = tarifs.some(
        (tarif) => Number(tarif.price) === Number(planning.supplier_price),
    );

    if (!currentIsStillValid) {
        planning.supplier_price = "";
    }
};

const selectSupplierTarif = (planning, price) => {
    planning.supplier_price = price || "";
};

const columnFilterConfigs = computed(() => ({
    filter_date_du: {
        title: "Star Date",
        options: props.columnFilters.start_dates || [],
    },
    filter_date_au: {
        title: "End Date",
        options: props.columnFilters.end_dates || [],
    },
    ref_dossier: {
        title: "Reference",
        options: props.columnFilters.references || [],
    },
    vehicule_id: {
        title: "Vehicle",
        options: props.vehicules,
        labelKey: "vehicleLabel",
    },
    nbr_personnes: {
        title: "PAX",
        options: props.columnFilters.paxes || [],
    },
    service_id: {
        title: "Type",
        options: props.services,
        labelKey: "designation",
    },
    flight: {
        title: "Flight",
        options: props.columnFilters.flights || [],
    },
    heure: {
        title: "Time",
        options: props.columnFilters.times || [],
    },
    point_depart: {
        title: "Start Point",
        options: props.columnFilters.start_points || [],
    },
    destination_id: {
        title: "End Point",
        options: props.destinations,
        labelKey: "name",
    },
    site: {
        title: "Location",
        options: props.columnFilters.locations || [],
    },
    supplier_client_id: {
        title: "Supplier Client",
        options: props.supplierClients,
        labelKey: "name",
    },
    supplier_vehicule_id: {
        title: "Vehicle Supplier",
        options: props.supplierVehicules,
        labelKey: "name",
    },
    driver_id: {
        title: "MD Driver",
        options: props.drivers,
        labelKey: "name",
    },
    guide_id: {
        title: "Guide",
        options: props.guides,
        labelKey: "name",
    },
    client_id: {
        title: "Passenger Names",
        options: props.clients,
        labelKey: "full_name",
    },
    budget: {
        title: "Budget",
        options: props.columnFilters.budgets || [],
    },
    supplier_price: {
        title: "Supplier Price",
        options: props.columnFilters.supplier_prices || [],
    },
}));

const toggleColumnFilter = (key) => {
    if (openColumnFilter.value === key) {
        openColumnFilter.value = null;
        return;
    }

    filterDraft[key] = normalizeFilterValues(props.query[key]);
    filterSearch[key] = "";
    openColumnFilter.value = key;
};

const normalizeFilterValues = (value) => {
    if (Array.isArray(value)) return value.map(String).filter(Boolean);
    return value ? [String(value)] : [];
};

const columnOptions = (key) => {
    const config = columnFilterConfigs.value[key] || {};
    const seen = new Set();
    const term = normalize(filterSearch[key]);

    return (config.options || [])
        .map((item) => ({
            value: optionValue(item),
            label: String(optionLabel(item, config.labelKey) || ""),
        }))
        .filter((item) => item.value !== "" && item.label !== "")
        .filter((item) => {
            if (seen.has(item.value)) return false;
            seen.add(item.value);
            return true;
        })
        .filter((item) => !term || normalize(item.label).includes(term));
};

const allColumnOptions = (key) => columnOptions(key);

const isDraftSelected = (key, value) =>
    (filterDraft[key] || []).includes(String(value));

const toggleDraftValue = (key, value) => {
    const current = filterDraft[key] || [];
    const stringValue = String(value);

    filterDraft[key] = current.includes(stringValue)
        ? current.filter((item) => item !== stringValue)
        : [...current, stringValue];
};

const toggleAllDraftValues = (key) => {
    const values = allColumnOptions(key).map((item) => item.value);
    const current = filterDraft[key] || [];
    const allSelected =
        values.length > 0 && values.every((value) => current.includes(value));

    filterDraft[key] = allSelected
        ? current.filter((value) => !values.includes(value))
        : [...new Set([...current, ...values])];
};

const allDraftSelected = (key) => {
    const values = allColumnOptions(key).map((item) => item.value);
    const current = filterDraft[key] || [];

    return values.length > 0 && values.every((value) => current.includes(value));
};

const selectedCount = (key) => normalizeFilterValues(props.query[key]).length;

const applyColumnFilter = (key = openColumnFilter.value) => {
    if (key) {
        props.query[key] = filterDraft[key] || [];
    }

    openColumnFilter.value = null;
    emit("apply-server-filters");
};

const clearColumnFilter = (key) => {
    filterDraft[key] = [];
    props.query[key] = [];
    applyColumnFilter(key);
};

const sortColumn = (key, direction) => {
    props.query.sort_column = key;
    props.query.sort_direction = direction;
    openColumnFilter.value = null;
    emit("apply-server-filters");
};

const isColumnFiltered = (key) => selectedCount(key) > 0;

const isColumnSorted = (key, direction) =>
    props.query.sort_column === key && props.query.sort_direction === direction;

const tableHeaders = [
    { label: "Star date", filterKey: "filter_date_du" },
    { label: "End date", filterKey: "filter_date_au" },
    { label: "Reference", filterKey: "ref_dossier" },
    { label: "Vehicle", filterKey: "vehicule_id" },
    { label: "PAX", filterKey: "nbr_personnes" },
    { label: "Type", filterKey: "service_id" },
    { label: "Flight", filterKey: "flight" },
    { label: "Time", filterKey: "heure" },
    { label: "Start Point", filterKey: "point_depart" },
    { label: "End Point", filterKey: "destination_id" },
    { label: "Location", filterKey: "site" },
    { label: "Suppliers Clients", filterKey: "supplier_client_id" },
    { label: "Vehicle Supplier", filterKey: "supplier_vehicule_id" },
    { label: "MD Driver", filterKey: "driver_id" },
    { label: "Guide", filterKey: "guide_id" },
    { label: "Clients", filterKey: "client_id" },
    { label: "Budget", filterKey: "budget" },
    { label: "Supplier Price", filterKey: "supplier_price" },
    { label: "Actions" },
];

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
        syncPlanningTarif(planning);
        emit(emitName("sync-supplier-vehicule-id"));
    }

    if (type === "vehicule") {
        planning.vehicule_id = item.id;
        inputs.vehicule = vehicleLabel(item);
        search[`${prefix}_vehicule`] = vehicleLabel(item);
        syncPlanningTarif(planning);
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
        syncPlanningTarif(planning);
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

const getDirectSupplierClient = (planning) => {
    return planning?.supplier_client || planning?.supplierClient || null;
};

const getClientDisplayItems = (planning) => {
    const clientItems = getClients(planning)
        .map((clientRel) => {
            const client = clientRel?.client;
            const name = client?.full_name || clientRel?.full_name;

            if (!name) return null;

            return {
                id: clientRel?.id || client?.id || name,
                name,
                type: "client",
            };
        })
        .filter(Boolean);

    if (clientItems.length) {
        return clientItems;
    }

    const supplier = getDirectSupplierClient(planning);

    if (!supplier?.name) {
        return [];
    }

    return [
        {
            id: `supplier-${supplier.id || supplier.name}`,
            name: `Supplier: ${supplier.name}`,
            type: "supplier",
        },
    ];
};

const getSupplierClientNames = (planning) => {
    const directSupplier = getDirectSupplierClient(planning)?.name;

    const clientSuppliers = getClients(planning)
        .map(
            (clientRel) =>
                clientRel?.client?.supplier_client?.name ||
                clientRel?.client?.supplierClient?.name,
        )
        .filter(Boolean);

    return [...new Set([directSupplier, ...clientSuppliers].filter(Boolean))];
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

const visibleRows = computed(() =>
    props.manualOrderMode ? localRows.value : props.rows,
);

const startRowDrag = (index) => {
    if (!props.manualOrderMode) return;
    draggedRowIndex.value = index;
};

const moveDraggedRow = (index) => {
    if (!props.manualOrderMode || draggedRowIndex.value === null) return;
    dragOverRowIndex.value = index;
};

const dropDraggedRow = (targetIndex) => {
    if (!props.manualOrderMode || draggedRowIndex.value === null) return;

    const fromIndex = draggedRowIndex.value;
    const toIndex = targetIndex;

    if (fromIndex !== toIndex) {
        const updated = [...localRows.value];
        const [moved] = updated.splice(fromIndex, 1);
        updated.splice(toIndex, 0, moved);
        localRows.value = updated;
    }

    draggedRowIndex.value = null;
    dragOverRowIndex.value = null;
};

const stopRowDrag = () => {
    draggedRowIndex.value = null;
    dragOverRowIndex.value = null;
};

const saveManualOrder = () => {
    emit(
        "save-planning-order",
        localRows.value.map((row) => row.id),
    );
};

const openPlanningPdf = (planning, type) => {
    actionMenuOpen.value = null;
    const routeName =
        type === "commande" ? "plannings.commande.pdf" : "road-sheets.pdf";

    window.open(route(routeName, planning.id), "_blank");
};

const sendPlanningDocument = (planning, type) => {
    actionMenuOpen.value = null;
    const routeName =
        type === "commande"
            ? "plannings.commande.send-email"
            : "road-sheets.send-email";
    const key = `${type}-${planning.id}`;

    sendingAction.value = key;

    router.post(
        route(routeName, planning.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                sendingAction.value = null;
            },
        },
    );
};

const toggleActionMenu = (planningId) => {
    actionMenuOpen.value =
        actionMenuOpen.value === planningId ? null : planningId;
};

const closeActionMenu = () => {
    actionMenuOpen.value = null;
};
</script>

<template>
    <div class="planning-table-card card border-0 shadow-sm rounded-4">
        <div class="planning-order-toolbar">
            <div>
                <div class="planning-order-title">
                    <i class="bx bx-sort-alt-2"></i>
                    Row order
                </div>
                <p class="planning-order-help">
                    {{
                        manualOrderMode
                            ? "Drag rows, then save the custom position."
                            : query.use_manual_order
                              ? "Saved manual order is active."
                              : "Default order is by date and time."
                    }}
                </p>
            </div>
            <div class="planning-order-actions">
                <button
                    type="button"
                    class="btn btn-soft-secondary btn-sm order-mode-btn"
                    :class="{ active: manualOrderMode }"
                    @click="$emit('toggle-manual-order-mode')"
                >
                    <i class="bx bx-move-vertical me-1"></i>
                    {{ manualOrderMode ? "Exit drag mode" : "Drag mode" }}
                </button>
                <button
                    v-if="manualOrderMode"
                    type="button"
                    class="btn btn-danger-red btn-sm order-save-btn"
                    :disabled="savingOrder"
                    @click="saveManualOrder"
                >
                    <span
                        v-if="savingOrder"
                        class="spinner-border spinner-border-sm me-1"
                    ></span>
                    <i v-else class="bx bx-save me-1"></i>
                    Save order
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table
                    class="table align-middle table-hover custom-planning-table mb-0"
                >
                    <thead>
                        <tr>
                            <th
                                v-for="header in tableHeaders"
                                :key="header.label"
                                class="header-filter-cell"
                            >
                                <div class="header-filter-wrap">
                                    <span>{{ header.label }}</span>
                                    <button
                                        v-if="header.filterKey"
                                        type="button"
                                        class="column-filter-btn"
                                        :class="{
                                            active: isColumnFiltered(
                                                header.filterKey,
                                            ),
                                        }"
                                        @click.stop="
                                            toggleColumnFilter(
                                                header.filterKey,
                                            )
                                        "
                                    >
                                        <i class="bx bx-filter-alt"></i>
                                    </button>

                                    <div
                                        v-if="
                                            header.filterKey &&
                                            openColumnFilter ===
                                                header.filterKey
                                        "
                                        class="column-filter-menu"
                                    >
                                        <div class="column-filter-title">
                                            {{
                                                columnFilterConfigs[
                                                    header.filterKey
                                                ]?.title || header.label
                                            }}
                                        </div>

                                        <div class="column-sort-actions">
                                            <button
                                                type="button"
                                                class="column-sort-btn"
                                                :class="{
                                                    active: isColumnSorted(
                                                        header.filterKey,
                                                        'asc',
                                                    ),
                                                }"
                                                @click="
                                                    sortColumn(
                                                        header.filterKey,
                                                        'asc',
                                                    )
                                                "
                                            >
                                                <span>A</span>
                                                <i class="bx bx-down-arrow-alt"></i>
                                                Asc
                                            </button>
                                            <button
                                                type="button"
                                                class="column-sort-btn"
                                                :class="{
                                                    active: isColumnSorted(
                                                        header.filterKey,
                                                        'desc',
                                                    ),
                                                }"
                                                @click="
                                                    sortColumn(
                                                        header.filterKey,
                                                        'desc',
                                                    )
                                                "
                                            >
                                                <span>Z</span>
                                                <i class="bx bx-down-arrow-alt"></i>
                                                Desc
                                            </button>
                                        </div>

                                        <div class="column-search-box">
                                            <i class="bx bx-search"></i>
                                            <input
                                                v-model="
                                                    filterSearch[
                                                        header.filterKey
                                                    ]
                                                "
                                                type="text"
                                                placeholder="Search"
                                            />
                                        </div>

                                        <div class="column-check-list">
                                            <label class="column-check-row all">
                                                <input
                                                    type="checkbox"
                                                    :checked="
                                                        allDraftSelected(
                                                            header.filterKey,
                                                        )
                                                    "
                                                    @change="
                                                        toggleAllDraftValues(
                                                            header.filterKey,
                                                        )
                                                    "
                                                />
                                                <span>(Select All)</span>
                                            </label>

                                            <label
                                                v-for="item in columnOptions(
                                                    header.filterKey,
                                                )"
                                                :key="item.value"
                                                class="column-check-row"
                                            >
                                                <input
                                                    type="checkbox"
                                                    :checked="
                                                        isDraftSelected(
                                                            header.filterKey,
                                                            item.value,
                                                        )
                                                    "
                                                    @change="
                                                        toggleDraftValue(
                                                            header.filterKey,
                                                            item.value,
                                                        )
                                                    "
                                                />
                                                <span>{{ item.label }}</span>
                                            </label>

                                            <div
                                                v-if="
                                                    !columnOptions(
                                                        header.filterKey,
                                                    ).length
                                                "
                                                class="column-empty-state"
                                            >
                                                No options
                                            </div>
                                        </div>

                                        <div class="column-filter-actions">
                                            <button
                                                type="button"
                                                class="column-apply-btn"
                                                @click="
                                                    applyColumnFilter(
                                                        header.filterKey,
                                                    )
                                                "
                                            >
                                                Apply
                                            </button>
                                            <button
                                                type="button"
                                                class="column-clear-btn"
                                                @click="
                                                    clearColumnFilter(
                                                        header.filterKey,
                                                    )
                                                "
                                            >
                                                Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </th>
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
                                                {{ vehicleLabel(item) }}
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
                                    <select
                                        class="form-select table-input small-input"
                                        :value="cfg.planning.supplier_price"
                                        :disabled="
                                            !cfg.planning.supplier_vehicule_id ||
                                            !cfg.planning.service_id ||
                                            !cfg.planning.vehicule_id ||
                                            !matchingTarifs(cfg.planning).length
                                        "
                                        @change="
                                            selectSupplierTarif(
                                                cfg.planning,
                                                $event.target.value,
                                            )
                                        "
                                    >
                                        <option value="">
                                            {{ tarifSelectPlaceholder(cfg.planning) }}
                                        </option>
                                        <option
                                            v-for="tarif in matchingTarifs(
                                                cfg.planning,
                                            )"
                                            :key="tarif.id"
                                            :value="tarif.price"
                                        >
                                            {{
                                                formatTarifPrice(tarif.price)
                                            }}
                                            MAD - {{ tarif.vehicle_seats }} places
                                        </option>
                                    </select>
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

                        <template
                            v-for="(planning, rowIndex) in visibleRows"
                            :key="planning.id"
                        >
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
                                                    {{ vehicleLabel(item) }}
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
                                        <select
                                            class="form-select table-input small-input"
                                            :value="cfg.planning.supplier_price"
                                            :disabled="
                                                !cfg.planning
                                                    .supplier_vehicule_id ||
                                                !cfg.planning.service_id ||
                                                !cfg.planning.vehicule_id ||
                                                !matchingTarifs(cfg.planning)
                                                    .length
                                            "
                                            @change="
                                                selectSupplierTarif(
                                                    cfg.planning,
                                                    $event.target.value,
                                                )
                                            "
                                        >
                                            <option value="">
                                                {{ tarifSelectPlaceholder(cfg.planning) }}
                                            </option>
                                            <option
                                                v-for="tarif in matchingTarifs(
                                                    cfg.planning,
                                                )"
                                                :key="tarif.id"
                                                :value="tarif.price"
                                            >
                                                {{
                                                    formatTarifPrice(
                                                        tarif.price,
                                                    )
                                                }}
                                                MAD - {{ tarif.vehicle_seats }} places
                                            </option>
                                        </select>
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

                            <tr
                                v-else
                                class="planning-display-row"
                                :class="{
                                    'manual-order-row': manualOrderMode,
                                    'dragging-row':
                                        draggedRowIndex === rowIndex,
                                    'drag-over-row':
                                        dragOverRowIndex === rowIndex,
                                }"
                                :draggable="manualOrderMode"
                                @dragstart="startRowDrag(rowIndex)"
                                @dragover.prevent="moveDraggedRow(rowIndex)"
                                @drop.prevent="dropDraggedRow(rowIndex)"
                                @dragend="stopRowDrag"
                            >
                                <td>
                                    <span
                                        v-if="manualOrderMode"
                                        class="row-drag-handle"
                                    >
                                        <i class="bx bx-grid-vertical"></i>
                                    </span>
                                    {{ formatDateOnly(planning.date_du) }}
                                </td>
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
                                    {{
                                        getSupplierClientNames(planning).join(
                                            ", ",
                                        ) || "-"
                                    }}
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
                                        v-if="
                                            getClientDisplayItems(planning)
                                                .length
                                        "
                                        class="client-tags"
                                    >
                                        <span
                                            v-for="item in getClientDisplayItems(
                                                planning,
                                            ).slice(0, 3)"
                                            :key="item.id"
                                            class="client-tag"
                                            :class="{
                                                'client-tag-supplier':
                                                    item.type === 'supplier',
                                            }"
                                        >
                                            {{ item.name }}
                                        </span>

                                        <button
                                            v-if="
                                                getClientDisplayItems(planning)
                                                    .length > 3
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
                                    <div class="planning-actions-menu">
                                        <button
                                            type="button"
                                            class="btn btn-action-menu-toggle"
                                            @click.stop="
                                                toggleActionMenu(planning.id)
                                            "
                                        >
                                            <i class="bx bx-dots-horizontal-rounded"></i>
                                        </button>

                                        <div
                                            v-if="
                                                actionMenuOpen === planning.id
                                            "
                                            class="planning-actions-dropdown"
                                        >
                                            <button
                                                type="button"
                                                class="action-menu-item edit"
                                                @click="
                                                    closeActionMenu();
                                                    $emit(
                                                        'open-edit-row',
                                                        planning,
                                                    );
                                                "
                                            >
                                                <i class="bx bx-edit"></i>
                                                Modifier
                                            </button>
                                            <Link
                                                :href="
                                                    route(
                                                        'plannings.commande.open',
                                                        planning.id,
                                                    )
                                                "
                                                class="action-menu-item doc"
                                                @click="closeActionMenu"
                                            >
                                                <i class="bx bx-receipt"></i>
                                                Bon de commande
                                            </Link>
                                            <button
                                                type="button"
                                                class="action-menu-item pdf"
                                                @click="
                                                    openPlanningPdf(
                                                        planning,
                                                        'commande',
                                                    )
                                                "
                                            >
                                                <i class="bx bx-file"></i>
                                                PDF commande
                                            </button>
                                            <button
                                                type="button"
                                                class="action-menu-item mail"
                                                :disabled="
                                                    sendingAction ===
                                                    `commande-${planning.id}`
                                                "
                                                @click="
                                                    sendPlanningDocument(
                                                        planning,
                                                        'commande',
                                                    )
                                                "
                                            >
                                                <span
                                                    v-if="
                                                        sendingAction ===
                                                        `commande-${planning.id}`
                                                    "
                                                    class="spinner-border spinner-border-sm"
                                                ></span>
                                                <i
                                                    v-else
                                                    class="bx bx-envelope"
                                                ></i>
                                                Email commande
                                            </button>
                                            <Link
                                                :href="
                                                    route(
                                                        'road-sheets.show',
                                                        planning.id,
                                                    )
                                                "
                                                class="action-menu-item road"
                                                @click="closeActionMenu"
                                            >
                                                <i class="bx bx-trip"></i>
                                                Roadsheet
                                            </Link>
                                            <button
                                                type="button"
                                                class="action-menu-item pdf"
                                                @click="
                                                    openPlanningPdf(
                                                        planning,
                                                        'roadSheet',
                                                    )
                                                "
                                            >
                                                <i class="bx bx-printer"></i>
                                                Imprimer roadsheet
                                            </button>
                                            <button
                                                type="button"
                                                class="action-menu-item mail"
                                                :disabled="
                                                    sendingAction ===
                                                    `roadSheet-${planning.id}`
                                                "
                                                @click="
                                                    sendPlanningDocument(
                                                        planning,
                                                        'roadSheet',
                                                    )
                                                "
                                            >
                                                <span
                                                    v-if="
                                                        sendingAction ===
                                                        `roadSheet-${planning.id}`
                                                    "
                                                    class="spinner-border spinner-border-sm"
                                                ></span>
                                                <i v-else class="bx bx-send"></i>
                                                Email roadsheet
                                            </button>
                                            <button
                                                type="button"
                                                class="action-menu-item delete"
                                                @click="
                                                    closeActionMenu();
                                                    $emit(
                                                        'destroy-planning',
                                                        planning.id,
                                                    );
                                                "
                                            >
                                                <i class="bx bx-trash"></i>
                                                Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="visibleRows.length === 0">
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

.planning-order-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 18px;
    border-bottom: 1px solid #eef2f7;
    background: linear-gradient(135deg, #ffffff, #fff7f8);
    border-radius: 24px 24px 0 0;
}

.planning-order-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #111827;
    font-size: 15px;
    font-weight: 950;
}

.planning-order-title i {
    color: #c1121f;
    font-size: 20px;
}

.planning-order-help {
    margin: 3px 0 0;
    color: #738096;
    font-size: 13px;
    font-weight: 700;
}

.planning-order-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.order-mode-btn,
.order-save-btn {
    min-height: 40px;
    border-radius: 12px;
    font-weight: 900;
    padding-inline: 14px;
}

.order-mode-btn.active {
    color: #fff;
    background: #111827;
    border-color: #111827;
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

.planning-display-row.manual-order-row {
    cursor: grab;
}

.planning-display-row.manual-order-row:active {
    cursor: grabbing;
}

.planning-display-row.dragging-row td {
    opacity: 0.45;
    background: #f8fafc !important;
}

.planning-display-row.drag-over-row td {
    background: #fff1f2 !important;
    box-shadow: inset 0 2px 0 #c1121f;
}

.row-drag-handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    margin-right: 8px;
    border-radius: 10px;
    background: #fff1f2;
    color: #c1121f;
    vertical-align: middle;
}

.row-drag-handle i {
    font-size: 18px;
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

.client-tag-supplier {
    background: linear-gradient(135deg, #f8fafc, #eef2f7);
    border-color: #cbd5e1;
    color: #334155;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
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
    min-width: 92px;
    width: 92px;
    overflow: visible;
}

.header-filter-cell {
    position: relative;
    overflow: visible;
}

.header-filter-wrap {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    position: relative;
}

.column-filter-btn {
    width: 24px;
    height: 24px;
    border: 1px solid rgba(161, 16, 30, 0.22);
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: #a1101e;
    font-size: 15px;
    transition:
        background 0.16s ease,
        color 0.16s ease,
        border-color 0.16s ease;
}

.column-filter-btn:hover,
.column-filter-btn.active {
    background: #c1121f;
    border-color: #c1121f;
    color: #fff;
}

.column-filter-menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 320px;
    padding: 14px;
    border: 1px solid rgba(15, 23, 42, 0.16);
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
    z-index: 200;
    text-transform: none;
    letter-spacing: 0;
}

.column-filter-title {
    color: #111827;
    font-size: 15px;
    font-weight: 950;
    margin-bottom: 10px;
}

.column-sort-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 12px;
}

.column-sort-btn {
    min-height: 40px;
    border: 1px solid #d5dbe6;
    border-radius: 10px;
    background: #f8fafc;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    font-size: 13px;
    font-weight: 900;
}

.column-sort-btn span {
    color: #2563eb;
    font-weight: 950;
}

.column-sort-btn:hover,
.column-sort-btn.active {
    border-color: #c1121f;
    background: #fff1f2;
    color: #9f101d;
}

.column-search-box {
    height: 42px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    margin-bottom: 10px;
}

.column-search-box:focus-within {
    border-color: #16a34a;
    box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.1);
}

.column-search-box i {
    color: #64748b;
    font-size: 18px;
}

.column-search-box input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #111827;
    font-size: 14px;
    font-weight: 750;
}

.column-check-list {
    max-height: 240px;
    overflow-y: auto;
    padding: 6px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}

.column-check-row {
    min-height: 34px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 8px;
    margin: 0;
    border-radius: 9px;
    color: #1f2937;
    font-size: 13px;
    font-weight: 850;
    cursor: pointer;
}

.column-check-row:hover {
    background: #fff;
}

.column-check-row.all {
    color: #111827;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 9px 9px 0 0;
    margin-bottom: 4px;
}

.column-check-row input {
    width: 17px;
    height: 17px;
    accent-color: #16a34a;
    flex: 0 0 auto;
}

.column-check-row span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.column-empty-state {
    padding: 16px 8px;
    color: #94a3b8;
    font-size: 13px;
    font-weight: 800;
    text-align: center;
}

.column-filter-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.column-apply-btn,
.column-clear-btn {
    flex: 1;
    min-height: 38px;
    border: 0;
    border-radius: 11px;
    font-weight: 900;
}

.column-apply-btn {
    background: #c1121f;
    color: #fff;
}

.column-clear-btn {
    background: #eef2f7;
    color: #475569;
}

.row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.planning-actions-menu {
    position: relative;
    display: flex;
    justify-content: center;
    overflow: visible;
}

.btn-action-menu-toggle {
    width: 44px;
    height: 44px;
    padding: 0;
    border: 1px solid rgba(161, 16, 30, 0.14);
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ffffff, #fff1f2);
    color: #9f101d;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
    transition:
        transform 0.16s ease,
        box-shadow 0.16s ease,
        background 0.16s ease;
}

.btn-action-menu-toggle:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #c1121f, #ef4444);
    color: #ffffff;
    box-shadow: 0 18px 34px rgba(193, 18, 31, 0.22);
}

.btn-action-menu-toggle i {
    font-size: 25px;
    line-height: 1;
}

.planning-actions-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    z-index: 300;
    width: 238px;
    padding: 8px;
    border: 1px solid rgba(15, 23, 42, 0.1);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.24);
}

.planning-actions-dropdown::before {
    content: "";
    position: absolute;
    top: -7px;
    right: 16px;
    width: 14px;
    height: 14px;
    transform: rotate(45deg);
    border-left: 1px solid rgba(15, 23, 42, 0.08);
    border-top: 1px solid rgba(15, 23, 42, 0.08);
    background: #ffffff;
}

.action-menu-item {
    width: 100%;
    min-height: 42px;
    padding: 9px 11px;
    border: 0;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: transparent;
    color: #182033;
    font-size: 14px;
    font-weight: 900;
    text-decoration: none;
    text-align: left;
    transition:
        background 0.14s ease,
        color 0.14s ease,
        transform 0.14s ease;
}

.action-menu-item:hover {
    transform: translateX(2px);
    color: #111827;
    background: #f8fafc;
}

.action-menu-item i,
.action-menu-item .spinner-border {
    width: 26px;
    height: 26px;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    font-size: 16px;
}

.action-menu-item.edit i {
    background: #fff7ed;
    color: #d97706;
}

.action-menu-item.doc i {
    background: #fff1f2;
    color: #be123c;
}

.action-menu-item.pdf i {
    background: #eff6ff;
    color: #2563eb;
}

.action-menu-item.mail i,
.action-menu-item.mail .spinner-border {
    background: #f5f3ff;
    color: #7c3aed;
}

.action-menu-item.road i {
    background: #ecfdf5;
    color: #0f766e;
}

.action-menu-item.delete {
    color: #dc2626;
}

.action-menu-item.delete i {
    background: #fef2f2;
    color: #dc2626;
}

.action-menu-item:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
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

.btn-doc-action,
.btn-pdf-action,
.btn-mail-action {
    color: #fff;
    border: 0;
    border-radius: 10px;
    font-weight: 850;
    padding: 8px 10px;
}

.btn-doc-action {
    background: linear-gradient(135deg, #7f1d1d, #dc2626);
}

.btn-pdf-action {
    background: linear-gradient(135deg, #1d4ed8, #2563eb);
}

.btn-mail-action {
    background: linear-gradient(135deg, #7c3aed, #a855f7);
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-road-sheet-action {
    background: linear-gradient(135deg, #0f766e, #115e59);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 800;
    padding: 10px 14px;
}

.btn-delete-action:hover,
.btn-save-action:hover,
.btn-edit-action:hover,
.btn-doc-action:hover,
.btn-pdf-action:hover,
.btn-mail-action:hover,
.btn-road-sheet-action:hover {
    color: #fff;
}
</style>
