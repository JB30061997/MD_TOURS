<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from "vue";
import VueApexCharts from "vue3-apexcharts";
import AppShell from "@/Layouts/AppShell.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import Swal from "sweetalert2";
import axios from "axios";
import { toastError, toastSuccess } from "@/utils/alert";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            date_from: "",
            date_to: "",
            supplier_vehicule_id: "",
        }),
    },
    supplierVehicules: {
        type: Array,
        default: () => [],
    },
    supplierVehicleInvoiceOptions: {
        type: Object,
        default: () => ({}),
    },
    canLinkSupplierInvoice: {
        type: Boolean,
        default: false,
    },
    services: {
        type: Array,
        default: () => [],
    },
    canEditPlanningService: {
        type: Boolean,
        default: false,
    },
    canAssignPlanningSupplier: {
        type: Boolean,
        default: false,
    },
    canManageMissingSuppliers: {
        type: Boolean,
        default: false,
    },
    missingServicePlanningsCount: {
        type: Number,
        default: 0,
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
    planningAnalyticsHierarchy: {
        type: Array,
        default: () => [],
    },
    supplierVehiculePerformance: {
        type: Array,
        default: () => [],
    },
    supplierServiceDrilldown: {
        type: Array,
        default: () => [],
    },
    monthlyFinancialSummary: {
        type: Array,
        default: () => [],
    },
    vehicleEfficiency: {
        type: Object,
        default: () => ({
            summary: {},
            vehicles: [],
            best_vehicle: null,
            worst_vehicle: null,
        }),
    },
});

const filterForm = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    supplier_vehicule_id: props.filters?.supplier_vehicule_id || "",
});

const chartType = reactive({
    metric: "total_plannings",
});

const selectedAnalyticsMonth = ref(null);
const selectedAnalyticsWeek = ref(null);
const selectedSupplierDrilldown = ref(null);
const selectedSupplierService = ref(null);
const selectedSupplierDay = ref(null);
const supplierDrillSearch = ref("");
const supplierDrillPage = ref(1);
const supplierDrillPerPage = ref(10);
const invoiceLinkModal = reactive({
    open: false,
    planning: null,
    invoice_id: "",
    processing: false,
});
const planningServiceModal = ref(null);
const serviceForm = reactive({
    service_id: "",
    search: "",
    processing: false,
});
const supplierAssignmentModal = reactive({
    open: false,
    planning: null,
    supplierId: "",
    processing: false,
});
const missingSupplierModal = reactive({
    open: false,
    loading: false,
    processing: false,
    autoProcessing: false,
    rows: [],
    total: 0,
    currentPage: 1,
    lastPage: 1,
    options: { suppliers: [], services: [], drivers: [], clients: [] },
    selectedIds: [],
    bulkSupplierId: "",
    rowSupplierIds: {},
});
const missingSupplierFilters = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    date: "",
    service_id: "",
    driver_id: "",
    client_id: "",
    search: "",
});
const missingServiceModal = reactive({
    open: false,
    loading: false,
    processing: false,
    rows: [],
    total: props.missingServicePlanningsCount,
    currentPage: 1,
    lastPage: 1,
    options: { services: [], drivers: [], clients: [], destinations: [] },
    selectedIds: [],
    bulkServiceId: "",
    bulkServiceSearch: "",
    rowServiceIds: {},
    rowServiceSearches: {},
});
const missingServiceFilters = reactive({
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
    date: "",
    driver_id: "",
    client_id: "",
    destination_id: "",
    search: "",
});

const planningActionModalOpen = computed(
    () =>
        Boolean(planningServiceModal.value) ||
        supplierAssignmentModal.open ||
        invoiceLinkModal.open ||
        missingSupplierModal.open ||
        missingServiceModal.open,
);

const closeTopPlanningActionModal = () => {
    if (planningServiceModal.value) closePlanningServiceModal();
    else if (supplierAssignmentModal.open) closeSupplierAssignmentModal();
    else if (invoiceLinkModal.open) closeInvoiceLinkModal();
    else if (missingSupplierModal.open) closeMissingSupplierModal();
    else if (missingServiceModal.open) closeMissingServiceModal();
};

const handlePlanningActionEscape = (event) => {
    if (event.key === "Escape") closeTopPlanningActionModal();
};

watch(planningActionModalOpen, (open) => {
    document.body.classList.toggle("planning-action-modal-open", open);
    window[open ? "addEventListener" : "removeEventListener"](
        "keydown",
        handlePlanningActionEscape,
    );
});

