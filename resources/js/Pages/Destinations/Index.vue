<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    destinations: Object,
    allDestinations: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const page = usePage();
const can = (permission) =>
    page.props?.auth?.isSuperAdmin || !!page.props?.auth?.can?.[permission];

const selectedIds = ref([]);
const showReplaceModal = ref(false);
const replacementDestinationId = ref("");

const selectedRows = computed(() =>
    (props.destinations.data || []).filter((item) => selectedIds.value.includes(item.id)),
);
const replacementOptions = computed(() =>
    props.allDestinations.filter((item) => !selectedIds.value.includes(item.id)),
);
const selectedReplacementDestination = computed(() =>
    props.allDestinations.find((item) => item.id == replacementDestinationId.value),
);
const affectedPlannings = computed(() =>
    selectedRows.value.reduce((total, item) => total + Number(item.destination_plannings_count || 0), 0),
);
const allPageSelected = computed(() =>
    (props.destinations.data || []).length > 0 &&
    props.destinations.data.every((item) => selectedIds.value.includes(item.id)),
);

const togglePageSelection = () => {
    const visibleIds = (props.destinations.data || []).map((item) => item.id);
    selectedIds.value = allPageSelected.value
        ? selectedIds.value.filter((id) => !visibleIds.includes(id))
        : [...new Set([...selectedIds.value, ...visibleIds])];
};

const openReplaceModal = () => {
    if (!selectedIds.value.length) {
        Swal.fire({ icon: "warning", title: "Aucune destination sélectionnée", text: "Sélectionnez au moins une destination.", confirmButtonColor: "#c1121f" });
        return;
    }
    replacementDestinationId.value = "";
    showReplaceModal.value = true;
};

const submitReplace = () => {
    if (!replacementDestinationId.value) {
        Swal.fire({ icon: "warning", title: "Destination obligatoire", text: "Sélectionnez la destination correcte.", confirmButtonColor: "#c1121f" });
        return;
    }

    showReplaceModal.value = false;
    setTimeout(() => {
        Swal.fire({
            icon: "warning",
            title: "Confirmer le remplacement ?",
            html: `<div style="text-align:left;line-height:1.7"><p><strong>${selectedIds.value.length}</strong> destination(s) seront fusionnées.</p><p><strong>${affectedPlannings.value}</strong> planning(s) seront transférés vers la destination correcte.</p><p>Destination finale : <strong>${selectedReplacementDestination.value?.name || "-"}</strong></p><p>Les anciennes destinations seront supprimées uniquement après ce transfert.</p></div>`,
            showCancelButton: true,
            confirmButtonText: "Oui, remplacer maintenant",
            cancelButtonText: "Annuler",
            confirmButtonColor: "#c1121f",
            cancelButtonColor: "#64748b",
            width: 720,
        }).then((result) => {
            if (!result.isConfirmed) {
                showReplaceModal.value = true;
                return;
            }
            router.post(route("destinations.replace-selected"), {
                selected_ids: selectedIds.value,
                replacement_destination_id: replacementDestinationId.value,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                    replacementDestinationId.value = "";
                    showReplaceModal.value = false;
                },
                onError: () => { showReplaceModal.value = true; },
            });
        });
    }, 180);
};

