<script setup>
import { computed, reactive, ref, nextTick } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import AppShell from "@/Layouts/AppShell.vue";
import Swal from "sweetalert2";

import FlashMessages from "./Partials/FlashMessages.vue";
import PlanningHero from "./Partials/PlanningHero.vue";
import PlanningToolbox from "./Partials/PlanningToolbox.vue";
import PlanningTable from "./Partials/PlanningTable.vue";
import ClientsModal from "./Partials/ClientsModal.vue";
import ClientModal from "./Partials/ClientModal.vue";
import SupplierModal from "./Partials/SupplierModal.vue";
import DriverModal from "./Partials/DriverModal.vue";
import GuideModal from "./Partials/GuideModal.vue";
import ServiceModal from "./Partials/ServiceModal.vue";
import "./Partials/planning.css";

defineOptions({ layout: AppShell });

const toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3200,
    timerProgressBar: true,
});

const showSuccess = (message = "Operation completed successfully.") => {
    toast.fire({ icon: "success", title: message });
};

const showError = (message = "An error occurred. Please try again.") => {
    Swal.fire({
        icon: "error",
        title: "Error",
        text: message,
        confirmButtonText: "OK",
        confirmButtonColor: "#c1121f",
    });
};

const showWarning = (message) => {
    Swal.fire({
        icon: "warning",
        title: "Warning",
        text: message,
        confirmButtonText: "OK",
        confirmButtonColor: "#c1121f",
    });
};

const props = defineProps({
    plannings: {
        type: Object,
        default: () => ({ data: [], links: [], current_page: 1, last_page: 1 }),
    },

    supplierVehicules: { type: Array, default: () => [] },
    supplierClients: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },

    filters: { type: Object, default: () => ({}) },
});

const showNewRow = ref(false);
const editingId = ref(null);
const loadingSave = ref(false);
const loadingUpdate = ref(false);

const savingSupplierVehicule = ref(false);
const savingDriver = ref(false);
const savingGuide = ref(false);
const savingService = ref(false);
const savingClient = ref(false);

const clientsModalTitle = ref("");
const selectedPlanningClients = ref([]);

const importForm = useForm({
    file: null,
    import_year: new Date().getFullYear(), // default current year
});

const selectedFileName = ref("");

const query = reactive({
    date_du: props.filters?.date_du || "",
    date_au: props.filters?.date_au || "",
    supplier_vehicule_id: props.filters?.supplier_vehicule_id || "",
    driver_id: props.filters?.driver_id || "",
    guide_id: props.filters?.guide_id || "",
    service_id: props.filters?.service_id || "",
    destination_id: props.filters?.destination_id || "",
    vehicule_id: props.filters?.vehicule_id || "",
    search: props.filters?.search || "",
});

const emptyPlanning = () => ({
    date_du: "",
    date_au: "",
    ref_dossier: "",
    nbr_personnes: "",
    flight: "",
    heure: "",
    point_depart: "",
    site: "",

    service_id: "",
    supplier_client_id: "",
    supplier_vehicule_id: "",
    driver_id: "",
    guide_id: "",
    destination_id: "",
    vehicule_id: "",

    budget: "",
    supplier_price: "",
    notes: "",
    client_ids: [],
});

const emptySearchInputs = () => ({
    supplierVehicule: "",
    supplierClient: "",
    driver: "",
    guide: "",
    service: "",
    client: "",
    destination: "",
    vehicule: "",
});

const newPlanning = reactive(emptyPlanning());
const editPlanning = reactive(emptyPlanning());

const searchInputs = reactive(emptySearchInputs());
const editSearchInputs = reactive(emptySearchInputs());

const errors = reactive({});
const editErrors = reactive({});

const modalSupplierVehicule = reactive({
    name: "",
    phone: "",
    email: "",
    address: "",
    notes: "",
});

const modalDriver = reactive({
    name: "",
    phone: "",
    email: "",
    status: "Available",
    notes: "",
});

const modalGuide = reactive({
    name: "",
    phone: "",
    email: "",
    status: "Available",
    notes: "",
});

const modalService = reactive({
    designation: "",
    type_service: "",
});

const modalClient = reactive({
    full_name: "",
    supplier_client_id: "",
    phone: "",
    email: "",
    notes: "",
});

const localSupplierVehicules = computed(() => props.supplierVehicules || []);
const localSupplierClients = computed(() => props.supplierClients || []);
const localDrivers = computed(() => props.drivers || []);
const localGuides = computed(() => props.guides || []);
const localServices = computed(() => props.services || []);
const localClients = computed(() => props.clients || []);
const localDestinations = computed(() => props.destinations || []);
const localVehicules = computed(() => props.vehicules || []);