onBeforeUnmount(() => {
    document.body.classList.remove("planning-action-modal-open");
    window.removeEventListener("keydown", handlePlanningActionEscape);
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
    filterForm.supplier_vehicule_id = "";

    applyFilters();
};

const formatMoney = (value) => {
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
};

const progressPercent = (active, total) => {
    const maximum = Number(total || 0);
    if (maximum <= 0) return 0;
    return Math.min(100, Math.round((Number(active || 0) / maximum) * 100));
};

const monthlyCardIcon = (key) =>
    ({
        budget: "bx-wallet-alt",
        supplier_cost: "bx-buildings",
        supplier_payments: "bx-credit-card",
        gross_margin: "bx-line-chart",
    })[key] || "bx-bar-chart-alt-2";

const monthlyCardClass = (key) =>
    ({
        budget: "finance-budget",
        supplier_cost: "finance-cost",
        supplier_payments: "finance-payments",
        gross_margin: "finance-margin",
    })[key] || "finance-budget";

const trendIcon = (trend) =>
    trend === "up"
        ? "bx-trending-up"
        : trend === "down"
          ? "bx-trending-down"
          : "bx-minus";

const trendClass = (trend) =>
    trend === "up"
        ? "trend-up"
        : trend === "down"
          ? "trend-down"
          : "trend-stable";

const formatPercent = (value) => {
    if (value === null || value === undefined) {
        return "Nouveau";
    }

    return `${Number(value) > 0 ? "+" : ""}${formatMoney(value)}%`;
};

const financeCards = computed(() =>
    props.monthlyFinancialSummary.length
        ? props.monthlyFinancialSummary
        : [
              {
                  key: "budget",
                  label: "Budget total",
                  value: props.stats?.total_budget || 0,
                  previous_value: 0,
                  change_percent: null,
                  trend: "stable",
              },
              {
                  key: "supplier_cost",
                  label: "Coût fournisseurs",
                  value: props.stats?.total_supplier_price || 0,
                  previous_value: 0,
                  change_percent: null,
                  trend: "stable",
              },
              {
                  key: "supplier_payments",
                  label: "Paiements fournisseurs",
                  value: 0,
                  previous_value: 0,
                  change_percent: null,
                  trend: "stable",
              },
              {
                  key: "gross_margin",
                  label: "Marge brute",
                  value: props.stats?.gross_margin || 0,
                  previous_value: 0,
                  change_percent: null,
                  trend: "stable",
              },
          ],
);

const vehicleRows = computed(() => props.vehicleEfficiency?.vehicles || []);

const vehicleSummary = computed(() => props.vehicleEfficiency?.summary || {});

const bestVehicle = computed(
    () => props.vehicleEfficiency?.best_vehicle || null,
);

const worstVehicle = computed(
    () => props.vehicleEfficiency?.worst_vehicle || null,
);

const selectedMetric = computed(() => {
    return (
        metricOptions.find((item) => item.value === chartType.metric) ||
        metricOptions[0]
    );
});

const analyticsLabels = computed(() => {
    return props.planningAnalyticsHierarchy.map((item) => item.short_label || item.label);
});

const analyticsSeries = computed(() => [
    {
        name: selectedMetric.value.label,
        data: props.planningAnalyticsHierarchy.map((item) =>
            Number(item[chartType.metric] || 0),
        ),
    },
]);

const selectedMetricTotal = computed(() => {
    return props.planningAnalyticsHierarchy.reduce(
        (sum, item) => sum + Number(item[chartType.metric] || 0),
        0,
    );
});

const analyticsPalette = [
    "#e11d48",
    "#2563eb",
    "#f59e0b",
    "#059669",
    "#7c3aed",
    "#0891b2",
    "#ea580c",
    "#be123c",
    "#16a34a",
    "#4f46e5",
    "#dc2626",
    "#0f766e",
];

const analyticsMonthColors = computed(() =>
    props.planningAnalyticsHierarchy.map(
        (_, index) => analyticsPalette[index % analyticsPalette.length],
    ),
);

const openMonthAnalytics = (monthIndex) => {
    const month = props.planningAnalyticsHierarchy[monthIndex];

    if (!month) return;

    selectedAnalyticsMonth.value = month;
    selectedAnalyticsWeek.value = null;
};

const closeMonthAnalytics = () => {
    selectedAnalyticsMonth.value = null;
    selectedAnalyticsWeek.value = null;
};

const openWeekAnalytics = (weekIndex) => {
    const week = selectedAnalyticsMonth.value?.weeks?.[weekIndex];

    if (!week) return;

    selectedAnalyticsWeek.value = week;
};

const closeWeekAnalytics = () => {
    selectedAnalyticsWeek.value = null;
};

const weekAnalyticsSeries = computed(() => [
    {
        name: selectedMetric.value.label,
        data: (selectedAnalyticsMonth.value?.weeks || []).map((week) =>
            Number(week[chartType.metric] || 0),
        ),
    },
]);

const dayAnalyticsSeries = computed(() => [
    {
        name: selectedMetric.value.label,
        data: (selectedAnalyticsWeek.value?.days || []).map((day) =>
            Number(day[chartType.metric] || 0),
        ),
    },
]);

const analyticsValueFormatter = (value) =>
    `${formatMoney(value)}${selectedMetric.value.suffix}`;

const analyticsChartOptions = computed(() => ({
    chart: {
        type: "bar",
        toolbar: { show: false },
        fontFamily: "Inter, system-ui, sans-serif",
        animations: {
            enabled: true,
            speed: 850,
            animateGradually: {
                enabled: true,
                delay: 80,
            },
            dynamicAnimation: {
                enabled: true,
                speed: 450,
            },
        },
        events: {
            dataPointSelection: (_event, _chartContext, config) =>
                openMonthAnalytics(config.dataPointIndex),
        },
    },
    plotOptions: {
        bar: {
            borderRadius: 12,
            borderRadiusApplication: "end",
            columnWidth: "52%",
            distributed: true,
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
            formatter: analyticsValueFormatter,
        },
    },
    colors: analyticsMonthColors.value,
    states: {
        hover: {
            filter: {
                type: "lighten",
                value: 0.08,
            },
        },
        active: {
            filter: {
                type: "darken",
                value: 0.12,
            },
        },
    },
    grid: {
        borderColor: "#eef2f7",
        strokeDashArray: 5,
    },
}));

const weekAnalyticsChartOptions = computed(() => ({
    chart: {
        type: "bar",
        toolbar: { show: false },
        fontFamily: "Inter, system-ui, sans-serif",
        animations: {
            enabled: true,
            speed: 900,
            animateGradually: { enabled: true, delay: 110 },
        },
        events: {
            dataPointSelection: (_event, _chartContext, config) =>
                openWeekAnalytics(config.dataPointIndex),
        },
    },
    plotOptions: {
        bar: {
            borderRadius: 14,
            borderRadiusApplication: "end",
            columnWidth: "46%",
            distributed: true,
        },
    },
    xaxis: {
        categories: (selectedAnalyticsMonth.value?.weeks || []).map(
            (week) => week.label,
        ),
        labels: {
            style: {
                fontWeight: 800,
                colors: "#475569",
            },
        },
    },
    yaxis: {
        labels: {
            formatter: (value) => formatMoney(value),
            style: {
                fontWeight: 800,
                colors: "#64748b",
            },
        },
    },
    tooltip: {
        y: { formatter: analyticsValueFormatter },
        x: {
            formatter: (_value, opts) =>
                selectedAnalyticsMonth.value?.weeks?.[opts.dataPointIndex]
                    ?.range_label || "",
        },
    },
    colors: ["#e11d48", "#2563eb", "#f59e0b", "#059669", "#7c3aed"],
    grid: {
        borderColor: "#e2e8f0",
        strokeDashArray: 6,
    },
}));

const dayAnalyticsChartOptions = computed(() => ({
    chart: {
        type: "bar",
        toolbar: { show: false },
        fontFamily: "Inter, system-ui, sans-serif",
        animations: {
            enabled: true,
            speed: 850,
            animateGradually: { enabled: true, delay: 85 },
        },
    },
    plotOptions: {
        bar: {
            borderRadius: 12,
            borderRadiusApplication: "end",
            columnWidth: "42%",
            distributed: true,
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: (selectedAnalyticsWeek.value?.days || []).map(
            (day) => day.label,
        ),
        labels: {
            style: {
                fontWeight: 800,
                colors: "#475569",
            },
        },
    },
    yaxis: {
        labels: {
            formatter: (value) => formatMoney(value),
            style: {
                fontWeight: 800,
                colors: "#64748b",
            },
        },
    },
    tooltip: {
        y: { formatter: analyticsValueFormatter },
        x: {
            formatter: (_value, opts) =>
                selectedAnalyticsWeek.value?.days?.[opts.dataPointIndex]
                    ?.day_label || "",
        },
    },
    colors: ["#0ea5e9", "#ef4444", "#f97316", "#22c55e", "#8b5cf6", "#14b8a6", "#f43f5e"],
    grid: {
        borderColor: "#e2e8f0",
        strokeDashArray: 6,
    },
}));

const supplierPerformanceLabels = computed(() =>
    props.supplierVehiculePerformance.map((item) => item.name),
);

const supplierPerformanceTrips = computed(() =>
    props.supplierVehiculePerformance.map((item) =>
        Number(item.total_trips || 0),
    ),
);

const supplierPerformanceTotalTrips = computed(() =>
    props.supplierVehiculePerformance.reduce(
        (sum, item) => sum + Number(item.total_trips || 0),
        0,
    ),
);

const supplierPerformanceTotalMargin = computed(() =>
    props.supplierVehiculePerformance.reduce(
        (sum, item) => sum + Number(item.gross_margin || 0),
        0,
    ),
);

const supplierPerformanceColors = [
    "#c1121f",
    "#172554",
    "#f59e0b",
    "#059669",
    "#7c3aed",
    "#0891b2",
    "#dc2626",
    "#475569",
    "#16a34a",
    "#ea580c",
];

const supplierColor = (index) =>
    supplierPerformanceColors[index % supplierPerformanceColors.length];

const supplierDrilldownById = computed(() => {
    return new Map(props.supplierServiceDrilldown.map((item) => [String(item.id), item]));
});

const refreshSupplierDashboard = () => {
    router.reload({
        only: [
            "supplierVehiculePerformance",
            "supplierServiceDrilldown",
            "topSupplierVehicules",
            "stats",
            "recentPlannings",
        ],
        preserveScroll: true,
        preserveState: true,
    });
};

const apiErrorMessage = (error, fallback) =>
    error?.response?.data?.message ||
    Object.values(error?.response?.data?.errors || {})?.[0]?.[0] ||
    fallback;

const loadMissingSupplierPlannings = async (page = 1) => {
    if (!props.canManageMissingSuppliers) return;

    missingSupplierModal.loading = true;
    try {
        const response = await axios.get(
            route("dashboard.missing-suppliers.index"),
            { params: { ...missingSupplierFilters, page } },
        );
        const payload = response.data.plannings;
        missingSupplierModal.rows = payload.data || [];
        missingSupplierModal.rows.forEach((row) => {
            if (!(row.id in missingSupplierModal.rowSupplierIds)) {
                missingSupplierModal.rowSupplierIds[row.id] = "";
            }
        });
        missingSupplierModal.total = payload.total || 0;
        missingSupplierModal.currentPage = payload.current_page || 1;
        missingSupplierModal.lastPage = payload.last_page || 1;
        missingSupplierModal.options = response.data.options;
        missingSupplierModal.selectedIds = missingSupplierModal.selectedIds.filter(
            (id) => missingSupplierModal.rows.some((row) => row.id === id),
        );
    } catch (error) {
        toastError(apiErrorMessage(error, "Impossible de charger les plannings sans fournisseur."));
    } finally {
        missingSupplierModal.loading = false;
    }
};

const openMissingSupplierModal = () => {
    if (!props.canManageMissingSuppliers) return;
    missingSupplierModal.open = true;
    missingSupplierModal.selectedIds = [];
    missingSupplierModal.bulkSupplierId = "";
    loadMissingSupplierPlannings();
};

const closeMissingSupplierModal = () => {
    if (missingSupplierModal.processing || missingSupplierModal.autoProcessing) return;
    missingSupplierModal.open = false;
    missingSupplierModal.selectedIds = [];
};

const resetMissingSupplierFilters = () => {
    Object.assign(missingSupplierFilters, {
        date_from: props.filters?.date_from || "",
        date_to: props.filters?.date_to || "",
        date: "",
        service_id: "",
        driver_id: "",
        client_id: "",
        search: "",
    });
    loadMissingSupplierPlannings();
};

const isMissingPlanningSelected = (planningId) =>
    missingSupplierModal.selectedIds.includes(planningId);

const toggleMissingPlanning = (planningId) => {
    missingSupplierModal.selectedIds = isMissingPlanningSelected(planningId)
        ? missingSupplierModal.selectedIds.filter((id) => id !== planningId)
        : [...missingSupplierModal.selectedIds, planningId];
};

const allVisibleMissingSelected = computed(
    () =>
        missingSupplierModal.rows.length > 0 &&
        missingSupplierModal.rows.every((row) =>
            missingSupplierModal.selectedIds.includes(row.id),
        ),
);

const toggleAllVisibleMissing = () => {
    missingSupplierModal.selectedIds = allVisibleMissingSelected.value
        ? []
        : missingSupplierModal.rows.map((row) => row.id);
};

const loadMissingServicePlannings = async (page = 1) => {
    missingServiceModal.loading = true;
    try {
        const response = await axios.get(route("dashboard.missing-services.index"), {
            params: { ...missingServiceFilters, page },
        });
        const payload = response.data.plannings;
        missingServiceModal.rows = payload.data || [];
        missingServiceModal.rows.forEach((row) => {
            if (!(row.id in missingServiceModal.rowServiceIds)) missingServiceModal.rowServiceIds[row.id] = "";
            if (!(row.id in missingServiceModal.rowServiceSearches)) missingServiceModal.rowServiceSearches[row.id] = "";
        });
        missingServiceModal.total = payload.total || 0;
        missingServiceModal.currentPage = payload.current_page || 1;
        missingServiceModal.lastPage = payload.last_page || 1;
        missingServiceModal.options = response.data.options;
        missingServiceModal.selectedIds = missingServiceModal.selectedIds.filter((id) =>
            missingServiceModal.rows.some((row) => row.id === id),
        );
    } catch (error) {
        toastError(apiErrorMessage(error, "Impossible de charger les plannings sans service."));
    } finally {
        missingServiceModal.loading = false;
    }
};

const openMissingServiceModal = () => {
    if (!props.canEditPlanningService) return;
    missingServiceModal.open = true;
    missingServiceModal.selectedIds = [];
    missingServiceModal.bulkServiceId = "";
    missingServiceModal.bulkServiceSearch = "";
    loadMissingServicePlannings();
};

const closeMissingServiceModal = () => {
    if (missingServiceModal.processing) return;
    missingServiceModal.open = false;
    missingServiceModal.selectedIds = [];
};

const resetMissingServiceFilters = () => {
    Object.assign(missingServiceFilters, {
        date_from: props.filters?.date_from || "",
        date_to: props.filters?.date_to || "",
        date: "",
        driver_id: "",
        client_id: "",
        destination_id: "",
        search: "",
    });
    loadMissingServicePlannings();
};

const isMissingServicePlanningSelected = (id) => missingServiceModal.selectedIds.includes(id);
const toggleMissingServicePlanning = (id) => {
    missingServiceModal.selectedIds = isMissingServicePlanningSelected(id)
        ? missingServiceModal.selectedIds.filter((selectedId) => selectedId !== id)
        : [...missingServiceModal.selectedIds, id];
};
const allVisibleMissingServicesSelected = computed(() =>
    missingServiceModal.rows.length > 0 &&
    missingServiceModal.rows.every((row) => missingServiceModal.selectedIds.includes(row.id)),
);
const toggleAllVisibleMissingServices = () => {
    missingServiceModal.selectedIds = allVisibleMissingServicesSelected.value
        ? []
        : missingServiceModal.rows.map((row) => row.id);
};

const assignMissingService = async (planningIds, serviceId, bulk = false) => {
    if (!serviceId) {
        toastError("Veuillez sélectionner un service.");
        return;
    }
    if (!planningIds.length) {
        toastError("Veuillez sélectionner au moins un planning.");
        return;
    }

    missingServiceModal.processing = true;
    try {
        const response = await axios.post(route("dashboard.missing-services.assign"), {
            planning_ids: planningIds,
            service_id: serviceId,
        });
        missingServiceModal.selectedIds = [];
        if (!bulk) {
            delete missingServiceModal.rowServiceIds[planningIds[0]];
            delete missingServiceModal.rowServiceSearches[planningIds[0]];
        }
        toastSuccess(response.data.message);
        await loadMissingServicePlannings(missingServiceModal.currentPage);
        router.reload({
            only: ["missingServicePlanningsCount", "stats", "topServices", "budgetPerService", "planningAnalytics", "planningAnalyticsHierarchy", "supplierServiceDrilldown"],
            preserveScroll: true,
            preserveState: true,
        });
    } catch (error) {
        toastError(apiErrorMessage(error, "L’affectation du service a échoué."));
    } finally {
        missingServiceModal.processing = false;
    }
};

const assignMissingSupplier = async (planningIds, supplierId, bulk = false) => {
    if (!supplierId) {
        toastError("Veuillez sélectionner un fournisseur véhicule.");
        return;
    }
    if (!planningIds.length) {
        toastError("Veuillez sélectionner au moins un planning.");
        return;
    }

    const confirmation = await Swal.fire({
        title: bulk ? "Affecter les services sélectionnés ?" : "Affecter ce planning ?",
        text: `${planningIds.length} planning(s) seront liés au fournisseur choisi. Un planning déjà affecté ne sera jamais écrasé.`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Confirmer l’affectation",
        cancelButtonText: "Annuler",
        confirmButtonColor: "#c1121f",
    });
    if (!confirmation.isConfirmed) return;

    missingSupplierModal.processing = true;
    try {
        const response = await axios.post(
            route("dashboard.missing-suppliers.assign"),
            { planning_ids: planningIds, supplier_vehicule_id: supplierId },
        );
        missingSupplierModal.selectedIds = [];
        if (!bulk) delete missingSupplierModal.rowSupplierIds[planningIds[0]];
        toastSuccess(response.data.message);
        await loadMissingSupplierPlannings(missingSupplierModal.currentPage);
        refreshSupplierDashboard();
    } catch (error) {
        toastError(apiErrorMessage(error, "L’affectation du fournisseur a échoué."));
    } finally {
        missingSupplierModal.processing = false;
    }
};

const openSupplierAssignmentModal = (planning) => {
    if (!props.canAssignPlanningSupplier || planning?.supplier_vehicle_id) return;
    supplierAssignmentModal.planning = planning;
    supplierAssignmentModal.supplierId = "";
    supplierAssignmentModal.open = true;
};

const closeSupplierAssignmentModal = () => {
    if (supplierAssignmentModal.processing) return;
    supplierAssignmentModal.open = false;
    supplierAssignmentModal.planning = null;
    supplierAssignmentModal.supplierId = "";
};

const saveSupplierAssignment = async () => {
    const planning = supplierAssignmentModal.planning;
    if (!planning || !supplierAssignmentModal.supplierId || supplierAssignmentModal.processing) return;

    const confirmation = await Swal.fire({
        title: "Affecter le fournisseur véhicule ?",
        text: `Le planning ${planning.ref_dossier} sera affecté au fournisseur sélectionné.`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Confirmer l’affectation",
        cancelButtonText: "Annuler",
        confirmButtonColor: "#059669",
    });
    if (!confirmation.isConfirmed) return;

    supplierAssignmentModal.processing = true;
    try {
        const response = await axios.post(route("dashboard.missing-suppliers.assign"), {
            planning_ids: [planning.id],
            supplier_vehicule_id: supplierAssignmentModal.supplierId,
        });
        closeSupplierDrilldown();
        supplierAssignmentModal.open = false;
        supplierAssignmentModal.planning = null;
        supplierAssignmentModal.supplierId = "";
        toastSuccess(response.data.message);
        refreshSupplierDashboard();
    } catch (error) {
        toastError(apiErrorMessage(error, "L’affectation du fournisseur a échoué."));
    } finally {
        supplierAssignmentModal.processing = false;
    }
};

const runAutomaticMdToursAssignment = async () => {
    if (!props.canManageMissingSuppliers || missingSupplierModal.autoProcessing) return;

    const confirmation = await Swal.fire({
        title: "Corriger les plannings MD TOURS ?",
        text: "Seuls les plannings sans fournisseur dont le chauffeur se termine par « DRIVER MD TOURS » seront modifiés.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Lancer la correction",
        cancelButtonText: "Annuler",
        confirmButtonColor: "#c1121f",
    });
    if (!confirmation.isConfirmed) return;

    missingSupplierModal.autoProcessing = true;
    Swal.fire({
        title: "Traitement en cours…",
        text: "Analyse des plannings sans fournisseur véhicule.",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const response = await axios.post(
            route("dashboard.missing-suppliers.auto-assign"),
        );
        await Swal.fire({
            title: "Correction terminée",
            text: response.data.message,
            icon: "success",
            confirmButtonColor: "#c1121f",
        });
        if (missingSupplierModal.open) {
            await loadMissingSupplierPlannings(missingSupplierModal.currentPage);
        }
        refreshSupplierDashboard();
    } catch (error) {
        await Swal.fire({
            title: "Correction impossible",
            text: apiErrorMessage(error, "Le traitement automatique a échoué."),
            icon: "error",
            confirmButtonColor: "#c1121f",
        });
    } finally {
        missingSupplierModal.autoProcessing = false;
    }
};

const openSupplierDrilldown = (supplierId) => {
    const detail = supplierDrilldownById.value.get(String(supplierId));

    if (!detail) return;

    selectedSupplierDrilldown.value = detail;
    selectedSupplierService.value = null;
    selectedSupplierDay.value = null;
    supplierDrillSearch.value = "";
    supplierDrillPage.value = 1;
};

const openSupplierDrilldownByIndex = (index) => {
    const supplier = props.supplierVehiculePerformance[index];
    if (!supplier) return;

    openSupplierDrilldown(supplier.id);
};

const closeSupplierDrilldown = () => {
    selectedSupplierDrilldown.value = null;
    selectedSupplierService.value = null;
    selectedSupplierDay.value = null;
};

const openSupplierService = (service) => {
    selectedSupplierService.value = service;
    selectedSupplierDay.value = null;
    supplierDrillSearch.value = "";
    supplierDrillPage.value = 1;
};

const closeSupplierService = () => {
    selectedSupplierService.value = null;
    selectedSupplierDay.value = null;
};

const openSupplierServiceDay = (dayIndex) => {
    const day = selectedSupplierService.value?.days?.[dayIndex];
    if (!day) return;

    selectedSupplierDay.value = day;
    supplierDrillSearch.value = "";
    supplierDrillPage.value = 1;
};

const closeSupplierDay = () => {
    selectedSupplierDay.value = null;
};

const serviceOptions = computed(() => {
    const priorities = planningServiceModal.value?.recommended_service_ids || [];
    const priorityIndex = new Map(priorities.map((id, index) => [String(id), index]));

    return [...props.services]
        .map((service) => ({
            ...service,
            name: service.designation,
            recommended: priorityIndex.has(String(service.id)),
        }))
        .sort((a, b) => {
            const aRank = priorityIndex.get(String(a.id)) ?? 9999;
            const bRank = priorityIndex.get(String(b.id)) ?? 9999;
            return aRank - bRank || a.designation.localeCompare(b.designation);
        });
});

const openPlanningServiceModal = (planning) => {
    if (!props.canEditPlanningService) return;

    planningServiceModal.value = planning;
    serviceForm.service_id = planning.service_id || "";
    serviceForm.search = planning.service_id ? planning.service : "";
};

const updateServiceSearch = (search) => {
    serviceForm.search = search;
    const selected = props.services.find(
        (service) => String(service.id) === String(serviceForm.service_id),
    );
    if (!selected || selected.designation !== search) serviceForm.service_id = "";
};

const closePlanningServiceModal = () => {
    if (serviceForm.processing) return;
    planningServiceModal.value = null;
    serviceForm.service_id = "";
    serviceForm.search = "";
};

const restoreSupplierDrilldown = async (supplierId, serviceId, dayDate) => {
    await nextTick();
    const supplier = supplierDrilldownById.value.get(String(supplierId));
    selectedSupplierDrilldown.value = supplier || null;
    selectedSupplierService.value =
        supplier?.services?.find((service) => String(service.id) === String(serviceId)) ||
        supplier?.services?.find((service) =>
            service.days?.some((day) => day.date === dayDate),
        ) ||
        null;
    selectedSupplierDay.value =
        selectedSupplierService.value?.days?.find((day) => day.date === dayDate) || null;
};

const savePlanningService = async () => {
    const planning = planningServiceModal.value;
    if (!planning || !serviceForm.service_id || serviceForm.processing) return;

    let replaceConfirmed = !planning.service_id;
    if (planning.service_id && String(planning.service_id) !== String(serviceForm.service_id)) {
        const confirmation = await Swal.fire({
            title: "Remplacer le service ?",
            text: `Le service « ${planning.service} » sera remplacé. Les factures et paiements ne seront pas modifiés.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Oui, remplacer",
            cancelButtonText: "Annuler",
            confirmButtonColor: "#c1121f",
        });
        if (!confirmation.isConfirmed) return;
        replaceConfirmed = true;
    } else if (planning.service_id) {
        closePlanningServiceModal();
        return;
    }

    const supplierId = selectedSupplierDrilldown.value?.id;
    const serviceId = selectedSupplierService.value?.id;
    const dayDate = selectedSupplierDay.value?.date;
    serviceForm.processing = true;

    router.patch(
        route("dashboard.plannings.service.update", planning.id),
        {
            service_id: serviceForm.service_id,
            replace_confirmed: replaceConfirmed,
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: [
                "supplierServiceDrilldown",
                "supplierVehiculePerformance",
                "topServices",
                "budgetPerService",
                "stats",
            ],
            onSuccess: async () => {
                planningServiceModal.value = null;
                await restoreSupplierDrilldown(supplierId, serviceId, dayDate);
                toastSuccess("Service affecté au planning.");
            },
            onError: (errors) =>
                toastError(errors.service_id || "Impossible de mettre à jour le service."),
            onFinish: () => {
                serviceForm.processing = false;
            },
        },
    );
};

const selectedSupplierServices = computed(
    () => selectedSupplierDrilldown.value?.services || [],
);

const selectedSupplierDayPlannings = computed(
    () => selectedSupplierDay.value?.plannings || [],
);

const supplierDrillLevel = computed(() =>
    selectedSupplierDay.value ? 4 : selectedSupplierService.value ? 3 : 2,
);

const supplierDrillRows = computed(() => {
    const query = supplierDrillSearch.value.trim().toLocaleLowerCase("fr");
    const rows = supplierDrillLevel.value === 2
        ? selectedSupplierServices.value
        : supplierDrillLevel.value === 3
            ? selectedSupplierService.value?.days || []
            : selectedSupplierDayPlannings.value;
    if (!query) return rows;
    return rows.filter((row) => JSON.stringify(row).toLocaleLowerCase("fr").includes(query));
});

const supplierDrillPageCount = computed(() =>
    Math.max(Math.ceil(supplierDrillRows.value.length / supplierDrillPerPage.value), 1),
);

const paginatedSupplierDrillRows = computed(() => {
    const page = Math.min(supplierDrillPage.value, supplierDrillPageCount.value);
    const start = (page - 1) * supplierDrillPerPage.value;
    return supplierDrillRows.value.slice(start, start + supplierDrillPerPage.value);
});

watch([supplierDrillSearch, supplierDrillPerPage], () => {
    supplierDrillPage.value = 1;
});

const supplierDayStats = (day) => {
    const plannings = day?.plannings || [];
    const invoiced = plannings.filter((planning) => planning.invoice).length;
    const paid = plannings.filter((planning) => planning.invoice?.payment_status === "paid").length;
    return {
        dossiers: new Set(plannings.map((planning) => planning.ref_dossier).filter(Boolean)).size,
        invoiced,
        notInvoiced: plannings.length - invoiced,
        paid,
        unpaid: plannings.length - paid,
    };
};

const selectedSupplierDayStats = computed(() => {
    const plannings = selectedSupplierDayPlannings.value;
    const invoiced = plannings.filter((planning) => planning.invoice).length;
    const paid = plannings.filter(
        (planning) => planning.invoice?.payment_status === "paid",
    ).length;
    const partial = plannings.filter(
        (planning) => planning.invoice?.payment_status === "partial",
    ).length;
    const unpaid = plannings.filter(
        (planning) => planning.invoice?.payment_status === "unpaid",
    ).length;

    return {
        invoiced,
        notInvoiced: Math.max(plannings.length - invoiced, 0),
        paid,
        partial,
        unpaid,
    };
});

const invoiceBadgeClass = (planning) =>
    planning.invoice ? "badge-facturee" : "badge-non-facturee";

const paymentBadgeClass = (planning) => {
    if (!planning.invoice) return "badge-not-ready";

    return {
        paid: "badge-paid",
        partial: "badge-partial",
        unpaid: "badge-unpaid",
    }[planning.invoice.payment_status] || "badge-unpaid";
};

const paymentLabel = (planning) =>
    planning.invoice?.payment_label || "Non facturée";

const supplierInvoiceOptionsFor = (planning) => {
    const supplierId = planning?.supplier_vehicle_id;

    return supplierId
        ? props.supplierVehicleInvoiceOptions?.[String(supplierId)] || []
        : [];
};

const invoiceLinkOptions = computed(() =>
    supplierInvoiceOptionsFor(invoiceLinkModal.planning),
);

const openInvoiceLinkModal = (planning) => {
    if (!props.canLinkSupplierInvoice) return;

    invoiceLinkModal.planning = planning;
    invoiceLinkModal.invoice_id = invoiceLinkOptions.value?.[0]?.id || "";
    invoiceLinkModal.open = true;
};

const closeInvoiceLinkModal = () => {
    if (invoiceLinkModal.processing) return;

    invoiceLinkModal.open = false;
    invoiceLinkModal.planning = null;
    invoiceLinkModal.invoice_id = "";
};

const submitInvoiceLink = () => {
    if (!invoiceLinkModal.planning?.id || !invoiceLinkModal.invoice_id) return;

    const supplierId = selectedSupplierDrilldown.value?.id;
    const serviceId = selectedSupplierService.value?.id;
    const dayDate = selectedSupplierDay.value?.date;
    invoiceLinkModal.processing = true;

    router.post(
        route(
            "dashboard.plannings.supplier-invoice",
            invoiceLinkModal.planning.id,
        ),
        { invoice_id: invoiceLinkModal.invoice_id },
        {
            preserveScroll: true,
            preserveState: true,
            only: ["supplierServiceDrilldown"],
            onSuccess: async () => {
                invoiceLinkModal.open = false;
                invoiceLinkModal.planning = null;
                await restoreSupplierDrilldown(supplierId, serviceId, dayDate);
                toastSuccess("Service rattaché à la facture fournisseur.");
            },
            onError: (errors) =>
                toastError(errors.invoice_id || "Rattachement impossible."),
            onFinish: () => {
                invoiceLinkModal.processing = false;
            },
        },
    );
};

const supplierServiceDaySeries = computed(() => [
    {
        name: "Trajets",
        data: (selectedSupplierService.value?.days || []).map((day) =>
            Number(day.total_trips || 0),
        ),
    },
]);

const supplierServiceDayChartOptions = computed(() => ({
    chart: {
        type: "bar",
        toolbar: { show: false },
        fontFamily: "Inter, system-ui, sans-serif",
        animations: {
            enabled: true,
            speed: 780,
            animateGradually: { enabled: true, delay: 80 },
        },
        events: {
            dataPointSelection: (_event, _chartContext, config) => {
                openSupplierServiceDay(config.dataPointIndex);
            },
        },
    },
    plotOptions: {
        bar: {
            borderRadius: 14,
            borderRadiusApplication: "end",
            columnWidth: "54%",
            distributed: true,
        },
    },
    dataLabels: {
        enabled: true,
        formatter: (value) => formatMoney(value),
        offsetY: -18,
        style: {
            fontSize: "14px",
            fontWeight: 950,
            colors: ["#0f172a"],
        },
    },
    xaxis: {
        categories: (selectedSupplierService.value?.days || []).map((day) => day.label),
        labels: {
            style: { fontWeight: 900, fontSize: "15px", colors: "#334155" },
        },
    },
    yaxis: {
        labels: {
            formatter: (value) => formatMoney(value),
            style: { fontWeight: 900, fontSize: "14px", colors: "#64748b" },
        },
    },
    tooltip: {
        y: {
            formatter: (value, opts) => {
                const day = selectedSupplierService.value?.days?.[opts.dataPointIndex] || {};
                return `${formatMoney(value)} trajets • Budget ${formatMoney(day.total_budget)} MAD • Marge ${formatMoney(day.gross_margin)} MAD`;
            },
        },
        x: {
            formatter: (_value, opts) =>
                selectedSupplierService.value?.days?.[opts.dataPointIndex]
                    ?.day_label || "",
        },
    },
    colors: ["#0ea5e9", "#ef4444", "#f97316", "#22c55e", "#8b5cf6", "#14b8a6", "#f43f5e", "#2563eb"],
    grid: { borderColor: "#e2e8f0", strokeDashArray: 6 },
}));

const supplierPerformanceChartOptions = computed(() => ({
    chart: {
        type: "donut",
        toolbar: { show: false },
        fontFamily: "Inter, system-ui, sans-serif",
        animations: {
            enabled: true,
            speed: 900,
            animateGradually: { enabled: true, delay: 80 },
        },
        events: {
            dataPointSelection: (_event, _chartContext, config) =>
                openSupplierDrilldownByIndex(config.dataPointIndex),
        },
    },
    labels: supplierPerformanceLabels.value,
    colors: supplierPerformanceColors,
    dataLabels: {
        enabled: true,
        formatter: (value) => `${Math.round(value)}%`,
        style: {
            fontWeight: 900,
        },
    },
    legend: {
        position: "bottom",
        fontWeight: 700,
        markers: {
            radius: 8,
        },
    },
    stroke: {
        width: 4,
        colors: ["#fff"],
    },
    plotOptions: {
        pie: {
            donut: {
                size: "68%",
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: "Trajets",
                        formatter: () =>
                            String(supplierPerformanceTotalTrips.value),
                    },
                    value: {
                        formatter: (value) => formatMoney(value),
                    },
                },
            },
        },
    },
    tooltip: {
        y: {
            formatter: (value, opts) => {
                const item =
                    props.supplierVehiculePerformance?.[
                        opts.seriesIndex
                    ] || {};

                return `${formatMoney(value)} trajets • Marge ${formatMoney(
                    item.gross_margin,
                )} MAD`;
            },
        },
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
                class="dashboard-hero card border-0 shadow-lg mb-3 overflow-hidden"
            >
                <div class="hero-overlay"></div>

                <div class="card-body p-3 p-lg-4 position-relative">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-xl-7">
                            <div class="hero-badge mb-3">
                                <i class="bx bx-shield-quarter"></i>
                                Pilotage global
                            </div>

                            <h1 class="dashboard-title mb-2">
                                Dashboard Exécutif
                            </h1>

                            <p class="dashboard-subtitle mb-3">
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

                                    <div class="col-12">
                                        <label class="form-label filter-label">
                                            Fournisseur véhicule
                                        </label>
                                        <select
                                            v-model="
                                                filterForm.supplier_vehicule_id
                                            "
                                            class="form-select modern-input"
                                        >
                                            <option value="">
                                                Tous les fournisseurs véhicules
                                            </option>
                                            <option
                                                v-for="supplier in supplierVehicules"
                                                :key="supplier.id"
                                                :value="supplier.id"
                                            >
                                                {{ supplier.name }}
                                            </option>
                                        </select>
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
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl-3">
                    <div
                        class="metric-card metric-red card border-0 shadow-sm h-100"
                    >
                        <div class="card-body metric-card-body">
                            <div class="metric-inline">
                                <div class="metric-icon"><i class="bx bx-calendar-star"></i></div>
                                <div class="metric-value">{{ stats.total_plannings || 0 }}</div>
                                <div class="metric-chip">Total plannings</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div
                        class="metric-card metric-blue card border-0 shadow-sm h-100"
                    >
                        <div class="card-body metric-card-body">
                            <div class="metric-inline">
                                <div class="metric-icon"><i class="bx bx-sun"></i></div>
                                <div class="metric-value">{{ stats.today_plannings || 0 }}</div>
                                <div class="metric-chip">Aujourd’hui</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div
                        class="metric-card metric-purple card border-0 shadow-sm h-100"
                    >
                        <div class="card-body metric-card-body">
                            <div class="metric-inline">
                                <div class="metric-icon"><i class="bx bx-time-five"></i></div>
                                <div class="metric-value">{{ stats.upcoming_plannings || 0 }}</div>
                                <div class="metric-chip">À venir</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                    <div
                        class="metric-card metric-green card border-0 shadow-sm h-100"
                    >
                        <div class="card-body metric-card-body">
                            <div class="metric-inline">
                                <div class="metric-icon"><i class="bx bx-user-plus"></i></div>
                                <div class="metric-value">{{ stats.assigned_clients || 0 }}</div>
                                <div class="metric-chip">Clients affectés</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FINANCE -->
            <div class="row g-3 mb-3">
                <div
                    v-for="item in financeCards"
                    :key="item.key"
                    class="col-12 col-md-6 col-xl-3"
                >
                    <div
                        :class="[
                            'finance-card card border-0 shadow-sm h-100',
                            monthlyCardClass(item.key),
                        ]"
                    >
                        <div class="card-body finance-card-body">
                            <div class="finance-top-line">
                                <div class="finance-heading">
                                    <div class="finance-icon">
                                        <i
                                            :class="[
                                                'bx',
                                                monthlyCardIcon(item.key),
                                            ]"
                                        ></i>
                                    </div>
                                    <div class="finance-title">
                                        {{ item.label }}
                                    </div>
                                </div>
                                <div
                                    class="finance-trend-pill"
                                    :class="trendClass(item.trend)"
                                >
                                    <i
                                        :class="[
                                            'bx',
                                            trendIcon(item.trend),
                                        ]"
                                    ></i>
                                    {{ formatPercent(item.change_percent) }}
                                </div>
                            </div>
                            <div class="finance-value text-white">
                                {{ formatMoney(item.value) }} MAD
                            </div>
                            <div class="finance-note">
                                Mois précédent ({{
                                    periodInfo?.previous_period_label || "N/A"
                                }}) :
                                {{ formatMoney(item.previous_value) }} MAD
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MINI STATS -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-suppliers card border-0 shadow-sm h-100">
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
                            <div class="mini-progress"><span :style="{ width: `${progressPercent(stats.active_supplier_vehicules, stats.total_supplier_vehicules)}%` }"></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-vehicles card border-0 shadow-sm h-100">
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
                            <div class="mini-progress"><span :style="{ width: `${progressPercent(stats.active_vehicules, stats.total_vehicules)}%` }"></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-drivers card border-0 shadow-sm h-100">
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
                            <div class="mini-progress"><span :style="{ width: `${progressPercent(stats.active_drivers, stats.total_drivers)}%` }"></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-guides card border-0 shadow-sm h-100">
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
                            <div class="mini-progress"><span :style="{ width: `${progressPercent(stats.active_guides, stats.total_guides)}%` }"></span></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-xl">
                    <div class="mini-stat-card mini-destinations card border-0 shadow-sm h-100">
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
                            <div class="mini-progress"><span :style="{ width: `${progressPercent(stats.active_destinations, stats.total_destinations)}%` }"></span></div>
                        </div>
                    </div>
                </div>

                <div v-if="canEditPlanningService" class="col-12 col-md-6 col-xl">
                    <button
                        type="button"
                        class="mini-stat-card missing-service-launch card border-0 shadow-sm h-100 w-100"
                        @click="openMissingServiceModal"
                    >
                        <span class="card-body mini-stat-card-body">
                            <span class="mini-stat-head">
                                <i class="bx bx-layer-plus"></i>
                                Plannings sans service
                            </span>
                            <strong class="mini-stat-value">{{ missingServicePlanningsCount }}</strong>
                            <span class="mini-stat-sub">À corriger maintenant</span>
                            <span class="missing-service-launch-action">Ouvrir la gestion <i class="bx bx-right-arrow-alt"></i></span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- SUPPLIER VEHICLE PERFORMANCE -->
            <div class="analytics-super-card card border-0 shadow-sm mb-3">
                <div class="card-body p-3 p-lg-4">
                    <div class="analytics-header align-items-start">
                        <div>
                            <div class="panel-kicker">
                                Fournisseurs véhicules
                            </div>
                            <h3 class="analytics-title">
                                Trajets et marge par fournisseur
                            </h3>
                            <p class="analytics-subtitle">
                                Ce graphique circulaire montre combien de
                                trajets chaque fournisseur véhicule a réalisé
                                sur la période filtrée, avec la marge gagnée
                                pour chacun.
                            </p>
                        </div>

                        <div class="supplier-summary-chip">
                            <span>Total marge</span>
                            <strong>
                                {{
                                    formatMoney(
                                        supplierPerformanceTotalMargin,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                    </div>

                    <div
                        v-if="supplierVehiculePerformance.length"
                        class="row g-3 align-items-center"
                    >
                        <div class="col-12 col-xl-5">
                            <VueApexCharts
                                type="donut"
                                height="280"
                                :options="supplierPerformanceChartOptions"
                                :series="supplierPerformanceTrips"
                            />
                        </div>

                        <div class="col-12 col-xl-7">
                            <div class="supplier-table-shell">
                                <table class="supplier-pro-table">
                                    <thead><tr><th>Fournisseur véhicule</th><th>Trajets</th><th>Budget</th><th>Prix fournisseur</th><th>Marge</th><th>% trajets</th><th></th></tr></thead>
                                    <tbody><tr
                                    v-for="(
                                        item, index
                                    ) in supplierVehiculePerformance"
                                    :key="item.id"
                                    class="supplier-pro-row"
                                    role="button"
                                    tabindex="0"
                                    @click="openSupplierDrilldown(item.id)"
                                    @keyup.enter="openSupplierDrilldown(item.id)"
                                >
                                    <td><div class="supplier-name-box">
                                        <span
                                            class="supplier-dot"
                                            :style="{
                                                background:
                                                    supplierColor(index),
                                                boxShadow: `0 0 0 5px ${supplierColor(index)}22`,
                                            }"
                                        ></span>
                                        <div>
                                            <strong>{{ item.name }}</strong>
                                            <small>
                                                {{
                                                    formatMoney(
                                                        item.total_budget,
                                                    )
                                                }}
                                                MAD budget
                                            </small>
                                            <button
                                                v-if="
                                                    item.id === 'none' &&
                                                    canManageMissingSuppliers
                                                "
                                                type="button"
                                                class="missing-supplier-auto-button"
                                                :disabled="missingSupplierModal.autoProcessing"
                                                @click.stop="runAutomaticMdToursAssignment"
                                                @keyup.enter.stop
                                            >
                                                <i
                                                    class="bx"
                                                    :class="
                                                        missingSupplierModal.autoProcessing
                                                            ? 'bx-loader-alt bx-spin'
                                                            : 'bx-magic-wand'
                                                    "
                                                ></i>
                                                Corriger MD TOURS
                                            </button>
                                        </div>
                                    </div></td>
                                    <td><strong>{{ item.total_trips }}</strong></td>
                                    <td>{{ formatMoney(item.total_budget) }} MAD</td>
                                    <td>{{ formatMoney(item.total_supplier_price) }} MAD</td>
                                    <td><strong class="positive">{{ formatMoney(item.gross_margin) }} MAD</strong></td>
                                    <td>{{ supplierPerformanceTotalTrips ? ((item.total_trips / supplierPerformanceTotalTrips) * 100).toFixed(1) : 0 }}%</td>
                                    <td class="supplier-row-arrow"><i class="bx bx-chevron-right"></i></td>
                                </tr></tbody></table>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="chart-click-hint">
                                <i class="bx bx-pointer"></i>
                                Cliquez sur un fournisseur pour lire ses services.
                            </div>
                        </div>
                    </div>

                    <div v-else class="empty-state py-5">
                        Aucun trajet fournisseur véhicule sur cette période.
                    </div>
                </div>
            </div>

            <div v-if="selectedSupplierDrilldown" class="analytics-modal-backdrop" @click.self="closeSupplierDrilldown">
                <div class="analytics-modal supplier-table-modal" role="dialog" aria-modal="true">
                    <div class="supplier-modal-toolbar">
                        <nav class="supplier-breadcrumb" aria-label="Fil d’Ariane">
                            <button type="button" @click="closeSupplierDrilldown">Fournisseurs</button><i class="bx bx-chevron-right"></i>
                            <button type="button" @click="closeSupplierService">{{ selectedSupplierDrilldown.name }}</button>
                            <template v-if="selectedSupplierService"><i class="bx bx-chevron-right"></i><button type="button" @click="closeSupplierDay">{{ selectedSupplierService.name }}</button></template>
                            <template v-if="selectedSupplierDay"><i class="bx bx-chevron-right"></i><span>{{ selectedSupplierDay.day_label }}</span></template>
                        </nav>
                        <div class="supplier-modal-actions">
                            <button v-if="supplierDrillLevel > 2" type="button" class="supplier-back-button" @click="supplierDrillLevel === 4 ? closeSupplierDay() : closeSupplierService()"><i class="bx bx-arrow-back"></i> Retour</button>
                            <button type="button" class="analytics-modal-close" @click="closeSupplierDrilldown"><i class="bx bx-x"></i><span>Fermer</span></button>
                        </div>
                    </div>

                    <div class="supplier-table-heading">
                        <div><div class="panel-kicker">{{ supplierDrillLevel === 2 ? 'Services' : supplierDrillLevel === 3 ? 'Jours' : 'Plannings et dossiers' }}</div><h3>{{ selectedSupplierDay?.day_label || selectedSupplierService?.name || selectedSupplierDrilldown.name }}</h3></div>
                        <button v-if="selectedSupplierDrilldown.id === 'none' && canManageMissingSuppliers" type="button" class="supplier-assign-button" @click="openMissingSupplierModal"><i class="bx bx-user-plus"></i> Affecter des fournisseurs</button>
                    </div>

                    <div class="supplier-compact-kpis">
                        <div class="supplier-kpi-card kpi-trips"><i class="bx bx-car"></i><div><span>Trajets</span><strong>{{ selectedSupplierDay?.total_trips ?? selectedSupplierService?.total_trips ?? selectedSupplierDrilldown.total_trips }}</strong><small>sur la sélection</small></div></div>
                        <div class="supplier-kpi-card kpi-budget"><i class="bx bx-wallet"></i><div><span>Budget</span><strong>{{ formatMoney(selectedSupplierDay?.total_budget ?? selectedSupplierService?.total_budget ?? selectedSupplierDrilldown.total_budget) }} MAD</strong><small>budget total</small></div></div>
                        <div class="supplier-kpi-card kpi-price"><i class="bx bx-receipt"></i><div><span>Prix fournisseur</span><strong>{{ formatMoney(selectedSupplierDay?.total_supplier_price ?? selectedSupplierService?.total_supplier_price ?? selectedSupplierDrilldown.total_supplier_price) }} MAD</strong><small>coût fournisseur</small></div></div>
                        <div class="supplier-kpi-card kpi-margin"><i class="bx bx-trending-up"></i><div><span>Marge</span><strong>{{ formatMoney(selectedSupplierDay?.gross_margin ?? selectedSupplierService?.gross_margin ?? selectedSupplierDrilldown.gross_margin) }} MAD</strong><small>marge brute</small></div></div>
                    </div>

                    <div class="supplier-table-controls">
                        <label><i class="bx bx-search"></i><input v-model="supplierDrillSearch" type="search" :placeholder="supplierDrillLevel === 2 ? 'Rechercher un service…' : supplierDrillLevel === 3 ? 'Rechercher une date ou un statut…' : 'Référence, chauffeur, client, véhicule…'" /></label>
                        <select v-model.number="supplierDrillPerPage"><option :value="10">10 lignes</option><option :value="25">25 lignes</option><option :value="50">50 lignes</option></select>
                        <span>{{ supplierDrillRows.length }} résultat(s)</span>
                    </div>

                    <div class="supplier-table-scroll">
                        <table class="supplier-detail-table">
                            <thead v-if="supplierDrillLevel === 2"><tr><th>Service</th><th class="head-trips"><i class="bx bx-car"></i> Trajets</th><th class="head-days"><i class="bx bx-calendar"></i> Jours</th><th class="head-budget"><i class="bx bx-wallet"></i> Budget</th><th class="head-price"><i class="bx bx-receipt"></i> Prix fournisseur</th><th class="head-margin"><i class="bx bx-trending-up"></i> Marge</th><th>Indicateur</th><th>Action</th></tr></thead>
                            <thead v-else-if="supplierDrillLevel === 3"><tr><th><i class="bx bx-calendar"></i> Date</th><th>Jour</th><th class="head-trips"><i class="bx bx-car"></i> Trajets</th><th class="head-files"><i class="bx bx-folder"></i> Dossiers</th><th class="head-budget">Budget</th><th class="head-price">Prix fournisseur</th><th class="head-margin">Marge</th><th>Facturé</th><th>Non facturé</th><th>Payé</th><th>Non payé</th><th></th></tr></thead>
                            <thead v-else><tr><th>Référence</th><th>Date</th><th>Heure</th><th>Service</th><th>Départ</th><th>Destination</th><th>Client supplier</th><th>Chauffeur</th><th>Guide</th><th>Véhicule</th><th>Fournisseur</th><th class="head-budget">Budget</th><th class="head-price">Prix fournisseur</th><th class="head-margin">Marge</th><th>Facture</th><th>Paiement</th><th class="actions-head">Actions</th></tr></thead>
                            <tbody v-if="supplierDrillLevel === 2"><tr v-for="(service, index) in paginatedSupplierDrillRows" :key="service.id" class="supplier-clickable-row service-color-row" :style="{ '--service-color': supplierColor(index) }" @click="openSupplierService(service)"><td><span class="supplier-cell-title"><i class="bx bx-transfer-alt"></i>{{ service.name }}</span></td><td><span class="metric-pill metric-trips"><i class="bx bx-car"></i>{{ service.total_trips }}</span></td><td><span class="metric-pill metric-days"><i class="bx bx-calendar"></i>{{ service.days?.length || 0 }}</span></td><td class="money-cell money-budget">{{ formatMoney(service.total_budget) }} MAD</td><td class="money-cell money-price">{{ formatMoney(service.total_supplier_price) }} MAD</td><td class="money-cell money-margin">{{ formatMoney(service.gross_margin) }} MAD</td><td><span class="supplier-status-badge badge-paid"><i class="bx bx-check-circle"></i> Actif</span></td><td><span class="supplier-view-action">Voir les jours <i class="bx bx-right-arrow-alt"></i></span></td></tr></tbody>
                            <tbody v-else-if="supplierDrillLevel === 3"><tr v-for="day in paginatedSupplierDrillRows" :key="day.date" class="supplier-clickable-row day-color-row" @click="openSupplierServiceDay((selectedSupplierService.days || []).findIndex(item => item.date === day.date))"><td><span class="date-cell"><i class="bx bx-calendar"></i>{{ day.label }}</span></td><td><span class="day-badge">{{ day.day_label }}</span></td><td><span class="metric-pill metric-trips">{{ day.total_trips }}</span></td><td><span class="metric-pill metric-files">{{ supplierDayStats(day).dossiers }}</span></td><td class="money-cell money-budget">{{ formatMoney(day.total_budget) }} MAD</td><td class="money-cell money-price">{{ formatMoney(day.total_supplier_price) }} MAD</td><td class="money-cell money-margin">{{ formatMoney(day.gross_margin) }} MAD</td><td><span class="count-badge count-success" :class="{ 'count-zero': !supplierDayStats(day).invoiced }"><i class="bx bx-file"></i>{{ supplierDayStats(day).invoiced }}</span></td><td><span class="count-badge count-danger" :class="{ 'count-zero': !supplierDayStats(day).notInvoiced }"><i class="bx bx-time-five"></i>{{ supplierDayStats(day).notInvoiced }}</span></td><td><span class="count-badge count-paid" :class="{ 'count-zero': !supplierDayStats(day).paid }"><i class="bx bx-check-shield"></i>{{ supplierDayStats(day).paid }}</span></td><td><span class="count-badge count-unpaid" :class="{ 'count-zero': !supplierDayStats(day).unpaid }"><i class="bx bx-error-circle"></i>{{ supplierDayStats(day).unpaid }}</span></td><td><span class="supplier-view-action icon-only"><i class="bx bx-right-arrow-alt"></i></span></td></tr></tbody>
                            <tbody v-else><tr v-for="planning in paginatedSupplierDrillRows" :key="planning.id"><td><strong>{{ planning.ref_dossier }}</strong></td><td>{{ planning.date_du }}</td><td>{{ planning.heure || '-' }}</td><td>{{ planning.service }}</td><td>{{ planning.point_depart }}</td><td>{{ planning.destination }}</td><td>{{ planning.supplier_client }}</td><td>{{ planning.driver }}</td><td>{{ planning.guide }}</td><td>{{ planning.vehicule }}</td><td>{{ planning.supplier_vehicle }}</td><td class="money-cell money-budget">{{ formatMoney(planning.budget) }}</td><td class="money-cell money-price">{{ formatMoney(planning.supplier_price) }}</td><td class="money-cell money-margin">{{ formatMoney(planning.gross_margin) }}</td><td><span class="supplier-status-badge" :class="invoiceBadgeClass(planning)"><i class="bx" :class="planning.invoice ? 'bx-file-find' : 'bx-time-five'"></i>{{ planning.invoice ? 'Facturé' : 'Non facturé' }}</span></td><td><span class="supplier-status-badge" :class="paymentBadgeClass(planning)"><i class="bx bx-credit-card"></i>{{ paymentLabel(planning) }}</span></td><td class="planning-row-actions"><button v-if="canEditPlanningService" type="button" class="compact-action-button service-action-button" title="Affecter le service" aria-label="Affecter le service" @click="openPlanningServiceModal(planning)"><i class="bx bx-layer-plus"></i><span>Affecter service</span></button><button v-if="canLinkSupplierInvoice" type="button" class="compact-action-button invoice-action-button" :class="{ linked: planning.invoice }" :title="planning.supplier_vehicle_id ? (planning.invoice ? 'Modifier la liaison à une facture' : 'Lier à une facture') : 'Affectez d’abord un fournisseur véhicule'" :aria-label="planning.invoice ? 'Modifier la liaison à une facture' : 'Lier à une facture'" :disabled="!planning.supplier_vehicle_id" @click="openInvoiceLinkModal(planning)"><i class="bx" :class="planning.invoice ? 'bx-file-find' : 'bx-receipt'"></i><span>Affecter facture</span></button><button v-if="canAssignPlanningSupplier" type="button" class="compact-action-button supplier-action-button" :title="planning.supplier_vehicle_id ? 'Un fournisseur véhicule est déjà affecté' : 'Affecter le fournisseur véhicule'" aria-label="Affecter le fournisseur véhicule" :disabled="Boolean(planning.supplier_vehicle_id)" @click="openSupplierAssignmentModal(planning)"><i class="bx bx-car"></i><span>Affecter fournisseur</span></button></td></tr></tbody>
                        </table>
                        <div v-if="!supplierDrillRows.length" class="planning-fiche-empty">Aucune donnée trouvée pour cette sélection.</div>
                    </div>
                    <div class="supplier-pagination"><button type="button" :disabled="supplierDrillPage <= 1" @click="supplierDrillPage--"><i class="bx bx-chevron-left"></i></button><span>Page {{ Math.min(supplierDrillPage, supplierDrillPageCount) }} / {{ supplierDrillPageCount }}</span><button type="button" :disabled="supplierDrillPage >= supplierDrillPageCount" @click="supplierDrillPage++"><i class="bx bx-chevron-right"></i></button></div>
                </div>
            </div>

            <div
                v-if="false && selectedSupplierDrilldown"
                class="analytics-modal-backdrop"
                @click.self="closeSupplierDrilldown"
            >
                <div class="analytics-modal supplier-drill-modal">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Lecture fournisseur</div>
                            <h3>{{ selectedSupplierDrilldown.name }}</h3>
                            <p>
                                Services réalisés par ce fournisseur sur la
                                période filtrée. Cliquez sur un service pour
                                ouvrir le détail journalier.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="analytics-modal-close"
                            @click="closeSupplierDrilldown"
                        >
                            <i class="bx bx-x"></i>
                            <span>Fermer</span>
                        </button>
                    </div>

                    <div class="supplier-drill-hero">
                        <div>
                            <span>Total trajets</span>
                            <strong>
                                {{ selectedSupplierDrilldown.total_trips }}
                            </strong>
                        </div>
                        <div>
                            <span>Budget</span>
                            <strong>
                                {{
                                    formatMoney(
                                        selectedSupplierDrilldown.total_budget,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                        <div>
                            <span>Marge</span>
                            <strong class="positive">
                                {{
                                    formatMoney(
                                        selectedSupplierDrilldown.gross_margin,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                    </div>

                    <div class="supplier-service-board">
                        <div class="supplier-service-board-head">
                            <div>
                                <strong>Services du fournisseur</strong>
                                <span>
                                    Lecture claire par service, triée par volume
                                    de trajets.
                                </span>
                            </div>
                            <div class="service-board-total">
                                {{ selectedSupplierServices.length }} services
                            </div>
                        </div>

                        <div class="supplier-service-cards">
                            <button
                                v-for="(service, index) in selectedSupplierServices"
                                :key="service.id"
                                type="button"
                                class="service-drill-card"
                                @click="openSupplierService(service)"
                            >
                                <span
                                    class="service-color-dot"
                                    :style="{
                                        background:
                                            supplierPerformanceColors[
                                                index %
                                                    supplierPerformanceColors.length
                                            ],
                                    }"
                                ></span>
                                <div class="service-drill-main">
                                    <div>
                                        <strong>{{ service.name }}</strong>
                                        <small>
                                            Cliquez pour voir les jours et les
                                            dossiers liés.
                                        </small>
                                    </div>

                                    <i class="bx bx-chevron-right"></i>
                                </div>

                                <div class="service-drill-kpis">
                                    <div>
                                        <span>Trajets</span>
                                        <strong>{{ service.total_trips }}</strong>
                                    </div>
                                    <div>
                                        <span>Budget</span>
                                        <strong>
                                            {{ formatMoney(service.total_budget) }}
                                            MAD
                                        </strong>
                                    </div>
                                    <div>
                                        <span>Marge</span>
                                        <strong class="positive">
                                            {{ formatMoney(service.gross_margin) }}
                                            MAD
                                        </strong>
                                    </div>
                                </div>

                                <div class="service-meter">
                                    <span
                                        :style="{
                                            width:
                                                (service.total_trips /
                                                    Math.max(
                                                        ...selectedSupplierServices.map(
                                                            (item) =>
                                                                item.total_trips,
                                                        ),
                                                        1,
                                                    )) *
                                                        100 +
                                                '%',
                                        }"
                                    ></span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="false && selectedSupplierService"
                class="analytics-modal-backdrop analytics-modal-backdrop-top"
                @click.self="closeSupplierService"
            >
                <div class="analytics-modal analytics-modal-compact">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Détail service</div>
                            <h3>{{ selectedSupplierService.name }}</h3>
                            <p>
                                {{ selectedSupplierDrilldown?.name }} — lecture
                                jour par jour.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="analytics-modal-close"
                            @click="closeSupplierService"
                        >
                            <i class="bx bx-x"></i>
                            <span>Fermer</span>
                        </button>
                    </div>

                    <div class="supplier-drill-hero compact">
                        <div>
                            <span>Trajets</span>
                            <strong>{{ selectedSupplierService.total_trips }}</strong>
                        </div>
                        <div>
                            <span>Budget</span>
                            <strong>
                                {{
                                    formatMoney(
                                        selectedSupplierService.total_budget,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                        <div>
                            <span>Marge</span>
                            <strong class="positive">
                                {{
                                    formatMoney(
                                        selectedSupplierService.gross_margin,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                    </div>

                    <div class="analytics-modal-chart">
                        <VueApexCharts
                            type="bar"
                            height="430"
                            :options="supplierServiceDayChartOptions"
                            :series="supplierServiceDaySeries"
                        />
                    </div>

                    <div class="chart-click-hint day-fiche-hint">
                        <i class="bx bx-pointer"></i>
                        Cliquez sur une barre pour ouvrir la fiche complète des
                        dossiers, factures et paiements.
                    </div>
                </div>
            </div>

            <div
                v-if="false && selectedSupplierDay"
                class="analytics-modal-backdrop analytics-modal-backdrop-top"
                @click.self="closeSupplierDay"
            >
                <div class="analytics-modal supplier-day-modal">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Fiche journée</div>
                            <h3>
                                {{ selectedSupplierService?.name }} -
                                {{ selectedSupplierDay.day_label }}
                            </h3>
                            <p>
                                {{ selectedSupplierDrilldown?.name }} - vue
                                complète planning, facture et paiement.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="analytics-modal-close"
                            @click="closeSupplierDay"
                        >
                            <i class="bx bx-x"></i>
                            <span>Fermer</span>
                        </button>
                    </div>

                    <div class="supplier-drill-hero compact day-fiche-summary">
                        <div>
                            <span>Dossiers</span>
                            <strong>{{ selectedSupplierDay.total_trips }}</strong>
                        </div>
                        <div>
                            <span>Budget</span>
                            <strong>
                                {{ formatMoney(selectedSupplierDay.total_budget) }}
                                MAD
                            </strong>
                        </div>
                        <div>
                            <span>Prix fournisseur</span>
                            <strong>
                                {{
                                    formatMoney(
                                        selectedSupplierDay.total_supplier_price,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                        <div>
                            <span>Marge</span>
                            <strong class="positive">
                                {{ formatMoney(selectedSupplierDay.gross_margin) }}
                                MAD
                            </strong>
                        </div>
                    </div>

                    <div class="day-fiche-status-row">
                        <div class="day-status-pill invoice-ready">
                            <span>Facturées</span>
                            <strong>{{ selectedSupplierDayStats.invoiced }}</strong>
                        </div>
                        <div class="day-status-pill invoice-missing">
                            <span>Non facturées</span>
                            <strong>
                                {{ selectedSupplierDayStats.notInvoiced }}
                            </strong>
                        </div>
                        <div class="day-status-pill payment-paid">
                            <span>Payées</span>
                            <strong>{{ selectedSupplierDayStats.paid }}</strong>
                        </div>
                        <div class="day-status-pill payment-partial">
                            <span>Partielles</span>
                            <strong>{{ selectedSupplierDayStats.partial }}</strong>
                        </div>
                        <div class="day-status-pill payment-unpaid">
                            <span>Non payées</span>
                            <strong>{{ selectedSupplierDayStats.unpaid }}</strong>
                        </div>
                    </div>

                    <div class="planning-fiche-grid">
                        <article
                            v-for="planning in selectedSupplierDayPlannings"
                            :key="planning.id"
                            class="planning-fiche-card"
                        >
                            <div class="planning-fiche-top">
                                <div>
                                    <span class="planning-fiche-ref">
                                        {{ planning.ref_dossier }}
                                    </span>
                                    <div class="planning-service-chip" :class="{ 'is-missing': !planning.service_id }">
                                        <span class="planning-service-chip-icon"><i :class="['bx', planning.service_id ? 'bx-briefcase-alt-2' : 'bx-error-circle']"></i></span>
                                        <span><small>Service</small><strong>{{ planning.service || "Sans service" }}</strong></span>
                                    </div>
                                </div>

                                <div class="planning-fiche-badges">
                                    <span
                                        class="fiche-badge"
                                        :class="invoiceBadgeClass(planning)"
                                    >
                                        {{ planning.invoice_label }}
                                    </span>
                                    <span
                                        class="fiche-badge"
                                        :class="paymentBadgeClass(planning)"
                                    >
                                        {{ paymentLabel(planning) }}
                                    </span>
                                </div>
                            </div>

                            <div class="planning-route-card">
                                <div>
                                    <span>Départ</span>
                                    <strong>{{ planning.point_depart }}</strong>
                                </div>
                                <i class="bx bx-right-arrow-alt"></i>
                                <div>
                                    <span>Destination</span>
                                    <strong>{{ planning.destination }}</strong>
                                </div>
                            </div>

                            <button v-if="canEditPlanningService" type="button" class="planning-service-action" @click="openPlanningServiceModal(planning)">
                                <span class="planning-service-action-icon"><i :class="['bx', planning.service_id ? 'bx-edit-alt' : 'bx-plus']"></i></span>
                                <span><strong>{{ planning.service_id ? "Modifier le service" : "Affecter un service" }}</strong><small>{{ planning.service_id ? "Mettre à jour cette prestation" : "Compléter les informations du trajet" }}</small></span>
                                <i class="bx bx-chevron-right planning-service-action-arrow"></i>
                            </button>

                            <div class="planning-meta-grid">
                                <div>
                                    <span>Date</span>
                                    <strong>{{ planning.date_du }}</strong>
                                </div>
                                <div>
                                    <span>Heure</span>
                                    <strong>{{ planning.heure || "-" }}</strong>
                                </div>
                                <div>
                                    <span>Pax</span>
                                    <strong>{{ planning.nbr_personnes || 0 }}</strong>
                                </div>
                                <div>
                                    <span>Client supplier</span>
                                    <strong>{{ planning.supplier_client }}</strong>
                                </div>
                                <div>
                                    <span>Driver</span>
                                    <strong>{{ planning.driver }}</strong>
                                </div>
                                <div>
                                    <span>Guide</span>
                                    <strong>{{ planning.guide }}</strong>
                                </div>
                                <div>
                                    <span>Véhicule</span>
                                    <strong>{{ planning.vehicule }}</strong>
                                </div>
                                <div>
                                    <span>Vol / site</span>
                                    <strong>
                                        {{ planning.flight }} / {{ planning.site }}
                                    </strong>
                                </div>
                            </div>

                            <div
                                v-if="planning.clients?.length"
                                class="planning-clients-line"
                            >
                                <i class="bx bx-group"></i>
                                {{ planning.clients.join(", ") }}
                            </div>

                            <div class="planning-money-grid">
                                <div>
                                    <span>Budget</span>
                                    <strong>{{ formatMoney(planning.budget) }} MAD</strong>
                                </div>
                                <div>
                                    <span>Supplier price</span>
                                    <strong>
                                        {{ formatMoney(planning.supplier_price) }}
                                        MAD
                                    </strong>
                                </div>
                                <div>
                                    <span>Marge</span>
                                    <strong class="positive">
                                        {{ formatMoney(planning.gross_margin) }}
                                        MAD
                                    </strong>
                                </div>
                            </div>

                            <div
                                v-if="planning.invoice"
                                class="planning-invoice-card"
                            >
                                <div>
                                    <span>Facture</span>
                                    <strong>
                                        #{{ planning.invoice.number || planning.invoice.id }}
                                    </strong>
                                </div>
                                <div>
                                    <span>Date facture</span>
                                    <strong>{{ planning.invoice.date || "-" }}</strong>
                                </div>
                                <div>
                                    <span>Total</span>
                                    <strong>
                                        {{
                                            formatMoney(
                                                planning.invoice.total_amount,
                                            )
                                        }}
                                        MAD
                                    </strong>
                                </div>
                                <div>
                                    <span>Payé</span>
                                    <strong>
                                        {{
                                            formatMoney(
                                                planning.invoice.paid_amount,
                                            )
                                        }}
                                        MAD
                                    </strong>
                                </div>
                                <div>
                                    <span>Reste</span>
                                    <strong>
                                        {{
                                            formatMoney(
                                                planning.invoice.remaining_amount,
                                            )
                                        }}
                                        MAD
                                    </strong>
                                </div>
                            </div>

                            <div
                                v-else
                                class="planning-invoice-empty planning-invoice-empty-action"
                            >
                                <div>
                                    <i class="bx bx-receipt"></i>
                                    Ce dossier n'est pas encore lié à une facture
                                    fournisseur véhicule.
                                </div>
                                <button
                                    v-if="
                                        canLinkSupplierInvoice &&
                                        planning.supplier_vehicle_id
                                    "
                                    type="button"
                                    class="link-invoice-button"
                                    @click="openInvoiceLinkModal(planning)"
                                >
                                    <i class="bx bx-link-alt"></i>
                                    Rattacher à une facture
                                </button>
                                <span
                                    v-else-if="!planning.supplier_vehicle_id"
                                    class="link-invoice-hint"
                                >
                                    Aucun fournisseur véhicule sur ce service.
                                </span>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="!selectedSupplierDayPlannings.length"
                        class="planning-fiche-empty"
                    >
                        Aucun dossier détaillé trouvé pour cette journée.
                    </div>
                </div>
            </div>

            <Teleport to="body">
            <div
                v-if="planningServiceModal"
                class="planning-action-overlay"
                @click.self="closePlanningServiceModal"
            >
                <div class="planning-action-panel planning-service-modal" role="dialog" aria-modal="true" aria-labelledby="planning-service-title">
                    <div class="planning-service-hero">
                        <div>
                            <div class="planning-service-eyebrow"><i class="bx bx-layer-plus"></i> Gestion du service</div>
                            <h3 class="text-white" id="planning-service-title">{{ planningServiceModal.service_id ? "Modifier l’affectation" : "Affecter un service" }}</h3>
                            <p>
                                Dossier <strong>{{ planningServiceModal.ref_dossier }}</strong> · Seul le service de ce planning sera modifié.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="analytics-modal-close"
                            :disabled="serviceForm.processing"
                            @click="closePlanningServiceModal"
                        >
                            <i class="bx bx-x"></i>
                            <span class="sr-only">Fermer</span>
                        </button>
                    </div>

                    <div class="planning-service-route-summary">
                        <div><span>Départ</span><strong>{{ planningServiceModal.point_depart || "-" }}</strong></div>
                        <i class="bx bx-right-arrow-alt"></i>
                        <div><span>Destination</span><strong>{{ planningServiceModal.destination || "-" }}</strong></div>
                    </div>

                    <div class="planning-service-section-title"><i class="bx bx-info-circle"></i> Informations du planning</div>
                    <div class="planning-service-context">
                        <div><span>Date & heure</span><strong>{{ planningServiceModal.date_du || "-" }} · {{ planningServiceModal.heure || "-" }}</strong></div>
                        <div><span>Client</span><strong>{{ planningServiceModal.clients?.join(", ") || planningServiceModal.supplier_client || "-" }}</strong></div>
                        <div><span>Fournisseur véhicule</span><strong>{{ planningServiceModal.supplier_vehicle || "-" }}</strong></div>
                        <div><span>Chauffeur</span><strong>{{ planningServiceModal.driver || "-" }}</strong></div>
                        <div><span>Véhicule</span><strong>{{ planningServiceModal.vehicule || "-" }}</strong></div>
                        <div><span>Service actuel</span><strong :class="{ 'planning-service-missing': !planningServiceModal.service_id }">{{ planningServiceModal.service || "Sans service" }}</strong></div>
                    </div>

                    <div
                        v-if="planningServiceModal.recommended_service_ids?.length"
                        class="planning-service-recommendation"
                    >
                        <i class="bx bx-bulb"></i>
                        <div>
                            <strong>Services cohérents proposés en premier</strong>
                            <span>{{ planningServiceModal.recommendation_reason }}</span>
                        </div>
                    </div>

                    <div class="planning-service-field">
                        <label>Rechercher et sélectionner un service</label>
                        <SearchSelect
                            v-model="serviceForm.service_id"
                            :search="serviceForm.search"
                            @update:search="updateServiceSearch"
                            :options="serviceOptions"
                            label-key="designation"
                            value-key="id"
                            :allow-custom="false"
                            placeholder="Rechercher un service par son nom..."
                        />
                        <p class="planning-service-help"><i class="bx bx-check-shield"></i> Cette action ne modifie ni les factures ni les paiements.</p>
                    </div>

                    <div class="planning-service-footer">
                        <button
                            type="button"
                            class="planning-service-cancel"
                            :disabled="serviceForm.processing"
                            @click="closePlanningServiceModal"
                        >
                            Annuler
                        </button>
                        <button
                            type="button"
                            class="planning-service-save"
                            :disabled="!serviceForm.service_id || serviceForm.processing"
                            @click="savePlanningService"
                        >
                            <i :class="['bx', serviceForm.processing ? 'bx-loader-alt bx-spin' : 'bx-check']"></i>
                            Enregistrer l’affectation
                        </button>
                    </div>
                </div>
            </div>
            </Teleport>

            <Teleport to="body">
                <div
                    v-if="supplierAssignmentModal.open"
                    class="planning-action-overlay"
                    @click.self="closeSupplierAssignmentModal"
                >
                    <div class="planning-action-panel supplier-quick-assign-modal" role="dialog" aria-modal="true" aria-labelledby="supplier-assign-title">
                        <div class="supplier-quick-assign-hero">
                            <div>
                                <div class="planning-service-eyebrow"><i class="bx bx-car"></i> Fournisseur véhicule</div>
                                <h3 id="supplier-assign-title">Affecter le planning</h3>
                                <p>Dossier <strong>{{ supplierAssignmentModal.planning?.ref_dossier }}</strong></p>
                            </div>
                            <button type="button" class="analytics-modal-close" :disabled="supplierAssignmentModal.processing" @click="closeSupplierAssignmentModal"><i class="bx bx-x"></i><span class="sr-only">Fermer</span></button>
                        </div>

                        <div class="supplier-assign-context">
                            <div><i class="bx bx-layer"></i><span>Service</span><strong>{{ supplierAssignmentModal.planning?.service || "Sans service" }}</strong></div>
                            <div><i class="bx bx-user"></i><span>Chauffeur</span><strong>{{ supplierAssignmentModal.planning?.driver || "-" }}</strong></div>
                            <div><i class="bx bx-bus"></i><span>Véhicule</span><strong>{{ supplierAssignmentModal.planning?.vehicule || "-" }}</strong></div>
                        </div>

                        <label class="supplier-assign-field">
                            <span>Fournisseur véhicule</span>
                            <select v-model="supplierAssignmentModal.supplierId" :disabled="supplierAssignmentModal.processing">
                                <option value="">Sélectionner un fournisseur…</option>
                                <option v-for="supplier in supplierVehicules" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                            </select>
                        </label>

                        <div class="planning-service-footer">
                            <button type="button" class="planning-service-cancel" :disabled="supplierAssignmentModal.processing" @click="closeSupplierAssignmentModal">Annuler</button>
                            <button type="button" class="supplier-assignment-save" :disabled="!supplierAssignmentModal.supplierId || supplierAssignmentModal.processing" @click="saveSupplierAssignment"><i :class="['bx', supplierAssignmentModal.processing ? 'bx-loader-alt bx-spin' : 'bx-check']"></i> Confirmer l’affectation</button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <Teleport to="body">
            <div
                v-if="invoiceLinkModal.open"
                class="planning-action-overlay"
                @click.self="closeInvoiceLinkModal"
            >
                <div class="planning-action-panel invoice-link-panel" role="dialog" aria-modal="true" aria-labelledby="invoice-link-title">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Rattachement facture</div>
                            <h3 id="invoice-link-title">
                                {{
                                    invoiceLinkModal.planning?.ref_dossier ||
                                    "Service"
                                }}
                            </h3>
                            <p>
                                Choisissez la facture du même fournisseur à
                                laquelle ce service doit être rattaché.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="analytics-modal-close"
                            :disabled="invoiceLinkModal.processing"
                            @click="closeInvoiceLinkModal"
                        >
                            <i class="bx bx-x"></i>
                            Fermer
                        </button>
                    </div>

                    <div class="invoice-link-summary">
                        <div>
                            <span>Fournisseur</span>
                            <strong>{{ invoiceLinkModal.planning?.supplier_vehicle }}</strong>
                        </div>
                        <div>
                            <span>Date</span>
                            <strong>{{ invoiceLinkModal.planning?.date_du }}</strong>
                        </div>
                        <div>
                            <span>Prix fournisseur</span>
                            <strong>
                                {{
                                    formatMoney(
                                        invoiceLinkModal.planning?.supplier_price,
                                    )
                                }}
                                MAD
                            </strong>
                        </div>
                    </div>

                    <form @submit.prevent="submitInvoiceLink">
                        <div
                            v-if="invoiceLinkOptions.length"
                            class="invoice-choice-grid"
                        >
                            <label
                                v-for="invoice in invoiceLinkOptions"
                                :key="invoice.id"
                                class="invoice-choice-card"
                                :class="{
                                    active:
                                        Number(invoiceLinkModal.invoice_id) ===
                                        Number(invoice.id),
                                }"
                            >
                                <input
                                    v-model="invoiceLinkModal.invoice_id"
                                    type="radio"
                                    :value="invoice.id"
                                />
                                <div>
                                    <span>Facture</span>
                                    <strong>#{{ invoice.number }}</strong>
                                </div>
                                <div>
                                    <span>Période</span>
                                    <strong>{{ invoice.period }}</strong>
                                </div>
                                <div>
                                    <span>Total</span>
                                    <strong>
                                        {{ formatMoney(invoice.total_amount) }} MAD
                                    </strong>
                                </div>
                                <div>
                                    <span>Reste</span>
                                    <strong>
                                        {{ formatMoney(invoice.remaining_amount) }}
                                        MAD
                                    </strong>
                                </div>
                            </label>
                        </div>

                        <div v-else class="invoice-choice-empty">
                            <i class="bx bx-info-circle"></i>
                            Aucune facture trouvée pour ce fournisseur sur cette
                            période. Créez d'abord la facture fournisseur véhicule.
                        </div>

                        <div class="invoice-link-actions">
                            <button
                                type="button"
                                class="invoice-link-secondary"
                                @click="closeInvoiceLinkModal"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                class="invoice-link-primary"
                                :disabled="
                                    !invoiceLinkModal.invoice_id ||
                                    invoiceLinkModal.processing
                                "
                            >
                                <i class="bx bx-check"></i>
                                {{
                                    invoiceLinkModal.processing
                                        ? "Rattachement..."
                                        : "Confirmer le rattachement"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            </Teleport>

            <Teleport to="body">
                <div
                    v-if="missingServiceModal.open"
                    class="planning-action-overlay"
                    @click.self="closeMissingServiceModal"
                >
                    <section class="planning-action-panel missing-supplier-panel missing-service-panel" role="dialog" aria-modal="true" aria-labelledby="missing-service-title">
                        <header class="missing-supplier-hero missing-service-hero">
                            <div>
                                <div class="planning-service-eyebrow"><i class="bx bx-layer-plus"></i> Contrôle des services</div>
                                <h3 id="missing-service-title">Plannings sans service</h3>
                                <p>Corrigez les plannings un par un ou en masse sans quitter cette interface.</p>
                            </div>
                            <div class="missing-supplier-hero-actions">
                                <div class="missing-supplier-count"><span>Restants</span><strong>{{ missingServiceModal.total }}</strong></div>
                                <button type="button" class="analytics-modal-close" @click="closeMissingServiceModal"><i class="bx bx-x"></i><span>Fermer</span></button>
                            </div>
                        </header>

                        <form class="missing-supplier-filters missing-service-filters" @submit.prevent="loadMissingServicePlannings(1)">
                            <label><span>Date début</span><input v-model="missingServiceFilters.date_from" type="date" /></label>
                            <label><span>Date fin</span><input v-model="missingServiceFilters.date_to" type="date" /></label>
                            <label><span>Date précise</span><input v-model="missingServiceFilters.date" type="date" /></label>
                            <label><span>Chauffeur</span><select v-model="missingServiceFilters.driver_id"><option value="">Tous les chauffeurs</option><option v-for="driver in missingServiceModal.options.drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option></select></label>
                            <label><span>Client</span><select v-model="missingServiceFilters.client_id"><option value="">Tous les clients</option><option v-for="client in missingServiceModal.options.clients" :key="client.id" :value="client.id">{{ client.full_name }}</option></select></label>
                            <label><span>Destination</span><select v-model="missingServiceFilters.destination_id"><option value="">Toutes les destinations</option><option v-for="destination in missingServiceModal.options.destinations" :key="destination.id" :value="destination.id">{{ destination.name }}{{ destination.city ? ` - ${destination.city}` : '' }}</option></select></label>
                            <label class="missing-supplier-search"><span>Référence / N° planning</span><input v-model.trim="missingServiceFilters.search" type="search" placeholder="Ex. DOS-2026 ou 1542" /></label>
                            <div class="missing-filter-actions">
                                <button type="submit" class="missing-primary-button"><i class="bx bx-search"></i> Rechercher</button>
                                <button type="button" class="missing-secondary-button" @click="resetMissingServiceFilters">Réinitialiser</button>
                            </div>
                        </form>

                        <div class="missing-bulk-toolbar">
                            <label class="missing-select-all"><input type="checkbox" :checked="allVisibleMissingServicesSelected" @change="toggleAllVisibleMissingServices" /> Tout sélectionner</label>
                            <span class="missing-selection-count">{{ missingServiceModal.selectedIds.length }} planning(s) sélectionné(s)</span>
                            <button type="button" class="missing-secondary-button" :disabled="!missingServiceModal.selectedIds.length" @click="missingServiceModal.selectedIds = []">Annuler la sélection</button>
                            <div class="missing-service-search-select">
                                <SearchSelect
                                    v-model="missingServiceModal.bulkServiceId"
                                    v-model:search="missingServiceModal.bulkServiceSearch"
                                    :options="missingServiceModal.options.services"
                                    label-key="designation"
                                    placeholder="Choisir un service commun"
                                    :allow-custom="false"
                                />
                            </div>
                            <button type="button" class="missing-primary-button" :disabled="missingServiceModal.processing || !missingServiceModal.selectedIds.length || !missingServiceModal.bulkServiceId" @click="assignMissingService(missingServiceModal.selectedIds, missingServiceModal.bulkServiceId, true)"><i class="bx bx-check-double"></i> Affecter le service aux plannings sélectionnés</button>
                        </div>

                        <div class="missing-supplier-table-wrap">
                            <div v-if="missingServiceModal.loading" class="missing-supplier-loading"><i class="bx bx-loader-alt bx-spin"></i> Chargement des plannings…</div>
                            <table v-else class="missing-supplier-table missing-service-table">
                                <thead><tr><th class="check-column"></th><th>Dossier / Planning</th><th>Date</th><th>Client</th><th>Chauffeur</th><th>Véhicule</th><th>Destination</th><th>Service actuel</th><th>Service à affecter</th><th>Action</th></tr></thead>
                                <tbody>
                                    <tr v-for="planning in missingServiceModal.rows" :key="planning.id">
                                        <td class="check-column"><input type="checkbox" :checked="isMissingServicePlanningSelected(planning.id)" @change="toggleMissingServicePlanning(planning.id)" /></td>
                                        <td><strong>{{ planning.reference }}</strong><small>{{ planning.planning_number }}</small></td>
                                        <td>{{ planning.date }}</td>
                                        <td>{{ planning.clients }}</td>
                                        <td>{{ planning.driver }}</td>
                                        <td>{{ planning.vehicle }}</td>
                                        <td>{{ planning.destination }}</td>
                                        <td><span class="missing-service-badge">{{ planning.service }}</span></td>
                                        <td>
                                            <div class="missing-service-row-select">
                                                <SearchSelect
                                                    v-model="missingServiceModal.rowServiceIds[planning.id]"
                                                    v-model:search="missingServiceModal.rowServiceSearches[planning.id]"
                                                    :options="missingServiceModal.options.services"
                                                    label-key="designation"
                                                    placeholder="Rechercher un service…"
                                                    :allow-custom="false"
                                                />
                                            </div>
                                        </td>
                                        <td><button type="button" class="missing-row-assign" :disabled="missingServiceModal.processing || !missingServiceModal.rowServiceIds[planning.id]" @click="assignMissingService([planning.id], missingServiceModal.rowServiceIds[planning.id])">Affecter</button></td>
                                    </tr>
                                    <tr v-if="!missingServiceModal.rows.length"><td colspan="10" class="missing-table-empty"><i class="bx bx-check-circle"></i> Aucun planning sans service pour ces filtres.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <footer v-if="missingServiceModal.lastPage > 1" class="missing-pagination">
                            <button type="button" :disabled="missingServiceModal.currentPage <= 1" @click="loadMissingServicePlannings(missingServiceModal.currentPage - 1)"><i class="bx bx-chevron-left"></i> Précédent</button>
                            <span>Page {{ missingServiceModal.currentPage }} / {{ missingServiceModal.lastPage }}</span>
                            <button type="button" :disabled="missingServiceModal.currentPage >= missingServiceModal.lastPage" @click="loadMissingServicePlannings(missingServiceModal.currentPage + 1)">Suivant <i class="bx bx-chevron-right"></i></button>
                        </footer>
                    </section>
                </div>
            </Teleport>

            <Teleport to="body">
                <div
                    v-if="missingSupplierModal.open"
                    class="planning-action-overlay"
                    @click.self="closeMissingSupplierModal"
                >
                    <section
                        class="planning-action-panel missing-supplier-panel"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="missing-supplier-title"
                    >
                        <header class="missing-supplier-hero">
                            <div>
                                <div class="planning-service-eyebrow">
                                    <i class="bx bx-buildings"></i>
                                    Contrôle fournisseurs véhicules
                                </div>
                                <h3 id="missing-supplier-title">
                                    Plannings sans fournisseur véhicule
                                </h3>
                                <p>
                                    Filtrez, sélectionnez puis affectez un fournisseur sans écraser les plannings déjà traités.
                                </p>
                            </div>
                            <div class="missing-supplier-hero-actions">
                                <div class="missing-supplier-count">
                                    <span>Restants</span>
                                    <strong>{{ missingSupplierModal.total }}</strong>
                                </div>
                                <button
                                    type="button"
                                    class="missing-supplier-auto-button hero-button"
                                    :disabled="missingSupplierModal.autoProcessing"
                                    @click="runAutomaticMdToursAssignment"
                                >
                                    <i class="bx bx-magic-wand"></i>
                                    Corriger MD TOURS
                                </button>
                                <button
                                    type="button"
                                    class="analytics-modal-close"
                                    @click="closeMissingSupplierModal"
                                >
                                    <i class="bx bx-x"></i>
                                    <span>Fermer</span>
                                </button>
                            </div>
                        </header>

                        <form
                            class="missing-supplier-filters"
                            @submit.prevent="loadMissingSupplierPlannings(1)"
                        >
                            <label>
                                <span>Du</span>
                                <input v-model="missingSupplierFilters.date_from" type="date" />
                            </label>
                            <label>
                                <span>Au</span>
                                <input v-model="missingSupplierFilters.date_to" type="date" />
                            </label>
                            <label>
                                <span>Date précise</span>
                                <input v-model="missingSupplierFilters.date" type="date" />
                            </label>
                            <label>
                                <span>Service</span>
                                <select v-model="missingSupplierFilters.service_id">
                                    <option value="">Tous les services</option>
                                    <option
                                        v-for="service in missingSupplierModal.options.services"
                                        :key="service.id"
                                        :value="service.id"
                                    >
                                        {{ service.designation }}
                                    </option>
                                </select>
                            </label>
                            <label>
                                <span>Chauffeur</span>
                                <select v-model="missingSupplierFilters.driver_id">
                                    <option value="">Tous les chauffeurs</option>
                                    <option
                                        v-for="driver in missingSupplierModal.options.drivers"
                                        :key="driver.id"
                                        :value="driver.id"
                                    >
                                        {{ driver.name }}
                                    </option>
                                </select>
                            </label>
                            <label>
                                <span>Client</span>
                                <select v-model="missingSupplierFilters.client_id">
                                    <option value="">Tous les clients</option>
                                    <option
                                        v-for="client in missingSupplierModal.options.clients"
                                        :key="client.id"
                                        :value="client.id"
                                    >
                                        {{ client.full_name }}
                                    </option>
                                </select>
                            </label>
                            <label class="missing-supplier-search">
                                <span>Référence / N° planning</span>
                                <input
                                    v-model.trim="missingSupplierFilters.search"
                                    type="search"
                                    placeholder="Ex. DOS-2026 ou 1542"
                                />
                            </label>
                            <div class="missing-filter-actions">
                                <button type="submit" class="missing-primary-button">
                                    <i class="bx bx-search"></i>
                                    Rechercher
                                </button>
                                <button
                                    type="button"
                                    class="missing-secondary-button"
                                    @click="resetMissingSupplierFilters"
                                >
                                    Réinitialiser
                                </button>
                            </div>
                        </form>

                        <div class="missing-bulk-toolbar">
                            <label class="missing-select-all">
                                <input
                                    type="checkbox"
                                    :checked="allVisibleMissingSelected"
                                    @change="toggleAllVisibleMissing"
                                />
                                Tout sélectionner
                            </label>
                            <span class="missing-selection-count">
                                {{ missingSupplierModal.selectedIds.length }} service(s) sélectionné(s)
                            </span>
                            <button
                                type="button"
                                class="missing-secondary-button"
                                :disabled="!missingSupplierModal.selectedIds.length"
                                @click="missingSupplierModal.selectedIds = []"
                            >
                                Annuler la sélection
                            </button>
                            <select v-model="missingSupplierModal.bulkSupplierId">
                                <option value="">Choisir un fournisseur commun</option>
                                <option
                                    v-for="supplier in missingSupplierModal.options.suppliers"
                                    :key="supplier.id"
                                    :value="supplier.id"
                                >
                                    {{ supplier.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="missing-primary-button"
                                :disabled="
                                    missingSupplierModal.processing ||
                                    !missingSupplierModal.selectedIds.length ||
                                    !missingSupplierModal.bulkSupplierId
                                "
                                @click="
                                    assignMissingSupplier(
                                        missingSupplierModal.selectedIds,
                                        missingSupplierModal.bulkSupplierId,
                                        true,
                                    )
                                "
                            >
                                <i class="bx bx-check-double"></i>
                                Affecter les services sélectionnés
                            </button>
                        </div>

                        <div class="missing-supplier-table-wrap">
                            <div v-if="missingSupplierModal.loading" class="missing-supplier-loading">
                                <i class="bx bx-loader-alt bx-spin"></i>
                                Chargement des plannings…
                            </div>
                            <table v-else class="missing-supplier-table">
                                <thead>
                                    <tr>
                                        <th class="check-column"></th>
                                        <th>Dossier / Planning</th>
                                        <th>Date</th>
                                        <th>Service / Type</th>
                                        <th>Client</th>
                                        <th>Chauffeur</th>
                                        <th>Véhicule</th>
                                        <th>Destination</th>
                                        <th>Fournisseur actuel</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="planning in missingSupplierModal.rows" :key="planning.id">
                                        <td class="check-column">
                                            <input
                                                type="checkbox"
                                                :checked="isMissingPlanningSelected(planning.id)"
                                                @change="toggleMissingPlanning(planning.id)"
                                            />
                                        </td>
                                        <td>
                                            <strong>{{ planning.reference }}</strong>
                                            <small>{{ planning.planning_number }}</small>
                                        </td>
                                        <td>{{ planning.date }}</td>
                                        <td>
                                            <strong>{{ planning.service }}</strong>
                                            <small>{{ planning.service_type }}</small>
                                        </td>
                                        <td>{{ planning.clients }}</td>
                                        <td>{{ planning.driver }}</td>
                                        <td>{{ planning.vehicle }}</td>
                                        <td>{{ planning.destination }}</td>
                                        <td>
                                            <span class="missing-supplier-badge">
                                                {{ planning.supplier_vehicle }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="missing-row-action">
                                                <select v-model="missingSupplierModal.rowSupplierIds[planning.id]">
                                                    <option value="">Choisir…</option>
                                                    <option
                                                        v-for="supplier in missingSupplierModal.options.suppliers"
                                                        :key="supplier.id"
                                                        :value="supplier.id"
                                                    >
                                                        {{ supplier.name }}
                                                    </option>
                                                </select>
                                                <button
                                                    type="button"
                                                    class="missing-row-assign"
                                                    :disabled="
                                                        missingSupplierModal.processing ||
                                                        !missingSupplierModal.rowSupplierIds[planning.id]
                                                    "
                                                    @click="
                                                        assignMissingSupplier(
                                                            [planning.id],
                                                            missingSupplierModal.rowSupplierIds[planning.id],
                                                        )
                                                    "
                                                >
                                                    Affecter
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!missingSupplierModal.rows.length">
                                        <td colspan="10" class="missing-table-empty">
                                            <i class="bx bx-check-circle"></i>
                                            Aucun planning sans fournisseur pour ces filtres.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <footer v-if="missingSupplierModal.lastPage > 1" class="missing-pagination">
                            <button
                                type="button"
                                :disabled="missingSupplierModal.currentPage <= 1"
                                @click="loadMissingSupplierPlannings(missingSupplierModal.currentPage - 1)"
                            >
                                <i class="bx bx-chevron-left"></i>
                                Précédent
                            </button>
                            <span>
                                Page {{ missingSupplierModal.currentPage }} / {{ missingSupplierModal.lastPage }}
                            </span>
                            <button
                                type="button"
                                :disabled="missingSupplierModal.currentPage >= missingSupplierModal.lastPage"
                                @click="loadMissingSupplierPlannings(missingSupplierModal.currentPage + 1)"
                            >
                                Suivant
                                <i class="bx bx-chevron-right"></i>
                            </button>
                        </footer>
                    </section>
                </div>
            </Teleport>

            <!-- VEHICLE EFFICIENCY -->
            <div class="vehicle-efficiency-card card border-0 shadow-sm mb-3">
                <div class="card-body p-3 p-lg-4">
                    <div class="analytics-header align-items-start">
                        <div>
                            <div class="panel-kicker">
                                Étude véhicules
                            </div>
                            <h3 class="analytics-title">
                                Travail, gasoil et rendement par véhicule
                            </h3>
                            <p class="analytics-subtitle">
                                Calcul basé sur les plannings, fiches de route
                                et factures gasoil liées à la période filtrée.
                            </p>
                        </div>

                        <div class="vehicle-summary-grid">
                            <div class="vehicle-summary-chip">
                                <span>Véhicules</span>
                                <strong>
                                    {{ vehicleSummary.vehicles_count || 0 }}
                                </strong>
                            </div>
                            <div class="vehicle-summary-chip">
                                <span>Services</span>
                                <strong>
                                    {{ vehicleSummary.total_trips || 0 }}
                                </strong>
                            </div>
                            <div class="vehicle-summary-chip">
                                <span>Km fiche</span>
                                <strong>
                                    {{
                                        formatMoney(
                                            vehicleSummary.total_distance,
                                        )
                                    }}
                                </strong>
                            </div>
                            <div class="vehicle-summary-chip money">
                                <span>Gasoil</span>
                                <strong>
                                    {{
                                        formatMoney(
                                            vehicleSummary.total_fuel_cost,
                                        )
                                    }}
                                    MAD
                                </strong>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="vehicleRows.length"
                        class="vehicle-highlight-grid"
                    >
                        <div class="vehicle-highlight-card best">
                            <div class="vehicle-highlight-icon">
                                <i class="bx bx-leaf"></i>
                            </div>
                            <div>
                                <span>Véhicule économique</span>
                                <strong>
                                    {{ bestVehicle?.name || "N/A" }}
                                </strong>
                                <small>
                                    {{
                                        bestVehicle?.fuel_cost_per_km !==
                                            null &&
                                        bestVehicle?.fuel_cost_per_km !==
                                            undefined
                                            ? formatMoney(
                                                  bestVehicle?.fuel_cost_per_km,
                                              ) + " MAD/km"
                                            : "Km non renseigné"
                                    }}
                                </small>
                            </div>
                        </div>

                        <div class="vehicle-highlight-card worst">
                            <div class="vehicle-highlight-icon">
                                <i class="bx bx-gas-pump"></i>
                            </div>
                            <div>
                                <span>Consommation élevée</span>
                                <strong>
                                    {{ worstVehicle?.name || "N/A" }}
                                </strong>
                                <small>
                                    {{
                                        worstVehicle?.fuel_cost_per_km !==
                                            null &&
                                        worstVehicle?.fuel_cost_per_km !==
                                            undefined
                                            ? formatMoney(
                                                  worstVehicle?.fuel_cost_per_km,
                                              ) + " MAD/km"
                                            : "Km non renseigné"
                                    }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="vehicleRows.length"
                        class="vehicle-table-wrap"
                    >
                        <table class="table vehicle-table mb-0">
                            <thead>
                                <tr>
                                    <th>Véhicule</th>
                                    <th>Driver</th>
                                    <th class="text-end">Services</th>
                                    <th class="text-end">Km</th>
                                    <th class="text-end">Gasoil</th>
                                    <th class="text-end">MAD/km</th>
                                    <th class="text-end">MAD/service</th>
                                    <th class="text-end">Jawaz</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="vehicle in vehicleRows"
                                    :key="vehicle.id"
                                >
                                    <td>
                                        <div class="vehicle-name">
                                            {{ vehicle.name }}
                                        </div>
                                        <div class="vehicle-muted">
                                            {{
                                                vehicle.invoice_fuel_amount > 0
                                                    ? "Factures gasoil"
                                                    : "Fiche de route"
                                            }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vehicle-driver">
                                            {{ vehicle.drivers || "-" }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        {{ vehicle.total_trips || 0 }}
                                    </td>
                                    <td class="text-end">
                                        {{
                                            formatMoney(
                                                vehicle.total_distance,
                                            )
                                        }}
                                    </td>
                                    <td class="text-end fw-bold">
                                        {{ formatMoney(vehicle.fuel_cost) }}
                                        MAD
                                    </td>
                                    <td class="text-end">
                                        {{
                                            vehicle.fuel_cost_per_km !== null &&
                                            vehicle.fuel_cost_per_km !==
                                                undefined
                                                ? formatMoney(
                                                      vehicle.fuel_cost_per_km,
                                                  )
                                                : "-"
                                        }}
                                    </td>
                                    <td class="text-end">
                                        {{
                                            vehicle.fuel_cost_per_trip !==
                                                null &&
                                            vehicle.fuel_cost_per_trip !==
                                                undefined
                                                ? formatMoney(
                                                      vehicle.fuel_cost_per_trip,
                                                  )
                                                : "-"
                                        }}
                                    </td>
                                    <td class="text-end">
                                        {{ formatMoney(vehicle.jawaz_amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="empty-state py-5">
                        Aucune donnée véhicule exploitable sur cette période.
                    </div>
                </div>
            </div>

            <!-- DYNAMIC REPORT -->
            <div class="analytics-super-card card border-0 shadow-sm mb-3">
                <div class="card-body p-3 p-lg-4">
                    <div class="analytics-header">
                        <div>
                            <div class="panel-kicker">Rapport dynamique</div>
                            <h3 class="analytics-title">
                                Analyse détaillée des plannings
                            </h3>
                            <p class="analytics-subtitle">
                                Vue annuelle par mois. Cliquez sur un mois pour
                                ouvrir les semaines, puis sur une semaine pour
                                voir les jours.
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
                                Total annuel — {{ selectedMetric.label }}
                            </div>
                            <div class="metric-preview-value">
                                {{ formatMoney(selectedMetricTotal) }}
                                {{ selectedMetric.suffix }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="planningAnalyticsHierarchy.length"
                        class="analytics-chart-box analytics-chart-box-clickable"
                    >
                        <VueApexCharts
                            type="bar"
                            height="310"
                            :options="analyticsChartOptions"
                            :series="analyticsSeries"
                        />
                        <div class="chart-click-hint">
                            <i class="bx bx-pointer"></i>
                            Cliquez sur un mois pour détailler les semaines.
                        </div>
                    </div>

                    <div v-else class="empty-state py-5">
                        Aucune donnée disponible pour cette période.
                    </div>
                </div>
            </div>

            <div
                v-if="selectedAnalyticsMonth"
                class="analytics-modal-backdrop"
                @click.self="closeMonthAnalytics"
            >
                <div class="analytics-modal">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Détail mensuel</div>
                            <h3>
                                {{ selectedAnalyticsMonth.label }}
                                {{ selectedAnalyticsMonth.year }}
                            </h3>
                            <p>
                                Répartition par semaines. Chaque semaine couvre
                                jusqu’à 7 jours du mois.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="analytics-modal-close"
                            @click="closeMonthAnalytics"
                        >
                            <i class="bx bx-x"></i>
                            <span>Fermer</span>
                        </button>
                    </div>

                    <div class="analytics-modal-summary">
                        <div class="metric-preview-icon">
                            <i :class="['bx', selectedMetric.icon]"></i>
                        </div>
                        <div>
                            <div class="metric-preview-label">
                                Total {{ selectedAnalyticsMonth.label }}
                            </div>
                            <div class="metric-preview-value">
                                {{
                                    formatMoney(
                                        selectedAnalyticsMonth[
                                            chartType.metric
                                        ],
                                    )
                                }}
                                {{ selectedMetric.suffix }}
                            </div>
                        </div>
                    </div>

                    <div class="analytics-modal-chart">
                        <VueApexCharts
                            type="bar"
                            height="330"
                            :options="weekAnalyticsChartOptions"
                            :series="weekAnalyticsSeries"
                        />
                    </div>

                    <div class="chart-click-hint">
                        <i class="bx bx-pointer"></i>
                        Cliquez sur une semaine pour voir ses jours.
                    </div>
                </div>
            </div>

            <div
                v-if="selectedAnalyticsWeek"
                class="analytics-modal-backdrop analytics-modal-backdrop-top"
                @click.self="closeWeekAnalytics"
            >
                <div class="analytics-modal analytics-modal-compact">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Détail hebdomadaire</div>
                            <h3>
                                {{ selectedAnalyticsWeek.label }} —
                                {{ selectedAnalyticsMonth?.label }}
                            </h3>
                            <p>{{ selectedAnalyticsWeek.range_label }}</p>
                        </div>

                        <button
                            type="button"
                            class="analytics-modal-close"
                            @click="closeWeekAnalytics"
                        >
                            <i class="bx bx-x"></i>
                            <span>Fermer</span>
                        </button>
                    </div>

                    <div class="analytics-modal-summary">
                        <div class="metric-preview-icon week-icon">
                            <i :class="['bx', selectedMetric.icon]"></i>
                        </div>
                        <div>
                            <div class="metric-preview-label">
                                Total {{ selectedAnalyticsWeek.label }}
                            </div>
                            <div class="metric-preview-value">
                                {{
                                    formatMoney(
                                        selectedAnalyticsWeek[chartType.metric],
                                    )
                                }}
                                {{ selectedMetric.suffix }}
                            </div>
                        </div>
                    </div>

                    <div class="analytics-modal-chart">
                        <VueApexCharts
                            type="bar"
                            height="300"
                            :options="dayAnalyticsChartOptions"
                            :series="dayAnalyticsSeries"
                        />
                    </div>
                </div>
            </div>

            <!-- TOP BLOCKS -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-3">
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
                            <div class="panel-head mb-3">
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
                            <div class="panel-head mb-3">
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
                            <div class="panel-head mb-3">
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
            <div class="row g-3 mb-3">
                <div class="col-12 col-xl-6">
                    <div class="dashboard-panel card border-0 shadow-sm h-100">
                        <div class="card-body dashboard-panel-body">
                            <div class="panel-head mb-3">
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
                            <div class="panel-head mb-3">
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

@keyframes dashboardRise {
    from {
        opacity: 0;
        transform: translateY(18px) scale(0.985);
        filter: blur(4px);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

@keyframes premiumSheen {
    from {
        transform: translateX(-135%) rotate(10deg);
    }

    to {
        transform: translateX(135%) rotate(10deg);
    }
}

@keyframes iconBreath {
    0%,
    100% {
        transform: translateY(0) scale(1);
    }

    50% {
        transform: translateY(-2px) scale(1.04);
    }
}

@keyframes softPulse {
    0%,
    100% {
        box-shadow: 0 0 0 rgba(255, 255, 255, 0);
    }

    50% {
        box-shadow: 0 0 24px rgba(255, 255, 255, 0.24);
    }
}

@keyframes barReveal {
    from {
        transform: scaleX(0);
    }

    to {
        transform: scaleX(1);
    }
}

@keyframes modalFloatIn {
    from {
        opacity: 0;
        transform: translateY(24px) scale(0.96);
        filter: blur(6px);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
        filter: blur(0);
    }
}

@keyframes backdropFadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
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
    border-radius: 24px;
    animation: dashboardRise 0.55s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.hero-overlay::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 -45%;
    width: 42%;
    background: linear-gradient(
        110deg,
        transparent 0%,
        rgba(255, 255, 255, 0.12) 45%,
        transparent 62%
    );
    animation: premiumSheen 4.8s ease-in-out 0.8s infinite;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.13);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 999px;
    padding: 7px 13px;
    font-weight: 800;
    font-size: 0.84rem;
}

.dashboard-title {
    color: #fff;
    font-size: 1.95rem;
    font-weight: 950;
    letter-spacing: 0.3px;
}

.dashboard-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
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
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #fff;
    font-weight: 800;
    font-size: 0.86rem;
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
    border-radius: 18px;
    padding: 14px;
    backdrop-filter: blur(10px);
}

.filter-panel-title {
    color: #fff;
    font-weight: 900;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-label {
    color: rgba(255, 255, 255, 0.78);
    font-weight: 800;
}

.modern-input {
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.96);
    min-height: 40px;
    box-shadow: none;
    font-weight: 700;
}

.action-btn {
    min-height: 40px;
    border-radius: 12px;
}

.metric-card,
.finance-card,
.mini-stat-card,
.dashboard-panel,
.analytics-super-card {
    border-radius: 24px;
    animation: dashboardRise 0.6s cubic-bezier(0.22, 1, 0.36, 1) both;
    transition:
        transform 0.24s ease,
        box-shadow 0.24s ease,
        border-color 0.24s ease;
}

.metric-card:hover,
.finance-card:hover,
.mini-stat-card:hover,
.dashboard-panel:hover,
.analytics-super-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 22px 42px rgba(15, 23, 42, 0.13) !important;
}

.row > [class*="col-"]:nth-child(1) > .card {
    animation-delay: 0.04s;
}

.row > [class*="col-"]:nth-child(2) > .card {
    animation-delay: 0.1s;
}

.row > [class*="col-"]:nth-child(3) > .card {
    animation-delay: 0.16s;
}

.row > [class*="col-"]:nth-child(4) > .card {
    animation-delay: 0.22s;
}

.row > [class*="col-"]:nth-child(5) > .card {
    animation-delay: 0.28s;
}

.metric-card {
    position: relative;
    min-height: 52px;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.16) !important;
    box-shadow:
        0 10px 24px rgba(15, 23, 42, 0.07),
        inset 0 1px 0 rgba(255, 255, 255, 0.18) !important;
}

.metric-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 88% 12%, rgba(255,255,255,.2), transparent 30%);
    pointer-events: none;
    transition: opacity 0.24s ease;
}

.metric-card::after {
    content: "";
    position: absolute;
    width: 50px;
    height: 50px;
    right: -20px;
    bottom: -22px;
    border-radius: 999px;
    opacity: 0.24;
    pointer-events: none;
    transition:
        transform 0.28s ease,
        opacity 0.28s ease;
}

.metric-card:hover::after {
    opacity: 0.34;
    transform: scale(1.05);
}

.metric-card-body {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 52px;
    padding: 7px 10px;
}

.metric-inline {
    width: 100%;
    height: 100%;
    min-width: 0;
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr) auto;
    align-items: center;
    column-gap: 10px;
}

.metric-red {
    background: linear-gradient(135deg, #881337, #e11d48);
}

.metric-red::after {
    background: #e11d48;
}

.metric-blue {
    background: linear-gradient(135deg, #1e3a8a, #2563eb);
}

.metric-blue::after {
    background: #2563eb;
}

.metric-purple {
    background: linear-gradient(135deg, #4c1d95, #8b5cf6);
}

.metric-purple::after {
    background: #7c3aed;
}

.metric-green {
    background: linear-gradient(135deg, #065f46, #10b981);
}

.metric-green::after {
    background: #10b981;
}

.metric-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 4px;
}

.metric-icon {
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
    box-shadow:
        0 7px 16px rgba(17, 24, 39, 0.13),
        inset 0 1px 0 rgba(255, 255, 255, 0.16);
    animation: none;
}

.metric-chip {
    font-size: 0.64rem;
    font-weight: 900;
    color: rgba(255,255,255,.88);
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 999px;
    padding: 3px 7px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    justify-self: end;
}

.metric-label,
.section-title,
.panel-kicker {
    color: #6b7280;
    font-weight: 800;
    font-size: 0.84rem;
}

.metric-main {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    min-width: 0;
}

.metric-value {
    flex: 0 0 auto;
    font-size: clamp(1.28rem, 1.55vw, 1.5rem);
    font-weight: 950;
    color: #fff;
    line-height: 1.1;
    letter-spacing: 0;
    white-space: nowrap;
    justify-self: center;
    align-self: center;
}

.metric-main .metric-label {
    color: rgba(255,255,255,.72);
    margin-top: 1px;
    font-size: clamp(0.69rem, 0.74vw, 0.78rem);
    font-weight: 950;
    line-height: 1.2;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.1) !important;
}

.finance-card {
    position: relative;
    min-height: 126px;
    border-radius: 20px;
    color: #fff;
    overflow: hidden;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1) !important;
}

.finance-card::before {
    content: "";
    position: absolute;
    inset: -45% auto -45% -35%;
    width: 34%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    transform: translateX(-135%) rotate(10deg);
    animation: premiumSheen 5.2s ease-in-out 1.2s infinite;
    pointer-events: none;
}

.finance-card::after {
    content: "";
    position: absolute;
    inset: auto -24px -32px auto;
    width: 78px;
    height: 78px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.09);
    pointer-events: none;
    transition: transform 0.28s ease;
}

.finance-card:hover::after {
    transform: scale(1.06);
}

.finance-budget {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
}

.finance-cost {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
}

.finance-margin {
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
}

.finance-payments {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
}

.finance-card-body {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 13px 14px 12px;
}

.finance-top-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.finance-heading {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 9px;
}

.finance-title {
    color: rgba(255, 255, 255, 0.84);
    font-size: 0.78rem;
    font-weight: 900;
    line-height: 1.15;
}

.finance-icon {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.08rem;
    flex-shrink: 0;
    animation: none;
}

.finance-trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 4px 7px;
    font-size: 0.68rem;
    font-weight: 950;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.14);
    white-space: nowrap;
    animation: none;
}

.trend-up {
    background: rgba(22, 163, 74, 0.22);
}

.trend-down {
    background: rgba(15, 23, 42, 0.22);
}

.trend-stable {
    background: rgba(255, 255, 255, 0.14);
}

.finance-value {
    font-size: clamp(1.35rem, 1.55vw, 1.68rem);
    font-weight: 950;
    line-height: 1.1;
    margin-top: 1px;
}

.finance-note {
    opacity: 0.72;
    margin-top: 3px;
    font-size: 0.7rem;
    line-height: 1.2;
}

.finance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14) !important;
}

.vehicle-efficiency-card {
    border-radius: 24px;
}

.vehicle-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(92px, 1fr));
    gap: 10px;
    min-width: min(100%, 520px);
}

.vehicle-summary-chip {
    background: #f8fafc;
    border: 1px solid #eef2f7;
    border-radius: 14px;
    padding: 10px 12px;
}

.vehicle-summary-chip span {
    display: block;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 900;
    margin-bottom: 3px;
}

.vehicle-summary-chip strong {
    color: #111827;
    font-size: 1.05rem;
    font-weight: 950;
}

.vehicle-summary-chip.money strong {
    color: #047857;
}

.vehicle-highlight-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}

.vehicle-highlight-card {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #eef2f7;
    border-radius: 16px;
    padding: 13px;
    background: #ffffff;
    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        border-color 0.22s ease;
}

.vehicle-highlight-card:hover {
    transform: translateY(-3px);
    border-color: #dbe4ef;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.1);
}

.vehicle-highlight-card.best {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.1), #ffffff);
}

.vehicle-highlight-card.worst {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), #ffffff);
}

.vehicle-highlight-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    background: #111827;
    flex-shrink: 0;
    animation: iconBreath 3.5s ease-in-out infinite;
}

.vehicle-highlight-card.best .vehicle-highlight-icon {
    background: linear-gradient(135deg, #059669, #10b981);
}

.vehicle-highlight-card.worst .vehicle-highlight-icon {
    background: linear-gradient(135deg, #dc2626, #ef4444);
}

.vehicle-highlight-card span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 900;
    margin-bottom: 2px;
}

.vehicle-highlight-card strong {
    display: block;
    color: #111827;
    font-weight: 950;
    line-height: 1.2;
}

.vehicle-highlight-card small {
    color: #475569;
    font-weight: 800;
}

.vehicle-table-wrap {
    border: 1px solid #eef2f7;
    border-radius: 18px;
    overflow-x: auto;
}

.vehicle-table {
    min-width: 1040px;
    vertical-align: middle;
}

.vehicle-table thead th {
    background: #f8fafc;
    color: #64748b;
    border-bottom: 1px solid #eef2f7;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
}

.vehicle-table tbody td {
    color: #111827;
    font-weight: 800;
    border-color: #eef2f7;
}

.vehicle-name {
    color: #111827;
    font-weight: 950;
}

.vehicle-muted {
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 800;
    margin-top: 2px;
}

.vehicle-driver {
    max-width: 230px;
    color: #334155;
    font-size: 0.86rem;
    font-weight: 900;
    line-height: 1.35;
}

.mini-stat-card {
    --mini-accent: #e11d48;
    position: relative;
    min-height: 116px;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--mini-accent) 18%, #e2e8f0) !important;
    border-radius: 20px;
    background:
        radial-gradient(circle at 96% 0, color-mix(in srgb, var(--mini-accent) 14%, transparent), transparent 42%),
        linear-gradient(145deg, #fff, #f8fafc);
    box-shadow: 0 12px 28px rgba(15,23,42,.075) !important;
}

.mini-stat-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--mini-accent);
}

.mini-suppliers { --mini-accent: #e11d48; }
.mini-vehicles { --mini-accent: #2563eb; }
.mini-drivers { --mini-accent: #7c3aed; }
.mini-guides { --mini-accent: #0891b2; }
.mini-destinations { --mini-accent: #059669; }

.mini-stat-card-body {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 13px 14px 12px 17px;
}

.mini-stat-head {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #475569;
    font-weight: 900;
    margin-bottom: 5px;
    font-size: 0.78rem;
}

.mini-stat-head i {
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    border-radius: 11px;
    font-size: 1.15rem;
    color: #fff;
    background: var(--mini-accent);
    box-shadow: 0 8px 18px color-mix(in srgb, var(--mini-accent) 26%, transparent);
}

.mini-stat-value {
    font-size: 1.55rem;
    font-weight: 950;
    color: #111827;
    line-height: 1.1;
}

.mini-stat-sub {
    color: #94a3b8;
    font-size: 0.7rem;
    font-weight: 800;
    margin-top: 2px;
}

.mini-progress {
    width: 100%;
    height: 5px;
    margin-top: 8px;
    overflow: hidden;
    border-radius: 999px;
    background: #e9eef5;
}

.mini-progress span {
    display: block;
    height: 100%;
    min-width: 3px;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--mini-accent), color-mix(in srgb, var(--mini-accent) 62%, #fff));
    transition: width .55s cubic-bezier(.22,1,.36,1);
}

.mini-stat-card:hover {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--mini-accent) 34%, #e2e8f0) !important;
    box-shadow: 0 18px 36px rgba(15,23,42,.12) !important;
}

.analytics-super-card {
    position: relative;
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

.analytics-super-card::before {
    content: "";
    position: absolute;
    inset: -55% auto -55% -32%;
    width: 26%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.5),
        transparent
    );
    animation: premiumSheen 6.2s ease-in-out 1s infinite;
    pointer-events: none;
}

.analytics-super-card > .card-body {
    position: relative;
    z-index: 1;
}

.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 16px;
}

.analytics-title {
    font-size: 1.22rem;
    font-weight: 950;
    color: #111827;
    margin: 4px 0 6px;
}

.analytics-subtitle {
    color: #64748b;
    font-weight: 600;
    margin: 0;
    font-size: 0.9rem;
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
    height: 42px;
    border-radius: 13px;
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
    gap: 12px;
    background: linear-gradient(135deg, #fff1f2, #ffffff);
    border: 1px solid #ffe4e6;
    border-radius: 18px;
    padding: 13px 16px;
    margin-bottom: 16px;
}

.metric-preview-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    box-shadow: 0 14px 30px rgba(220, 38, 38, 0.22);
    animation: iconBreath 3.5s ease-in-out infinite;
}

.metric-preview-label {
    color: #64748b;
    font-weight: 900;
    font-size: 0.9rem;
}

.metric-preview-value {
    color: #111827;
    font-size: 1.45rem;
    font-weight: 950;
    line-height: 1.1;
}

.supplier-summary-chip {
    min-width: 220px;
    border-radius: 16px;
    padding: 12px 14px;
    background: linear-gradient(135deg, #111827, #1f2937);
    color: #fff;
    box-shadow: 0 16px 34px rgba(17, 24, 39, 0.16);
}

.supplier-summary-chip span {
    display: block;
    color: #cbd5e1;
    font-weight: 850;
    margin-bottom: 6px;
}

.supplier-summary-chip strong {
    font-size: 1.12rem;
    font-weight: 950;
}

.supplier-performance-list {
    display: grid;
    gap: 9px;
}

.supplier-performance-row {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) 110px minmax(160px, 190px);
    align-items: center;
    gap: 10px;
    padding: 11px;
    border: 1px solid #eef2f7;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.78);
    cursor: pointer;
    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        border-color 0.22s ease;
}

.supplier-performance-row:hover {
    transform: translateX(4px);
    border-color: #dbe4ef;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
}

.supplier-performance-row:focus-visible {
    outline: 3px solid rgba(225, 29, 72, 0.22);
    outline-offset: 2px;
}

.supplier-name-box {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.supplier-dot {
    width: 13px;
    height: 13px;
    border-radius: 999px;
    flex: 0 0 auto;
    animation: softPulse 3.5s ease-in-out infinite;
}

.supplier-name-box strong {
    display: block;
    color: #111827;
    font-weight: 950;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.supplier-name-box small {
    color: #64748b;
    font-weight: 750;
}

.supplier-kpi {
    border-radius: 12px;
    padding: 8px 10px;
    background: #f8fafc;
}

.supplier-kpi span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 900;
    margin-bottom: 2px;
}

.supplier-kpi strong {
    color: #172554;
    font-weight: 950;
}

.supplier-kpi.money strong {
    color: #047857;
}

.analytics-modal.supplier-drill-modal {
    width: calc(100vw - 48px);
    max-width: none;
    max-height: calc(100vh - 48px);
    padding: 34px;
}

.supplier-drill-hero {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}

.supplier-drill-hero > div {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 24px;
    background:
        radial-gradient(circle at 95% 5%, rgba(255, 255, 255, 0.3), transparent 30%),
        linear-gradient(135deg, #101827, #1e293b);
    color: #fff;
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.18);
}

.supplier-drill-hero > div:nth-child(2) {
    background:
        radial-gradient(circle at 95% 5%, rgba(255, 255, 255, 0.3), transparent 30%),
        linear-gradient(135deg, #be123c, #e11d48);
}

.supplier-drill-hero > div:nth-child(3) {
    background:
        radial-gradient(circle at 95% 5%, rgba(255, 255, 255, 0.3), transparent 30%),
        linear-gradient(135deg, #047857, #10b981);
}

.supplier-drill-hero span {
    display: block;
    color: rgba(255, 255, 255, 0.74);
    font-weight: 850;
    margin-bottom: 8px;
    font-size: 1rem;
}

.supplier-drill-hero strong {
    display: block;
    font-size: 1.7rem;
    font-weight: 950;
}

.supplier-drill-hero.compact {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.supplier-service-board {
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 26px;
    background:
        radial-gradient(circle at 4% 0%, rgba(225, 29, 72, 0.08), transparent 30%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.94));
    padding: 20px;
}

.supplier-service-board-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.supplier-service-board-head strong {
    display: block;
    color: #0f172a;
    font-size: 1.25rem;
    font-weight: 950;
}

.supplier-service-board-head span {
    display: block;
    color: #64748b;
    font-weight: 850;
    margin-top: 3px;
}

.service-board-total {
    flex-shrink: 0;
    border-radius: 999px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 18px;
    font-weight: 950;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.18);
}

.supplier-service-cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(280px, 1fr));
    gap: 16px;
    align-content: start;
    max-height: calc(100vh - 410px);
    overflow-y: auto;
    padding-right: 8px;
}

.service-drill-card {
    position: relative;
    border: 1px solid #e5e7eb;
    border-radius: 22px;
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.9)),
        #fff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
    padding: 20px 20px 18px 56px;
    text-align: left;
    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        border-color 0.22s ease;
}

.service-drill-card:hover {
    transform: translateY(-3px) scale(1.01);
    border-color: rgba(225, 29, 72, 0.18);
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.11);
}

.service-drill-main {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
}

.service-drill-main i {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(225, 29, 72, 0.1);
    color: #be123c;
    font-size: 1.35rem;
    transition:
        transform 0.22s ease,
        background 0.22s ease,
        color 0.22s ease;
}

.service-drill-card:hover .service-drill-main i {
    transform: translateX(3px);
    background: #be123c;
    color: #ffffff;
}

.service-color-dot {
    position: absolute;
    top: 23px;
    left: 20px;
    width: 17px;
    height: 17px;
    border-radius: 999px;
    box-shadow: 0 0 0 6px rgba(225, 29, 72, 0.08);
}

.service-drill-card strong {
    display: block;
    color: #0f172a;
    font-weight: 950;
    margin-bottom: 6px;
    font-size: 1.08rem;
}

.service-drill-card small {
    display: block;
    color: #64748b;
    font-weight: 850;
    font-size: 0.95rem;
}

.service-drill-kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 16px;
}

.service-drill-kpis div {
    min-width: 0;
    border-radius: 16px;
    background: rgba(248, 250, 252, 0.95);
    border: 1px solid rgba(226, 232, 240, 0.95);
    padding: 11px 12px;
}

.service-drill-kpis span {
    display: block;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.service-drill-kpis strong {
    display: block;
    overflow-wrap: anywhere;
    color: #0f172a;
    font-size: 1rem;
    font-weight: 950;
    margin-top: 4px;
}

.service-drill-kpis strong.positive {
    color: #047857;
}

.service-meter {
    height: 11px;
    border-radius: 999px;
    background: #eef2f7;
    overflow: hidden;
    margin-top: 10px;
}

.service-meter span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #e11d48, #2563eb);
    animation: barReveal 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.analytics-chart-box {
    background: #ffffff;
    border: 1px solid #eef2f7;
    border-radius: 18px;
    padding: 10px;
}

.analytics-chart-box-clickable {
    position: relative;
    cursor: pointer;
    overflow: hidden;
}

.analytics-chart-box-clickable::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 18% 0%, rgba(225, 29, 72, 0.07), transparent 28%),
        radial-gradient(circle at 82% 100%, rgba(37, 99, 235, 0.08), transparent 26%);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.analytics-chart-box-clickable:hover::before {
    opacity: 1;
}

.chart-click-hint {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 8px 6px 0;
    color: #7f1d1d;
    font-weight: 850;
    font-size: 0.86rem;
}

.chart-click-hint i {
    color: #e11d48;
    font-size: 1.1rem;
}

.analytics-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 22px;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(12px);
    animation: backdropFadeIn 0.24s ease both;
}

.analytics-modal-backdrop-top {
    z-index: 1090;
    background: rgba(15, 23, 42, 0.48);
}

.analytics-modal {
    width: min(1080px, 100%);
    max-height: min(90vh, 820px);
    overflow-y: auto;
    background:
        radial-gradient(circle at 8% 4%, rgba(225, 29, 72, 0.12), transparent 26%),
        radial-gradient(circle at 90% 8%, rgba(37, 99, 235, 0.12), transparent 28%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 26px;
    box-shadow: 0 34px 90px rgba(15, 23, 42, 0.32);
    padding: 24px;
    animation: modalFloatIn 0.34s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.analytics-modal-compact {
    width: calc(100vw - 48px);
    max-width: none;
    max-height: calc(100vh - 48px);
    padding: 30px;
}

.analytics-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 22px;
}

.analytics-modal-head h3 {
    color: #0f172a;
    font-weight: 950;
    margin: 2px 0 4px;
    font-size: 1.45rem;
}

.analytics-modal-head p {
    color: #64748b;
    font-weight: 820;
    margin: 0;
    font-size: 1rem;
}

.analytics-modal-close {
    min-width: 118px;
    height: 58px;
    border: 0;
    border-radius: 18px;
    background: linear-gradient(135deg, #0f172a, #334155);
    color: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 1.3rem;
    font-weight: 950;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.22);
    transition:
        transform 0.2s ease,
        background 0.2s ease,
        color 0.2s ease;
}

.analytics-modal-close span {
    font-size: 1rem;
    letter-spacing: 0.2px;
}

.analytics-modal-close:hover {
    transform: translateY(-2px) scale(1.03);
    background: linear-gradient(135deg, #be123c, #e11d48);
    color: #ffffff;
}

.analytics-modal-summary {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    padding: 14px;
    border-radius: 18px;
    background:
        linear-gradient(135deg, rgba(225, 29, 72, 0.12), rgba(37, 99, 235, 0.08)),
        #fff;
    border: 1px solid rgba(225, 29, 72, 0.1);
}

.analytics-modal-chart {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    padding: 18px;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.95);
}

.supplier-day-modal {
    width: calc(100vw - 48px);
    max-width: none;
    max-height: calc(100vh - 48px);
}

.day-fiche-hint {
    margin-top: 14px;
    justify-content: center;
}

.day-fiche-summary {
    grid-template-columns: repeat(4, minmax(160px, 1fr));
}

.day-fiche-status-row {
    display: grid;
    grid-template-columns: repeat(5, minmax(130px, 1fr));
    gap: 12px;
    margin: 16px 0 20px;
}

.day-status-pill {
    border-radius: 18px;
    padding: 14px 16px;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: #ffffff;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
}

.day-status-pill span {
    display: block;
    color: #64748b;
    font-weight: 900;
    font-size: 0.82rem;
}

.day-status-pill strong {
    display: block;
    color: #0f172a;
    font-size: 1.55rem;
    font-weight: 950;
    line-height: 1;
    margin-top: 8px;
}

.invoice-ready {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), #fff);
}

.invoice-missing,
.payment-unpaid {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), #fff);
}

.payment-paid {
    background: linear-gradient(135deg, rgba(5, 150, 105, 0.14), #fff);
}

.payment-partial {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.16), #fff);
}

.planning-fiche-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.planning-fiche-card {
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 24px;
    background:
        radial-gradient(circle at 10% 0%, rgba(225, 29, 72, 0.08), transparent 28%),
        linear-gradient(135deg, #ffffff, #f8fafc);
    padding: 18px;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
}

.planning-fiche-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 14px;
}

.planning-fiche-ref {
    display: block;
    color: #be123c;
    font-weight: 950;
    font-size: 0.9rem;
    margin-bottom: 2px;
}

.planning-fiche-top strong {
    color: #0f172a;
    font-size: 1.1rem;
    font-weight: 950;
}

.planning-fiche-badges {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.fiche-badge {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 7px 11px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 950;
    white-space: nowrap;
}

.badge-facturee,
.badge-paid {
    color: #047857;
    background: rgba(16, 185, 129, 0.12);
}

.badge-non-facturee,
.badge-not-ready,
.badge-unpaid {
    color: #be123c;
    background: rgba(239, 68, 68, 0.12);
}

.badge-partial {
    color: #b45309;
    background: rgba(245, 158, 11, 0.16);
}

.planning-route-card {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 12px;
    padding: 14px;
    border-radius: 18px;
    background: #0f172a;
    color: #ffffff;
}

.planning-route-card span,
.planning-meta-grid span,
.planning-money-grid span,
.planning-invoice-card span {
    display: block;
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.planning-route-card strong {
    display: block;
    color: #ffffff;
    font-size: 1rem;
    font-weight: 950;
    margin-top: 4px;
}

.planning-route-card i {
    color: #fb7185;
    font-size: 1.55rem;
}

.planning-meta-grid,
.planning-money-grid,
.planning-invoice-card {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.planning-meta-grid div,
.planning-money-grid div,
.planning-invoice-card div {
    min-width: 0;
    border-radius: 16px;
    background: rgba(248, 250, 252, 0.92);
    border: 1px solid rgba(226, 232, 240, 0.9);
    padding: 11px 12px;
}

.planning-meta-grid strong,
.planning-money-grid strong,
.planning-invoice-card strong {
    display: block;
    overflow-wrap: anywhere;
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 950;
    margin-top: 5px;
}

.planning-clients-line {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-top: 12px;
    color: #475569;
    font-weight: 850;
    line-height: 1.45;
}

.planning-clients-line i {
    color: #2563eb;
    font-size: 1.1rem;
    margin-top: 2px;
}

.planning-money-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.planning-money-grid div {
    background: #ffffff;
}

.planning-invoice-card {
    grid-template-columns: repeat(5, minmax(0, 1fr));
}

.planning-invoice-card div {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.09), #ffffff);
}

.planning-invoice-empty-action {
    flex-direction: column;
    align-items: stretch;
    padding: 14px;
}

.planning-invoice-empty-action > div {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.link-invoice-button {
    width: 100%;
    min-height: 48px;
    border: 0;
    border-radius: 16px;
    color: #fff;
    background: linear-gradient(135deg, #be123c, #ef4444);
    box-shadow: 0 16px 36px rgba(190, 18, 60, 0.2);
    font-weight: 950;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.link-invoice-hint {
    color: #64748b;
    font-weight: 850;
}

.invoice-link-panel {
    width: min(1180px, 100%);
}

.invoice-link-summary,
.invoice-choice-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 22px;
}

.invoice-link-summary > div,
.invoice-choice-card {
    border-radius: 20px;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: rgba(255, 255, 255, 0.86);
    padding: 16px 18px;
}

.invoice-link-summary span,
.invoice-choice-card span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.invoice-link-summary strong,
.invoice-choice-card strong {
    display: block;
    color: #0f172a;
    font-size: 1.05rem;
    font-weight: 950;
    margin-top: 6px;
}

.invoice-choice-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.invoice-choice-card {
    cursor: pointer;
    display: grid;
    grid-template-columns: auto repeat(4, minmax(0, 1fr));
    align-items: center;
    gap: 14px;
    transition: 0.18s ease;
}

.invoice-choice-card input {
    width: 22px;
    height: 22px;
    accent-color: #be123c;
}

.invoice-choice-card.active {
    border-color: rgba(190, 18, 60, 0.38);
    background: linear-gradient(135deg, rgba(255, 241, 242, 0.95), #fff);
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
}

.invoice-choice-empty {
    margin-top: 22px;
    min-height: 120px;
    border-radius: 22px;
    border: 1px dashed rgba(190, 18, 60, 0.32);
    color: #9f1239;
    background: rgba(255, 241, 242, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 950;
    text-align: center;
}

.invoice-link-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 22px;
}

.invoice-link-primary,
.invoice-link-secondary {
    min-height: 52px;
    border: 0;
    border-radius: 16px;
    padding: 0 22px;
    font-weight: 950;
}

.invoice-link-primary {
    color: #fff;
    background: #0f172a;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18);
}

.invoice-link-primary:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.invoice-link-secondary {
    color: #0f172a;
    background: #f1f5f9;
}

.planning-invoice-empty,
.planning-fiche-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 12px;
    min-height: 62px;
    border-radius: 18px;
    color: #be123c;
    background: rgba(254, 226, 226, 0.52);
    border: 1px dashed rgba(225, 29, 72, 0.26);
    font-weight: 900;
    text-align: center;
}

.planning-fiche-empty {
    min-height: 120px;
}

.week-icon {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
}

.dashboard-panel {
    min-height: 300px;
    max-height: 300px;
}

.dashboard-panel-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 18px;
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
    font-size: 1rem;
}

.panel-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
    animation: iconBreath 3.7s ease-in-out infinite;
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
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
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
    transform-origin: left center;
    animation: barReveal 0.75s cubic-bezier(0.22, 1, 0.36, 1) both;
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

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.001ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: 0.001ms !important;
    }
}

@media (max-width: 991.98px) {
    .dashboard-title {
        font-size: 1.7rem;
    }

    .metric-main {
        align-items: flex-start;
        flex-direction: column;
        gap: 0;
    }

    .analytics-header {
        flex-direction: column;
    }

    .analytics-selector {
        width: 100%;
    }

    .analytics-modal-backdrop {
        padding: 10px;
        align-items: flex-start;
        overflow-y: auto;
    }

    .analytics-modal {
        padding: 16px;
        border-radius: 20px;
        max-height: none;
        margin-top: 12px;
    }

    .analytics-modal-head {
        gap: 12px;
    }

    .day-fiche-summary,
    .day-fiche-status-row,
    .planning-fiche-grid,
    .planning-meta-grid,
    .planning-money-grid,
    .planning-invoice-card {
        grid-template-columns: 1fr;
    }

    .planning-fiche-top {
        flex-direction: column;
    }

    .planning-fiche-badges {
        justify-content: flex-start;
    }

    .planning-route-card {
        grid-template-columns: 1fr;
    }

    .metric-preview-value {
        font-size: 1.35rem;
    }

    .chart-row,
    .rank-row,
    .supplier-performance-row {
        grid-template-columns: 1fr;
    }

    .chart-value {
        text-align: left;
    }

    .supplier-summary-chip {
        width: 100%;
    }

    .supplier-drill-hero,
    .supplier-drill-hero.compact,
    .supplier-service-cards,
    .service-drill-kpis {
        grid-template-columns: 1fr;
    }

    .supplier-service-cards {
        max-height: none;
        overflow: visible;
        padding-right: 0;
    }

    .supplier-service-board-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .vehicle-summary-grid,
    .vehicle-highlight-grid {
        grid-template-columns: 1fr;
        width: 100%;
    }

    .vehicle-highlight-card {
        align-items: flex-start;
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

.planning-service-missing {
    color: #b45309 !important;
}

:global(:root) {
    --dashboard-planning-action-layer: 2000;
}

:global(body.planning-action-modal-open) {
    overflow: hidden;
}

.planning-action-overlay {
    position: fixed;
    inset: 0;
    z-index: var(--dashboard-planning-action-layer);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow-x: hidden;
    overflow-y: auto;
    padding: 24px;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    animation: backdropFadeIn 0.24s ease both;
    isolation: isolate;
}

.planning-action-panel {
    position: relative;
    z-index: 1;
    flex: 0 0 auto;
    width: min(1100px, 100%);
    max-height: calc(100dvh - 48px);
    overflow-y: auto;
    overscroll-behavior: contain;
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 26px;
    padding: 24px;
    background:
        radial-gradient(circle at 8% 4%, rgba(225, 29, 72, 0.12), transparent 26%),
        radial-gradient(circle at 90% 8%, rgba(37, 99, 235, 0.12), transparent 28%),
        linear-gradient(135deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.98));
    box-shadow: 0 34px 90px rgba(15, 23, 42, 0.36);
    animation: modalFloatIn 0.34s cubic-bezier(0.22, 1, 0.36, 1) both;
}

.planning-service-chip { display:inline-flex; align-items:center; gap:9px; margin-top:7px; padding:7px 10px 7px 7px; border:1px solid rgba(16,185,129,.2); border-radius:13px; background:rgba(236,253,245,.8); color:#065f46; }
.planning-service-chip.is-missing { border-color:rgba(245,158,11,.28); background:#fffbeb; color:#92400e; }
.planning-service-chip-icon { width:29px; height:29px; display:grid; place-items:center; border-radius:9px; background:rgba(16,185,129,.13); font-size:1.05rem; }
.planning-service-chip.is-missing .planning-service-chip-icon { background:rgba(245,158,11,.14); }
.planning-service-chip small,.planning-service-chip strong { display:block; line-height:1.15; }
.planning-service-chip small { margin-bottom:2px; font-size:.61rem; font-weight:900; letter-spacing:.08em; text-transform:uppercase; opacity:.65; }
.planning-service-chip strong { font-size:.82rem; }

.planning-service-action {
    width:100%; margin-top:12px; display:flex; align-items:center; gap:11px; border:1px solid rgba(148,163,184,.24); border-radius:15px; padding:10px 12px; text-align:left; background:linear-gradient(135deg,#fff,rgba(248,250,252,.92)); color:#334155; box-shadow:0 8px 20px rgba(15,23,42,.045); transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease;
}

.planning-service-action:hover {
    transform:translateY(-2px); border-color:rgba(193,18,31,.28); box-shadow:0 13px 28px rgba(15,23,42,.1); color:#9f1239;
}
.planning-service-action-icon { flex:0 0 36px; height:36px; display:grid; place-items:center; border-radius:11px; background:linear-gradient(135deg,#fff1f2,#ffe4e6); color:#be123c; font-size:1.15rem; }
.planning-service-action > span:nth-child(2) { min-width:0; flex:1; }
.planning-service-action strong,.planning-service-action small { display:block; }
.planning-service-action strong { font-size:.8rem; font-weight:950; }
.planning-service-action small { margin-top:2px; color:#94a3b8; font-size:.67rem; font-weight:750; }
.planning-service-action-arrow { font-size:1.25rem; transition:transform .2s ease; }
.planning-service-action:hover .planning-service-action-arrow { transform:translateX(3px); }

.planning-service-modal {
    width: min(1040px, 100%);
}

.planning-service-hero { display:flex; justify-content:space-between; gap:20px; margin:-24px -24px 20px; padding:25px 28px; color:#fff; background:radial-gradient(circle at 15% 0,rgba(244,63,94,.32),transparent 42%),linear-gradient(125deg,#0f172a,#1e293b); }
.planning-service-hero h3 { margin:5px 0; font-size:1.55rem; font-weight:950; }
.planning-service-hero p { margin:0; color:#cbd5e1; }
.planning-service-eyebrow { display:flex; align-items:center; gap:7px; color:#fda4af; font-size:.72rem; font-weight:950; text-transform:uppercase; letter-spacing:.13em; }
.planning-service-hero .analytics-modal-close { color:#fff; background:rgba(255,255,255,.1); border-color:rgba(255,255,255,.14); }
.planning-service-route-summary { display:grid; grid-template-columns:1fr auto 1fr; gap:16px; align-items:center; padding:16px 18px; border:1px solid #e2e8f0; border-radius:18px; background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.055); }
.planning-service-route-summary > i { width:38px; height:38px; display:grid; place-items:center; border-radius:50%; color:#fff; background:#c1121f; font-size:1.25rem; }
.planning-service-route-summary span,.planning-service-route-summary strong { display:block; }
.planning-service-route-summary span { color:#94a3b8; font-size:.66rem; font-weight:900; text-transform:uppercase; letter-spacing:.1em; }
.planning-service-route-summary strong { margin-top:4px; color:#0f172a; overflow-wrap:anywhere; }
.planning-service-section-title { display:flex; align-items:center; gap:8px; margin:20px 0 12px; color:#0f172a; font-weight:950; }
.planning-service-section-title i { color:#c1121f; font-size:1.15rem; }

.planning-service-context {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin: 22px 0;
}

.planning-service-context > div {
    min-width: 0;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px 14px;
    background: #f8fafc;
}

.planning-service-context span,
.planning-service-context strong {
    display: block;
}

.planning-service-context span {
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
}

.planning-service-context strong {
    margin-top: 4px;
    overflow-wrap: anywhere;
    color: #172554;
}

.planning-service-recommendation {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 18px;
    border: 1px solid #fde68a;
    border-radius: 14px;
    padding: 13px 15px;
    background: #fffbeb;
    color: #92400e;
}

.planning-service-recommendation i {
    font-size: 1.35rem;
}

.planning-service-recommendation strong,
.planning-service-recommendation span {
    display: block;
}

.planning-service-recommendation span {
    margin-top: 3px;
    font-size: 0.86rem;
}

.planning-service-field label {
    display: block;
    margin-bottom: 8px;
    color: #172554;
    font-weight: 850;
}
.planning-service-help { display:flex; gap:7px; align-items:center; margin:10px 0 0; color:#64748b; font-size:.75rem; font-weight:750; }

.planning-service-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
}

.planning-service-cancel,
.planning-service-save {
    border-radius: 12px;
    padding: 11px 16px;
    font-weight: 850;
}

.planning-service-cancel {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #475569;
}

.planning-service-save {
    border: 0;
    background: #c1121f;
    color: #fff;
}

.planning-service-save:disabled,
.planning-service-cancel:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.missing-supplier-auto-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 7px;
    border: 1px solid rgba(193, 18, 31, 0.18);
    border-radius: 9px;
    padding: 5px 8px;
    background: #fff1f2;
    color: #9f1239;
    font-size: 0.7rem;
    font-weight: 900;
    line-height: 1;
    transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
}

.missing-supplier-auto-button:hover {
    transform: translateY(-1px);
    background: #ffe4e6;
    box-shadow: 0 7px 16px rgba(193, 18, 31, 0.12);
}

.missing-supplier-auto-button:disabled {
    cursor: wait;
    opacity: 0.6;
}

.missing-supplier-panel {
    width: calc(100vw - 32px);
    max-width: none;
    max-height: calc(100dvh - 32px);
    padding: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.missing-service-launch {
    border: 0;
    color: inherit;
    text-align: left;
    cursor: pointer;
}

.missing-service-launch .mini-stat-head i,
.missing-service-launch .mini-stat-value {
    color: #b45309;
}

.missing-service-launch-action {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 9px;
    color: #b45309;
    font-size: .72rem;
    font-weight: 900;
}

.missing-service-hero {
    background:
        radial-gradient(circle at 12% 0, rgba(245, 158, 11, .32), transparent 38%),
        linear-gradient(125deg, #0f172a, #292524);
}

.missing-service-panel {
    width: 98vw;
}

.missing-service-filters {
    grid-template-columns: repeat(7, minmax(125px, 1fr));
}

.missing-service-search-select {
    width: min(320px, 100%);
    margin-left: auto;
}

.missing-service-row-select {
    min-width: 260px;
}

.missing-service-badge {
    display: inline-flex;
    padding: 6px 9px;
    border-radius: 999px;
    color: #92400e;
    background: #fffbeb;
    font-size: .72rem;
    font-weight: 900;
}

.missing-service-table {
    min-width: 1650px;
}

.missing-service-panel :deep(.search-select-trigger) {
    min-height: 38px;
    border-radius: 10px;
}

.missing-supplier-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 22px;
    padding: 20px 24px;
    color: #fff;
    background:
        radial-gradient(circle at 12% 0, rgba(225, 29, 72, 0.32), transparent 38%),
        linear-gradient(125deg, #0f172a, #1e293b);
}

.missing-supplier-hero h3 {
    margin: 4px 0;
    color: #fff;
    font-size: 1.4rem;
    font-weight: 950;
}

.missing-supplier-hero p {
    margin: 0;
    color: #cbd5e1;
    font-weight: 700;
}

.missing-supplier-hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
}

.missing-supplier-count {
    min-width: 86px;
    padding: 7px 12px;
    border: 1px solid rgba(255, 255, 255, 0.13);
    border-radius: 13px;
    background: rgba(255, 255, 255, 0.08);
    text-align: center;
}

.missing-supplier-count span,
.missing-supplier-count strong {
    display: block;
}

.missing-supplier-count span {
    color: #cbd5e1;
    font-size: 0.65rem;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.missing-supplier-count strong {
    font-size: 1.15rem;
    font-weight: 950;
}

.missing-supplier-auto-button.hero-button {
    margin: 0;
    padding: 10px 12px;
    border-color: rgba(251, 113, 133, 0.24);
    background: rgba(225, 29, 72, 0.17);
    color: #fff;
    font-size: 0.78rem;
}

.missing-supplier-hero .analytics-modal-close {
    min-width: auto;
    height: 42px;
    padding: 0 13px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 13px;
    background: rgba(255, 255, 255, 0.09);
    box-shadow: none;
    font-size: 1.05rem;
}

.missing-supplier-filters {
    display: grid;
    grid-template-columns: repeat(6, minmax(135px, 1fr));
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.missing-supplier-filters label span {
    display: block;
    margin-bottom: 5px;
    color: #64748b;
    font-size: 0.66rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.missing-supplier-filters input,
.missing-supplier-filters select,
.missing-bulk-toolbar select,
.missing-row-action select {
    width: 100%;
    min-height: 38px;
    border: 1px solid #dbe2ea;
    border-radius: 10px;
    padding: 7px 10px;
    background: #fff;
    color: #334155;
    font-size: 0.78rem;
    font-weight: 750;
    outline: none;
}

.missing-supplier-filters input:focus,
.missing-supplier-filters select:focus,
.missing-bulk-toolbar select:focus,
.missing-row-action select:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 3px rgba(193, 18, 31, 0.08);
}

.missing-supplier-search {
    grid-column: span 2;
}

.missing-filter-actions {
    grid-column: span 2;
    display: flex;
    align-items: flex-end;
    gap: 8px;
}

.missing-primary-button,
.missing-secondary-button,
.missing-row-assign,
.missing-pagination button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-height: 38px;
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 0.75rem;
    font-weight: 900;
    transition: transform 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease;
}

.missing-primary-button,
.missing-row-assign {
    border: 0;
    background: linear-gradient(135deg, #be123c, #e11d48);
    color: #fff;
    box-shadow: 0 8px 18px rgba(190, 18, 60, 0.17);
}

.missing-secondary-button,
.missing-pagination button {
    border: 1px solid #dbe2ea;
    background: #fff;
    color: #475569;
}

.missing-primary-button:hover:not(:disabled),
.missing-row-assign:hover:not(:disabled),
.missing-secondary-button:hover:not(:disabled),
.missing-pagination button:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 9px 20px rgba(15, 23, 42, 0.1);
}

.missing-primary-button:disabled,
.missing-secondary-button:disabled,
.missing-row-assign:disabled,
.missing-pagination button:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.missing-bulk-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}

.missing-select-all {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #334155;
    font-size: 0.76rem;
    font-weight: 900;
    white-space: nowrap;
}

.missing-selection-count {
    padding: 6px 9px;
    border-radius: 999px;
    background: #fff1f2;
    color: #9f1239;
    font-size: 0.7rem;
    font-weight: 900;
    white-space: nowrap;
}

.missing-bulk-toolbar select {
    margin-left: auto;
    max-width: 260px;
}

.missing-supplier-table-wrap {
    position: relative;
    flex: 1 1 auto;
    overflow: auto;
    background: #fff;
}

.missing-supplier-loading,
.missing-table-empty {
    padding: 50px 24px !important;
    color: #64748b;
    text-align: center;
    font-weight: 850;
}

.missing-supplier-loading i,
.missing-table-empty i {
    margin-right: 7px;
    color: #c1121f;
    font-size: 1.2rem;
}

.missing-supplier-table {
    width: 100%;
    min-width: 1450px;
    border-collapse: separate;
    border-spacing: 0;
}

.missing-supplier-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 10px 9px;
    border-bottom: 1px solid #dbe2ea;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.65rem;
    font-weight: 950;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.055em;
    white-space: nowrap;
}

.missing-supplier-table td {
    padding: 10px 9px;
    border-bottom: 1px dashed #e2e8f0;
    color: #475569;
    font-size: 0.74rem;
    font-weight: 700;
    vertical-align: middle;
}

.missing-supplier-table td strong,
.missing-supplier-table td small {
    display: block;
}

.missing-supplier-table td strong {
    color: #172554;
    font-weight: 900;
}

.missing-supplier-table td small {
    margin-top: 2px;
    color: #94a3b8;
    font-size: 0.66rem;
}

.missing-supplier-table tbody tr:hover td {
    background: #fcfcfd;
}

.check-column {
    width: 38px;
    text-align: center !important;
}

.missing-supplier-badge {
    display: inline-flex;
    padding: 5px 8px;
    border: 1px solid #fde68a;
    border-radius: 999px;
    background: #fffbeb;
    color: #92400e;
    font-size: 0.66rem;
    font-weight: 900;
    white-space: nowrap;
}

.missing-row-action {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 245px;
}

.missing-row-assign {
    min-height: 36px;
    padding: 7px 10px;
}

.missing-pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 9px 18px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    font-size: 0.73rem;
    font-weight: 850;
}

@media (max-width: 767px) {
    .invoice-link-summary,
    .invoice-choice-grid {
        grid-template-columns: 1fr;
    }

    .invoice-choice-card {
        grid-template-columns: auto 1fr;
    }

    .invoice-link-actions {
        flex-direction: column-reverse;
    }

    .invoice-link-actions button {
        width: 100%;
    }

    .planning-service-context {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .planning-action-overlay { align-items:flex-end; padding:0; }
    .planning-action-panel { width:100%; max-height:94dvh; border-radius:24px 24px 0 0; }
    .planning-service-hero { margin:-24px -24px 16px; padding:20px; }
    .planning-service-route-summary { grid-template-columns:1fr; gap:8px; }
    .planning-service-route-summary > i { width:30px; height:30px; transform:rotate(90deg); }

    .planning-service-footer {
        flex-direction: column-reverse;
    }

    .planning-service-footer button {
        width: 100%;
    }

    .missing-supplier-panel {
        max-height: 96dvh;
        border-radius: 22px 22px 0 0;
    }

    .missing-supplier-hero {
        align-items: flex-start;
        padding: 17px;
    }

    .missing-supplier-hero p,
    .missing-supplier-count,
    .missing-supplier-auto-button.hero-button span {
        display: none;
    }

    .missing-supplier-hero-actions {
        gap: 6px;
    }

    .missing-supplier-hero .analytics-modal-close span {
        display: none;
    }

    .missing-supplier-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        padding: 12px;
    }

    .missing-supplier-search,
    .missing-filter-actions {
        grid-column: span 2;
    }

    .missing-bulk-toolbar {
        overflow-x: auto;
        padding: 10px 12px;
    }

    .missing-bulk-toolbar select {
        min-width: 230px;
    }

    .missing-service-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .missing-service-search-select {
        min-width: 260px;
        margin-left: 0;
    }
}

@media (max-width: 480px) {
    .planning-service-context { grid-template-columns:1fr; }
    .planning-service-action small { display:none; }
}

.supplier-table-shell,
.supplier-table-scroll { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; }
.supplier-pro-table,
.supplier-detail-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .82rem; white-space: nowrap; }
.supplier-pro-table th,
.supplier-detail-table th { padding: 13px 14px; color: #334155; background: linear-gradient(180deg, #f8fafc, #eef3f9); border-bottom: 1px solid #cdd9e7; font-size: .7rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; text-align: left; }
.supplier-pro-table td,
.supplier-detail-table td { padding: 15px 14px; border-bottom: 1px solid #edf2f7; color: #334155; vertical-align: middle; }
.supplier-detail-table tbody tr:nth-child(even) { background: #fbfdff; }
.supplier-pro-row,
.supplier-clickable-row { cursor: pointer; transition: background .2s ease, box-shadow .2s ease, transform .2s ease; }
.supplier-pro-row:hover,
.supplier-clickable-row:hover { background: linear-gradient(90deg, #fff1f2, #fff 72%); box-shadow: inset 4px 0 #c1121f, 0 6px 18px rgba(15,23,42,.06); transform: translateY(-1px); }
.supplier-row-arrow { color: #94a3b8; font-size: 1.35rem; transition: transform .18s ease, color .18s ease; }
.supplier-pro-row:hover .supplier-row-arrow { color: #c1121f; transform: translateX(3px); }
.supplier-table-modal { width: 95vw; max-width: 1780px; height: min(90vh, 980px); max-height: 90vh; padding: 0; overflow: hidden; display: flex; flex-direction: column; }
.supplier-modal-toolbar { display: flex; justify-content: space-between; gap: 16px; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #fff; }
.supplier-breadcrumb { display: flex; align-items: center; gap: 6px; min-width: 0; overflow-x: auto; }
.supplier-breadcrumb button { border: 0; background: transparent; padding: 4px; color: #64748b; font-weight: 800; white-space: nowrap; }
.supplier-breadcrumb button:hover { color: #c1121f; }
.supplier-breadcrumb span { color: #172554; font-weight: 900; white-space: nowrap; }
.supplier-modal-actions { display: flex; gap: 8px; }
.supplier-back-button,
.supplier-assign-button,
.supplier-row-action { border: 1px solid #dbe4ef; border-radius: 10px; background: #fff; color: #334155; padding: 9px 12px; font-weight: 800; }
.supplier-assign-button { background: #c1121f; color: #fff; border-color: #c1121f; }
.supplier-table-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 18px 20px 8px; }
.supplier-table-heading h3 { margin: 2px 0 0; color: #172554; font-size: 1.25rem; font-weight: 900; }
.supplier-compact-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; padding: 10px 20px; }
.supplier-compact-kpis > div { padding: 11px 13px; border: 1px solid #e2e8f0; border-radius: 14px; background: #f8fafc; }
.supplier-compact-kpis span { display: block; color: #64748b; font-size: .68rem; font-weight: 800; text-transform: uppercase; }
.supplier-compact-kpis strong { display: block; margin-top: 3px; color: #172554; }
.supplier-kpi-card { display: flex; align-items: center; gap: 11px; min-width: 0; box-shadow: 0 8px 20px rgba(15,23,42,.05); transition: transform .2s ease, box-shadow .2s ease; }
.supplier-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(15,23,42,.09); }
.supplier-kpi-card > i { display: grid; place-items: center; flex: 0 0 39px; width: 39px; height: 39px; border-radius: 12px; font-size: 1.25rem; }
.supplier-kpi-card > div { min-width: 0; }
.supplier-kpi-card small { display: block; margin-top: 1px; color: #94a3b8; font-size: .64rem; font-weight: 750; }
.supplier-kpi-card.kpi-trips { background: linear-gradient(135deg, #eff6ff, #fff); border-color: #bfdbfe; }
.supplier-kpi-card.kpi-trips > i { color: #2563eb; background: #dbeafe; }
.supplier-kpi-card.kpi-budget { background: linear-gradient(135deg, #fff1f2, #fff); border-color: #fecdd3; }
.supplier-kpi-card.kpi-budget > i { color: #be123c; background: #ffe4e6; }
.supplier-kpi-card.kpi-price { background: linear-gradient(135deg, #fff7ed, #fff); border-color: #fed7aa; }
.supplier-kpi-card.kpi-price > i { color: #ea580c; background: #ffedd5; }
.supplier-kpi-card.kpi-margin { background: linear-gradient(135deg, #ecfdf5, #fff); border-color: #a7f3d0; }
.supplier-kpi-card.kpi-margin > i { color: #059669; background: #d1fae5; }
.supplier-kpi-card.kpi-margin strong { color: #047857; }
.supplier-table-controls { display: flex; align-items: center; gap: 10px; padding: 8px 20px 12px; }
.supplier-table-controls label { flex: 1; display: flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; border-radius: 11px; padding: 9px 12px; }
.supplier-table-controls input { width: 100%; border: 0; outline: 0; background: transparent; }
.supplier-table-controls select { border: 1px solid #cbd5e1; border-radius: 11px; padding: 9px 12px; background: #fff; }
.supplier-table-controls > span { color: #64748b; font-weight: 800; white-space: nowrap; }
.supplier-table-scroll { margin: 0 20px; flex: 1; min-height: 180px; }
.supplier-cell-title { display: flex; align-items: center; gap: 8px; font-weight: 900; color: #172554; }
.supplier-cell-title i { color: #c1121f; font-size: 1.15rem; }
.supplier-status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 6px 9px; border-radius: 999px; font-size: .68rem; font-weight: 900; }
.service-color-row { box-shadow: inset 4px 0 var(--service-color, #c1121f); }
.day-color-row { box-shadow: inset 4px 0 #7c3aed; }
.head-trips { color: #1d4ed8 !important; background: #eff6ff !important; }
.head-days { color: #6d28d9 !important; background: #f5f3ff !important; }
.head-files { color: #c2410c !important; background: #fff7ed !important; }
.head-budget { color: #be123c !important; background: #fff1f2 !important; }
.head-price { color: #c2410c !important; background: #fff7ed !important; }
.head-margin { color: #047857 !important; background: #ecfdf5 !important; }
.money-cell { font-weight: 850; border-radius: 0; }
.money-budget { color: #9f1239 !important; background: rgba(255,241,242,.72); }
.money-price { color: #c2410c !important; background: rgba(255,247,237,.75); }
.money-margin { color: #047857 !important; background: rgba(236,253,245,.8); }
.metric-pill, .count-badge, .date-cell, .day-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; border-radius: 999px; padding: 6px 9px; font-weight: 900; }
.metric-trips { color: #1d4ed8; background: #dbeafe; }
.metric-days { color: #6d28d9; background: #ede9fe; }
.metric-files { color: #c2410c; background: #ffedd5; }
.date-cell { color: #1d4ed8; background: #eff6ff; }
.day-badge { color: #6d28d9; background: #f5f3ff; }
.count-success { color: #047857; background: #d1fae5; }
.count-danger { color: #be123c; background: #ffe4e6; }
.count-paid { color: #065f46; background: #a7f3d0; }
.count-unpaid { color: #c2410c; background: #ffedd5; }
.count-zero { color: #94a3b8; background: #f1f5f9; opacity: .72; }
.supplier-view-action { display: inline-flex; align-items: center; gap: 5px; color: #c1121f; font-weight: 900; }
.supplier-view-action i { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 9px; background: #ffe4e6; font-size: 1.15rem; transition: transform .2s ease, background .2s ease; }
.supplier-clickable-row:hover .supplier-view-action i { transform: translateX(4px); background: #fecdd3; }
.supplier-view-action.icon-only i { width: 32px; height: 32px; }
.actions-head { position: sticky; right: 0; z-index: 3; min-width: 410px; background: linear-gradient(180deg, #eef2ff, #e0e7ff) !important; color: #4338ca !important; }
.planning-row-actions { position: sticky; right: 0; z-index: 2; display: flex; gap: 6px; min-width: 410px; background: rgba(255,255,255,.96); box-shadow: -8px 0 16px rgba(15,23,42,.05); }
.supplier-detail-table tbody tr:nth-child(even) .planning-row-actions { background: rgba(251,253,255,.97); }
.supplier-detail-table tbody tr:hover .planning-row-actions { background: #fff7f7; }
.compact-action-button { display: inline-flex; align-items: center; gap: 5px; min-height: 32px; padding: 6px 8px; border: 1px solid transparent; border-radius: 9px; font-size: .68rem; font-weight: 900; white-space: nowrap; transition: transform .18s ease, box-shadow .18s ease; }
.compact-action-button:hover { transform: translateY(-1px); box-shadow: 0 7px 14px rgba(15,23,42,.12); }
.compact-action-button:disabled { cursor: not-allowed; opacity: .42; transform: none; box-shadow: none; }
.planning-row-actions .compact-action-button { justify-content: center; width: auto; min-width: 0; padding: 6px 9px; }
.planning-row-actions .compact-action-button span { display: inline; }
.service-action-button { color: #5b21b6; background: #ede9fe; border-color: #ddd6fe; }
.invoice-action-button { color: #be123c; background: #ffe4e6; border-color: #fecdd3; }
.invoice-action-button.linked { color: #9f1239; background: #fecdd3; border-color: #fda4af; }
.supplier-action-button { color: #047857; background: #d1fae5; border-color: #a7f3d0; }
.supplier-quick-assign-modal { width: min(94vw, 620px); }
.supplier-quick-assign-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin: -24px -24px 18px; padding: 22px 24px; color: #fff; background: linear-gradient(135deg, #047857, #059669 55%, #0f766e); border-radius: 24px 24px 0 0; }
.supplier-quick-assign-hero h3 { margin: 4px 0; color: #fff; font-size: 1.35rem; font-weight: 900; }
.supplier-quick-assign-hero p { margin: 0; color: rgba(255,255,255,.78); }
.supplier-quick-assign-hero .analytics-modal-close { color: #fff; background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.2); }
.supplier-assign-context { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 10px; }
.supplier-assign-context > div { display: grid; gap: 3px; padding: 12px; border: 1px solid #dbe4ef; border-radius: 12px; background: #f8fafc; }
.supplier-assign-context i { color: #059669; font-size: 1.15rem; }
.supplier-assign-context span, .supplier-assign-field > span { color: #64748b; font-size: .68rem; font-weight: 900; text-transform: uppercase; }
.supplier-assign-context strong { overflow: hidden; color: #172554; font-size: .78rem; text-overflow: ellipsis; white-space: nowrap; }
.supplier-assign-field { display: grid; gap: 7px; margin-top: 16px; }
.supplier-assign-field select { width: 100%; min-height: 45px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 11px; background: #fff; color: #172554; font-weight: 750; }
.supplier-assignment-save { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 11px 16px; border: 0; border-radius: 11px; color: #fff; background: #059669; font-weight: 900; }
.supplier-assignment-save:disabled { opacity: .55; }
.supplier-pagination { display: flex; justify-content: flex-end; align-items: center; gap: 10px; padding: 12px 20px 16px; color: #64748b; font-weight: 800; }
.supplier-pagination button { width: 34px; height: 34px; border: 1px solid #dbe4ef; border-radius: 9px; background: #fff; }
.supplier-pagination button:disabled { opacity: .4; }
@media (max-width: 768px) {
    .supplier-table-modal { width: 95vw; height: 92vh; max-height: 92vh; }
    .supplier-modal-toolbar, .supplier-table-heading { align-items: flex-start; }
    .supplier-modal-toolbar { padding: 12px; }
    .supplier-modal-actions .analytics-modal-close span { display: none; }
    .supplier-compact-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 8px 12px; }
    .supplier-table-controls { padding: 8px 12px; flex-wrap: wrap; }
    .supplier-table-controls label { flex-basis: 100%; }
    .supplier-table-scroll { margin: 0 12px; }
    .supplier-table-heading { padding: 14px 12px 6px; }
    .compact-action-button span { display: none; }
    .planning-row-actions, .actions-head { min-width: 92px; }
    .supplier-assign-context { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .supplier-table-modal { width: 100vw; height: 100vh; max-height: 100vh; border-radius: 0; }
}
</style>
