<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, watch, ref, computed } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    supplierVehicules: Object,
    allSupplierVehicules: Array,
    filters: Object,
});

const page = usePage();

const query = reactive({
    search: props.filters?.search || "",
});

const selectedIds = ref([]);
const showReplaceModal = ref(false);
const replacementSupplierVehiculeId = ref("");

const selectedRows = computed(() => {
    return props.supplierVehicules.data.filter((item) =>
        selectedIds.value.includes(item.id),
    );
});

const selectedReplacementSupplier = computed(() => {
    return props.allSupplierVehicules?.find(
        (item) => item.id == replacementSupplierVehiculeId.value,
    );
});

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

const applyFilters = () => {
    router.get(route("supplier-vehicules.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const openReplaceModal = () => {
    if (!selectedIds.value.length) {
        Swal.fire({
            icon: "warning",
            title: "No rows selected",
            text: "Please select at least one row.",
            confirmButtonColor: "#c1121f",
        });

        return;
    }

    showReplaceModal.value = true;
};

const submitReplace = () => {
    if (!replacementSupplierVehiculeId.value) {
        Swal.fire({
            icon: "warning",
            title: "Vehicle Supplier required",
            text: "Please select the correct Vehicle Supplier.",
            confirmButtonColor: "#c1121f",
        });

        return;
    }

    showReplaceModal.value = false;

    setTimeout(() => {
        Swal.fire({
            icon: "warning",
            title: "Confirm replacement?",
            html: `
                <div style="text-align:left; line-height:1.7">
                    <p><strong>${selectedRows.value.length}</strong> selected row(s) will be processed.</p>
                    <p>Each selected row will be created as Driver, linked to User, updated in plannings, then deleted from Vehicle Suppliers.</p>
                    <p>Replacement Vehicle Supplier: <strong>${selectedReplacementSupplier.value?.name || "-"}</strong></p>
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
                route("supplier-vehicules.replace-selected"),
                {
                    selected_ids: selectedIds.value,
                    replacement_supplier_vehicule_id:
                        replacementSupplierVehiculeId.value,
                },
                {
                    preserveScroll: true,

                    onSuccess: () => {
                        selectedIds.value = [];
                        replacementSupplierVehiculeId.value = "";
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

const destroySupplierVehicule = (id) => {
    Swal.fire({
        title: "Delete this vehicle supplier?",
        text: "This action is irreversible.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("supplier-vehicules.destroy", id), {
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <Head title="Vehicle Suppliers" />

    <div class="supplier-vehicles-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-bus"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Vehicle Suppliers</h1>

                            <p class="hero-subtitle mb-0">
                                Manage all suppliers related to vehicles.
                            </p>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-replace"
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
                            :href="route('supplier-vehicules.create')"
                            class="btn btn-add"
                        >
                            <i class="bx bx-plus-circle me-2"></i>
                            New Vehicle Supplier
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div class="mini-stat-card">
                        <div class="stat-icon">
                            <i class="bx bx-buildings"></i>
                        </div>

                        <div>
                            <div class="stat-label">
                                Total Vehicle Suppliers
                            </div>

                            <div class="stat-value">
                                {{ supplierVehicules.total || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="toolbar-card">
                        <div class="search-wrapper">
                            <i class="bx bx-search search-leading-icon"></i>

                            <input
                                v-model="query.search"
                                type="text"
                                class="form-control search-input"
                                placeholder="Search by name, phone, email or address..."
                                @keyup.enter="applyFilters"
                            />

                            <button
                                class="btn btn-search"
                                @click="applyFilters"
                            >
                                <i class="bx bx-search"></i>
                                <span>Search</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-card">
                <div class="table-header">
                    <div>
                        <h5 class="table-title mb-1">Vehicle Suppliers List</h5>

                        <p class="table-subtitle mb-0">
                            Showing {{ supplierVehicules.from || 0 }} to
                            {{ supplierVehicules.to || 0 }} of
                            {{ supplierVehicules.total || 0 }}
                            vehicle suppliers
                        </p>
                    </div>
                </div>

                <div class="table-responsive custom-table-wrapper">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th></th>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Linked User</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody
                            v-if="
                                supplierVehicules.data &&
                                supplierVehicules.data.length
                            "
                        >
                            <tr
                                v-for="item in supplierVehicules.data"
                                :key="item.id"
                                :class="{
                                    'row-selected': selectedIds.includes(
                                        item.id,
                                    ),
                                }"
                            >
                                <td>
                                    <input
                                        v-model="selectedIds"
                                        class="form-check-input custom-check"
                                        type="checkbox"
                                        :value="item.id"
                                    />
                                </td>

                                <td>
                                    <span class="id-badge">
                                        #{{ item.id }}
                                    </span>
                                </td>

                                <td>
                                    <div class="supplier-name">
                                        <div class="supplier-avatar">
                                            <i class="bx bx-bus"></i>
                                        </div>

                                        <div>
                                            <div class="name-text">
                                                {{ item.name }}
                                            </div>

                                            <div class="small-text">
                                                Vehicle Supplier
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="info-badge phone-badge">
                                        <i class="bx bx-phone-call me-1"></i>
                                        {{ item.phone || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span class="info-badge email-badge">
                                        <i class="bx bx-envelope me-1"></i>
                                        {{ item.email || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span class="address-text">
                                        {{ item.address || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span class="user-badge">
                                        <i class="bx bx-user me-1"></i>
                                        {{ item.user?.name || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span
                                        class="status-badge"
                                        :class="
                                            item.is_active
                                                ? 'active'
                                                : 'inactive'
                                        "
                                    >
                                        {{
                                            item.is_active
                                                ? "Active"
                                                : "Inactive"
                                        }}
                                    </span>
                                </td>

                                <td>
                                    <div
                                        class="notes-box"
                                        :title="item.notes || '-'"
                                    >
                                        {{ item.notes || "-" }}
                                    </div>
                                </td>

                                <td>
                                    <div class="actions-wrapper">
                                        <Link
                                            :href="
                                                route(
                                                    'supplier-vehicules.edit',
                                                    item.id,
                                                )
                                            "
                                            class="btn btn-action-edit"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                            <span>Edit</span>
                                        </Link>

                                        <button
                                            type="button"
                                            class="btn btn-action-delete"
                                            @click="
                                                destroySupplierVehicule(item.id)
                                            "
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
                                <td colspan="10">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bx bx-search-alt"></i>
                                        </div>

                                        <h5 class="mb-2">
                                            No vehicle suppliers found
                                        </h5>

                                        <p class="text-muted mb-0">
                                            Try adjusting your search or add a
                                            new vehicle supplier.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="supplierVehicules.links?.length"
                    class="pagination-area"
                >
                    <div class="pagination-info">
                        Page {{ supplierVehicules.current_page }} /
                        {{ supplierVehicules.last_page }}
                    </div>

                    <div class="pagination-list">
                        <Link
                            v-for="(link, index) in supplierVehicules.links"
                            :key="index"
                            :href="link.url || ''"
                            v-html="link.label"
                            class="page-btn"
                            :class="{
                                active: link.active,
                                disabled: !link.url,
                            }"
                            preserve-scroll
                            preserve-state
                        />
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showReplaceModal" class="replace-modal-backdrop">
            <div class="replace-modal">
                <div class="replace-modal-header">
                    <div>
                        <div class="modal-icon-title">
                            <div class="modal-icon">
                                <i class="bx bx-transfer-alt"></i>
                            </div>

                            <div>
                                <h4 class="mb-1">Replace selected rows</h4>

                                <p class="mb-0">
                                    Review carefully before confirming.
                                </p>
                            </div>
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
                            The selected Vehicle Suppliers are actually drivers.
                            The system will create them as Drivers, link them to
                            Users, update the old plannings with their
                            driver_id, replace their supplier_vehicule_id by the
                            correct Vehicle Supplier, then delete them from
                            Vehicle Suppliers.
                        </p>
                    </div>
                </div>

                <div class="summary-grid mb-4">
                    <div class="summary-card">
                        <span class="summary-label">Selected rows</span>
                        <strong>{{ selectedRows.length }}</strong>
                    </div>

                    <div class="summary-card">
                        <span class="summary-label">Correct supplier</span>
                        <strong>
                            {{
                                selectedReplacementSupplier?.name ||
                                "Not selected"
                            }}
                        </strong>
                    </div>
                </div>

                <div class="replace-section">
                    <div class="section-title">
                        <i class="bx bx-list-check"></i>
                        Selected rows to convert into Drivers
                    </div>

                    <div class="selected-list">
                        <div
                            v-for="row in selectedRows"
                            :key="row.id"
                            class="selected-item"
                        >
                            <div class="selected-left">
                                <div class="selected-avatar">
                                    <i class="bx bx-user"></i>
                                </div>

                                <div>
                                    <strong>
                                        #{{ row.id }} - {{ row.name }}
                                    </strong>

                                    <span> Phone: {{ row.phone || "-" }} </span>
                                </div>
                            </div>

                            <div class="selected-badge">Will become Driver</div>
                        </div>
                    </div>
                </div>

                <div class="replace-section mt-4">
                    <label class="section-title mb-2">
                        <i class="bx bx-bus"></i>
                        Replace in plannings with this Vehicle Supplier
                    </label>

                    <select
                        v-model="replacementSupplierVehiculeId"
                        class="form-select supplier-select"
                    >
                        <option value="">-- Select Vehicle Supplier --</option>

                        <option
                            v-for="supplier in allSupplierVehicules"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.name }}
                        </option>
                    </select>
                </div>

                <div
                    v-if="selectedReplacementSupplier"
                    class="final-preview mt-4"
                >
                    <strong>Final preview:</strong>
                    selected rows will become drivers, and their old plannings
                    will use
                    <span>{{ selectedReplacementSupplier.name }}</span>
                    as the Vehicle Supplier.
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
.supplier-vehicles-page {
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
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.hero-actions {
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

.btn-add,
.btn-replace {
    border: 0;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-add {
    color: #991b1b;
    background: #fff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-add:hover {
    color: #7f1d1d;
    background: #fff;
    transform: translateY(-2px);
}

.btn-replace {
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
}

.btn-replace:hover {
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
    background: rgba(255, 255, 255, 0.88);
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
    display: flex;
    gap: 12px;
    position: relative;
}

.search-leading-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 18px;
    z-index: 2;
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

.btn-search {
    min-height: 56px;
    border: 0;
    color: #fff;
    border-radius: 18px;
    padding: 0 24px;
    font-weight: 800;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-search:hover {
    color: #fff;
    transform: translateY(-2px);
}

.main-card {
    padding: 0;
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

.row-selected {
    background: rgba(255, 247, 237, 0.9);
}

.custom-check {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.id-badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 14px;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    font-weight: 800;
}

.supplier-name {
    display: flex;
    align-items: center;
    gap: 14px;
}

.supplier-avatar {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 22px;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
}

.name-text {
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 2px;
}

.small-text {
    font-size: 0.82rem;
    color: #94a3b8;
}

.info-badge,
.user-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 700;
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

.user-badge {
    background: #ecfdf5;
    color: #047857;
}

.address-text {
    max-width: 220px;
    display: inline-block;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 900;
    font-size: 0.82rem;
}

.status-badge.active {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.status-badge.inactive {
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
}

.notes-box {
    max-width: 220px;
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
    color: #fff;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.btn-action-edit {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}

.btn-action-delete {
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
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    border-top: 1px solid #eef2f7;
}

.pagination-info {
    color: #64748b;
    font-size: 0.88rem;
    font-weight: 700;
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
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(7px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.replace-modal {
    width: min(980px, 100%);
    max-height: 92vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 28px;
    padding: 28px;
    box-shadow: 0 30px 90px rgba(15, 23, 42, 0.35);
}

.replace-modal-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding-bottom: 18px;
    margin-bottom: 20px;
    border-bottom: 1px solid #eef2f7;
}

.modal-icon-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.replace-modal-header h4 {
    font-weight: 900;
    color: #0f172a;
}

.replace-modal-header p {
    color: #64748b;
}

.btn-close-custom {
    border: 0;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #fee2e2;
    color: #dc2626;
    font-size: 22px;
}

.warning-box {
    display: flex;
    gap: 14px;
    padding: 16px;
    border-radius: 18px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
}

.warning-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 14px;
    background: #ffedd5;
    color: #c2410c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.warning-box h6 {
    color: #7c2d12;
    font-weight: 900;
}

.warning-box p {
    color: #9a3412;
    font-size: 0.92rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.summary-card {
    padding: 16px;
    border-radius: 18px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.summary-label {
    display: block;
    color: #64748b;
    font-size: 0.82rem;
    margin-bottom: 6px;
}

.summary-card strong {
    color: #0f172a;
    font-size: 1.05rem;
}

.replace-section {
    padding: 16px;
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid #eef2f7;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 12px;
}

.selected-list {
    max-height: 300px;
    overflow-y: auto;
    display: grid;
    gap: 10px;
    padding-right: 4px;
}

.selected-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.selected-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.selected-avatar {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.selected-left strong {
    display: block;
    color: #0f172a;
    margin-bottom: 2px;
}

.selected-left span {
    color: #64748b;
    font-size: 0.84rem;
}

.selected-badge {
    white-space: nowrap;
    padding: 8px 12px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-size: 0.78rem;
    font-weight: 900;
}

.supplier-select {
    min-height: 52px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    font-weight: 700;
    color: #334155;
}

.supplier-select:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.final-preview {
    padding: 14px 16px;
    border-radius: 16px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    font-size: 0.92rem;
}

.final-preview span {
    font-weight: 900;
}

.replace-modal-actions {
    margin-top: 24px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-cancel-replace,
.btn-confirm-replace {
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 900;
}

.btn-confirm-replace {
    color: #fff;
    border: 0;
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

    .search-wrapper {
        flex-direction: column;
    }

    .btn-search {
        width: 100%;
        justify-content: center;
    }

    .replace-modal {
        padding: 20px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .selected-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .replace-modal-actions {
        justify-content: stretch;
    }

    .btn-cancel-replace,
    .btn-confirm-replace {
        width: 100%;
    }

    .actions-wrapper {
        justify-content: flex-start;
    }

    .btn-action-edit span,
    .btn-action-delete span {
        display: none;
    }

    .notes-box,
    .address-text {
        max-width: 170px;
    }
}
</style>