const paginatedRows = computed(() => {
    return (props.plannings?.data || []).map((planning) => {
        const supplierVehicule =
            planning.supplier_vehicule || planning.supplierVehicule || null;

        const planningClients =
            planning.planning_clients || planning.planningClients || [];

        return {
            ...planning,

            destination:
                planning.destination?.name || planning.destination || "-",

            vehicule:
                planning.vehicule?.matricule ||
                planning.vehicule?.name ||
                planning.bus ||
                "-",

            bus:
                planning.vehicule?.matricule ||
                planning.vehicule?.name ||
                planning.bus ||
                "-",

            supplier_client:
                planning.supplier_client || planning.supplierClient || null,

            supplierVehicule: supplierVehicule,
            supplier_vehicule: supplierVehicule,

            driver: planning.driver || null,
            guide: planning.guide || null,
            service: planning.service || null,

            planning_clients: planningClients,
            planningClients: planningClients,
        };
    });
});

const currentMonthRange = () => {
    const now = new Date();

    return {
        first: new Date(now.getFullYear(), now.getMonth(), 1)
            .toISOString()
            .slice(0, 10),
        last: new Date(now.getFullYear(), now.getMonth() + 1, 0)
            .toISOString()
            .slice(0, 10),
    };
};

const formatDateOnly = (value) => {
    if (!value) return "-";

    try {
        return new Date(value).toLocaleDateString("en-GB");
    } catch (e) {
        return String(value).split("T")[0] || "-";
    }
};

const cleanDate = (value) => {
    if (!value) return "";
    return String(value).split("T")[0].slice(0, 10);
};

