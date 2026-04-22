<script setup>
import { computed, reactive, ref, nextTick } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    plannings: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
        }),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    supplierTypes: {
        type: Array,
        default: () => [],
    },
    drivers: {
        type: Array,
        default: () => [],
    },
    guides: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Array,
        default: () => [],
    },
    clients: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const showNewRow = ref(false);
const loadingSave = ref(false);
const savingSupplier = ref(false);
const savingDriver = ref(false);
const savingGuide = ref(false);
const savingService = ref(false);
const savingClient = ref(false);

const clientsModalTitle = ref("");
const selectedPlanningClients = ref([]);

const importForm = useForm({
    file: null,
});

const selectedFileName = ref("");

const handleImportFile = (e) => {
    const file = e.target.files?.[0] || null;
    importForm.file = file;
    selectedFileName.value = file ? file.name : "";
};

const clearImportInput = () => {
    importForm.reset();
    selectedFileName.value = "";
    const input = document.getElementById("planningImportInput");
    if (input) input.value = null;
};

const submitImport = () => {
    if (!importForm.file) {
        alert("Choisir un fichier Excel d'abord.");
        return;
    }

    importForm.post("/plannings/import-excel", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearImportInput();
        },
    });
};

const query = reactive({
    date_du: props.filters?.date_du || "",
    date_au: props.filters?.date_au || "",
    supplier_id: props.filters?.supplier_id || "",
    driver_id: props.filters?.driver_id || "",
    guide_id: props.filters?.guide_id || "",
    service_id: props.filters?.service_id || "",
    search: props.filters?.search || "",
});

const newPlanning = reactive({
    date_du: "",
    date_au: "",
    ref_dossier: "",
    bus: "",
    nbr_personnes: "",
    flight: "",
    heure: "",
    point_depart: "",
    destination: "",
    service_id: "",
    supplier_id: "",
    driver_id: "",
    guide_id: "",
    budget: "",
    supplier_price: "",
    notes: "",
    client_ids: [],
});

const searchInputs = reactive({
    supplier: "",
    driver: "",
    guide: "",
    service: "",
    client: "",
    supplierType: "",
});

const errors = reactive({});

const modalSupplier = reactive({
    name: "",
    type_supplier_id: "",
    type_supplier: "",
    type: "",
    phone: "",
    email: "",
    address: "",
    notes: "",
});

const modalDriver = reactive({
    name: "",
    phone: "",
    email: "",
    status: "Disponible",
    notes: "",
});

const modalGuide = reactive({
    name: "",
    phone: "",
    email: "",
    status: "Disponible",
    notes: "",
});

const modalService = reactive({
    designation: "",
    type_service: "",
});

const modalClient = reactive({
    full_name: "",
    phone: "",
    email: "",
    notes: "",
});

const localSuppliers = ref([...props.suppliers]);
const localSupplierTypes = ref([...props.supplierTypes]);
const localDrivers = ref([...props.drivers]);
const localGuides = ref([...props.guides]);
const localServices = ref([...props.services]);
const localClients = ref([...props.clients]);

const paginatedRows = computed(() => props.plannings?.data || []);

const currentMonthRange = () => {
    const now = new Date();
    const first = new Date(now.getFullYear(), now.getMonth(), 1)
        .toISOString()
        .slice(0, 10);
    const last = new Date(now.getFullYear(), now.getMonth() + 1, 0)
        .toISOString()
        .slice(0, 10);

    return { first, last };
};

const formatDateOnly = (value) => {
    if (!value) return "-";

    try {
        return new Date(value).toLocaleDateString("fr-FR");
    } catch (e) {
        return String(value).split("T")[0] || "-";
    }
};

const getByName = (list, labelKey, value) => {
    if (!value) return null;

    const term = String(value).trim().toLowerCase();

    return (
        list.find(
            (item) =>
                String(item[labelKey] || "")
                    .trim()
                    .toLowerCase() === term,
        ) || null
    );
};

const getSupplierTypeByLabel = (value) => {
    if (!value) return null;

    const term = String(value).trim().toLowerCase();

    return (
        localSupplierTypes.value.find(
            (item) =>
                String(item.designation || "")
                    .trim()
                    .toLowerCase() === term,
        ) || null
    );
};

const syncSupplierId = () => {
    const found = getByName(
        localSuppliers.value,
        "name",
        searchInputs.supplier,
    );
    newPlanning.supplier_id = found ? found.id : "";
};

const syncDriverId = () => {
    const found = getByName(localDrivers.value, "name", searchInputs.driver);
    newPlanning.driver_id = found ? found.id : "";
};

