<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    drivers: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    allDrivers: {
        type: Array,
        default: () => [],
    },
    vehicules: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
        }),
    },
});

const page = usePage();

const can = (permission) =>
    page.props?.auth?.isSuperAdmin || !!page.props?.auth?.can?.[permission];

const showPermissionDenied = () => {
    Swal.fire({
        icon: "warning",
        title: "Accès refusé",
        text: "Vous n’avez pas la permission d’effectuer cette action.",
        confirmButtonColor: "#c1121f",
    });
};

const form = reactive({
    search: props.filters?.search || "",
});

const selectedIds = ref([]);
const showReplaceModal = ref(false);
const replacementDriverId = ref("");
const showOperationsModal = ref(false);
const operationsDriver = ref(null);
const operationBusy = ref(null);

const today = new Date().toISOString().slice(0, 10);

const vehicleForm = reactive({
    vehicule_id: "",
    assigned_date: today,
    released_date: today,
    notes: "",
});

const fuelCardForm = reactive({
    card_number: "",
    label: "",
    initial_balance: "",
    status: "active",
    notes: "",
});

const fuelMovementForm = reactive({
    card_id: "",
    type: "recharge",
    amount: "",
    transaction_date: today,
    reference: "",
    notes: "",
});

const selectedRows = computed(() => {
    return (props.drivers.data || []).filter((driver) =>
        selectedIds.value.includes(driver.id),
    );
});

const selectedReplacementDriver = computed(() => {
    return props.allDrivers.find((driver) => driver.id == replacementDriverId.value);
});

const driverFuelCards = computed(() => operationsDriver.value?.fuel_cards || []);

const activeFuelCards = computed(() =>
    driverFuelCards.value.filter((card) => card.status === "active"),
);

let searchTimeout = null;

const syncOperationsDriver = () => {
    if (!operationsDriver.value) return;

    const freshDriver = (props.drivers.data || []).find(
        (driver) => Number(driver.id) === Number(operationsDriver.value.id),
    );

    if (!freshDriver) return;

    operationsDriver.value = freshDriver;
    fuelMovementForm.card_id = activeFuelCards.value[0]?.id || "";
};

const reloadDriversForOperations = () => {
    router.reload({
        only: ["drivers", "vehicules"],
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => syncOperationsDriver(),
    });
};

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: flash.success,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
        }

        if (flash?.error) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: flash.error,
                confirmButtonColor: "#c1121f",
            });
        }
    },
    { immediate: true },
);

watch(
    () => form.search,
    (value) => {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            router.get(
                "/drivers",
                { search: value },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 400);
    },
);

watch(
    () => props.drivers.data,
    () => {
        if (showOperationsModal.value) {
            syncOperationsDriver();
        }
    },
    { deep: true },
);

const openReplaceModal = () => {
    if (!can("drivers.manage")) {
        showPermissionDenied();
        return;
    }

    if (!selectedIds.value.length) {
        Swal.fire({
            icon: "warning",
            title: "No rows selected",
            text: "Please select at least one driver.",
            confirmButtonColor: "#c1121f",
        });

        return;
    }

    showReplaceModal.value = true;
};