const cleanTime = (value) => {
    if (!value) return "";
    return String(value).slice(0, 5);
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

const syncSupplierVehiculeId = () => {
    newPlanning.supplier_vehicule_id =
        getByName(
            localSupplierVehicules.value,
            "name",
            searchInputs.supplierVehicule,
        )?.id || "";
};

const syncDriverId = () => {
    newPlanning.driver_id =
        getByName(localDrivers.value, "name", searchInputs.driver)?.id || "";
};

const syncGuideId = () => {
    newPlanning.guide_id =
        getByName(localGuides.value, "name", searchInputs.guide)?.id || "";
};

const syncServiceId = () => {
    newPlanning.service_id =
        getByName(localServices.value, "designation", searchInputs.service)
            ?.id || "";
};

const syncDestinationId = () => {
    newPlanning.destination_id =
        getByName(localDestinations.value, "name", searchInputs.destination)
            ?.id || "";
};

const syncVehiculeId = () => {
    newPlanning.vehicule_id =
        getByName(localVehicules.value, "matricule", searchInputs.vehicule)
            ?.id || "";
};

const syncEditSupplierVehiculeId = () => {
    editPlanning.supplier_vehicule_id =
        getByName(
            localSupplierVehicules.value,
            "name",
            editSearchInputs.supplierVehicule,
        )?.id || "";
};

const syncEditDriverId = () => {
    editPlanning.driver_id =
        getByName(localDrivers.value, "name", editSearchInputs.driver)?.id ||
        "";
};

const syncEditGuideId = () => {
    editPlanning.guide_id =
        getByName(localGuides.value, "name", editSearchInputs.guide)?.id || "";
};

const syncEditServiceId = () => {
    editPlanning.service_id =
        getByName(localServices.value, "designation", editSearchInputs.service)
            ?.id || "";
};

const syncEditDestinationId = () => {
    editPlanning.destination_id =
        getByName(localDestinations.value, "name", editSearchInputs.destination)
            ?.id || "";
};

const syncEditVehiculeId = () => {
    editPlanning.vehicule_id =
        getByName(localVehicules.value, "matricule", editSearchInputs.vehicule)
            ?.id || "";
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

const addEditClientFromSearch = () => {
    const found = getByName(
        localClients.value,
        "full_name",
        editSearchInputs.client,
    );

    if (!found) return;

    if (!editPlanning.client_ids.includes(found.id)) {
        editPlanning.client_ids.push(found.id);
    }

    editSearchInputs.client = "";
};

const removeClient = (id) => {
    newPlanning.client_ids = newPlanning.client_ids.filter(
        (item) => item !== id,
    );
};

const removeEditClient = (id) => {
    editPlanning.client_ids = editPlanning.client_ids.filter(
        (item) => item !== id,
    );
};

const selectedClientsObjects = computed(() => {
    return localClients.value.filter((client) =>
        newPlanning.client_ids.includes(client.id),
    );
});

const selectedEditClientsObjects = computed(() => {
    return localClients.value.filter((client) =>
        editPlanning.client_ids.includes(client.id),
    );
});

const clearErrors = () => {
    Object.keys(errors).forEach((key) => (errors[key] = ""));
};

const clearEditErrors = () => {
    Object.keys(editErrors).forEach((key) => (editErrors[key] = ""));
};

const resetPlanningForm = () => {
    Object.assign(newPlanning, emptyPlanning());
    Object.assign(searchInputs, emptySearchInputs());
};

const resetEditPlanningForm = () => {
    Object.assign(editPlanning, emptyPlanning());
    Object.assign(editSearchInputs, emptySearchInputs());
};

const openNewRow = () => {
    cancelEditRow();
    resetPlanningForm();
    showNewRow.value = true;
    clearErrors();
};

const cancelNewRow = () => {
    showNewRow.value = false;
    resetPlanningForm();
    clearErrors();
};

const openEditRow = (planning) => {
    cancelNewRow();

    editingId.value = planning.id;

    Object.assign(editPlanning, {
        date_du: cleanDate(planning.date_du),
        date_au: cleanDate(planning.date_au),
        ref_dossier: planning.ref_dossier || "",
        nbr_personnes: planning.nbr_personnes || "",
        flight: planning.flight || "",
        heure: cleanTime(planning.heure),
        point_depart: planning.point_depart || "",
        site: planning.site || "",

        service_id: planning.service_id || planning.service?.id || "",
        supplier_client_id:
            planning.supplier_client_id ||
            planning.supplier_client?.id ||
            planning.supplierClient?.id ||
            "",
        supplier_vehicule_id:
            planning.supplier_vehicule_id ||
            planning.supplierVehicule?.id ||
            planning.supplier_vehicule?.id ||
            "",
        driver_id: planning.driver_id || planning.driver?.id || "",
        guide_id: planning.guide_id || planning.guide?.id || "",
        destination_id:
            planning.destination_id || planning.destination?.id || "",
        vehicule_id: planning.vehicule_id || planning.vehicule?.id || "",

        budget: planning.budget || "",
        supplier_price: planning.supplier_price || "",
        notes: planning.notes || "",
        client_ids: (
            planning.planning_clients ||
            planning.planningClients ||
            []
        )
            .map((item) => item.client_id || item.client?.id)
            .filter(Boolean),
    });

    Object.assign(editSearchInputs, {
        supplierVehicule:
            planning.supplierVehicule?.name ||
            planning.supplier_vehicule?.name ||
            "",
        supplierClient:
            planning.supplier_client?.name ||
            planning.supplierClient?.name ||
            "",
        driver: planning.driver?.name || "",
        guide: planning.guide?.name || "",
        service: planning.service?.designation || "",
        client: "",
        destination: planning.destination?.name || "",
        vehicule: planning.vehicule?.matricule || "",
    });

    clearEditErrors();
};

const cancelEditRow = () => {
    editingId.value = null;
    resetEditPlanningForm();
    clearEditErrors();
};

const validatePlanning = () => {
    clearErrors();

    let valid = true;

    syncSupplierVehiculeId();
    syncDriverId();
    syncGuideId();
    syncServiceId();
    syncDestinationId();
    syncVehiculeId();

    if (!newPlanning.date_du) {
        errors.date_du = "The FROM field is required.";
        valid = false;
    }

    if (!newPlanning.ref_dossier) {
        errors.ref_dossier = "The file reference is required.";
        valid = false;
    }

    return valid;
};

const validateEditPlanning = () => {
    clearEditErrors();

    let valid = true;

    syncEditSupplierVehiculeId();
    syncEditDriverId();
    syncEditGuideId();
    syncEditServiceId();
    syncEditDestinationId();
    syncEditVehiculeId();

    if (!editPlanning.date_du) {
        editErrors.date_du = "The FROM field is required.";
        valid = false;
    }

    if (!editPlanning.ref_dossier) {
        editErrors.ref_dossier = "The file reference is required.";
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
                showSuccess("Planning added successfully.");
            },
            onError: (backendErrors) => {
                Object.assign(errors, backendErrors);
                showError("Please check the planning fields.");
            },
            onFinish: () => (loadingSave.value = false),
        },
    );
};