const syncGuideId = () => {
    const found = getByName(localGuides.value, "name", searchInputs.guide);
    newPlanning.guide_id = found ? found.id : "";
};

const syncServiceId = () => {
    const found = getByName(
        localServices.value,
        "designation",
        searchInputs.service,
    );
    newPlanning.service_id = found ? found.id : "";
};

const syncSupplierType = () => {
    const found = getSupplierTypeByLabel(searchInputs.supplierType);

    if (found) {
        modalSupplier.type_supplier_id = found.id;
        modalSupplier.type_supplier = found.designation;
        modalSupplier.type = found.designation;
    } else {
        modalSupplier.type_supplier_id = "";
        modalSupplier.type_supplier = "";
        modalSupplier.type = "";
    }
};

const addClientFromSearch = () => {
    const found = getByName(
        localClients.value,
        "full_name",
        searchInputs.client,
    );

    if (!found) return;

    if (!newPlanning.client_ids.includes(found.id)) {
        newPlanning.client_ids.push(found.id);
    }

    searchInputs.client = "";
};

const removeClient = (id) => {
    newPlanning.client_ids = newPlanning.client_ids.filter(
        (item) => item !== id,
    );
};

const selectedClientsObjects = computed(() => {
    return localClients.value.filter((client) =>
        newPlanning.client_ids.includes(client.id),
    );
});

const openNewRow = () => {
    resetPlanningForm();
    showNewRow.value = true;
    clearErrors();
};

const cancelNewRow = () => {
    showNewRow.value = false;
    resetPlanningForm();
    clearErrors();
};

const resetPlanningForm = () => {
    newPlanning.date_du = "";
    newPlanning.date_au = "";
    newPlanning.ref_dossier = "";
    newPlanning.bus = "";
    newPlanning.nbr_personnes = "";
    newPlanning.flight = "";
    newPlanning.heure = "";
    newPlanning.point_depart = "";
    newPlanning.destination = "";
    newPlanning.service_id = "";
    newPlanning.supplier_id = "";
    newPlanning.driver_id = "";
    newPlanning.guide_id = "";
    newPlanning.budget = "";
    newPlanning.supplier_price = "";
    newPlanning.notes = "";
    newPlanning.client_ids = [];

    searchInputs.supplier = "";
    searchInputs.driver = "";
    searchInputs.guide = "";
    searchInputs.service = "";
    searchInputs.client = "";
};

const resetSupplierModal = () => {
    modalSupplier.name = "";
    modalSupplier.type_supplier_id = "";
    modalSupplier.type_supplier = "";
    modalSupplier.type = "";
    modalSupplier.phone = "";
    modalSupplier.email = "";
    modalSupplier.address = "";
    modalSupplier.notes = "";
    searchInputs.supplierType = "";
};

const clearErrors = () => {
    Object.keys(errors).forEach((key) => {
        errors[key] = "";
    });
};

const validatePlanning = () => {
    clearErrors();
    let valid = true;

    syncSupplierId();
    syncDriverId();
    syncGuideId();
    syncServiceId();

    if (!newPlanning.date_du) {
        errors.date_du = "Le champ DU est obligatoire.";
        valid = false;
    }

    if (!newPlanning.ref_dossier) {
        errors.ref_dossier = "La référence dossier est obligatoire.";
        valid = false;
    }

    if (!newPlanning.destination) {
        errors.destination = "La destination est obligatoire.";
        valid = false;
    }

    return valid;
};

const savePlanning = () => {
    if (!validatePlanning()) return;

    loadingSave.value = true;

    router.post(
        "/plannings",
        { ...newPlanning },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelNewRow();
            },
            onError: (backendErrors) => {
                Object.assign(errors, backendErrors);
            },
            onFinish: () => {
                loadingSave.value = false;
            },
        },
    );
};

const destroyPlanning = (id) => {
    if (!confirm("Voulez-vous vraiment supprimer ce planning ?")) return;

    router.delete(`/plannings/${id}`, {
        preserveScroll: true,
    });
};

const applyServerFilters = () => {
    router.get(
        "/plannings",
        { ...query },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    const range = currentMonthRange();

    query.date_du = range.first;
    query.date_au = range.last;
    query.supplier_id = "";
    query.driver_id = "";
    query.guide_id = "";
    query.service_id = "";
    query.search = "";

    applyServerFilters();
};

const openModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;

    if (window.bootstrap) {
        const modal = new window.bootstrap.Modal(el);
        modal.show();
    }
};

const closeModal = (id) => {
    const el = document.getElementById(id);
    if (!el) return;

    if (window.bootstrap) {
        const instance =
            window.bootstrap.Modal.getInstance(el) ||
            new window.bootstrap.Modal(el);
        instance.hide();
    }
};

