<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    supplierClients: Object,
    allSupplierClients: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const page = usePage();

const query = reactive({
    search: props.filters?.search || "",
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
    router.get(route("supplier-clients.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const replaceSupplierClient = (oldItem) => {
    const options = props.allSupplierClients
        .filter((item) => item.id !== oldItem.id)
        .map((item) => `<option value="${item.id}">${item.name}</option>`)
        .join("");

    Swal.fire({
        title: `Remplacer ${oldItem.name}`,
        html: `
            <div style="text-align:left;margin-bottom:10px;">
                <label style="font-weight:700;color:#334155;">
                    Nouveau client supplier
                </label>
                <select id="new_supplier_client_id" class="swal2-input" style="width:100%;margin:10px 0 0 0;">
                    <option value="">Choisir...</option>
                    ${options}
                </select>
            </div>

            <small style="color:#64748b;">
                Tous les clients liés à <b>${oldItem.name}</b> seront transférés vers le nouveau supplier.
            </small>
        `,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Oui, remplacer",
        cancelButtonText: "Annuler",
        confirmButtonColor: "#ea580c",
        cancelButtonColor: "#64748b",
        preConfirm: () => {
            const value = document.getElementById(
                "new_supplier_client_id",
            ).value;

            if (!value) {
                Swal.showValidationMessage("Veuillez choisir un supplier.");
                return false;
            }

            return value;
        },
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(
                route("supplier-clients.replace", oldItem.id),
                {
                    new_supplier_client_id: result.value,
                },
                {
                    preserveScroll: true,
                },
            );
        }
    });
};

const destroySupplierClient = (id) => {
    Swal.fire({
        title: "Delete this client supplier?",
        text: "This action is irreversible.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("supplier-clients.destroy", id), {
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <Head title="Client Suppliers" />

    <div class="supplier-clients-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-business"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Client Suppliers</h1>
                            <p class="hero-subtitle mb-0">
                                Manage suppliers linked to your clients.
                            </p>
                        </div>
                    </div>

                    <div class="hero-right">
                        <Link
                            :href="route('supplier-clients.create')"
                            class="btn btn-add"
                        >
                            <i class="bx bx-plus-circle me-2"></i>
                            New Client Supplier
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
                            <div class="stat-label">Total Client Suppliers</div>
                            <div class="stat-value">
                                {{ supplierClients.total || 0 }}
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
                        <h5 class="table-title mb-1">Client Suppliers List</h5>
                        <p class="table-subtitle mb-0">
                            Showing {{ supplierClients.from || 0 }} to
                            {{ supplierClients.to || 0 }} of
                            {{ supplierClients.total || 0 }} client suppliers
                        </p>
                    </div>
                </div>

                <div class="table-responsive custom-table-wrapper">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Linked User</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th
                                    class="text-center"
                                    style="min-width: 420px"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            v-if="
                                supplierClients.data &&
                                supplierClients.data.length
                            "
                        >
                            <tr
                                v-for="item in supplierClients.data"
                                :key="item.id"
                            >
                                <td>
                                    <span class="id-badge">#{{ item.id }}</span>
                                </td>

                                <td>
                                    <div class="supplier-name">
                                        <div class="supplier-avatar">
                                            <i class="bx bx-building-house"></i>
                                        </div>

                                        <div>
                                            <div class="name-text">
                                                {{ item.name }}
                                            </div>
                                            <div class="small-text">
                                                Client Supplier
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
                                        <button
                                            type="button"
                                            class="btn btn-action-replace"
                                            @click="replaceSupplierClient(item)"
                                        >
                                            <i class="bx bx-transfer"></i>
                                            <span>Replace</span>
                                        </button>

                                        <Link
                                            :href="
                                                route(
                                                    'supplier-clients.edit',
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
                                                destroySupplierClient(item.id)
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
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bx bx-search-alt"></i>
                                        </div>
                                        <h5 class="mb-2">
                                            No client suppliers found
                                        </h5>
                                        <p class="text-muted mb-0">
                                            Try adjusting your search or add a
                                            new client supplier.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="supplierClients.links?.length"
                    class="pagination-area"
                >
                    <div class="pagination-info">
                        Page {{ supplierClients.current_page }} /
                        {{ supplierClients.last_page }}
                    </div>

                    <div class="pagination-list">
                        <Link
                            v-for="(link, index) in supplierClients.links"
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
    </div>
</template>

<style scoped>
.supplier-clients-page {
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

.btn-add {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-add:hover {
    transform: translateY(-2px);
    color: #7f1d1d;
    background: #fff;
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
    display: flex;
    gap: 12px;
}

.search-leading-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #94a3b8;
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
    padding: 0 22px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    white-space: nowrap;
}

.btn-search:hover {
    color: #fff;
    transform: translateY(-2px);
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
    max-width: 240px;
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

.btn-action-replace,
.btn-action-edit,
.btn-action-delete {
    border: 0;
    border-radius: 12px;
    padding: 9px 14px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #fff;
}

.btn-action-replace {
    background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
}

.btn-action-edit {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}

.btn-action-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.btn-action-replace:hover,
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

    .actions-wrapper {
        justify-content: flex-start;
    }

    .btn-action-replace span,
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