const updatePlanning = () => {
    if (!editingId.value || !validateEditPlanning()) return;

    loadingUpdate.value = true;

    router.put(
        `/plannings/${editingId.value}`,
        { ...editPlanning },
        {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditRow();
                showSuccess("Planning updated successfully.");
            },
            onError: (backendErrors) => {
                Object.assign(editErrors, backendErrors);
                showError("Please check the planning fields.");
            },
            onFinish: () => (loadingUpdate.value = false),
        },
    );
};

const destroyPlanning = async (id) => {
    const result = await Swal.fire({
        icon: "warning",
        title: "Delete this planning?",
        text: "This action cannot be undone.",
        showCancelButton: true,
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#6b7280",
    });

    if (!result.isConfirmed) return;

    router.delete(`/plannings/${id}`, {
        preserveScroll: true,
        onSuccess: () => showSuccess("Planning deleted successfully."),
        onError: () => showError("Unable to delete this planning."),
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

    Object.assign(query, {
        date_du: range.first,
        date_au: range.last,
        supplier_vehicule_id: "",
        driver_id: "",
        guide_id: "",
        service_id: "",
        destination_id: "",
        vehicule_id: "",
        search: "",
    });

    applyServerFilters();
};

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
        showWarning("Please select an Excel file first.");
        return;
    }

    importForm.post("/plannings/import-excel", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearImportInput();
            showSuccess("Excel import completed successfully.");
        },
        onError: () => showError("Unable to import this Excel file."),
    });
};

const openModal = (id) => {
    const el = document.getElementById(id);

    if (!el || !window.bootstrap) return;

    new window.bootstrap.Modal(el).show();
};

const closeModal = (id) => {
    const el = document.getElementById(id);

    if (!el || !window.bootstrap) return;

    const instance =
        window.bootstrap.Modal.getInstance(el) ||
        new window.bootstrap.Modal(el);

    instance.hide();
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

const resetSupplierVehiculeModal = () => {
    Object.assign(modalSupplierVehicule, {
        name: "",
        phone: "",
        email: "",
        address: "",
        notes: "",
    });
};

const saveSupplierVehicule = () => {
    if (!modalSupplierVehicule.name) return;

    savingSupplierVehicule.value = true;

    router.post(
        "/supplier-vehicules",
        { ...modalSupplierVehicule },
        {
            preserveScroll: true,
            onSuccess: async () => {
                resetSupplierVehiculeModal();
                closeModal("supplierModal");
                reloadPlanningDependencies(["supplierVehicules"]);
                await nextTick();
                showSuccess("Vehicle supplier added successfully.");
            },
            onError: () => showError("Unable to add this vehicle supplier."),
            onFinish: () => (savingSupplierVehicule.value = false),
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
                Object.assign(modalDriver, {
                    name: "",
                    phone: "",
                    email: "",
                    status: "Available",
                    notes: "",
                });

                closeModal("driverModal");
                reloadPlanningDependencies(["drivers"]);
                showSuccess("Driver added successfully.");
            },
            onError: () => showError("Unable to add this driver."),
            onFinish: () => (savingDriver.value = false),
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
                Object.assign(modalGuide, {
                    name: "",
                    phone: "",
                    email: "",
                    status: "Available",
                    notes: "",
                });

                closeModal("guideModal");
                reloadPlanningDependencies(["guides"]);
                showSuccess("Guide added successfully.");
            },
            onError: () => showError("Unable to add this guide."),
            onFinish: () => (savingGuide.value = false),
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
                Object.assign(modalService, {
                    designation: "",
                    type_service: "",
                });

                closeModal("serviceModal");
                reloadPlanningDependencies(["services"]);
                showSuccess("Service added successfully.");
            },
            onError: () => showError("Unable to add this service."),
            onFinish: () => (savingService.value = false),
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
                Object.assign(modalClient, {
                    full_name: "",
                    supplier_client_id: "",
                    phone: "",
                    email: "",
                    notes: "",
                });

                closeModal("clientModal");
                reloadPlanningDependencies(["clients"]);
                showSuccess("Client added successfully.");
            },
            onError: () => showError("Unable to add this client."),
            onFinish: () => (savingClient.value = false),
        },
    );
};
</script>