const reloadPlanningDependencies = (only = []) => {
    router.reload({
        only,
        preserveScroll: true,
        preserveState: true,
    });
};

const openClientsModal = (planning) => {
    clientsModalTitle.value = planning?.ref_dossier || "Planning";
    selectedPlanningClients.value =
        planning?.planning_clients
            ?.map((item) => item.client?.full_name)
            .filter(Boolean) ||
        planning?.planningClients
            ?.map((item) => item.client?.full_name)
            .filter(Boolean) ||
        [];

    openModal("clientsModal");
};

const saveSupplier = () => {
    if (!modalSupplier.name) return;

    syncSupplierType();
    savingSupplier.value = true;

    router.post(
        "/suppliers",
        {
            name: modalSupplier.name,
            type_supplier_id: modalSupplier.type_supplier_id || null,
            type_supplier: modalSupplier.type_supplier || null,
            type: modalSupplier.type || null,
            phone: modalSupplier.phone,
            email: modalSupplier.email,
            address: modalSupplier.address,
            notes: modalSupplier.notes,
        },
        {
            preserveScroll: true,
            onSuccess: async () => {
                resetSupplierModal();
                closeModal("supplierModal");

                reloadPlanningDependencies(["suppliers", "supplierTypes"]);

                await nextTick();
            },
            onFinish: () => {
                savingSupplier.value = false;
            },
        },
    );
};

const saveDriver = () => {
    if (!modalDriver.name) return;

    savingDriver.value = true;

    router.post(
        "/drivers",
        { ...modalDriver },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalDriver.name = "";
                modalDriver.phone = "";
                modalDriver.email = "";
                modalDriver.status = "Disponible";
                modalDriver.notes = "";

                closeModal("driverModal");
                reloadPlanningDependencies(["drivers"]);
            },
            onFinish: () => {
                savingDriver.value = false;
            },
        },
    );
};

const saveGuide = () => {
    if (!modalGuide.name) return;

    savingGuide.value = true;

    router.post(
        "/guides",
        { ...modalGuide },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalGuide.name = "";
                modalGuide.phone = "";
                modalGuide.email = "";
                modalGuide.status = "Disponible";
                modalGuide.notes = "";

                closeModal("guideModal");
                reloadPlanningDependencies(["guides"]);
            },
            onFinish: () => {
                savingGuide.value = false;
            },
        },
    );
};

const saveService = () => {
    if (!modalService.designation) return;

    savingService.value = true;

    router.post(
        "/services",
        { ...modalService },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalService.designation = "";
                modalService.type_service = "";

                closeModal("serviceModal");
                reloadPlanningDependencies(["services"]);
            },
            onFinish: () => {
                savingService.value = false;
            },
        },
    );
};

