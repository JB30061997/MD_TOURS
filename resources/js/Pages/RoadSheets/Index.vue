<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import { formatDate } from "@/utils/dateFormat";

defineOptions({ layout: AppShell });

const props = defineProps({
    plannings: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
});

const state = reactive({
    search: props.filters?.search || "",
});

const rows = computed(() => props.plannings?.data || []);

const clientsText = (planning) => {
    const clients = planning.planning_clients || planning.planningClients || [];

    return (
        clients
            .map((item) => item.client?.full_name)
            .filter(Boolean)
            .slice(0, 3)
            .join(", ") || "-"
    );
};

const totalDistance = (planning) => {
    return (planning.road_sheet?.lines || []).reduce((sum, line) => {
        return sum + Number(line.distance || 0);
    }, 0);
};
const realDistance = (planning) =>
    Number(planning.road_sheet?.pre_service_km || 0) + totalDistance(planning);

const completedCount = computed(
    () => rows.value.filter((planning) => planning.road_sheet?.lines?.length).length,
);

const pendingCount = computed(() => rows.value.length - completedCount.value);

const visibleDistance = computed(() =>
    rows.value.reduce((sum, planning) => sum + totalDistance(planning), 0),
);

const vehicleLabel = (planning) =>
    planning.vehicule?.matricule ||
    planning.supplier_vehicule?.name ||
    planning.supplierVehicule?.name ||
    "-";

const applySearch = () => {
    router.get(
        route("road-sheets.index"),
        { search: state.search },
        { preserveState: true, replace: true },
    );
};

const resetSearch = () => {
    state.search = "";
    applySearch();
};
</script>

<template>
    <Head title="Road Sheets" />

    <div class="road-sheets-page">
        <div class="container-fluid py-4">
            <div class="hero-card">
                <div class="hero-copy">
                    <div class="hero-icon">
                        <i class="bx bx-trip"></i>
                    </div>
                    <div>
                        <p class="eyebrow">MD TOURS</p>
                        <h1>Fiches de route</h1>
                        <p>
                            Suivi des kilomètres, Jawaz, carburant et dépenses
                            liés à chaque planning.
                        </p>
                    </div>
                </div>

                <Link :href="route('plannings.index')" class="hero-btn">
                    <i class="bx bx-calendar me-2"></i>
                    Plannings
                </Link>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <span>Fiches visibles</span>
                    <strong>{{ rows.length }}</strong>
                </div>
                <div class="stat-card success">
                    <span>Renseignées</span>
                    <strong>{{ completedCount }}</strong>
                </div>
                <div class="stat-card warning">
                    <span>À compléter</span>
                    <strong>{{ pendingCount }}</strong>
                </div>
                <div class="stat-card dark">
                    <span>Distance visible</span>
                    <strong>{{ visibleDistance }} km</strong>
                </div>
            </div>

            <div class="toolbar-card">
                <div class="search-wrap">
                    <i class="bx bx-search"></i>
                    <input
                        v-model="state.search"
                        class="search-input"
                        type="text"
                        placeholder="Rechercher référence, chauffeur, client..."
                        @keyup.enter="applySearch"
                    />
                </div>
                <div class="toolbar-actions">
                    <button
                        type="button"
                        class="btn-primary"
                        @click="applySearch"
                    >
                        Recherche
                    </button>
                    <button type="button" class="btn-light" @click="resetSearch">
                        Reset
                    </button>
                </div>
            </div>

            <div class="table-card">
                <div class="table-title-row">
                    <div>
                        <h2>Liste des fiches</h2>
                        <p>
                            Ouvrez une fiche pour compléter les kilomètres,
                            Jawaz et autres frais.
                        </p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="road-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Driver</th>
                                <th>Vehicle</th>
                                <th>Kilométrage</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="planning in rows" :key="planning.id">
                                <td class="date-cell">
                                    {{ formatDate(planning.date_du) }}
                                </td>
                                <td class="reference-cell">
                                    <span>{{ planning.ref_dossier || "-" }}</span>
                                </td>
                                <td class="client-cell">
                                    {{ clientsText(planning) }}
                                </td>
                                <td class="service-cell">
                                    {{ planning.service?.designation || "-" }}
                                </td>
                                <td class="driver-cell">
                                    {{ planning.driver?.name || "-" }}
                                </td>
                                <td class="vehicle-cell">
                                    {{ vehicleLabel(planning) }}
                                </td>
                                <td class="distance-cell">
                                    <span>Réel : {{ realDistance(planning) }} km</span>
                                    <small>Avant service : {{ Number(planning.road_sheet?.pre_service_km || 0) }} km</small>
                                </td>
                                <td>
                                    <span
                                        class="status-pill"
                                        :class="{
                                            done:
                                                planning.road_sheet?.lines
                                                    ?.length,
                                        }"
                                    >
                                        {{
                                            planning.road_sheet?.lines?.length
                                                ? "Renseignée"
                                                : "À compléter"
                                        }}
                                    </span>
                                </td>
                                <td>
                                    <Link
                                        :href="
                                            route(
                                                'road-sheets.show',
                                                planning.id,
                                            )
                                        "
                                        class="open-btn"
                                    >
                                        <i class="bx bx-folder-open me-1"></i>
                                        Ouvrir
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!rows.length">
                                <td colspan="9" class="empty-cell">
                                    Aucune fiche trouvée.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="props.plannings?.links?.length"
                    class="pagination-row"
                >
                    <Link
                        v-for="link in props.plannings.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="page-link"
                        :class="{ active: link.active, disabled: !link.url }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.road-sheets-page {
    background:
        radial-gradient(circle at top left, rgba(193, 18, 31, 0.07), transparent 28%),
        #f6f7fb;
    min-height: 100vh;
}