<template>
    <Head title="Planning Management" />

    <div class="page-content">
        <div class="container-fluid">
            <FlashMessages />

            <PlanningHero :total="plannings?.total || 0" :query="query" />

            <PlanningToolbox
                :query="query"
                :supplier-vehicules="localSupplierVehicules"
                :drivers="localDrivers"
                :services="localServices"
                :destinations="localDestinations"
                :vehicules="localVehicules"
                :selected-file-name="selectedFileName"
                :import-processing="importForm.processing"
                :has-import-file="!!importForm.file"
                @open-new-row="openNewRow"
                @handle-import-file="handleImportFile"
                @submit-import="submitImport"
                @clear-import-input="clearImportInput"
                @apply-server-filters="applyServerFilters"
                @reset-filters="resetFilters"
                @open-import-modal="openModal('importExcelModal')"
            />

            <PlanningTable
                :plannings="plannings"
                :rows="paginatedRows"
                :show-new-row="showNewRow"
                :editing-id="editingId"
                :new-planning="newPlanning"
                :edit-planning="editPlanning"
                :search-inputs="searchInputs"
                :edit-search-inputs="editSearchInputs"
                :errors="errors"
                :edit-errors="editErrors"
                :loading-save="loadingSave"
                :loading-update="loadingUpdate"
                :supplier-vehicules="localSupplierVehicules"
                :supplier-clients="localSupplierClients"
                :drivers="localDrivers"
                :guides="localGuides"
                :services="localServices"
                :clients="localClients"
                :destinations="localDestinations"
                :vehicules="localVehicules"
                :selected-clients-objects="selectedClientsObjects"
                :selected-edit-clients-objects="selectedEditClientsObjects"
                :format-date-only="formatDateOnly"
                @sync-supplier-vehicule-id="syncSupplierVehiculeId"
                @sync-driver-id="syncDriverId"
                @sync-guide-id="syncGuideId"
                @sync-service-id="syncServiceId"
                @sync-destination-id="syncDestinationId"
                @sync-vehicule-id="syncVehiculeId"
                @sync-edit-supplier-vehicule-id="syncEditSupplierVehiculeId"
                @sync-edit-driver-id="syncEditDriverId"
                @sync-edit-guide-id="syncEditGuideId"
                @sync-edit-service-id="syncEditServiceId"
                @sync-edit-destination-id="syncEditDestinationId"
                @sync-edit-vehicule-id="syncEditVehiculeId"
                @add-client-from-search="addClientFromSearch"
                @add-edit-client-from-search="addEditClientFromSearch"
                @remove-client="removeClient"
                @remove-edit-client="removeEditClient"
                @open-modal="openModal"
                @save-planning="savePlanning"
                @update-planning="updatePlanning"
                @cancel-new-row="cancelNewRow"
                @open-edit-row="openEditRow"
                @cancel-edit-row="cancelEditRow"
                @open-clients-modal="openClientsModal"
                @destroy-planning="destroyPlanning"
            />

            <ClientsModal
                :title="clientsModalTitle"
                :clients="selectedPlanningClients"
            />

            <ClientModal
                :form="modalClient"
                :saving="savingClient"
                :supplier-clients="localSupplierClients"
                @save="saveClient"
            />

            <SupplierModal
                :form="modalSupplierVehicule"
                :saving="savingSupplierVehicule"
                @save="saveSupplierVehicule"
            />

            <DriverModal
                :form="modalDriver"
                :saving="savingDriver"
                @save="saveDriver"
            />

            <GuideModal
                :form="modalGuide"
                :saving="savingGuide"
                @save="saveGuide"
            />

            <ServiceModal
                :form="modalService"
                :saving="savingService"
                @save="saveService"
            />
        </div>
    </div>

    <div
        class="modal fade"
        id="importExcelModal"
        tabindex="-1"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Import Excel File</h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>
                </div>

                <div class="modal-body">
                    <label class="form-label fw-bold mb-2"> Choose file </label>

                    <input
                        id="planningImportInput"
                        type="file"
                        accept=".xlsx,.xls"
                        class="form-control"
                        @change="handleImportFile"
                    />

                    <div v-if="selectedFileName" class="mt-3 small text-muted">
                        File:
                        <strong>{{ selectedFileName }}</strong>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-bold mb-2">
                            Import Year
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            v-model="importForm.import_year"
                            placeholder="Example: 2025"
                        />
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button
                        type="button"
                        class="btn btn-light fw-bold"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger fw-bold"
                        :disabled="importForm.processing || !importForm.file"
                        @click="submitImport"
                    >
                        <span
                            v-if="importForm.processing"
                            class="spinner-border spinner-border-sm me-1"
                        ></span>
                        Import
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