watch(() => page.props.flash, (flash) => {
    if (flash?.success) Swal.fire({ toast: true, position: "top-end", icon: "success", title: flash.success, showConfirmButton: false, timer: 3000, timerProgressBar: true });
    if (flash?.error) Swal.fire({ icon: "error", title: "Erreur", text: flash.error, confirmButtonColor: "#c1121f" });
}, { deep: true });

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
    Swal.fire({
        title: "Delete this destination?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("destinations.destroy", id), {
                preserveScroll: true,
                onSuccess: () => {
                    if (page.props.flash?.error) {
                        Swal.fire({
                            icon: "error",
                            title: "Suppression impossible",
                            text: page.props.flash.error,
                            confirmButtonColor: "#c1121f",
                        });
                        return;
                    }

                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Destination deleted successfully",
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Could not delete this destination.",
                        confirmButtonColor: "#c1121f",
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Destinations" />

    <div class="destinations-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-map"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Destination Management</h1>
                            <p class="hero-subtitle mb-0">
                                Manage cities, countries and location types.
                            </p>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <button v-if="can('destinations.manage') && selectedIds.length" type="button" class="btn btn-replace-destination" @click="openReplaceModal">
                            <i class="bx bx-transfer-alt me-2"></i> Replace
                            <span class="selected-count">{{ selectedIds.length }}</span>
                        </button>
                        <Link :href="route('destinations.create')" class="btn btn-add-destination">
                            <i class="bx bx-plus-circle me-2"></i> New Destination
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div class="mini-stat-card">
                        <div class="stat-icon">
                            <i class="bx bx-map-pin"></i>
                        </div>
                        <div>
                            <div class="stat-label">Total Destinations</div>
                            <div class="stat-value">
                                {{ destinations.total || 0 }}
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
                                placeholder="Search by name, city, country or type..."
                                @keyup.enter="applyFilters"
                            />

                            <button class="btn btn-search" @click="applyFilters">
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
                        <h5 class="table-title mb-1">Destinations List</h5>
                        <p class="table-subtitle mb-0">
                            Showing {{ destinations.from || 0 }} to
                            {{ destinations.to || 0 }} of
                            {{ destinations.total || 0 }} destinations
                        </p>
                    </div>
                </div>

                <div class="table-responsive custom-table-wrapper">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="select-col"><input type="checkbox" class="destination-check" :checked="allPageSelected" @change="togglePageSelection" /></th>
                                <th>#</th>
                                <th>Destination</th>
                                <th>City</th>
                                <th>Country</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody v-if="destinations.data && destinations.data.length">
                            <tr v-for="item in destinations.data" :key="item.id" :class="{ 'row-selected': selectedIds.includes(item.id) }">
                                <td class="select-col"><input v-model="selectedIds" type="checkbox" class="destination-check" :value="item.id" /></td>
                                <td>
                                    <span class="id-badge">#{{ item.id }}</span>
                                </td>

                                <td>
                                    <div class="destination-cell">
                                        <div class="destination-avatar">
                                            <i class="bx bx-map-pin"></i>
                                        </div>
                                        <div>
                                            <div class="destination-name">
                                                {{ item.name }}
                                            </div>
                                            <div class="destination-id">
                                                ID #{{ item.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

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
                                        :class="item.status === 'Actif' || item.status === 'Active' ? 'active' : 'inactive'"
                                    >
                                        {{ item.status === "Actif" ? "Active" : item.status || "-" }}
                                    </span>
                                </td>

                                <td>
                                    <div class="notes-box" :title="item.notes || '-'">
                                        {{ item.notes || "-" }}
                                    </div>
                                </td>

                                <td>
                                    <div class="actions-wrapper">
                                        <Link
                                            :href="route('destinations.edit', item.id)"
                                            class="btn btn-action-edit"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                            <span>Edit</span>
                                        </Link>

                                        <button
                                            type="button"
                                            class="btn btn-action-delete"
                                            @click="destroyDestination(item.id)"
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
                                        <h5 class="mb-2">No destinations found</h5>
                                        <p class="text-muted mb-0">
                                            Try adjusting your search or add a new destination.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="destinations.links?.length"
                    class="pagination-area"
                >
                    <div class="pagination-info">
                        Page {{ destinations.current_page }} /
                        {{ destinations.last_page }}
                    </div>

                    <div class="pagination-list">
                        <Link
                            v-for="(link, index) in destinations.links"
                            :key="index"
                            :href="link.url || ''"
                            v-html="link.label"
                            class="page-btn"
                            :class="{ active: link.active, disabled: !link.url }"
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
                    <div class="modal-icon-title">
                        <div class="modal-icon"><i class="bx bx-transfer-alt"></i></div>
                        <div><h4 class="mb-1">Replace selected destinations</h4><p class="mb-0">Merge duplicate destinations into one correct destination.</p></div>
                    </div>
                    <button class="btn-close-custom" @click="showReplaceModal = false"><i class="bx bx-x"></i></button>
                </div>

                <div class="warning-box mb-4">
                    <div class="warning-icon"><i class="bx bx-error-circle"></i></div>
                    <div><h6 class="mb-1">What will happen?</h6><p class="mb-0">All linked plannings will be moved to the correct destination. Duplicate destinations will be deleted only after your explicit confirmation.</p></div>
                </div>

                <div class="summary-grid mb-4">
                    <div class="summary-card"><span class="summary-label">Selected destinations</span><strong>{{ selectedRows.length }}</strong></div>
                    <div class="summary-card"><span class="summary-label">Affected plannings</span><strong>{{ affectedPlannings }}</strong></div>
                    <div class="summary-card"><span class="summary-label">Correct destination</span><strong>{{ selectedReplacementDestination?.name || "Not selected" }}</strong></div>
                </div>

                <div class="replace-section">
                    <div class="section-title"><i class="bx bx-list-check"></i> Selected duplicate destinations</div>
                    <div class="selected-list">
                        <div v-for="row in selectedRows" :key="row.id" class="selected-item">
                            <div class="selected-left">
                                <div class="selected-avatar"><i class="bx bx-map-pin"></i></div>
                                <div><strong>#{{ row.id }} - {{ row.name }}</strong><span>{{ row.city || "-" }} · {{ row.country || "-" }} · {{ row.destination_plannings_count || 0 }} planning(s)</span></div>
                            </div>
                            <div class="selected-badge">Will be merged</div>
                        </div>
                    </div>
                </div>

                <div class="replace-section mt-4">
                    <label class="section-title mb-2"><i class="bx bx-map"></i> Replace in plannings with this correct Destination</label>
                    <select v-model="replacementDestinationId" class="form-select destination-select">
                        <option value="">-- Select Destination --</option>
                        <option v-for="destination in replacementOptions" :key="destination.id" :value="destination.id">#{{ destination.id }} - {{ destination.name }}{{ destination.city ? ` · ${destination.city}` : '' }} ({{ destination.destination_plannings_count || 0 }} plannings)</option>
                    </select>
                </div>

                <div v-if="selectedReplacementDestination" class="final-preview mt-4"><strong>Final preview:</strong> {{ selectedRows.length }} destination(s) and {{ affectedPlannings }} planning(s) will be merged into <span>{{ selectedReplacementDestination.name }}</span>.</div>

                <div class="replace-modal-actions">
                    <button class="btn btn-light btn-cancel-replace" @click="showReplaceModal = false">Cancel</button>
                    <button class="btn btn-confirm-replace" @click="submitReplace"><i class="bx bx-check-circle me-2"></i> Continue to confirmation</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.destinations-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(225, 29, 72, 0.1), transparent 24%),
        radial-gradient(circle at top right, rgba(249, 115, 22, 0.08), transparent 22%),
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
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18), transparent 25%),
        radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.12), transparent 25%);
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

