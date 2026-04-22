<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive, computed } from "vue";

import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
    layout: AppShell
})

const props = defineProps({
    typeServices: {
        type: Array,
        default: () => [],
    },
});

const filters = reactive({
    search: "",
});

const filteredTypeServices = computed(() => {
    let rows = [...props.typeServices];

    if (filters.search) {
        const q = filters.search.toLowerCase();
        rows = rows.filter(
            (item) =>
                String(item.designation || "")
                    .toLowerCase()
                    .includes(q) ||
                String(item.description || "")
                    .toLowerCase()
                    .includes(q),
        );
    }

    return rows;
});

const destroyTypeService = (id) => {
    if (!confirm("Voulez-vous supprimer ce type de service ?")) return;

    router.delete(`/type-services/${id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Type Services" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div
                    class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3"
                >
                    <div>
                        <h3 class="fw-bold mb-1 text-dark">
                            Gestion des types de services
                        </h3>
                        <p class="text-muted mb-0">
                            Liste des catégories de services
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <input
                            v-model="filters.search"
                            type="text"
                            class="form-control form-control-modern"
                            placeholder="Rechercher..."
                        />

                        <Link
                            href="/type-services/create"
                            class="btn btn-danger-red px-4 rounded-3"
                        >
                            <i class="bx bx-plus me-1"></i>
                            Nouveau
                        </Link>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table table-hover align-middle mb-0 custom-table"
                        >
                            <thead>
                                <tr>
                                    <th>Désignation</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="item in filteredTypeServices"
                                    :key="item.id"
                                >
                                    <td class="fw-semibold">
                                        {{ item.designation }}
                                    </td>
                                    <td>{{ item.description || "-" }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <Link
                                                :href="`/type-services/${item.id}/edit`"
                                                class="btn btn-edit-action btn-sm"
                                            >
                                                <i
                                                    class="bx bx-edit-alt me-1"
                                                ></i>
                                                Éditer
                                            </Link>

                                            <button
                                                class="btn btn-delete-action btn-sm"
                                                @click="
                                                    destroyTypeService(item.id)
                                                "
                                            >
                                                <i class="bx bx-trash me-1"></i>
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredTypeServices.length === 0">
                                    <td
                                        colspan="3"
                                        class="text-center py-5 text-muted"
                                    >
                                        Aucun type de service trouvé
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background: #f4f6fb;
    min-height: 100vh;
}

.form-control-modern {
    border-radius: 14px;
    min-height: 46px;
    border: 1px solid #dfe3ec;
}

.form-control-modern:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 0.18rem rgba(193, 18, 31, 0.12);
}

.btn-danger-red {
    background: linear-gradient(135deg, #d11a2a 0%, #a20e19 100%);
    color: #fff;
    border: 0;
}

.btn-edit-action {
    background: linear-gradient(135deg, #ff8a00 0%, #ff6b00 100%);
    color: #fff;
    border: 0;
    border-radius: 10px;
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border: 0;
    border-radius: 10px;
}

.custom-table thead th {
    background: #fff4f5;
    color: #92111b;
    font-weight: 800;
    padding: 16px 14px;
}

.custom-table tbody td {
    padding: 14px;
    border-color: #edf0f5;
}
</style>
