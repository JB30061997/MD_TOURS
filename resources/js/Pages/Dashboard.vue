<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, reactive } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            date_from: "",
            date_to: "",
        }),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    topSuppliers: {
        type: Array,
        default: () => [],
    },
    topServices: {
        type: Array,
        default: () => [],
    },
    topDrivers: {
        type: Array,
        default: () => [],
    },
    topGuides: {
        type: Array,
        default: () => [],
    },
    planningPerDay: {
        type: Array,
        default: () => [],
    },
    budgetPerService: {
        type: Array,
        default: () => [],
    },
    recentPlannings: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const applyFilters = () => {
    router.get(
        "/dashboard",
        { ...filterForm },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
        .toISOString()
        .slice(0, 10);
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)
        .toISOString()
        .slice(0, 10);

    filterForm.date_from = firstDay;
    filterForm.date_to = lastDay;

    applyFilters();
};

const maxPlanningDay = computed(() => {
    return Math.max(...props.planningPerDay.map((item) => item.total), 1);
});

const maxBudgetService = computed(() => {
    return Math.max(...props.budgetPerService.map((item) => item.total), 1);
});

const maxTopSupplier = computed(() =>
    Math.max(...props.topSuppliers.map((i) => i.total), 1),
);
const maxTopService = computed(() =>
    Math.max(...props.topServices.map((i) => i.total), 1),
);
const maxTopDriver = computed(() =>
    Math.max(...props.topDrivers.map((i) => i.total), 1),
);
const maxTopGuide = computed(() =>
    Math.max(...props.topGuides.map((i) => i.total), 1),
);

const formatMoney = (value) => {
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};

