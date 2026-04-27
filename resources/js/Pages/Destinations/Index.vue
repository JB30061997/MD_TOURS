<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    destinations: Object,
    filters: Object,
});

const query = reactive({
    search: props.filters?.search || "",
});

const applyFilters = () => {
    router.get(route("destinations.index"), query, {
        preserveState: true,
        replace: true,
    });
};

const destroyDestination = (id) => {
    if (!confirm("Voulez-vous vraiment supprimer cette destination ?")) return;

    router.delete(route("destinations.destroy", id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Destinations" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="hero-card mb-4">
                <div>
                    <h1>Destinations</h1>
                    <p>Gestion des destinations, villes, pays et types de lieux.</p>
                </div>

                <Link :href="route('destinations.create')" class="btn btn-main">
                    <i class="bx bx-plus me-2"></i>
                    Nouvelle destination
                </Link>
            </div>

            <div class="filter-card mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-10">
                        <label class="form-label">Recherche</label>
                        <input
                            v-model="query.search"
                            type="text"
                            class="form-control input-modern"
                            placeholder="Nom, ville, pays, type..."
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-dark w-100 py-3 rounded-3" @click="applyFilters">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nom</th>
                                <th>Ville</th>
                                <th>Pays</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="item in destinations.data" :key="item.id">
                                <td>
                                    <span class="id-badge">#{{ item.id }}</span>
                                </td>

                                <td class="fw-bold">{{ item.name }}</td>
                                <td>{{ item.city || "-" }}</td>
                                <td>{{ item.country || "-" }}</td>

                                <td>
                                    <span class="type-badge">
                                        {{ item.type || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <span
                                        class="status-badge"
                                        :class="item.status === 'Actif' ? 'active' : 'inactive'"
                                    >
                                        {{ item.status || "-" }}
                                    </span>
                                </td>

                                <td class="notes-cell">{{ item.notes || "-" }}</td>

                                <td>
                                    <div class="actions">
                                        <Link
                                            :href="route('destinations.edit', item.id)"
                                            class="btn btn-edit btn-sm"
                                        >
                                            <i class="bx bx-edit-alt me-1"></i>
                                            Éditer
                                        </Link>

                                        <button
                                            type="button"
                                            class="btn btn-delete btn-sm"
                                            @click="destroyDestination(item.id)"
                                        >
                                            <i class="bx bx-trash me-1"></i>
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="destinations.data.length === 0">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    Aucune destination trouvée.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="destinations.links?.length"
                    class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top"
                >
                    <div class="text-muted small">
                        Page {{ destinations.current_page }} / {{ destinations.last_page }}
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <Link
                            v-for="(link, index) in destinations.links"
                            :key="index"
                            :href="link.url || ''"
                            v-html="link.label"
                            class="btn btn-sm"
                            :class="link.active ? 'btn-main' : 'btn-outline-secondary'"
                            :disabled="!link.url"
                            preserve-scroll
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(193, 18, 31, 0.06), transparent 18%),
        radial-gradient(circle at bottom right, rgba(29, 78, 216, 0.06), transparent 18%),
        #f4f6fb;
}

.hero-card {
    border-radius: 28px;
    padding: 32px;
    color: #fff;
    background: linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    box-shadow: 0 20px 45px rgba(127, 16, 36, 0.18);
}

.hero-card h1 {
    font-weight: 900;
    margin: 0;
}

.hero-card p {
    margin: 8px 0 0;
    color: rgba(255,255,255,.85);
}

.filter-card,
.table-card {
    border-radius: 26px;
    background: rgba(255,255,255,.95);
    border: 1px solid #eef2f7;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
}

.filter-card {
    padding: 22px;
}

.input-modern {
    min-height: 52px;
    border-radius: 16px;
    border: 1px solid #dbe2ea;
    font-weight: 600;
}

.table thead th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 900;
    padding: 16px 14px;
    white-space: nowrap;
}

.table tbody td {
    padding: 16px 14px;
    border-color: #eef2f7;
}

.id-badge,
.type-badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 14px;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
    font-weight: 800;
}

.status-badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 900;
    font-size: .82rem;
}

.status-badge.active {
    background: rgba(22, 163, 74, .12);
    color: #15803d;
}

.status-badge.inactive {
    background: rgba(220, 38, 38, .12);
    color: #dc2626;
}

.notes-cell {
    max-width: 260px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.actions {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
}

.btn-main {
    border: none;
    color: #fff;
    font-weight: 900;
    border-radius: 16px;
    padding: 11px 18px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
}

.btn-main:hover {
    color: #fff;
}

.btn-edit,
.btn-delete {
    border: none;
    color: #fff;
    border-radius: 12px;
    font-weight: 800;
}

.btn-edit {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.btn-edit:hover,
.btn-delete:hover {
    color: #fff;
}
</style>