.btn-add-destination {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-add-destination:hover {
    transform: translateY(-2px);
    color: #7f1d1d;
    background: #fff;
}

.hero-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.btn-replace-destination { border: 0; border-radius: 16px; padding: 12px 18px; color: #fff; background: rgba(255,255,255,.16); backdrop-filter: blur(8px); font-weight: 800; }
.btn-replace-destination:hover { color: #fff; background: rgba(255,255,255,.24); transform: translateY(-2px); }
.selected-count { display: inline-grid; place-items: center; min-width: 24px; height: 24px; margin-left: 4px; padding: 0 7px; border-radius: 999px; color: #be123c; background: #fff; font-size: .76rem; font-weight: 900; }

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
.custom-table tbody tr.row-selected { background: rgba(225,29,72,.06); }
.select-col { width: 58px; text-align: center; }
.destination-check { width: 19px; height: 19px; cursor: pointer; accent-color: #c1121f; }

.id-badge,
.type-badge {
    display: inline-flex;
    padding: 8px 12px;
    border-radius: 14px;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    font-weight: 800;
}

.destination-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.destination-avatar {
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

.destination-name {
    font-weight: 800;
    color: #0f172a;
}

.destination-id {
    font-size: 0.82rem;
    color: #94a3b8;
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

.btn-action-edit {
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
}

.btn-action-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.btn-action-edit:hover,
.btn-action-delete:hover,
.btn-search:hover {
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

.replace-modal-backdrop { position: fixed; z-index: 2000; inset: 0; display: flex; align-items: center; justify-content: center; padding: 24px; background: rgba(15,23,42,.68); backdrop-filter: blur(9px); }
.replace-modal { width: min(860px, 100%); max-height: calc(100vh - 48px); overflow-y: auto; border: 1px solid rgba(255,255,255,.8); border-radius: 26px; padding: 24px; background: linear-gradient(135deg,#fff,#f8fafc); box-shadow: 0 30px 80px rgba(15,23,42,.35); }
.replace-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 20px; }
.modal-icon-title { display: flex; align-items: center; gap: 14px; }
.modal-icon { display: grid; place-items: center; flex: 0 0 50px; height: 50px; border-radius: 16px; color: #fff; background: linear-gradient(135deg,#be123c,#ea580c); font-size: 1.45rem; box-shadow: 0 10px 22px rgba(190,18,60,.2); }
.replace-modal-header h4 { color: #0f172a; font-weight: 900; }
.replace-modal-header p { color: #64748b; }
.btn-close-custom { display: grid; place-items: center; width: 40px; height: 40px; border: 1px solid #e2e8f0; border-radius: 12px; color: #64748b; background: #fff; font-size: 1.25rem; }
.warning-box { display: flex; gap: 12px; padding: 14px; border: 1px solid #fed7aa; border-radius: 16px; color: #9a3412; background: #fff7ed; }
.warning-icon { font-size: 1.35rem; }
.warning-box h6 { font-weight: 900; }.warning-box p { font-size: .85rem; }
.summary-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 12px; }
.summary-card { padding: 14px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; }
.summary-label,.summary-card strong { display: block; }.summary-label { color: #64748b; font-size: .7rem; font-weight: 850; text-transform: uppercase; }.summary-card strong { margin-top: 5px; color: #0f172a; font-size: 1.05rem; font-weight: 900; }
.replace-section { padding: 16px; border: 1px solid #e2e8f0; border-radius: 18px; background: rgba(255,255,255,.85); }
.section-title { display: flex; align-items: center; gap: 7px; color: #334155; font-size: .82rem; font-weight: 900; }.section-title i { color: #be123c; }
.selected-list { display: grid; gap: 8px; max-height: 230px; margin-top: 12px; overflow-y: auto; }
.selected-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px; border: 1px solid #edf2f7; border-radius: 14px; background: #f8fafc; }
.selected-left { display: flex; align-items: center; gap: 10px; min-width: 0; }.selected-left strong,.selected-left span { display: block; }.selected-left strong { color: #172554; }.selected-left span { color: #64748b; font-size: .75rem; }
.selected-avatar { display: grid; place-items: center; flex: 0 0 38px; height: 38px; border-radius: 12px; color: #fff; background: linear-gradient(135deg,#be123c,#f97316); }
.selected-badge { padding: 5px 8px; border-radius: 999px; color: #9f1239; background: #ffe4e6; font-size: .68rem; font-weight: 900; white-space: nowrap; }
.destination-select { min-height: 48px; border-radius: 13px; border-color: #cbd5e1; }
.final-preview { padding: 14px; border: 1px solid #bbf7d0; border-radius: 15px; color: #166534; background: #f0fdf4; }.final-preview span { font-weight: 900; }
.replace-modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
.btn-cancel-replace,.btn-confirm-replace { min-height: 44px; border-radius: 12px; padding: 9px 16px; font-weight: 850; }.btn-confirm-replace { border: 0; color: #fff; background: linear-gradient(135deg,#be123c,#e11d48); }.btn-confirm-replace:hover { color: #fff; transform: translateY(-1px); }

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

    .notes-box {
        max-width: 170px;
    }

    .btn-action-edit span,
    .btn-action-delete span {
        display: none;
    }

    .replace-modal-backdrop { align-items: flex-end; padding: 0; }
    .replace-modal { width: 100%; max-height: 94vh; border-radius: 24px 24px 0 0; padding: 18px; }
    .summary-grid { grid-template-columns: 1fr; }
    .replace-modal-actions { flex-direction: column-reverse; }
    .replace-modal-actions button { width: 100%; }
}
</style>