const formatDateOnly = (value) => {
    if (!value) return "-";

    try {
        return new Date(value).toLocaleDateString("fr-FR");
    } catch (e) {
        return String(value).split("T")[0] || "-";
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="page-content">
        <div class="container-fluid">
            <!-- HERO -->
            <div class="dashboard-hero card border-0 shadow-lg mb-4 overflow-hidden">
                <div class="hero-overlay"></div>

                <div class="card-body p-4 p-lg-5 position-relative">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-xl-7">
                            <div class="hero-badge mb-3">
                                <i class="bx bx-shield-quarter"></i>
                                Pilotage global
                            </div>

                            <h1 class="dashboard-title mb-2">
                                Dashboard Exécutif
                            </h1>

                            <p class="dashboard-subtitle mb-4">
                                Vision générale et instantanée des plannings,
                                budgets, fournisseurs, chauffeurs, guides et
                                services.
                            </p>

                            <div class="hero-stats">
                                <div class="hero-stat-pill">
                                    <i class="bx bx-calendar-check"></i>
                                    {{ stats.total_plannings || 0 }} plannings
                                </div>
                                <div class="hero-stat-pill">
                                    <i class="bx bx-wallet"></i>
                                    {{ formatMoney(stats.total_budget) }} MAD
                                </div>
                                <div class="hero-stat-pill">
                                    <i class="bx bx-group"></i>
                                    {{ stats.assigned_clients || 0 }} clients
                                    affectés
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-5">
                            <div class="filter-panel">
                                <div class="filter-panel-title">
                                    <i class="bx bx-slider-alt"></i>
                                    Filtres de période
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label filter-label">Du</label>
                                        <input
                                            v-model="filterForm.date_from"
                                            type="date"
                                            class="form-control modern-input"
                                        />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label filter-label">Au</label>
                                        <input
                                            v-model="filterForm.date_to"
                                            type="date"
                                            class="form-control modern-input"
                                        />
                                    </div>

                                    <div class="col-12 d-flex gap-2">
                                        <button
                                            class="btn btn-light fw-semibold flex-fill action-btn"
                                            @click="applyFilters"
                                        >
                                            <i class="bx bx-filter-alt me-1"></i>
                                            Filtrer
                                        </button>

                                        <button
                                            class="btn btn-outline-light fw-semibold flex-fill action-btn"
                                            @click="resetFilters"
                                        >
                                            <i class="bx bx-refresh me-1"></i>
                                            Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- MAIN KPIS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="metric-card metric-card-fixed metric-red card border-0 shadow-sm h-100">
                        <div class="card-body metric-card-body">
                            <div class="metric-top">
                                <div class="metric-icon">
                                    <i class="bx bx-calendar-star"></i>
                                </div>
                                <div class="metric-chip">Période</div>
                            </div>
                            <div class="metric-label">Total plannings</div>
                            <div class="metric-value">
                                {{ stats.total_plannings || 0 }}
                            </div>
                            <div class="metric-foot">
                                Tous les plannings filtrés
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="metric-card metric-card-fixed metric-blue card border-0 shadow-sm h-100">
                        <div class="card-body metric-card-body">
                            <div class="metric-top">
                                <div class="metric-icon">
                                    <i class="bx bx-sun"></i>
                                </div>
                                <div class="metric-chip">Today</div>
                            </div>
                            <div class="metric-label">Aujourd’hui</div>
                            <div class="metric-value">
                                {{ stats.today_plannings || 0 }}
                            </div>
                            <div class="metric-foot">Plannings du jour</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="metric-card metric-card-fixed metric-purple card border-0 shadow-sm h-100">
                        <div class="card-body metric-card-body">
                            <div class="metric-top">
                                <div class="metric-icon">
                                    <i class="bx bx-time-five"></i>
                                </div>
                                <div class="metric-chip">Future</div>
                            </div>
                            <div class="metric-label">À venir</div>
                            <div class="metric-value">
                                {{ stats.upcoming_plannings || 0 }}
                            </div>
                            <div class="metric-foot">Plannings futurs</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div class="metric-card metric-card-fixed metric-green card border-0 shadow-sm h-100">
                        <div class="card-body metric-card-body">
                            <div class="metric-top">
                                <div class="metric-icon">
                                    <i class="bx bx-user-plus"></i>
                                </div>
                                <div class="metric-chip">Clients</div>
                            </div>
                            <div class="metric-label">Clients affectés</div>
                            <div class="metric-value">
                                {{ stats.assigned_clients || 0 }}
                            </div>
                            <div class="metric-foot">Liés aux plannings</div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- FINANCIAL SECTION -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div class="finance-card finance-card-fixed finance-budget card border-0 shadow-sm h-100">
                        <div class="card-body finance-card-body">
                            <div class="finance-icon">
                                <i class="bx bx-wallet-alt"></i>
                            </div>
                            <div class="section-title text-white-50">
                                Budget total
                            </div>
                            <div class="finance-value text-white">
                                {{ formatMoney(stats.total_budget) }} MAD
                            </div>
                            <div class="finance-note">
                                Montant global estimé
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="finance-card finance-card-fixed finance-cost card border-0 shadow-sm h-100">
                        <div class="card-body finance-card-body">
                            <div class="finance-icon">
                                <i class="bx bx-buildings"></i>
                            </div>
                            <div class="section-title text-white-50">
                                Coût fournisseurs
                            </div>
                            <div class="finance-value text-white">
                                {{ formatMoney(stats.total_supplier_price) }} MAD
                            </div>
                            <div class="finance-note">
                                Montant versé / engagé
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="finance-card finance-card-fixed finance-margin card border-0 shadow-sm h-100">
                        <div class="card-body finance-card-body">
                            <div class="finance-icon">
                                <i class="bx bx-line-chart"></i>
                            </div>
                            <div class="section-title text-white-50">
                                Marge brute
                            </div>
                            <div class="finance-value text-white">
                                {{ formatMoney(stats.gross_margin) }} MAD
                            </div>
                            <div class="finance-note">
                                Budget - Coût fournisseurs
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- ACTIVITY CARDS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-stat-card-fixed card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-briefcase-alt-2"></i>
                                Suppliers
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_suppliers || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actifs / {{ stats.total_suppliers || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-stat-card-fixed card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-car"></i>
                                Drivers
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_drivers || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actifs / {{ stats.total_drivers || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-stat-card-fixed card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-id-card"></i>
                                Guides
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_guides || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actifs / {{ stats.total_guides || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-stat-card-fixed card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-package"></i>
                                Services
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_services || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actifs / {{ stats.total_services || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-stat-card-fixed card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-group"></i>
                                Clients
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.total_clients || 0 }}
                            </div>
                            <div class="mini-stat-sub">Base enregistrée</div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- CHARTS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel dashboard-panel-fixed card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">
                                        Analyse temporelle
                                    </div>
                                    <div class="panel-title">
                                        Plannings par jour
                                    </div>
                                </div>
                                <div class="panel-icon red-soft">
                                    <i class="bx bx-bar-chart-alt-2"></i>
                                </div>
                            </div>

                            <div v-if="planningPerDay.length" class="panel-scroll-area">
                                <div
                                    v-for="(item, index) in planningPerDay"
                                    :key="index"
                                    class="chart-row"
                                >
                                    <div class="chart-label">
                                        {{ item.label }}
                                    </div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill red-fill"
                                            :style="{
                                                width: (item.total / maxPlanningDay) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucune donnée sur la période.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel dashboard-panel-fixed card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">
                                        Analyse financière
                                    </div>
                                    <div class="panel-title">
                                        Budget par service
                                    </div>
                                </div>
                                <div class="panel-icon blue-soft">
                                    <i class="bx bx-pie-chart-alt-2"></i>
                                </div>
                            </div>

                            <div v-if="budgetPerService.length" class="panel-scroll-area">
                                <div
                                    v-for="(item, index) in budgetPerService"
                                    :key="index"
                                    class="chart-row"
                                >
                                    <div class="chart-label text-truncate">
                                        {{ item.label }}
                                    </div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill blue-fill"
                                            :style="{
                                                width: (item.total / maxBudgetService) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ formatMoney(item.total) }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucune donnée budgétaire.
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- TOP BLOCKS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel dashboard-panel-fixed card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">Top suppliers</div>
                                </div>
                                <div class="panel-icon red-soft">
                                    <i class="bx bx-building-house"></i>
                                </div>
                            </div>

                            <div v-if="topSuppliers.length" class="panel-scroll-area">
                                <div
                                    v-for="(item, index) in topSuppliers"
                                    :key="index"
                                    class="rank-row"
                                >
                                    <div class="rank-name">{{ item.name }}</div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill red-fill"
                                            :style="{
                                                width: (item.total / maxTopSupplier) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucun supplier actif.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel dashboard-panel-fixed card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">Top services</div>
                                </div>
                                <div class="panel-icon gold-soft">
                                    <i class="bx bx-award"></i>
                                </div>
                            </div>

                            <div v-if="topServices.length" class="panel-scroll-area">
                                <div
                                    v-for="(item, index) in topServices"
                                    :key="index"
                                    class="rank-row"
                                >
                                    <div class="rank-name">{{ item.name }}</div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill gold-fill"
                                            :style="{
                                                width: (item.total / maxTopService) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucun service actif.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel dashboard-panel-fixed card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">Top drivers</div>
                                </div>
                                <div class="panel-icon green-soft">
                                    <i class="bx bx-car-front"></i>
                                </div>
                            </div>

                            <div v-if="topDrivers.length" class="panel-scroll-area">
                                <div
                                    v-for="(item, index) in topDrivers"
                                    :key="index"
                                    class="rank-row"
                                >
                                    <div class="rank-name">{{ item.name }}</div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill green-fill"
                                            :style="{
                                                width: (item.total / maxTopDriver) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucun driver actif.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel dashboard-panel-fixed card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">Top guides</div>
                                </div>
                                <div class="panel-icon purple-soft">
                                    <i class="bx bx-user-voice"></i>
                                </div>
                            </div>

                            <div v-if="topGuides.length" class="panel-scroll-area">
                                <div
                                    v-for="(item, index) in topGuides"
                                    :key="index"
                                    class="rank-row"
                                >
                                    <div class="rank-name">{{ item.name }}</div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill purple-fill"
                                            :style="{
                                                width: (item.total / maxTopGuide) * 100 + '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucun guide actif.
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- RECENT PLANNINGS -->
            <div class="dashboard-panel dashboard-table-panel dashboard-table-panel-fixed card border-0 shadow-sm">
                <div class="card-body dashboard-panel-body">
                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"
                    >
                        <div>
                            <div class="panel-kicker">Suivi opérationnel</div>
                            <div class="panel-title mb-0">
                                Derniers plannings
                            </div>
                        </div>

                        <button
                            class="btn btn-danger-soft btn-sm"
                            @click="$inertia.visit('/plannings')"
                        >
                            <i class="bx bx-right-arrow-alt me-1"></i>
                            Voir la liste complète
                        </button>
                    </div>

                    <div class="table-responsive panel-scroll-area table-scroll-area">
                        <table class="table align-middle table-hover dashboard-table">
                            <thead>
                                <tr>
                                    <th>DU</th>
                                    <th>Réf</th>
                                    <th>Départ</th>
                                    <th>Destination</th>
                                    <th>Supplier</th>
                                    <th>Driver</th>
                                    <th>Guide</th>
                                    <th>Service</th>
                                    <th>Budget</th>
                                    <th>Prix four.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="planning in recentPlannings"
                                    :key="planning.id"
                                >
                                    <td>{{ formatDateOnly(planning.date_du) }}</td>
                                    <td>
                                        <span class="ref-pill">
                                            {{ planning.ref_dossier || "-" }}
                                        </span>
                                    </td>
                                    <td>{{ planning.point_depart || "-" }}</td>
                                    <td>{{ planning.destination || "-" }}</td>
                                    <td>{{ planning?.supplier?.name || "-" }}</td>
                                    <td>{{ planning?.driver?.name || "-" }}</td>
                                    <td>{{ planning?.guide?.name || "-" }}</td>
                                    <td>{{ planning?.service?.designation || "-" }}</td>
                                    <td class="fw-bold text-success">
                                        {{ formatMoney(planning.budget) }}
                                    </td>
                                    <td class="fw-bold text-danger">
                                        {{ formatMoney(planning.supplier_price) }}
                                    </td>
                                </tr>

                                <tr v-if="!recentPlannings.length">
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        Aucun planning disponible.
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
    background:
        radial-gradient(circle at top left, rgba(225, 29, 72, 0.04), transparent 20%),
        radial-gradient(circle at bottom right, rgba(37, 99, 235, 0.05), transparent 20%),
        #f4f6fb;
    min-height: 100vh;
}

.dashboard-hero {
    position: relative;
    background:
        radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.22), transparent 18%),
        radial-gradient(circle at 20% 120%, rgba(255, 255, 255, 0.12), transparent 28%),
        linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    border-radius: 28px;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.02),
        rgba(255, 255, 255, 0.01)
    );
    pointer-events: none;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.13);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 700;
    font-size: 0.9rem;
}

.dashboard-title {
    color: #fff;
    font-size: 2.2rem;
    font-weight: 900;
    letter-spacing: 0.3px;
}

.dashboard-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    max-width: 720px;
}

.hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #fff;
    font-weight: 700;
    font-size: 0.92rem;
}

.filter-panel {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 22px;
    padding: 18px;
    backdrop-filter: blur(10px);
}

.filter-panel-title {
    color: #fff;
    font-weight: 800;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-label {
    color: rgba(255, 255, 255, 0.75);
    font-weight: 700;
}

.modern-input {
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.96);
    min-height: 46px;
    box-shadow: none;
}

.action-btn {
    min-height: 44px;
    border-radius: 12px;
}

.metric-card {
    border-radius: 22px;
    overflow: hidden;
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-card:hover,
.dashboard-panel:hover,
.mini-stat-card:hover,
.finance-card:hover {
    transform: translateY(-3px);
}

.metric-red {
    background: linear-gradient(
        135deg,
        rgba(225, 29, 72, 0.1),
        rgba(255, 255, 255, 1)
    );
}

.metric-blue {
    background: linear-gradient(
        135deg,
        rgba(37, 99, 235, 0.1),
        rgba(255, 255, 255, 1)
    );
}

.metric-purple {
    background: linear-gradient(
        135deg,
        rgba(124, 58, 237, 0.1),
        rgba(255, 255, 255, 1)
    );
}

.metric-green {
    background: linear-gradient(
        135deg,
        rgba(22, 163, 74, 0.1),
        rgba(255, 255, 255, 1)
    );
}

.metric-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.metric-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #fff;
    background: linear-gradient(135deg, #111827, #374151);
    box-shadow: 0 12px 24px rgba(17, 24, 39, 0.18);
}

.metric-chip {
    font-size: 0.78rem;
    font-weight: 800;
    color: #6b7280;
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 999px;
    padding: 6px 10px;
}

.metric-label,
.mini-label,
.section-title,
.panel-kicker {
    color: #6b7280;
    font-weight: 700;
    font-size: 0.92rem;
}

.metric-value {
    font-size: 2.2rem;
    font-weight: 900;
    color: #111827;
    line-height: 1.1;
    margin-top: 8px;
}

.metric-foot {
    color: #9ca3af;
    margin-top: 6px;
    font-size: 0.88rem;
}

.finance-card {
    border-radius: 22px;
    overflow: hidden;
    color: #fff;
}

.finance-budget {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
}

.finance-cost {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
}

.finance-margin {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
}

.finance-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin-bottom: 16px;
}