const submitReplace = () => {
    if (!replacementDriverId.value) {
        Swal.fire({
            icon: "warning",
            title: "Driver required",
            text: "Please select the correct driver.",
            confirmButtonColor: "#c1121f",
        });

        return;
    }

    const selectedWithoutReplacement = selectedIds.value.filter(
        (id) => Number(id) !== Number(replacementDriverId.value),
    );

    if (!selectedWithoutReplacement.length) {
        Swal.fire({
            icon: "warning",
            title: "Nothing to replace",
            text: "The replacement driver cannot be the only selected row.",
            confirmButtonColor: "#c1121f",
        });

        return;
    }

    showReplaceModal.value = false;

    setTimeout(() => {
        Swal.fire({
            icon: "warning",
            title: "Confirm driver replacement?",
            html: `
                <div style="text-align:left; line-height:1.7">
                    <p><strong>${selectedWithoutReplacement.length}</strong> selected driver(s) will be merged.</p>
                    <p>Their plannings and driver fuel invoices will be moved to the correct driver, then the duplicate driver rows will be deleted.</p>
                    <p>Correct driver: <strong>${selectedReplacementDriver.value?.name || "-"}</strong></p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Yes, replace now",
            cancelButtonText: "Cancel",
            confirmButtonColor: "#c1121f",
            cancelButtonColor: "#64748b",
            width: 720,
        }).then((result) => {
            if (!result.isConfirmed) {
                showReplaceModal.value = true;
                return;
            }

            router.post(
                route("drivers.replace-selected"),
                {
                    selected_ids: selectedIds.value,
                    replacement_driver_id: replacementDriverId.value,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        selectedIds.value = [];
                        replacementDriverId.value = "";
                        showReplaceModal.value = false;
                    },
                    onError: () => {
                        showReplaceModal.value = true;
                    },
                },
            );
        });
    }, 180);
};

const openOperationsModal = (driver) => {
    if (!can("drivers.manage")) {
        showPermissionDenied();
        return;
    }

    operationsDriver.value = driver;
    showOperationsModal.value = true;
    vehicleForm.vehicule_id = "";
    vehicleForm.assigned_date = today;
    vehicleForm.released_date = today;
    vehicleForm.notes = "";
    fuelCardForm.card_number = "";
    fuelCardForm.label = "";
    fuelCardForm.initial_balance = "";
    fuelCardForm.status = "active";
    fuelCardForm.notes = "";
    fuelMovementForm.card_id = activeFuelCards.value[0]?.id || "";
    fuelMovementForm.type = "recharge";
    fuelMovementForm.amount = "";
    fuelMovementForm.transaction_date = today;
    fuelMovementForm.reference = "";
    fuelMovementForm.notes = "";
};

const closeOperationsModal = () => {
    showOperationsModal.value = false;
    operationsDriver.value = null;
};

const firstErrorMessage = (errors, fallback) => {
    const value = Object.values(errors || {})[0];

    if (Array.isArray(value)) {
        return value[0] || fallback;
    }

    return value || fallback;
};

const showOperationError = (errors, fallback) => {
    Swal.fire({
        icon: "error",
        title: "Gestion driver",
        text: firstErrorMessage(errors, fallback),
        confirmButtonColor: "#c1121f",
    });
};

const submitVehicleAssignment = () => {
    if (!operationsDriver.value || operationBusy.value) return;

    if (!vehicleForm.vehicule_id) {
        showOperationError({}, "Choisis d'abord une voiture.");
        return;
    }

    operationBusy.value = "vehicle";

    router.post(
        `/drivers/${operationsDriver.value.id}/vehicle-assignments`,
        {
            vehicule_id: vehicleForm.vehicule_id,
            assigned_date: vehicleForm.assigned_date,
            notes: vehicleForm.notes,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                vehicleForm.vehicule_id = "";
                vehicleForm.notes = "";
                reloadDriversForOperations();
            },
            onError: (errors) =>
                showOperationError(errors, "Impossible d'affecter ce véhicule."),
            onFinish: () => {
                operationBusy.value = null;
            },
        },
    );
};

const releaseVehicle = () => {
    if (!operationsDriver.value?.current_vehicle_assignment || operationBusy.value) return;

    operationBusy.value = "release";

    router.patch(
        `/drivers/${operationsDriver.value.id}/vehicle-assignments/release`,
        { released_date: vehicleForm.released_date },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => reloadDriversForOperations(),
            onError: (errors) =>
                showOperationError(errors, "Impossible de libérer ce véhicule."),
            onFinish: () => {
                operationBusy.value = null;
            },
        },
    );
};

const submitFuelCard = () => {
    if (!operationsDriver.value || operationBusy.value) return;

    if (!String(fuelCardForm.card_number || "").trim()) {
        showOperationError({}, "Saisis le numéro de carte gasoil.");
        return;
    }

    operationBusy.value = "fuel-card";

    router.post(
        `/drivers/${operationsDriver.value.id}/fuel-cards`,
        { ...fuelCardForm },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                fuelCardForm.card_number = "";
                fuelCardForm.label = "";
                fuelCardForm.initial_balance = "";
                fuelCardForm.status = "active";
                fuelCardForm.notes = "";
                reloadDriversForOperations();
            },
            onError: (errors) =>
                showOperationError(errors, "Impossible d'ajouter cette carte gasoil."),
            onFinish: () => {
                operationBusy.value = null;
            },
        },
    );
};

const submitFuelMovement = () => {
    if (!operationsDriver.value || operationBusy.value) return;

    if (!fuelMovementForm.card_id) {
        showOperationError({}, "Choisis d'abord une carte gasoil.");
        return;
    }

    if (!fuelMovementForm.amount || Number(fuelMovementForm.amount) <= 0) {
        showOperationError({}, "Saisis un montant supérieur à 0.");
        return;
    }

    operationBusy.value = "fuel-movement";

    router.post(
        `/drivers/${operationsDriver.value.id}/fuel-cards/${fuelMovementForm.card_id}/transactions`,
        { ...fuelMovementForm },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                fuelMovementForm.amount = "";
                fuelMovementForm.reference = "";
                fuelMovementForm.notes = "";
                reloadDriversForOperations();
            },
            onError: (errors) => {
                showOperationError(
                    errors,
                    "Impossible d'enregistrer ce mouvement.",
                );
            },
            onFinish: () => {
                operationBusy.value = null;
            },
        },
    );
};

const toggleFuelCardStatus = (card) => {
    if (!operationsDriver.value || operationBusy.value) return;

    operationBusy.value = `fuel-status-${card.id}`;

    router.patch(
        `/drivers/${operationsDriver.value.id}/fuel-cards/${card.id}/status`,
        { status: card.status === "active" ? "inactive" : "active" },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => reloadDriversForOperations(),
            onError: (errors) =>
                showOperationError(errors, "Impossible de modifier cette carte."),
            onFinish: () => {
                operationBusy.value = null;
            },
        },
    );
};

const destroyDriver = (id) => {
    if (!can("drivers.delete")) {
        showPermissionDenied();
        return;
    }

    Swal.fire({
        title: "Delete this driver?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/drivers/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Driver deleted successfully",
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Could not delete this driver.",
                        confirmButtonColor: "#c1121f",
                    });
                },
            });
        }
    });
};

const getInitials = (name) => {
    if (!name) return "DR";

    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
};

const getStatusClass = (status) => {
    const value = String(status || "").toLowerCase();

    if (
        value.includes("actif") ||
        value.includes("active") ||
        value.includes("disponible") ||
        value.includes("available")
    ) {
        return "status-success";
    }

    if (
        value.includes("inactive") ||
        value.includes("inactif") ||
        value.includes("indisponible") ||
        value.includes("unavailable")
    ) {
        return "status-danger";
    }

    if (
        value.includes("pause") ||
        value.includes("attente") ||
        value.includes("pending") ||
        value.includes("busy")
    ) {
        return "status-warning";
    }

    return "status-neutral";
};

const vehicleLabel = (vehicule) => {
    if (!vehicule) return "-";

    return [vehicule.matricule, vehicule.marque, vehicule.modele]
        .filter(Boolean)
        .join(" • ");
};

const currentVehicleLabel = (driver) => {
    return vehicleLabel(driver.current_vehicle_assignment?.vehicule);
};

const fuelCardsBalance = (driver) => {
    return (driver.fuel_cards || []).reduce(
        (sum, card) => sum + Number(card.balance || 0),
        0,
    );
};

const formatMoney = (value) => {
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};

const formatDate = (value) => {
    if (!value) return "-";
    const match = String(value).match(/^\d{4}-\d{2}-\d{2}/);
    return match ? match[0] : value;
};

const driverOptionLabel = (driver) => {
    const count =
        driver.plannings_count !== undefined
            ? ` (${driver.plannings_count} plannings)`
            : "";

    return `#${driver.id} - ${driver.name}${count}`;
};
</script>
<template>
    <Head title="Drivers" />

    <div class="drivers-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-id-card"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Driver Management</h1>
                            <p class="hero-subtitle mb-0">
                                Manage, search and track all your drivers
                                easily.
                            </p>
                        </div>
                    </div>

                    <div class="hero-right">
                        <button
                            v-if="can('drivers.manage')"
                            type="button"
                            class="btn btn-replace-driver"
                            @click="openReplaceModal"
                        >
                            <i class="bx bx-transfer-alt me-2"></i>
                            Replace
                            <span
                                v-if="selectedIds.length"
                                class="selected-count"
                            >
                                {{ selectedIds.length }}
                            </span>
                        </button>

                        <Link
                            v-if="can('drivers.create')"
                            href="/drivers/create"
                            class="btn btn-add-driver"
                        >
                            <i class="bx bx-plus-circle me-2"></i>
                            New Driver
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div class="mini-stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="bx bx-user"></i>
                        </div>
                        <div>
                            <div class="stat-label">Total Drivers</div>
                            <div class="stat-value">
                                {{ drivers.total || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="toolbar-card">
                        <div class="search-wrapper">
                            <i class="bx bx-search search-leading-icon"></i>
                            <input
                                v-model="form.search"
                                type="text"
                                class="form-control search-input"
                                placeholder="Search by name, phone, email or status..."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-card">
                <div class="table-header">
                    <div>
                        <h5 class="table-title mb-1">Drivers List</h5>
                        <p class="table-subtitle mb-0">
                            Showing {{ drivers.from || 0 }} to
                            {{ drivers.to || 0 }} of
                            {{ drivers.total || 0 }} drivers
                        </p>
                    </div>
                </div>

                <div class="table-responsive custom-table-wrapper">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Driver</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Véhicule actuel</th>
                                <th>Cartes gasoil</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody v-if="drivers.data && drivers.data.length">
                            <tr
                                v-for="driver in drivers.data"
                                :key="driver.id"
                                :class="{
                                    'row-selected': selectedIds.includes(
                                        driver.id,
                                    ),
                                }"
                            >
                                <td>
                                    <input
                                        v-model="selectedIds"
                                        class="form-check-input custom-check"
                                        type="checkbox"
                                        :value="driver.id"
                                    />
                                </td>

                                <td>
                                    <div class="driver-cell">
                                        <div class="driver-avatar">
                                            {{ getInitials(driver.name) }}
                                        </div>

                                        <div>
                                            <div class="driver-name">
                                                {{ driver.name }}
                                            </div>
                                            <div class="driver-id">
                                                ID #{{ driver.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="info-badge phone-badge">
                                        <i class="bx bx-phone-call me-1"></i>
                                        {{ driver.phone || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span class="info-badge email-badge">
                                        <i class="bx bx-envelope me-1"></i>
                                        {{ driver.email || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <div class="vehicle-chip">
                                        <i class="bx bx-car"></i>
                                        <span>
                                            {{ currentVehicleLabel(driver) }}
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="fuel-summary">
                                        <span class="fuel-count">
                                            {{
                                                driver.fuel_cards?.length || 0
                                            }}
                                            carte(s)
                                        </span>
                                        <strong>
                                            {{
                                                formatMoney(
                                                    fuelCardsBalance(driver),
                                                )
                                            }}
                                            MAD
                                        </strong>
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="status-badge"
                                        :class="getStatusClass(driver.status)"
                                    >
                                        <i
                                            class="bx bx-radio-circle-marked me-1"
                                        ></i>
                                        {{ driver.status || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <div
                                        class="notes-box"
                                        :title="driver.notes || '-'"
                                    >
                                        {{ driver.notes || "-" }}
                                    </div>
                                </td>

                                <td>
                                    <div class="actions-wrapper">
                                        <button
                                            v-if="can('drivers.manage')"
                                            class="btn btn-action-manage"
                                            @click="openOperationsModal(driver)"
                                        >
                                            <i class="bx bx-cog"></i>
                                            <span>Gestion</span>
                                        </button>

                                        <Link
                                            v-if="can('drivers.edit')"
                                            :href="`/drivers/${driver.id}/edit`"
                                            class="btn btn-action-edit"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                            <span>Edit</span>
                                        </Link>

                                        <button
                                            v-if="can('drivers.delete')"
                                            class="btn btn-action-delete"
                                            @click="destroyDriver(driver.id)"
                                        >
                                            <i class="bx bx-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bx bx-search-alt"></i>
                                        </div>
                                        <h5 class="mb-2">No drivers found</h5>
                                        <p class="text-muted mb-0">
                                            Try adjusting your search or add a
                                            new driver.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="drivers.links && drivers.links.length > 3"
                    class="pagination-area"
                >
                    <div class="pagination-list">
                        <template
                            v-for="(link, index) in drivers.links"
                            :key="index"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="page-btn"
                                :class="{ active: link.active }"
                                v-html="link.label"
                                preserve-scroll
                                preserve-state
                            />
                            <span
                                v-else
                                class="page-btn disabled"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showOperationsModal" class="operations-modal-backdrop">
            <div class="operations-modal">
                <div class="operations-modal-header">
                    <div>
                        <h4 class="mb-1">
                            Gestion driver: {{ operationsDriver?.name }}
                        </h4>
                        <p class="mb-0">
                            Affectation véhicule et cartes gasoil du driver.
                        </p>
                    </div>

                    <button class="btn-close-custom" @click="closeOperationsModal">
                        <i class="bx bx-x"></i>
                    </button>
                </div>

                <div class="operations-grid">
                    <div class="operations-card">
                        <div class="operations-card-title">
                            <i class="bx bx-car"></i>
                            Véhicule actuel
                        </div>

                        <div class="current-vehicle-box">
                            <span>Actuel</span>
                            <strong>
                                {{ currentVehicleLabel(operationsDriver || {}) }}
                            </strong>
                            <small
                                v-if="
                                    operationsDriver?.current_vehicle_assignment
                                        ?.assigned_date
                                "
                            >
                                Depuis
                                {{
                                    formatDate(
                                        operationsDriver
                                            .current_vehicle_assignment
                                            .assigned_date,
                                    )
                                }}
                            </small>
                        </div>

                        <div class="operation-form">
                            <label>Nouvelle voiture</label>
                            <select
                                v-model="vehicleForm.vehicule_id"
                                class="form-select operation-input"
                            >
                                <option value="">-- Choisir véhicule --</option>
                                <option
                                    v-for="vehicule in vehicules"
                                    :key="vehicule.id"
                                    :value="vehicule.id"
                                >
                                    {{ vehicleLabel(vehicule) }}
                                </option>
                            </select>

                            <label>Date affectation</label>
                            <input
                                v-model="vehicleForm.assigned_date"
                                type="date"
                                class="form-control operation-input"
                            />

                            <label>Notes</label>
                            <textarea
                                v-model="vehicleForm.notes"
                                class="form-control operation-input"
                                rows="2"
                                placeholder="Notes affectation..."
                            ></textarea>

                            <button
                                class="btn operation-primary-btn"
                                :disabled="Boolean(operationBusy)"
                                @click="submitVehicleAssignment"
                            >
                                {{
                                    operationBusy === "vehicle"
                                        ? "Affectation..."
                                        : "Affecter véhicule"
                                }}
                            </button>
                        </div>

                        <div
                            v-if="operationsDriver?.current_vehicle_assignment"
                            class="release-box"
                        >
                            <label>Date libération</label>
                            <input
                                v-model="vehicleForm.released_date"
                                type="date"
                                class="form-control operation-input"
                            />
                            <button
                                class="btn operation-light-btn"
                                :disabled="Boolean(operationBusy)"
                                @click="releaseVehicle"
                            >
                                {{
                                    operationBusy === "release"
                                        ? "Libération..."
                                        : "Libérer véhicule actuel"
                                }}
                            </button>
                        </div>
                    </div>

                    <div class="operations-card">
                        <div class="operations-card-title">
                            <i class="bx bx-credit-card"></i>
                            Nouvelle carte gasoil
                        </div>

                        <div class="operation-form">
                            <label>N° carte</label>
                            <input
                                v-model="fuelCardForm.card_number"
                                type="text"
                                class="form-control operation-input"
                                placeholder="Ex: GAS-001"
                            />

                            <label>Libellé</label>
                            <input
                                v-model="fuelCardForm.label"
                                type="text"
                                class="form-control operation-input"
                                placeholder="Carte principale..."
                            />

                            <label>Solde initial</label>
                            <input
                                v-model="fuelCardForm.initial_balance"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control operation-input"
                                placeholder="0.00"
                            />

                            <label>Status</label>
                            <select
                                v-model="fuelCardForm.status"
                                class="form-select operation-input"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>

                            <label>Notes</label>
                            <textarea
                                v-model="fuelCardForm.notes"
                                class="form-control operation-input"
                                rows="2"
                            ></textarea>

                            <button
                                class="btn operation-primary-btn"
                                :disabled="Boolean(operationBusy)"
                                @click="submitFuelCard"
                            >
                                {{
                                    operationBusy === "fuel-card"
                                        ? "Ajout en cours..."
                                        : "Ajouter carte"
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="operations-card mt-4">
                    <div class="operations-card-title">
                        <i class="bx bx-gas-pump"></i>
                        Mouvement carte gasoil
                    </div>

                    <div class="fuel-movement-grid">
                        <div>
                            <label>Carte</label>
                            <select
                                v-model="fuelMovementForm.card_id"
                                class="form-select operation-input"
                            >
                                <option value="">-- Choisir carte --</option>
                                <option
                                    v-for="card in activeFuelCards"
                                    :key="card.id"
                                    :value="card.id"
                                >
                                    {{ card.card_number }} -
                                    {{ formatMoney(card.balance) }} MAD
                                </option>
                            </select>
                        </div>

                        <div>
                            <label>Type</label>
                            <select
                                v-model="fuelMovementForm.type"
                                class="form-select operation-input"
                            >
                                <option value="recharge">Recharge</option>
                                <option value="expense">Dépense mazot</option>
                            </select>
                        </div>

                        <div>
                            <label>Montant</label>
                            <input
                                v-model="fuelMovementForm.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="form-control operation-input"
                            />
                        </div>

                        <div>
                            <label>Date</label>
                            <input
                                v-model="fuelMovementForm.transaction_date"
                                type="date"
                                class="form-control operation-input"
                            />
                        </div>

                        <div>
                            <label>Référence</label>
                            <input
                                v-model="fuelMovementForm.reference"
                                type="text"
                                class="form-control operation-input"
                                placeholder="Bon, station..."
                            />
                        </div>

                        <div>
                            <label>Notes</label>
                            <input
                                v-model="fuelMovementForm.notes"
                                type="text"
                                class="form-control operation-input"
                            />
                        </div>
                    </div>

                    <button
                        class="btn operation-primary-btn mt-3"
                        :disabled="Boolean(operationBusy)"
                        @click="submitFuelMovement"
                    >
                        {{
                            operationBusy === "fuel-movement"
                                ? "Enregistrement..."
                                : "Enregistrer mouvement"
                        }}
                    </button>
                </div>

                <div class="operations-card mt-4">
                    <div class="operations-card-title">
                        <i class="bx bx-list-ul"></i>
                        Cartes du driver
                    </div>

                    <div v-if="driverFuelCards.length" class="fuel-card-list">
                        <div
                            v-for="card in driverFuelCards"
                            :key="card.id"
                            class="fuel-card-item"
                        >
                            <div>
                                <strong>{{ card.card_number }}</strong>
                                <span>{{ card.label || "Carte gasoil" }}</span>
                                <small>
                                    {{ card.transactions?.length || 0 }}
                                    mouvement(s) récent(s)
                                </small>
                            </div>

                            <div class="fuel-card-right">
                                <strong>{{ formatMoney(card.balance) }} MAD</strong>
                                <button
                                    class="btn fuel-status-btn"
                                    :class="card.status"
                                    :disabled="Boolean(operationBusy)"
                                    @click="toggleFuelCardStatus(card)"
                                >
                                    {{
                                        card.status === "active"
                                            ? "Active"
                                            : "Inactive"
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="fuel-empty">
                        Aucun carte gasoil enregistrée pour ce driver.
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showReplaceModal" class="replace-modal-backdrop">
            <div class="replace-modal">
                <div class="replace-modal-header">
                    <div class="modal-icon-title">
                        <div class="modal-icon">
                            <i class="bx bx-transfer-alt"></i>
                        </div>

                        <div>
                            <h4 class="mb-1">Replace selected drivers</h4>
                            <p class="mb-0">
                                Merge duplicate drivers into one correct driver.
                            </p>
                        </div>
                    </div>

                    <button
                        class="btn-close-custom"
                        @click="showReplaceModal = false"
                    >
                        <i class="bx bx-x"></i>
                    </button>
                </div>

                <div class="warning-box mb-4">
                    <div class="warning-icon">
                        <i class="bx bx-error-circle"></i>
                    </div>

                    <div>
                        <h6 class="mb-1">What will happen?</h6>
                        <p class="mb-0">
                            The selected duplicate drivers will be removed. All
                            their plannings and driver fuel invoices will be
                            moved to the correct driver selected below.
                        </p>
                    </div>
                </div>

                <div class="summary-grid mb-4">
                    <div class="summary-card">
                        <span class="summary-label">Selected drivers</span>
                        <strong>{{ selectedRows.length }}</strong>
                    </div>

                    <div class="summary-card">
                        <span class="summary-label">Correct driver</span>
                        <strong>
                            {{
                                selectedReplacementDriver?.name ||
                                "Not selected"
                            }}
                        </strong>
                    </div>
                </div>

                <div class="replace-section">
                    <div class="section-title">
                        <i class="bx bx-list-check"></i>
                        Selected duplicate drivers
                    </div>

                    <div class="selected-list">
                        <div
                            v-for="row in selectedRows"
                            :key="row.id"
                            class="selected-item"
                        >
                            <div class="selected-left">
                                <div class="selected-avatar">
                                    {{ getInitials(row.name) }}
                                </div>

                                <div>
                                    <strong>
                                        #{{ row.id }} - {{ row.name }}
                                    </strong>
                                    <span> Email: {{ row.email || "-" }} </span>
                                </div>
                            </div>

                            <div class="selected-badge">Will be merged</div>
                        </div>
                    </div>
                </div>

                <div class="replace-section mt-4">
                    <label class="section-title mb-2">
                        <i class="bx bx-id-card"></i>
                        Replace in plannings with this correct Driver
                    </label>

                    <select
                        v-model="replacementDriverId"
                        class="form-select driver-select"
                    >
                        <option value="">-- Select Driver --</option>

                        <option
                            v-for="driver in allDrivers"
                            :key="driver.id"
                            :value="driver.id"
                        >
                            {{ driverOptionLabel(driver) }}
                        </option>
                    </select>
                </div>

                <div v-if="selectedReplacementDriver" class="final-preview mt-4">
                    <strong>Final preview:</strong>
                    selected duplicate drivers will be merged into
                    <span>{{ selectedReplacementDriver.name }}</span>.
                </div>

                <div class="replace-modal-actions">
                    <button
                        class="btn btn-light btn-cancel-replace"
                        @click="showReplaceModal = false"
                    >
                        Cancel
                    </button>

                    <button
                        class="btn btn-confirm-replace"
                        @click="submitReplace"
                    >
                        <i class="bx bx-check-circle me-2"></i>
                        Continue to confirmation
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.drivers-page {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.1),
            transparent 24%
        ),
        radial-gradient(
            circle at top right,
            rgba(249, 115, 22, 0.08),
            transparent 22%
        ),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
    box-shadow: 0 20px 40px rgba(190, 24, 93, 0.18);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(255, 255, 255, 0.18),
            transparent 25%
        ),
        radial-gradient(
            circle at 80% 30%,
            rgba(255, 255, 255, 0.12),
            transparent 25%
        );
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.hero-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-size: 34px;
    backdrop-filter: blur(8px);
}

.hero-title {
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 6px;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.98rem;
}

.btn-add-driver,
.btn-replace-driver {
    border: 0;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-add-driver {
    color: #991b1b;
    background: #fff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-add-driver:hover {
    transform: translateY(-2px);
    color: #7f1d1d;
    background: #fff;
}

.btn-replace-driver {
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
}

.btn-replace-driver:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.24);
    transform: translateY(-2px);
}

.selected-count {
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    border-radius: 999px;
    background: #fff;
    color: #be123c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 900;
}

.mini-stat-card,
.toolbar-card,
.main-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 24px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.mini-stat-card {
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    height: 100%;
}

.stat-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    background: linear-gradient(135deg, #e11d48 0%, #fb923c 100%);
}

.stat-label {
    font-size: 0.92rem;
    color: #64748b;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.8rem;
    line-height: 1;
    font-weight: 800;
    color: #0f172a;
}

.toolbar-card {
    padding: 20px;
    height: 100%;
    display: flex;
    align-items: center;
}

.search-wrapper {
    width: 100%;
    position: relative;
}

.search-leading-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #94a3b8;
}

.search-input {
    min-height: 56px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    padding-left: 46px;
    font-size: 0.98rem;
    background: #fff;
}

.search-input:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.main-card {
    overflow: hidden;
}

.table-header {
    padding: 22px 24px 16px;
    border-bottom: 1px solid #eef2f7;
}

.table-title {
    font-weight: 800;
    color: #0f172a;
}

.table-subtitle {
    color: #64748b;
    font-size: 0.92rem;
}

.custom-table thead th {
    padding: 16px 18px;
    background: linear-gradient(180deg, #fff1f2 0%, #fff7ed 100%);
    color: #9f1239;
    font-size: 0.85rem;
    font-weight: 800;
    border-bottom: 1px solid #ffe4e6;
    white-space: nowrap;
}

.custom-table tbody td {
    padding: 18px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.custom-table tbody tr:hover {
    background: rgba(248, 250, 252, 0.95);
}

.custom-table tbody tr.row-selected {
    background: rgba(225, 29, 72, 0.06);
}

.custom-check {
    width: 18px;
    height: 18px;
    border-radius: 6px;
    border-color: #fecdd3;
    cursor: pointer;
}

.custom-check:checked {
    background-color: #e11d48;
    border-color: #e11d48;
}

.driver-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.driver-avatar {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
}

.driver-name {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}

.driver-id {
    font-size: 0.82rem;
    color: #94a3b8;
}

.info-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 600;
    white-space: nowrap;
}

.phone-badge {
    background: #eff6ff;
    color: #1d4ed8;
}

.email-badge {
    background: #f5f3ff;
    color: #7c3aed;
}

.vehicle-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    max-width: 240px;
    padding: 8px 12px;
    border-radius: 999px;
    background: #f0fdf4;
    color: #047857;
    font-weight: 800;
    white-space: nowrap;
}

.vehicle-chip span {
    overflow: hidden;
    text-overflow: ellipsis;
}

.fuel-summary {
    display: inline-flex;
    flex-direction: column;
    gap: 4px;
    min-width: 120px;
    padding: 9px 12px;
    border-radius: 14px;
    background: #fff7ed;
    color: #9a3412;
}

.fuel-summary strong {
    color: #047857;
    font-weight: 900;
}

.fuel-count {
    font-size: 0.78rem;
    font-weight: 800;
}

.status-success {
    background: #ecfdf3;
    color: #047857;
}

.status-danger {
    background: #fef2f2;
    color: #dc2626;
}

.status-warning {
    background: #fffbeb;
    color: #d97706;
}

.status-neutral {
    background: #f1f5f9;
    color: #475569;
}

.notes-box {
    max-width: 260px;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 10px 12px;
    border-radius: 12px;
}

.actions-wrapper {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-action-edit,
.btn-action-delete,
.btn-action-manage {
    border: 0;
    border-radius: 12px;
    padding: 9px 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.btn-action-edit {
    color: #fff;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}

.btn-action-delete {
    color: #fff;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.btn-action-manage {
    color: #fff;
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
}

.btn-action-edit:hover,
.btn-action-delete:hover,
.btn-action-manage:hover {
    color: #fff;
    transform: translateY(-2px);
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-icon {
    width: 78px;
    height: 78px;
    margin: 0 auto 16px;
    border-radius: 24px;
    background: linear-gradient(135deg, #fda4af 0%, #fdba74 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
}

.pagination-area {
    padding: 18px 24px 24px;
    display: flex;
    justify-content: center;
    border-top: 1px solid #eef2f7;
}

.pagination-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.page-btn {
    min-width: 42px;
    height: 42px;
    padding: 0 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-weight: 700;
}

.page-btn.active {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.page-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
    background: #f8fafc;
}

.operations-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 32px 18px;
    overflow-y: auto;
}

.operations-modal {
    width: min(1180px, 100%);
    border-radius: 28px;
    background: #fff;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
    padding: 24px;
}

.operations-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding-bottom: 18px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 20px;
}

.operations-modal-header h4 {
    font-weight: 900;
    color: #0f172a;
}

.operations-modal-header p {
    color: #64748b;
}

.operations-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.operations-card {
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    padding: 18px;
    background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

.operations-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 900;
    margin-bottom: 16px;
}

.operations-card-title i {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #b91c1c;
    background: #fee2e2;
}

.current-vehicle-box {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 14px;
    border-radius: 16px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    margin-bottom: 16px;
}

.current-vehicle-box span,
.current-vehicle-box small {
    color: #047857;
    font-weight: 800;
}

.current-vehicle-box strong {
    color: #0f172a;
}

.operation-form {
    display: grid;
    gap: 10px;
}

.operation-form label,
.fuel-movement-grid label,
.release-box label {
    color: #475569;
    font-size: 0.82rem;
    font-weight: 900;
}

.operation-input {
    min-height: 44px;
    border-radius: 14px;
    border-color: #e2e8f0;
    font-weight: 700;
}

.operation-input:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.operation-primary-btn,
.operation-light-btn {
    min-height: 46px;
    border-radius: 14px;
    font-weight: 900;
}

.operation-primary-btn {
    color: #fff;
    background: linear-gradient(135deg, #b91c1c, #dc2626);
}

.operation-light-btn {
    color: #9a3412;
    background: #fff7ed;
}

.release-box {
    display: grid;
    gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px dashed #e2e8f0;
}

.fuel-movement-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.fuel-card-list {
    display: grid;
    gap: 12px;
}

.fuel-card-item {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    padding: 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.fuel-card-item > div:first-child {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.fuel-card-item strong {
    color: #0f172a;
}

.fuel-card-item span,
.fuel-card-item small {
    color: #64748b;
    font-weight: 700;
}

.fuel-card-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
}

.fuel-status-btn {
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 0.78rem;
    font-weight: 900;
}

.fuel-status-btn.active {
    color: #047857;
    background: #dcfce7;
}

.fuel-status-btn.inactive {
    color: #b91c1c;
    background: #fee2e2;
}

.fuel-empty {
    padding: 24px;
    border: 2px dashed #e2e8f0;
    border-radius: 18px;
    text-align: center;
    color: #64748b;
    font-weight: 800;
}

.replace-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.replace-modal {
    width: min(880px, 100%);
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
    padding: 24px;
}

.replace-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    padding-bottom: 18px;
    border-bottom: 1px solid #eef2f7;
    margin-bottom: 18px;
}

.modal-icon-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-icon,
.warning-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    font-size: 24px;
    flex: 0 0 auto;
}

.replace-modal-header h4 {
    font-weight: 900;
    color: #0f172a;
}

.replace-modal-header p,
.warning-box p {
    color: #64748b;
}

.btn-close-custom {
    border: 0;
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #f8fafc;
    color: #64748b;
    font-size: 22px;
}

.warning-box {
    display: flex;
    gap: 14px;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid #fed7aa;
    background: #fff7ed;
}

.warning-box h6 {
    font-weight: 900;
    color: #9a3412;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.summary-card,
.replace-section,
.final-preview {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #f8fafc;
}

.summary-card {
    padding: 16px;
}

.summary-label {
    display: block;
    color: #64748b;
    font-size: 0.84rem;
    margin-bottom: 6px;
}

.summary-card strong {
    color: #0f172a;
    font-size: 1.05rem;
}

.replace-section {
    padding: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
    font-weight: 900;
}

.selected-list {
    margin-top: 12px;
    display: grid;
    gap: 10px;
}

.selected-item {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    padding: 12px;
    border-radius: 14px;
    background: #fff;
    border: 1px solid #eef2f7;
}

.selected-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.selected-avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
}

.selected-left span {
    display: block;
    color: #64748b;
    font-size: 0.85rem;
}

.selected-badge {
    white-space: nowrap;
    padding: 7px 10px;
    border-radius: 999px;
    color: #be123c;
    background: #fff1f2;
    font-weight: 800;
    font-size: 0.78rem;
}

.driver-select {
    min-height: 52px;
    border-radius: 14px;
    border-color: #e2e8f0;
    margin-top: 8px;
}

.driver-select:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.final-preview {
    padding: 14px 16px;
    color: #475569;
}

.final-preview span {
    color: #be123c;
    font-weight: 900;
}

.replace-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 22px;
}

.btn-cancel-replace,
.btn-confirm-replace {
    border: 0;
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 900;
}

.btn-confirm-replace {
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.btn-confirm-replace:hover {
    color: #fff;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .hero-card {
        padding: 20px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .notes-box {
        max-width: 170px;
    }

    .btn-action-edit span,
    .btn-action-delete span {
        display: none;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .operations-grid,
    .fuel-movement-grid {
        grid-template-columns: 1fr;
    }

    .operations-modal {
        padding: 18px;
    }

    .fuel-card-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .replace-modal-actions {
        flex-direction: column;
    }

    .btn-cancel-replace,
    .btn-confirm-replace {
        width: 100%;
    }
}
</style>