const saveClient = () => {
    if (!modalClient.full_name) return;

    savingClient.value = true;

    router.post(
        "/clients",
        { ...modalClient },
        {
            preserveScroll: true,
            onSuccess: () => {
                modalClient.full_name = "";
                modalClient.phone = "";
                modalClient.email = "";
                modalClient.notes = "";

                closeModal("clientModal");
                reloadPlanningDependencies(["clients"]);
            },
            onFinish: () => {
                savingClient.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Gestion des plannings" />

    <div class="page-content">
        <div class="container-fluid">
            <div
                v-if="$page.props.flash?.error"
                class="alert alert-danger rounded-4 shadow-sm mb-4"
            >
                <i class="bx bx-error-circle me-1"></i>
                {{ $page.props.flash.error }}
            </div>

            <div
                v-if="$page.props.flash?.success"
                class="alert alert-success rounded-4 shadow-sm mb-4"
            >
                <i class="bx bx-check-circle me-1"></i>
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="
                    $page.props.flash?.import_errors &&
                    $page.props.flash.import_errors.length
                "
                class="alert alert-warning rounded-4 shadow-sm mb-4"
            >
                <div class="fw-bold mb-2">
                    Détails des lignes ignorées / en erreur :
                </div>
                <ul class="mb-0 ps-3">
                    <li
                        v-for="(item, index) in $page.props.flash.import_errors"
                        :key="index"
                    >
                        {{ item }}
                    </li>
                </ul>
            </div>

            <div
                class="planning-hero card border-0 shadow-lg mb-4 overflow-hidden"
            >
                <div class="hero-glow"></div>
                <div class="card-body p-4 p-lg-5 position-relative">
                    <div
                        class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3"
                    >
                        <div>
                            <!-- <div class="hero-chip mb-3">
                                <i class="bx bx-command"></i>
                                Pilotage planning
                            </div> -->

                            <h1 class="planning-title mb-2">
                                Gestion des plannings
                            </h1>
                            <!-- <p class="planning-subtitle mb-0">
                                Organisez vos trajets, chauffeurs, guides, clients et fournisseurs dans une interface premium.
                            </p> -->
                        </div>

                        <div class="hero-stats-box">
                            <div class="planning-badge">
                                <i class="bx bx-calendar-check me-1"></i>
                                {{ plannings?.total || 0 }} résultat(s)
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="toolbox-card card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="top-tools-grid mb-4">
                        <button
                            type="button"
                            class="btn btn-danger-red main-cta"
                            @click="openNewRow"
                        >
                            <i class="bx bx-plus-circle me-2"></i>
                            Nouveau Planning
                        </button>

                        <div class="import-box">
                            <label
                                class="btn btn-outline-danger import-label-main mb-0"
                            >
                                <i class="bx bx-upload me-2"></i>
                                {{ selectedFileName || "Choisir Excel" }}
                                <input
                                    id="planningImportInput"
                                    type="file"
                                    accept=".xlsx,.xls"
                                    class="d-none"
                                    @change="handleImportFile"
                                />
                            </label>

                            <button
                                type="button"
                                class="btn btn-danger-red import-btn"
                                @click="submitImport"
                                :disabled="
                                    importForm.processing || !importForm.file
                                "
                            >
                                <span
                                    v-if="importForm.processing"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                Import
                            </button>

                            <button
                                type="button"
                                class="btn btn-soft-secondary clear-btn"
                                @click="clearImportInput"
                            >
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-6 col-md-3 col-xl-2">
                            <label
                                class="form-label fw-semibold small text-muted"
                                >DU</label
                            >
                            <input
                                v-model="query.date_du"
                                type="date"
                                class="form-control form-control-modern"
                            />
                        </div>

                        <div class="col-6 col-md-3 col-xl-2">
                            <label
                                class="form-label fw-semibold small text-muted"
                                >AU</label
                            >
                            <input
                                v-model="query.date_au"
                                type="date"
                                class="form-control form-control-modern"
                            />
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <label
                                class="form-label fw-semibold small text-muted"
                                >Supplier</label
                            >
                            <select
                                v-model="query.supplier_id"
                                class="form-select form-control-modern"
                            >
                                <option value="">Sélectionner...</option>
                                <option
                                    v-for="item in localSuppliers"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <label
                                class="form-label fw-semibold small text-muted"
                                >Driver</label
                            >
                            <select
                                v-model="query.driver_id"
                                class="form-select form-control-modern"
                            >
                                <option value="">Sélectionner...</option>
                                <option
                                    v-for="item in localDrivers"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <label
                                class="form-label fw-semibold small text-muted"
                                >Service</label
                            >
                            <select
                                v-model="query.service_id"
                                class="form-select form-control-modern"
                            >
                                <option value="">Sélectionner...</option>
                                <option
                                    v-for="item in localServices"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.designation }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-xl-2">
                            <label
                                class="form-label fw-semibold small text-muted"
                                >Recherche</label
                            >
                            <input
                                v-model="query.search"
                                type="text"
                                class="form-control form-control-modern"
                                placeholder="Réf, client, destination..."
                            />
                        </div>

                        <div class="col-6 col-xl-1">
                            <button
                                type="button"
                                class="btn btn-outline-dark w-100 rounded-3 py-3"
                                @click="applyServerFilters"
                            >
                                <i class="bx bx-filter-alt"></i>
                            </button>
                        </div>

                        <div class="col-6 col-xl-1">
                            <button
                                type="button"
                                class="btn btn-soft-secondary w-100 rounded-3 py-3"
                                @click="resetFilters"
                            >
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="planning-table-card card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table align-middle table-hover custom-planning-table mb-0"
                        >
                            <thead>
                                <tr>
                                    <th>DU</th>
                                    <th>AU</th>
                                    <th>Réf</th>
                                    <th>Bus</th>
                                    <th>Pers</th>
                                    <th>Flight</th>
                                    <th>Heure</th>
                                    <th>Départ</th>
                                    <th>Destination</th>
                                    <th>Supplier</th>
                                    <th>Driver</th>
                                    <th>Guide</th>
                                    <th>Service</th>
                                    <th>Clients</th>
                                    <th>Budget</th>
                                    <th>Prix Four.</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="showNewRow" class="planning-new-row">
                                    <td>
                                        <input
                                            v-model="newPlanning.date_du"
                                            type="date"
                                            class="form-control form-control-sm table-input"
                                        />
                                        <small class="text-danger">{{
                                            errors.date_du
                                        }}</small>
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.date_au"
                                            type="date"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.ref_dossier"
                                            type="text"
                                            class="form-control form-control-sm table-input"
                                            placeholder="P001"
                                        />
                                        <small class="text-danger">{{
                                            errors.ref_dossier
                                        }}</small>
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.bus"
                                            type="text"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.nbr_personnes"
                                            type="number"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.flight"
                                            type="text"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.heure"
                                            type="time"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.point_depart"
                                            type="text"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.destination"
                                            type="text"
                                            class="form-control form-control-sm table-input"
                                        />
                                        <small class="text-danger">{{
                                            errors.destination
                                        }}</small>
                                    </td>

                                    <td>
                                        <div class="search-select-box">
                                            <input
                                                v-model="searchInputs.supplier"
                                                list="suppliers-list"
                                                class="form-control form-control-sm table-input"
                                                placeholder="Chercher supplier..."
                                                @change="syncSupplierId"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light border plus-btn"
                                                @click="
                                                    openModal('supplierModal')
                                                "
                                            >
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="search-select-box">
                                            <input
                                                v-model="searchInputs.driver"
                                                list="drivers-list"
                                                class="form-control form-control-sm table-input"
                                                placeholder="Chercher driver..."
                                                @change="syncDriverId"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light border plus-btn"
                                                @click="
                                                    openModal('driverModal')
                                                "
                                            >
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="search-select-box">
                                            <input
                                                v-model="searchInputs.guide"
                                                list="guides-list"
                                                class="form-control form-control-sm table-input"
                                                placeholder="Chercher guide..."
                                                @change="syncGuideId"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light border plus-btn"
                                                @click="openModal('guideModal')"
                                            >
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="search-select-box">
                                            <input
                                                v-model="searchInputs.service"
                                                list="services-list"
                                                class="form-control form-control-sm table-input"
                                                placeholder="Chercher service..."
                                                @change="syncServiceId"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-light border plus-btn"
                                                @click="
                                                    openModal('serviceModal')
                                                "
                                            >
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td class="clients-cell-new">
                                        <div class="clients-picker-box">
                                            <div class="search-select-box mb-2">
                                                <input
                                                    v-model="
                                                        searchInputs.client
                                                    "
                                                    list="clients-list"
                                                    class="form-control form-control-sm table-input"
                                                    placeholder="Ajouter client..."
                                                    @change="
                                                        addClientFromSearch
                                                    "
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light border plus-btn"
                                                    @click="
                                                        openModal('clientModal')
                                                    "
                                                >
                                                    <i class="bx bx-plus"></i>
                                                </button>
                                            </div>

                                            <div
                                                v-if="
                                                    selectedClientsObjects.length
                                                "
                                                class="client-tags"
                                            >
                                                <span
                                                    v-for="client in selectedClientsObjects"
                                                    :key="client.id"
                                                    class="client-tag client-tag-selected"
                                                >
                                                    {{ client.full_name }}
                                                    <button
                                                        type="button"
                                                        class="tag-remove"
                                                        @click="
                                                            removeClient(
                                                                client.id,
                                                            )
                                                        "
                                                    >
                                                        ×
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.budget"
                                            type="number"
                                            step="0.01"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="newPlanning.supplier_price"
                                            type="number"
                                            step="0.01"
                                            class="form-control form-control-sm table-input"
                                        />
                                    </td>

                                    <td style="min-width: 190px">
                                        <div class="d-flex gap-2 flex-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-primary btn-sm fw-semibold"
                                                @click="savePlanning"
                                                :disabled="loadingSave"
                                            >
                                                <span
                                                    v-if="loadingSave"
                                                    class="spinner-border spinner-border-sm me-1"
                                                ></span>
                                                Enregistrer
                                            </button>
                                            <button
                                                type="button"
                                                class="btn btn-cancel btn-sm fw-semibold"
                                                @click="cancelNewRow"
                                            >
                                                Annuler
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-for="planning in paginatedRows"
                                    :key="planning.id"
                                >
                                    <td>
                                        {{ formatDateOnly(planning.date_du) }}
                                    </td>
                                    <td>
                                        {{ formatDateOnly(planning.date_au) }}
                                    </td>
                                    <td>
                                        <span class="ref-badge">
                                            {{ planning.ref_dossier || "-" }}
                                        </span>
                                    </td>
                                    <td>{{ planning.bus || "-" }}</td>
                                    <td>{{ planning.nbr_personnes || "-" }}</td>
                                    <td>{{ planning.flight || "-" }}</td>
                                    <td>{{ planning.heure || "-" }}</td>
                                    <td>{{ planning.point_depart || "-" }}</td>
                                    <td>{{ planning.destination || "-" }}</td>
                                    <td>
                                        {{ planning?.supplier?.name || "-" }}
                                    </td>
                                    <td>{{ planning?.driver?.name || "-" }}</td>
                                    <td>{{ planning?.guide?.name || "-" }}</td>
                                    <td>
                                        {{
                                            planning?.service?.designation ||
                                            "-"
                                        }}
                                    </td>

                                    <td class="clients-inline-cell">
                                        <div
                                            v-if="
                                                planning?.planning_clients
                                                    ?.length ||
                                                planning?.planningClients
                                                    ?.length
                                            "
                                            class="client-tags"
                                        >
                                            <span
                                                v-for="clientRel in (
                                                    planning.planning_clients ||
                                                    planning.planningClients ||
                                                    []
                                                ).slice(0, 3)"
                                                :key="clientRel.id"
                                                class="client-tag"
                                            >
                                                {{
                                                    clientRel?.client
                                                        ?.full_name || "-"
                                                }}
                                            </span>

                                            <button
                                                v-if="
                                                    (
                                                        planning.planning_clients ||
                                                        planning.planningClients ||
                                                        []
                                                    ).length > 3
                                                "
                                                type="button"
                                                class="btn btn-link p-0 small fw-bold text-danger"
                                                @click="
                                                    openClientsModal(planning)
                                                "
                                            >
                                                Voir +
                                            </button>
                                        </div>

                                        <span v-else class="text-muted">-</span>
                                    </td>

                                    <td>{{ planning.budget || "-" }}</td>
                                    <td>
                                        {{ planning.supplier_price || "-" }}
                                    </td>
                                    <td class="actions-cell">
                                        <div class="row-actions">
                                            <!-- <button
                                                type="button"
                                                class="btn btn-client-action btn-sm"
                                                @click="openClientsModal(planning)"
                                            >
                                                <i class="bx bx-user me-1"></i>
                                                Clients
                                            </button> -->

                                            <button
                                                type="button"
                                                class="btn btn-edit-action btn-sm"
                                                @click="
                                                    $inertia.visit(
                                                        `/plannings/${planning.id}/edit`,
                                                    )
                                                "
                                            >
                                                <i
                                                    class="bx bx-edit-alt me-1"
                                                ></i>
                                                Éditer
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-delete-action btn-sm"
                                                @click="
                                                    destroyPlanning(planning.id)
                                                "
                                            >
                                                <i class="bx bx-trash me-1"></i>
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="paginatedRows.length === 0">
                                    <td
                                        colspan="17"
                                        class="text-center py-5 text-muted"
                                    >
                                        Aucun planning trouvé.
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

            <!-- datalists -->
            <datalist id="suppliers-list">
                <option
                    v-for="item in localSuppliers"
                    :key="item.id"
                    :value="item.name"
                />
            </datalist>

            <datalist id="drivers-list">
                <option
                    v-for="item in localDrivers"
                    :key="item.id"
                    :value="item.name"
                />
            </datalist>

            <datalist id="guides-list">
                <option
                    v-for="item in localGuides"
                    :key="item.id"
                    :value="item.name"
                />
            </datalist>

            <datalist id="services-list">
                <option
                    v-for="item in localServices"
                    :key="item.id"
                    :value="item.designation"
                />
            </datalist>

            <datalist id="clients-list">
                <option
                    v-for="item in localClients"
                    :key="item.id"
                    :value="item.full_name"
                />
            </datalist>

            <datalist id="supplier-types-list">
                <option
                    v-for="item in localSupplierTypes"
                    :key="item.id"
                    :value="item.designation"
                />
            </datalist>

            <!-- clients modal -->
            <div
                class="modal fade"
                id="clientsModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">
                                Clients du planning {{ clientsModalTitle }}
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div v-if="selectedPlanningClients.length">
                                <ul class="list-group list-group-flush">
                                    <li
                                        v-for="(
                                            client, index
                                        ) in selectedPlanningClients"
                                        :key="index"
                                        class="list-group-item px-0"
                                    >
                                        <i
                                            class="bx bx-user-circle me-2 text-primary"
                                        ></i>
                                        {{ client }}
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="text-muted text-center py-3">
                                Aucun client lié à ce planning.
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- client modal -->
            <div
                class="modal fade"
                id="clientModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">
                                Ajouter un client
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label"
                                        >Nom complet</label
                                    >
                                    <input
                                        v-model="modalClient.full_name"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input
                                        v-model="modalClient.phone"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input
                                        v-model="modalClient.email"
                                        type="email"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea
                                        v-model="modalClient.notes"
                                        rows="3"
                                        class="form-control form-control-modern"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger-red rounded-3"
                                @click="saveClient"
                                :disabled="savingClient"
                            >
                                <span
                                    v-if="savingClient"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- supplier modal -->
            <div
                class="modal fade"
                id="supplierModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">
                                Ajouter un supplier
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nom</label>
                                    <input
                                        v-model="modalSupplier.name"
                                        type="text"
                                        class="form-control form-control-modern"
                                        placeholder="Nom du supplier"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label"
                                        >Type supplier</label
                                    >
                                    <div class="search-select-box">
                                        <input
                                            v-model="searchInputs.supplierType"
                                            list="supplier-types-list"
                                            class="form-control form-control-modern"
                                            placeholder="Chercher type supplier..."
                                            @change="syncSupplierType"
                                        />
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input
                                        v-model="modalSupplier.phone"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input
                                        v-model="modalSupplier.email"
                                        type="email"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Adresse</label>
                                    <input
                                        v-model="modalSupplier.address"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">Notes</label>
                                    <textarea
                                        v-model="modalSupplier.notes"
                                        rows="3"
                                        class="form-control form-control-modern"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger-red rounded-3"
                                @click="saveSupplier"
                                :disabled="savingSupplier"
                            >
                                <span
                                    v-if="savingSupplier"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- driver modal -->
            <div
                class="modal fade"
                id="driverModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">
                                Ajouter un driver
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nom</label>
                                    <input
                                        v-model="modalDriver.name"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input
                                        v-model="modalDriver.phone"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input
                                        v-model="modalDriver.email"
                                        type="email"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Status</label>
                                    <select
                                        v-model="modalDriver.status"
                                        class="form-select form-control-modern"
                                    >
                                        <option value="Disponible">
                                            Disponible
                                        </option>
                                        <option value="Occupé">Occupé</option>
                                        <option value="Indisponible">
                                            Indisponible
                                        </option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea
                                        v-model="modalDriver.notes"
                                        rows="3"
                                        class="form-control form-control-modern"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger-red rounded-3"
                                @click="saveDriver"
                                :disabled="savingDriver"
                            >
                                <span
                                    v-if="savingDriver"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- guide modal -->
            <div
                class="modal fade"
                id="guideModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">
                                Ajouter un guide
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nom</label>
                                    <input
                                        v-model="modalGuide.name"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input
                                        v-model="modalGuide.phone"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input
                                        v-model="modalGuide.email"
                                        type="email"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Status</label>
                                    <select
                                        v-model="modalGuide.status"
                                        class="form-select form-control-modern"
                                    >
                                        <option value="Disponible">
                                            Disponible
                                        </option>
                                        <option value="Occupé">Occupé</option>
                                        <option value="Indisponible">
                                            Indisponible
                                        </option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea
                                        v-model="modalGuide.notes"
                                        rows="3"
                                        class="form-control form-control-modern"
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger-red rounded-3"
                                @click="saveGuide"
                                :disabled="savingGuide"
                            >
                                <span
                                    v-if="savingGuide"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- service modal -->
            <div
                class="modal fade"
                id="serviceModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">
                                Ajouter un service
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label"
                                        >Désignation</label
                                    >
                                    <input
                                        v-model="modalService.designation"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>

                                <div class="col-12">
                                    <label class="form-label"
                                        >Type service</label
                                    >
                                    <input
                                        v-model="modalService.type_service"
                                        type="text"
                                        class="form-control form-control-modern"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3"
                                data-bs-dismiss="modal"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                class="btn btn-danger-red rounded-3"
                                @click="saveService"
                                :disabled="savingService"
                            >
                                <span
                                    v-if="savingService"
                                    class="spinner-border spinner-border-sm me-1"
                                ></span>
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background:
        radial-gradient(
            circle at top left,
            rgba(193, 18, 31, 0.06),
            transparent 18%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(29, 78, 216, 0.06),
            transparent 18%
        ),
        #f4f6fb;
    min-height: 100vh;
}

