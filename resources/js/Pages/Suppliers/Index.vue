<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive, watch } from "vue";

import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
    layout: AppShell
})

const props = defineProps({
    suppliers: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
        }),
    },
});

const form = reactive({
    search: props.filters?.search || "",
});

let searchTimeout = null;

watch(
    () => form.search,
    (value) => {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            router.get(
                "/suppliers",
                {
                    search: value,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 400);
    },
);

const destroySupplier = (id) => {
    if (!confirm("Voulez-vous supprimer ce supplier ?")) return;

    router.delete(`/suppliers/${id}`, {
        preserveScroll: true,
    });
};

const getInitials = (name) => {
    if (!name) return "SP";

    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
};
</script>

<template>
    <Head title="Suppliers" />

    <div class="suppliers-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-package"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Gestion des suppliers</h1>
                            <p class="hero-subtitle mb-0">
                                Gérez, recherchez et suivez facilement tous vos
                                fournisseurs.
                            </p>
                        </div>
                    </div>

                    <div class="hero-right">
                        <Link
                            href="/suppliers/create"
                            class="btn btn-add-supplier"
                        >
                            <i class="bx bx-plus-circle me-2"></i>
                            Nouveau supplier
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div class="mini-stat-card stat-primary">
                        <div class="stat-icon">
                            <i class="bx bx-group"></i>
                        </div>
                        <div>
                            <div class="stat-label">Total suppliers</div>
                            <div class="stat-value">
                                {{ suppliers.total || 0 }}
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
                                placeholder="Rechercher par nom, type, téléphone, email..."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-card">
                <div class="table-header">
                    <div>
                        <h5 class="table-title mb-1">Liste des suppliers</h5>
                        <p class="table-subtitle mb-0">
                            Affichage de {{ suppliers.from || 0 }} à
                            {{ suppliers.to || 0 }} sur
                            {{ suppliers.total || 0 }} suppliers
                        </p>
                    </div>
                </div>

                <div class="table-responsive custom-table-wrapper">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Supplier</th>
                                <th>Type</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th>Adresse</th>
                                <th>Notes</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody v-if="suppliers.data && suppliers.data.length">
                            <tr
                                v-for="supplier in suppliers.data"
                                :key="supplier.id"
                            >
                                <td>
                                    <div class="supplier-cell">
                                        <div class="supplier-avatar">
                                            {{ getInitials(supplier.name) }}
                                        </div>

                                        <div>
                                            <div class="supplier-name">
                                                {{ supplier.name }}
                                            </div>
                                            <div class="supplier-id">
                                                ID #{{ supplier.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="info-badge type-badge">
                                        <i class="bx bx-category-alt me-1"></i>
                                        {{
                                            supplier.typeSupplier
                                                ?.designation || "-"
                                        }}
                                    </span>
                                </td>

                                <td>
                                    <span class="info-badge phone-badge">
                                        <i class="bx bx-phone-call me-1"></i>
                                        {{ supplier.phone || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span class="info-badge email-badge">
                                        <i class="bx bx-envelope me-1"></i>
                                        {{ supplier.email || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <div
                                        class="address-box"
                                        :title="supplier.address || '-'"
                                    >
                                        {{ supplier.address || "-" }}
                                    </div>
                                </td>

                                <td>
                                    <div
                                        class="notes-box"
                                        :title="supplier.notes || '-'"
                                    >
                                        {{ supplier.notes || "-" }}
                                    </div>
                                </td>

                                <td>
                                    <div class="actions-wrapper">
                                        <Link
                                            :href="`/suppliers/${supplier.id}/edit`"
                                            class="btn btn-action-edit"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                            <span>Éditer</span>
                                        </Link>

                                        <button
                                            class="btn btn-action-delete"
                                            @click="
                                                destroySupplier(supplier.id)
                                            "
                                        >
                                            <i class="bx bx-trash"></i>
                                            <span>Supprimer</span>
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
                                        <h5 class="mb-2">
                                            Aucun supplier trouvé
                                        </h5>
                                        <p class="text-muted mb-0">
                                            Essayez de modifier votre recherche
                                            ou ajoutez un nouveau supplier.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="suppliers.links && suppliers.links.length > 3"
                    class="pagination-area"
                >
                    <div class="pagination-list">
                        <template
                            v-for="(link, index) in suppliers.links"
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
    </div>
</template>

<style scoped>
.suppliers-page {
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
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
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

.btn-add-supplier {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 700;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
    transition: 0.25s ease;
}

.btn-add-supplier:hover {
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
    box-shadow: 0 12px 22px rgba(225, 29, 72, 0.22);
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

.custom-table tbody tr {
    transition: 0.2s ease;
}

.custom-table tbody tr:hover {
    background: rgba(248, 250, 252, 0.95);
}

.supplier-cell {
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
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    box-shadow: 0 10px 18px rgba(190, 24, 93, 0.18);
}

.supplier-name {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}

.supplier-id {
    font-size: 0.82rem;
    color: #94a3b8;
}

.info-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 600;
    white-space: nowrap;
}

.type-badge {
    background: #fff7ed;
    color: #c2410c;
}

.phone-badge {
    background: #eff6ff;
    color: #1d4ed8;
}

.email-badge {
    background: #f5f3ff;
    color: #7c3aed;
}

.address-box,
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
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: 0.2s ease;
}

.btn-action-edit {
    color: #fff;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    box-shadow: 0 10px 18px rgba(245, 158, 11, 0.2);
}

.btn-action-delete {
    color: #fff;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 10px 18px rgba(239, 68, 68, 0.2);
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
    box-shadow: 0 16px 28px rgba(244, 114, 182, 0.18);
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
    transition: 0.2s ease;
}

.page-btn:hover {
    background: #fff1f2;
    color: #be123c;
    border-color: #fecdd3;
}

.page-btn.active {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    box-shadow: 0 12px 22px rgba(190, 24, 93, 0.22);
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

    .address-box,
    .notes-box {
        max-width: 150px;
    }

    .btn-action-edit span,
    .btn-action-delete span {
        display: none;
    }
}
</style>
