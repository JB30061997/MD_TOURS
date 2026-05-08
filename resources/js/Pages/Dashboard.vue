<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, reactive } from "vue";
import VueApexCharts from "vue3-apexcharts";
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
    periodInfo: {
        type: Object,
        default: () => ({}),
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    topSupplierVehicules: {
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
    topDestinations: {
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
    planningAnalytics: {
        type: Array,
        default: () => [],
    },
});

const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const chartType = reactive({
    metric: "total_plannings",
});

const metricOptions = [
    {
        value: "total_plannings",
        label: "Nombre de plannings",
        icon: "bx-calendar-check",
        suffix: "",
    },
    {
        value: "total_budget",
        label: "Budget",
        icon: "bx-wallet",
        suffix: " MAD",
    },
    {
        value: "total_supplier_price",
        label: "Prix fournisseur",
        icon: "bx-buildings",
        suffix: " MAD",
    },
    {
        value: "gross_margin",
        label: "Marge brute",
        icon: "bx-line-chart",
        suffix: " MAD",
    },
    {
        value: "total_clients",
        label: "Clients affectés",
        icon: "bx-group",
        suffix: "",
    },
];

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

const formatMoney = (value) => {
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};

const selectedMetric = computed(() => {
    return (
        metricOptions.find((item) => item.value === chartType.metric) ||
        metricOptions[0]
    );
});

const analyticsLabels = computed(() => {
    return props.planningAnalytics.map((item) => item.label);
});

const analyticsSeries = computed(() => [
    {
        name: selectedMetric.value.label,
        data: props.planningAnalytics.map((item) =>
            Number(item[chartType.metric] || 0),
        ),
    },
]);

const selectedMetricTotal = computed(() => {
    return props.planningAnalytics.reduce(
        (sum, item) => sum + Number(item[chartType.metric] || 0),
        0,
    );
});

const analyticsChartOptions = computed(() => ({
    chart: {
        type: "bar",
        toolbar: { show: false },
        fontFamily: "Inter, system-ui, sans-serif",
    },
    plotOptions: {
        bar: {
            borderRadius: 10,
            columnWidth: "45%",
        },
    },
    dataLabels: {
        enabled: false,
    },
    xaxis: {
        categories: analyticsLabels.value,
        labels: {
            style: {
                fontWeight: 700,
                colors: "#64748b",
            },
        },
    },
    yaxis: {
        labels: {
            formatter: (value) => formatMoney(value),
            style: {
                fontWeight: 700,
                colors: "#64748b",
            },
        },
    },
    tooltip: {
        y: {
            formatter: (value) =>
                `${formatMoney(value)}${selectedMetric.value.suffix}`,
        },
    },
    colors: ["#dc2626"],
    grid: {
        borderColor: "#eef2f7",
        strokeDashArray: 5,
    },
}));

const maxTopSupplierVehicule = computed(() =>
    Math.max(...props.topSupplierVehicules.map((i) => i.total), 1),
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

const maxTopDestination = computed(() =>
    Math.max(...props.topDestinations.map((i) => i.total), 1),
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="page-content">
        <div class="container-fluid">
            <!-- HERO -->
            <div
                class="dashboard-hero card border-0 shadow-lg mb-4 overflow-hidden"
            >
                <div class="hero-overlay"></div>

                <div class="card-body p-4 p-lg-5 position-relative">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-xl-7">
                            <div class="hero-badge mb-3">
                                <i class="bx bx-shield-quarter"></i>
                                Pilotage global
                            </div>

                            <h1 class="dashboard-title mb-2">
                                Dashboard Exécutif version test
                            </h1>

                            <p class="dashboard-subtitle mb-4">
                                Vision globale des plannings, budgets,
                                fournisseurs véhicules, chauffeurs, guides,
                                services et destinations.
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

                            <div
                                v-if="periodInfo?.is_auto_period"
                                class="auto-period-alert mt-4"
                            >
                                <i class="bx bx-info-circle"></i>
                                Période automatique :
                                {{ periodInfo.period_label }}
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
                                        <label class="form-label filter-label">
                                            Du
                                        </label>
                                        <input
                                            v-model="filterForm.date_from"
                                            type="date"
                                            class="form-control modern-input"
                                        />
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label filter-label">
                                            Au
                                        </label>
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
                                            <i
                                                class="bx bx-filter-alt me-1"
                                            ></i>
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

            <!-- KPIS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                    <div
                        class="metric-card metric-red card border-0 shadow-sm h-100"
                    >
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
                    <div
                        class="metric-card metric-blue card border-0 shadow-sm h-100"
                    >
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
                    <div
                        class="metric-card metric-purple card border-0 shadow-sm h-100"
                    >
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
                    <div
                        class="metric-card metric-green card border-0 shadow-sm h-100"
                    >
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

            <!-- FINANCE -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div
                        class="finance-card finance-budget card border-0 shadow-sm h-100"
                    >
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
                    <div
                        class="finance-card finance-cost card border-0 shadow-sm h-100"
                    >
                        <div class="card-body finance-card-body">
                            <div class="finance-icon">
                                <i class="bx bx-buildings"></i>
                            </div>
                            <div class="section-title text-white-50">
                                Coût fournisseurs
                            </div>
                            <div class="finance-value text-white">
                                {{ formatMoney(stats.total_supplier_price) }}
                                MAD
                            </div>
                            <div class="finance-note">
                                Montant fournisseur véhicule
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div
                        class="finance-card finance-margin card border-0 shadow-sm h-100"
                    >
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
                                Budget - coût fournisseur
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MINI STATS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-buildings"></i>
                                Fourn. véhicules
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_supplier_vehicules || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actifs /
                                {{ stats.total_supplier_vehicules || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-bus"></i>
                                Véhicules
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_vehicules || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actifs / {{ stats.total_vehicules || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-car"></i>
                                Chauffeurs
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
                    <div class="mini-stat-card card border-0 shadow-sm h-100">
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
                    <div class="mini-stat-card card border-0 shadow-sm h-100">
                        <div class="card-body mini-stat-card-body">
                            <div class="mini-stat-head">
                                <i class="bx bx-map-pin"></i>
                                Destinations
                            </div>
                            <div class="mini-stat-value">
                                {{ stats.active_destinations || 0 }}
                            </div>
                            <div class="mini-stat-sub">
                                Actives /
                                {{ stats.total_destinations || 0 }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DYNAMIC REPORT -->
            <div class="analytics-super-card card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="analytics-header">
                        <div>
                            <div class="panel-kicker">Rapport dynamique</div>
                            <h3 class="analytics-title">
                                Analyse détaillée des plannings
                            </h3>
                            <p class="analytics-subtitle">
                                Choisissez l’indicateur souhaité pour visualiser
                                les résultats jour par jour sur la période.
                            </p>
                        </div>

                        <div class="analytics-selector">
                            <label class="selector-label">Indicateur</label>
                            <select
                                v-model="chartType.metric"
                                class="form-select analytics-select"
                            >
                                <option
                                    v-for="option in metricOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="analytics-metric-preview">
                        <div class="metric-preview-icon">
                            <i :class="['bx', selectedMetric.icon]"></i>
                        </div>

                        <div>
                            <div class="metric-preview-label">
                                Total — {{ selectedMetric.label }}
                            </div>
                            <div class="metric-preview-value">
                                {{ formatMoney(selectedMetricTotal) }}
                                {{ selectedMetric.suffix }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="planningAnalytics.length"
                        class="analytics-chart-box"
                    >
                        <VueApexCharts
                            type="bar"
                            height="380"
                            :options="analyticsChartOptions"
                            :series="analyticsSeries"
                        />
                    </div>

                    <div v-else class="empty-state py-5">
                        Aucune donnée disponible pour cette période.
                    </div>
                </div>
            </div>

            <!-- TOP BLOCKS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">
                                        Top fournisseurs véhicules
                                    </div>
                                </div>
                                <div class="panel-icon red-soft">
                                    <i class="bx bx-building-house"></i>
                                </div>
                            </div>

                            <div
                                v-if="topSupplierVehicules.length"
                                class="panel-scroll-area"
                            >
                                <div
                                    v-for="(
                                        item, index
                                    ) in topSupplierVehicules"
                                    :key="index"
                                    class="rank-row"
                                >
                                    <div class="rank-name">{{ item.name }}</div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill red-fill"
                                            :style="{
                                                width:
                                                    (item.total /
                                                        maxTopSupplierVehicule) *
                                                        100 +
                                                    '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucun fournisseur véhicule actif.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
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

                            <div
                                v-if="topServices.length"
                                class="panel-scroll-area"
                            >
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
                                                width:
                                                    (item.total /
                                                        maxTopService) *
                                                        100 +
                                                    '%',
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
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">
                                        Top chauffeurs
                                    </div>
                                </div>
                                <div class="panel-icon green-soft">
                                    <i class="bx bx-car"></i>
                                </div>
                            </div>

                            <div
                                v-if="topDrivers.length"
                                class="panel-scroll-area"
                            >
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
                                                width:
                                                    (item.total /
                                                        maxTopDriver) *
                                                        100 +
                                                    '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucun chauffeur actif.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-4">
                                <div>
                                    <div class="panel-kicker">Classement</div>
                                    <div class="panel-title">
                                        Top destinations
                                    </div>
                                </div>
                                <div class="panel-icon purple-soft">
                                    <i class="bx bx-map"></i>
                                </div>
                            </div>

                            <div
                                v-if="topDestinations.length"
                                class="panel-scroll-area"
                            >
                                <div
                                    v-for="(item, index) in topDestinations"
                                    :key="index"
                                    class="rank-row"
                                >
                                    <div class="rank-name">{{ item.name }}</div>
                                    <div class="chart-track">
                                        <div
                                            class="chart-fill purple-fill"
                                            :style="{
                                                width:
                                                    (item.total /
                                                        maxTopDestination) *
                                                        100 +
                                                    '%',
                                            }"
                                        ></div>
                                    </div>
                                    <div class="chart-value">
                                        {{ item.total }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-state fixed-empty-state">
                                Aucune destination active.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECONDARY ANALYSIS -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
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

                            <div
                                v-if="planningPerDay.length"
                                class="panel-scroll-area"
                            >
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
                                                width:
                                                    (item.total /
                                                        Math.max(
                                                            ...planningPerDay.map(
                                                                (i) => i.total,
                                                            ),
                                                            1,
                                                        )) *
                                                        100 +
                                                    '%',
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
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
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

                            <div
                                v-if="budgetPerService.length"
                                class="panel-scroll-area"
                            >
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
                                                width:
                                                    (item.total /
                                                        Math.max(
                                                            ...budgetPerService.map(
                                                                (i) => i.total,
                                                            ),
                                                            1,
                                                        )) *
                                                        100 +
                                                    '%',
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
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.04),
            transparent 20%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(37, 99, 235, 0.05),
            transparent 20%
        ),
        #f4f6fb;
    min-height: 100vh;
}

.dashboard-hero {
    position: relative;
    background:
        radial-gradient(
            circle at 85% 15%,
            rgba(255, 255, 255, 0.22),
            transparent 18%
        ),
        radial-gradient(
            circle at 20% 120%,
            rgba(255, 255, 255, 0.12),
            transparent 28%
        ),
        linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    border-radius: 28px;
}

.hero-overlay {
    position: absolute;
    inset: 0;
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
    font-weight: 800;
    font-size: 0.9rem;
}

.dashboard-title {
    color: #fff;
    font-size: 2.2rem;
    font-weight: 950;
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
    font-weight: 800;
    font-size: 0.92rem;
}

.auto-period-alert {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #fff;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    padding: 10px 14px;
    border-radius: 14px;
    font-weight: 800;
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
    font-weight: 900;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-label {
    color: rgba(255, 255, 255, 0.78);
    font-weight: 800;
}

.modern-input {
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.96);
    min-height: 46px;
    box-shadow: none;
    font-weight: 700;
}

.action-btn {
    min-height: 44px;
    border-radius: 12px;
}

.metric-card,
.finance-card,
.mini-stat-card,
.dashboard-panel,
.analytics-super-card {
    border-radius: 24px;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.metric-card:hover,
.finance-card:hover,
.mini-stat-card:hover,
.dashboard-panel:hover,
.analytics-super-card:hover {
    transform: translateY(-3px);
}

.metric-card {
    min-height: 220px;
}

.metric-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.metric-red {
    background: linear-gradient(135deg, rgba(225, 29, 72, 0.1), #fff);
}

.metric-blue {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), #fff);
}

.metric-purple {
    background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), #fff);
}

.metric-green {
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.1), #fff);
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
    font-weight: 900;
    color: #6b7280;
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 999px;
    padding: 6px 10px;
}

.metric-label,
.section-title,
.panel-kicker {
    color: #6b7280;
    font-weight: 800;
    font-size: 0.92rem;
}

.metric-value {
    font-size: 2.2rem;
    font-weight: 950;
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
    min-height: 220px;
    color: #fff;
    overflow: hidden;
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

.finance-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
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
    font-size: 2.1rem;
    font-weight: 950;
    line-height: 1.1;
    margin-top: 8px;
}

.finance-note {
    opacity: 0.88;
    margin-top: 6px;
    font-size: 0.9rem;
}

.mini-stat-card {
    min-height: 140px;
}

.mini-stat-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.mini-stat-head {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-weight: 900;
    margin-bottom: 8px;
}

.mini-stat-head i {
    font-size: 1.2rem;
    color: #c1121f;
}

.mini-stat-value {
    font-size: 1.8rem;
    font-weight: 950;
    color: #111827;
    line-height: 1.1;
}

.mini-stat-sub {
    color: #9ca3af;
    font-size: 0.88rem;
    margin-top: 6px;
}

.analytics-super-card {
    background:
        radial-gradient(
            circle at top right,
            rgba(220, 38, 38, 0.08),
            transparent 28%
        ),
        radial-gradient(
            circle at bottom left,
            rgba(37, 99, 235, 0.07),
            transparent 30%
        ),
        #ffffff;
    overflow: hidden;
}

.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 22px;
    margin-bottom: 24px;
}

.analytics-title {
    font-size: 1.45rem;
    font-weight: 950;
    color: #111827;
    margin: 4px 0 6px;
}

.analytics-subtitle {
    color: #64748b;
    font-weight: 600;
    margin: 0;
}

.analytics-selector {
    min-width: 280px;
}

.selector-label {
    display: block;
    color: #7f1d1d;
    font-size: 0.82rem;
    font-weight: 950;
    margin-bottom: 8px;
}

.analytics-select {
    height: 48px;
    border-radius: 16px;
    border: 1px solid #fecaca;
    background: #fffafa;
    color: #991b1b;
    font-weight: 900;
    box-shadow: none;
}

.analytics-select:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
}

.analytics-metric-preview {
    display: flex;
    align-items: center;
    gap: 16px;
    background: linear-gradient(135deg, #fff1f2, #ffffff);
    border: 1px solid #ffe4e6;
    border-radius: 22px;
    padding: 18px 20px;
    margin-bottom: 24px;
}

.metric-preview-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    box-shadow: 0 14px 30px rgba(220, 38, 38, 0.22);
}

.metric-preview-label {
    color: #64748b;
    font-weight: 900;
    font-size: 0.9rem;
}

.metric-preview-value {
    color: #111827;
    font-size: 1.8rem;
    font-weight: 950;
    line-height: 1.1;
}

.analytics-chart-box {
    background: #ffffff;
    border: 1px solid #eef2f7;
    border-radius: 24px;
    padding: 16px;
}

.dashboard-panel {
    min-height: 370px;
    max-height: 370px;
}

.dashboard-panel-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
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
    font-weight: 950;
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

.panel-scroll-area {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 6px;
    min-height: 0;
}

.chart-row,
.rank-row {
    display: grid;
    grid-template-columns: 120px 1fr 90px;
    gap: 12px;
    align-items: center;
    margin-bottom: 14px;
}

.rank-row {
    grid-template-columns: 180px 1fr 60px;
}

.chart-label,
.rank-name {
    font-weight: 800;
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
    font-weight: 950;
    color: #111827;
    font-size: 0.9rem;
}

.empty-state {
    padding: 28px 0;
    color: #9ca3af;
    text-align: center;
}

.fixed-empty-state {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Scrollbar */
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

    .analytics-header {
        flex-direction: column;
    }

    .analytics-selector {
        width: 100%;
    }

    .metric-preview-value {
        font-size: 1.35rem;
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

    .dashboard-panel {
        min-height: auto;
        max-height: none;
    }

    .dashboard-panel-body {
        height: auto;
    }

    .panel-scroll-area {
        overflow: visible;
        max-height: none;
        padding-right: 0;
    }
}
</style>