.hero-card {
    background:
        linear-gradient(135deg, rgba(122, 6, 16, 0.97), rgba(22, 33, 84, 0.97)),
        linear-gradient(135deg, #78000b, #172554);
    color: #fff;
    border-radius: 24px;
    padding: 30px 34px;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.2);
    overflow: hidden;
    position: relative;
}

.hero-card::after {
    content: "";
    position: absolute;
    right: -90px;
    top: -140px;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    border: 48px solid rgba(255, 255, 255, 0.08);
}

.hero-copy {
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    z-index: 1;
}

.hero-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
    font-size: 30px;
}

.eyebrow {
    margin: 0 0 8px;
    font-weight: 900;
    letter-spacing: 0;
    color: rgba(255, 255, 255, 0.78);
}

.hero-card h1 {
    margin: 0;
    font-weight: 900;
    color: #fff;
    font-size: 34px;
}

.hero-card p:last-child {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.82);
    font-size: 16px;
}

.hero-btn,
.open-btn,
.btn-primary,
.btn-light {
    border: 0;
    border-radius: 14px;
    padding: 12px 18px;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    position: relative;
    z-index: 1;
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        background 0.18s ease;
}

.hero-btn,
.btn-primary,
.open-btn {
    background: #c1121f;
    color: #fff;
    box-shadow: 0 12px 24px rgba(193, 18, 31, 0.22);
}

.btn-light {
    background: #eef2f7;
    color: #334155;
}

.hero-btn:hover,
.open-btn:hover,
.btn-primary:hover {
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 16px 30px rgba(193, 18, 31, 0.28);
}

.btn-light:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin: 22px 0;
}

.stat-card {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
}

.stat-card span {
    display: block;
    color: #64748b;
    font-weight: 800;
    margin-bottom: 8px;
}

.stat-card strong {
    color: #0f172a;
    font-size: 28px;
    font-weight: 950;
}

.stat-card.success strong {
    color: #047857;
}

.stat-card.warning strong {
    color: #c2410c;
}

.stat-card.dark {
    background: #111827;
}

.stat-card.dark span {
    color: #cbd5e1;
}

.stat-card.dark strong {
    color: #fff;
}

.toolbar-card,
.table-card {
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 22px;
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
}

.toolbar-card {
    margin: 0 0 22px;
    padding: 14px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.search-wrap {
    flex: 1;
    min-width: 220px;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid #d8dee9;
    border-radius: 16px;
    padding: 0 14px;
    min-height: 56px;
    background: #fbfcfe;
}

.search-wrap i {
    color: #94a3b8;
    font-size: 22px;
}

.search-input {
    flex: 1;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: #111827;
    font-weight: 700;
}

.search-input::placeholder {
    color: #9ca3af;
}

.toolbar-actions {
    display: flex;
    gap: 10px;
}

.table-card {
    overflow: hidden;
}

.table-title-row {
    padding: 20px 22px;
    border-bottom: 1px solid #edf0f5;
}

.table-title-row h2 {
    margin: 0;
    color: #111827;
    font-size: 20px;
    font-weight: 950;
}

.table-title-row p {
    margin: 6px 0 0;
    color: #64748b;
    font-weight: 600;
}

.road-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.road-table th {
    background: #f8fafc;
    color: #7a0610;
    padding: 14px 16px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0;
    white-space: nowrap;
    border-bottom: 1px solid #edf0f5;
}

.road-table th:nth-child(1) {
    width: 116px;
}

.road-table th:nth-child(2) {
    width: 230px;
}

.road-table th:nth-child(3) {
    width: 310px;
}

.road-table th:nth-child(4) {
    width: 170px;
}

.road-table th:nth-child(5) {
    width: 130px;
}

.road-table th:nth-child(6) {
    width: 130px;
}

.road-table th:nth-child(7) {
    width: 120px;
}

.road-table th:nth-child(8) {
    width: 150px;
}

.road-table th:nth-child(9) {
    width: 120px;
}

.road-table td {
    padding: 16px;
    border-top: 1px solid #edf0f5;
    color: #1f2937;
    vertical-align: middle;
    font-weight: 650;
}

.road-table tbody tr {
    transition:
        background 0.16s ease,
        transform 0.16s ease;
}

.road-table tbody tr:hover {
    background: #fbfcff;
}

.date-cell {
    white-space: nowrap;
    color: #475569;
    font-variant-numeric: tabular-nums;
}

.reference-cell span {
    color: #172033;
    font-weight: 950;
    line-height: 1.35;
}

.client-cell,
.service-cell {
    color: #334155;
    line-height: 1.45;
}

.driver-cell,
.vehicle-cell {
    color: #475569;
    white-space: normal;
}

.distance-cell span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 76px;
    border-radius: 999px;
    padding: 7px 10px;
    background: #eef2ff;
    color: #1e3a8a;
    font-weight: 900;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 9px 13px;
    background: #fff7ed;
    color: #c2410c;
    font-weight: 900;
    line-height: 1.1;
    min-width: 116px;
}

.status-pill.done {
    background: #ecfdf5;
    color: #047857;
}

.empty-cell {
    text-align: center;
    color: #64748b;
    padding: 42px;
    font-weight: 800;
}

.pagination-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 18px 22px;
    border-top: 1px solid #edf0f5;
}

.page-link {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 8px 12px;
    color: #334155;
    text-decoration: none;
}

.page-link.active {
    background: #c1121f;
    color: #fff;
}

.page-link.disabled {
    opacity: 0.45;
    pointer-events: none;
}

@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .hero-card,
    .toolbar-card {
        flex-direction: column;
        align-items: stretch;
    }

    .hero-copy {
        align-items: flex-start;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .toolbar-actions {
        width: 100%;
    }

    .toolbar-actions button {
        flex: 1;
    }
}
</style>