.planning-hero {
    position: relative;
    background:
        radial-gradient(
            circle at 85% 15%,
            rgba(255, 255, 255, 0.24),
            transparent 18%
        ),
        linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    border-radius: 28px;
}

.hero-glow {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.03),
        rgba(255, 255, 255, 0.01)
    );
    pointer-events: none;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 700;
    font-size: 0.9rem;
}

.planning-title {
    color: #fff;
    font-size: 2.15rem;
    font-weight: 900;
    letter-spacing: 0.3px;
}

.planning-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.98rem;
    max-width: 740px;
}

.hero-stats-box {
    display: flex;
    align-items: center;
}

.planning-badge {
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 999px;
    padding: 12px 18px;
    font-weight: 800;
}

.toolbox-card,
.planning-table-card {
    border-radius: 24px;
    overflow: hidden;
}

.top-tools-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 16px;
}

.main-cta {
    min-height: 48px;
    border-radius: 16px;
    font-weight: 800;
    font-size: 1rem;
}

.import-box {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    gap: 10px;
    align-items: center;
}

.import-label-main {
    min-height: 58px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-weight: 700;
}

.import-btn,
.clear-btn {
    min-height: 58px;
    border-radius: 16px;
    font-weight: 700;
}

.btn-danger-red {
    background: linear-gradient(135deg, #d11a2a 0%, #a20e19 100%);
    color: #fff;
    border: 0;
    box-shadow: 0 12px 24px rgba(193, 18, 31, 0.22);
}

.btn-danger-red:hover {
    color: #fff;
    background: linear-gradient(135deg, #b91422 0%, #8f0a14 100%);
}

.btn-soft-secondary {
    background: #eef1f7;
    border: 1px solid #dfe4ee;
    color: #5d6574;
}

.form-control-modern,
.form-select.form-control-modern {
    border-radius: 14px;
    border: 1px solid #dfe3ec;
    min-height: 50px;
    box-shadow: none;
    background: #fff;
}

.form-control-modern:focus,
.form-select.form-control-modern:focus,
.table-input:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 0.18rem rgba(193, 18, 31, 0.12);
}

.custom-planning-table thead th {
    background: linear-gradient(180deg, #fff7f8 0%, #fff1f2 100%);
    color: #92111b;
    font-size: 0.85rem;
    font-weight: 900;
    border-bottom: 1px solid #f0d7da;
    white-space: nowrap;
    padding: 16px 14px;
}

.custom-planning-table tbody td {
    padding: 14px;
    vertical-align: top;
    border-color: #edf0f5;
    color: #2f3747;
    background: #fff;
}

.custom-planning-table tbody tr:hover td {
    background: #fffafb;
}

.planning-new-row td {
    background: linear-gradient(180deg, #fff8f9 0%, #fffdfd 100%) !important;
    border-bottom: 1px solid #f0d7da;
}

.table-input {
    min-width: 120px;
    border-radius: 12px;
    border: 1px solid #dee3ed;
    box-shadow: none;
}

.search-select-box {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 6px;
    align-items: center;
}

.plus-btn {
    border-radius: 10px;
    min-width: 34px;
}

.clients-cell-new {
    min-width: 250px;
}

.clients-picker-box {
    min-width: 240px;
}

.client-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.client-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(29, 78, 216, 0.08);
    color: #1d4ed8;
    border: 1px solid rgba(29, 78, 216, 0.12);
    border-radius: 999px;
    padding: 5px 10px;
    font-size: 0.78rem;
    font-weight: 700;
    max-width: 180px;
}

.client-tag-selected {
    background: rgba(193, 18, 31, 0.08);
    color: #b91422;
    border: 1px solid rgba(193, 18, 31, 0.14);
}

.tag-remove {
    background: transparent;
    border: 0;
    color: inherit;
    font-weight: 900;
    line-height: 1;
    padding: 0;
}

.ref-badge {
    display: inline-block;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(17, 24, 39, 0.06);
    color: #111827;
    font-weight: 900;
    font-size: 0.82rem;
}

.clients-inline-cell {
    min-width: 220px;
}

.actions-cell {
    min-width: 290px;
}

.row-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    align-items: center;
}

.row-actions .btn {
    white-space: nowrap;
    border-radius: 10px;
    font-weight: 700;
}

.btn-client-action {
    background: linear-gradient(135deg, #1d72f3 0%, #0d5bd7 100%);
    color: #fff;
    border: 0;
}

.btn-client-action:hover {
    color: #fff;
}

.btn-edit-action {
    background: linear-gradient(135deg, #ff8a00 0%, #ff6b00 100%);
    color: #fff;
    border: 0;
}

.btn-edit-action:hover {
    color: #fff;
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border: 0;
}

.btn-delete-action:hover {
    color: #fff;
}

.btn-cancel {
    background: #f3f4f6;
    border: 1px solid #e4e7ec;
    color: #4b5563;
    border-radius: 10px;
}

.modal-content {
    overflow: hidden;
    border-radius: 22px;
}

.modal-header .modal-title {
    color: #1f2937;
}

@media (max-width: 1199.98px) {
    .top-tools-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 991.98px) {
    .planning-title {
        font-size: 1.55rem;
    }

    .import-box {
        grid-template-columns: 1fr;
    }

    .row-actions {
        flex-wrap: wrap;
    }
}
</style>
