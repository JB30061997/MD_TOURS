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
    filters: {
        type: Object,
        default: () => ({
            search: "",
        }),
    },
});

const page = usePage();

const form = reactive({
    search: props.filters?.search || "",
});

const selectedIds = ref([]);
const showReplaceModal = ref(false);
const replacementDriverId = ref("");

const selectedRows = computed(() => {
    return (props.drivers.data || []).filter((driver) =>
        selectedIds.value.includes(driver.id),
    );
});

const selectedReplacementDriver = computed(() => {
    return props.allDrivers.find((driver) => driver.id == replacementDriverId.value);
});

let searchTimeout = null;

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

const openReplaceModal = () => {
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

const destroyDriver = (id) => {
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

                        <Link href="/drivers/create" class="btn btn-add-driver">
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
                                        <Link
                                            :href="`/drivers/${driver.id}/edit`"
                                            class="btn btn-action-edit"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                            <span>Edit</span>
                                        </Link>

                                        <button
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
                                <td colspan="7">
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
.btn-action-delete {
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

.btn-action-edit:hover,
.btn-action-delete:hover {
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

    .replace-modal-actions {
        flex-direction: column;
    }

    .btn-cancel-replace,
    .btn-confirm-replace {
        width: 100%;
    }
}
</style>