.finance-value {
    font-size: 2.2rem;
    font-weight: 900;
    line-height: 1.1;
    margin-top: 8px;
}

.finance-note {
    opacity: 0.88;
    margin-top: 6px;
    font-size: 0.9rem;
}

.mini-stat-card,
.dashboard-panel {
    border-radius: 22px;
}

.mini-stat-head {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-weight: 800;
    margin-bottom: 8px;
}

.mini-stat-head i {
    font-size: 1.2rem;
    color: #c1121f;
}

.mini-stat-value {
    font-size: 1.8rem;
    font-weight: 900;
    color: #111827;
    line-height: 1.1;
}

.mini-stat-sub {
    color: #9ca3af;
    font-size: 0.88rem;
    margin-top: 6px;
}

.panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.panel-title {
    color: #111827;
    font-weight: 900;
    font-size: 1.15rem;
}

.panel-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.red-soft {
    background: rgba(225, 29, 72, 0.12);
    color: #be123c;
}

.blue-soft {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.green-soft {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.purple-soft {
    background: rgba(124, 58, 237, 0.12);
    color: #6d28d9;
}

.gold-soft {
    background: rgba(245, 158, 11, 0.14);
    color: #d97706;
}

.chart-row,
.rank-row {
    display: grid;
    grid-template-columns: 90px 1fr 78px;
    gap: 12px;
    align-items: center;
    margin-bottom: 14px;
}

.rank-row {
    grid-template-columns: 170px 1fr 60px;
}

.chart-label,
.rank-name {
    font-weight: 700;
    color: #374151;
    font-size: 0.92rem;
}

.chart-track {
    width: 100%;
    height: 12px;
    background: #edf2f7;
    border-radius: 999px;
    overflow: hidden;
}

.chart-fill {
    height: 100%;
    border-radius: 999px;
    box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.12);
}

.red-fill {
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
}

.blue-fill {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.green-fill {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
}

.purple-fill {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
}

.gold-fill {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.chart-value {
    text-align: right;
    font-weight: 900;
    color: #111827;
    font-size: 0.9rem;
}

.empty-state {
    padding: 28px 0;
    color: #9ca3af;
    text-align: center;
}

.btn-danger-soft {
    background: rgba(193, 18, 31, 0.08);
    color: #b91422;
    border: 1px solid rgba(193, 18, 31, 0.15);
    border-radius: 12px;
    font-weight: 700;
    padding: 8px 14px;
}

.btn-danger-soft:hover {
    background: rgba(193, 18, 31, 0.14);
    color: #8f0a14;
}

.dashboard-table thead th {
    background: linear-gradient(180deg, #fff4f5 0%, #fff0f2 100%);
    color: #8f111c;
    font-weight: 900;
    border-bottom: 1px solid #f0d7da;
    padding: 15px 14px;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 2;
}

.dashboard-table tbody td {
    padding: 14px;
    border-color: #edf0f5;
    background: #fff;
    vertical-align: middle;
}

.dashboard-table tbody tr:hover td {
    background: #fffafb;
}

.ref-pill {
    display: inline-block;
    background: rgba(29, 78, 216, 0.08);
    color: #1d4ed8;
    border: 1px solid rgba(29, 78, 216, 0.12);
    border-radius: 999px;
    padding: 6px 10px;
    font-weight: 800;
    font-size: 0.83rem;
}

/* FIXED HEIGHTS */
.metric-card-fixed {
    min-height: 220px;
    max-height: 220px;
}

.metric-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.finance-card-fixed {
    min-height: 220px;
    max-height: 220px;
}

.finance-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.mini-stat-card-fixed {
    min-height: 140px;
    max-height: 140px;
}

.mini-stat-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.dashboard-panel-fixed {
    min-height: 370px;
    max-height: 370px;
}

.dashboard-table-panel-fixed {
    min-height: 470px;
    max-height: 470px;
}

.dashboard-panel-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.panel-scroll-area {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 6px;
    min-height: 0;
}

.table-scroll-area {
    padding-right: 0;
}

.fixed-empty-state {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.panel-scroll-area .rank-row:last-child,
.panel-scroll-area .chart-row:last-child {
    margin-bottom: 0;
}

/* SCROLLBAR */
.panel-scroll-area::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.panel-scroll-area::-webkit-scrollbar-track {
    background: #eef2f7;
    border-radius: 999px;
}

.panel-scroll-area::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.panel-scroll-area::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

@media (max-width: 991.98px) {
    .dashboard-title {
        font-size: 1.7rem;
    }

    .chart-row,
    .rank-row {
        grid-template-columns: 1fr;
    }

    .chart-value {
        text-align: left;
    }

    .hero-stats {
        flex-direction: column;
        align-items: flex-start;
    }

    .metric-card-fixed,
    .finance-card-fixed,
    .mini-stat-card-fixed,
    .dashboard-panel-fixed,
    .dashboard-table-panel-fixed {
        min-height: auto;
        max-height: none;
    }

    .dashboard-panel-body,
    .metric-card-body,
    .finance-card-body,
    .mini-stat-card-body {
        height: auto;
    }

    .panel-scroll-area {
        overflow: visible;
        max-height: none;
        padding-right: 0;
    }

    .dashboard-table thead th {
        position: static;
    }
}
</style>