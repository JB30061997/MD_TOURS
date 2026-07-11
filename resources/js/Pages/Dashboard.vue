<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, nextTick, reactive, ref } from "vue";
import VueApexCharts from "vue3-apexcharts";
import AppShell from "@/Layouts/AppShell.vue";
import SearchSelect from "@/Components/SearchSelect.vue";
import Swal from "sweetalert2";
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

const monthlyCardIcon = (key) =>
    ({
        budget: "bx-wallet-alt",
        supplier_cost: "bx-buildings",
        gross_margin: "bx-line-chart",
    })[key] || "bx-bar-chart-alt-2";

const monthlyCardClass = (key) =>
    ({
        budget: "finance-budget",
        supplier_cost: "finance-cost",
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

const openSupplierDrilldown = (supplierId) => {
    const detail = supplierDrilldownById.value.get(String(supplierId));

    if (!detail) return;

    selectedSupplierDrilldown.value = detail;
    selectedSupplierService.value = null;
    selectedSupplierDay.value = null;
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
};

const closeSupplierService = () => {
    selectedSupplierService.value = null;
    selectedSupplierDay.value = null;
};

const openSupplierServiceDay = (dayIndex) => {
    const day = selectedSupplierService.value?.days?.[dayIndex];
    if (!day) return;

    selectedSupplierDay.value = day;
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
                            <div class="metric-top">
                                <div class="metric-icon">
                                    <i class="bx bx-calendar-star"></i>
                                </div>
                                <div class="metric-chip">Période</div>
                            </div>
                            <div class="metric-main">
                                <div class="metric-value">
                                    {{ stats.total_plannings || 0 }}
                                </div>
                                <div class="metric-label">Total plannings</div>
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
                            <div class="metric-main">
                                <div class="metric-value">
                                    {{ stats.today_plannings || 0 }}
                                </div>
                                <div class="metric-label">Aujourd’hui</div>
                            </div>
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
                            <div class="metric-main">
                                <div class="metric-value">
                                    {{ stats.upcoming_plannings || 0 }}
                                </div>
                                <div class="metric-label">À venir</div>
                            </div>
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
                            <div class="metric-main">
                                <div class="metric-value">
                                    {{ stats.assigned_clients || 0 }}
                                </div>
                                <div class="metric-label">Clients affectés</div>
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
                    class="col-12 col-xl-4"
                >
                    <div
                        :class="[
                            'finance-card card border-0 shadow-sm h-100',
                            monthlyCardClass(item.key),
                        ]"
                    >
                        <div class="card-body finance-card-body">
                            <div class="finance-top-line">
                                <div class="finance-icon">
                                    <i
                                        :class="[
                                            'bx',
                                            monthlyCardIcon(item.key),
                                        ]"
                                    ></i>
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
                            <div class="section-title text-white-50">
                                {{ item.label }}
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
                            <div class="supplier-performance-list">
                                <div
                                    v-for="(
                                        item, index
                                    ) in supplierVehiculePerformance"
                                    :key="item.id"
                                    class="supplier-performance-row"
                                    role="button"
                                    tabindex="0"
                                    @click="openSupplierDrilldown(item.id)"
                                    @keyup.enter="openSupplierDrilldown(item.id)"
                                >
                                    <div class="supplier-name-box">
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
                                        </div>
                                    </div>

                                    <div class="supplier-kpi">
                                        <span>Trajets</span>
                                        <strong>
                                            {{ item.total_trips }}
                                        </strong>
                                    </div>

                                    <div class="supplier-kpi money">
                                        <span>Marge</span>
                                        <strong>
                                            {{
                                                formatMoney(
                                                    item.gross_margin,
                                                )
                                            }}
                                            MAD
                                        </strong>
                                    </div>
                                </div>
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

            <div
                v-if="selectedSupplierDrilldown"
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
                v-if="selectedSupplierService"
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
                v-if="selectedSupplierDay"
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
                                    <strong
                                        :class="{
                                            'planning-service-missing':
                                                !planning.service_id,
                                        }"
                                    >
                                        {{ planning.service || "Sans service" }}
                                    </strong>
                                    <button
                                        v-if="canEditPlanningService"
                                        type="button"
                                        class="planning-service-action"
                                        @click="openPlanningServiceModal(planning)"
                                    >
                                        <i class="bx bx-edit-alt"></i>
                                        {{
                                            planning.service_id
                                                ? "Modifier le service"
                                                : "Affecter un service"
                                        }}
                                    </button>
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

            <div
                v-if="planningServiceModal"
                class="analytics-modal-backdrop planning-service-backdrop"
                @click.self="closePlanningServiceModal"
            >
                <div class="analytics-modal planning-service-modal">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Affectation du service</div>
                            <h3>{{ planningServiceModal.ref_dossier }}</h3>
                            <p>
                                Choisissez le service correspondant au trajet. Seul
                                le planning sera mis à jour.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="analytics-modal-close"
                            :disabled="serviceForm.processing"
                            @click="closePlanningServiceModal"
                        >
                            <i class="bx bx-x"></i>
                            <span>Fermer</span>
                        </button>
                    </div>

                    <div class="planning-service-context">
                        <div><span>Dossier</span><strong>{{ planningServiceModal.ref_dossier }}</strong></div>
                        <div><span>Date</span><strong>{{ planningServiceModal.date_du || "-" }}</strong></div>
                        <div><span>Heure</span><strong>{{ planningServiceModal.heure || "-" }}</strong></div>
                        <div><span>Départ</span><strong>{{ planningServiceModal.point_depart || "-" }}</strong></div>
                        <div><span>Destination</span><strong>{{ planningServiceModal.destination || "-" }}</strong></div>
                        <div><span>Client</span><strong>{{ planningServiceModal.clients?.join(", ") || planningServiceModal.supplier_client || "-" }}</strong></div>
                        <div><span>Fournisseur véhicule</span><strong>{{ planningServiceModal.supplier_vehicle || "-" }}</strong></div>
                        <div><span>Chauffeur</span><strong>{{ planningServiceModal.driver || "-" }}</strong></div>
                        <div><span>Véhicule</span><strong>{{ planningServiceModal.vehicule || "-" }}</strong></div>
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
                        <label>Service disponible</label>
                        <SearchSelect
                            v-model="serviceForm.service_id"
                            v-model:search="serviceForm.search"
                            :options="serviceOptions"
                            label-key="designation"
                            value-key="id"
                            :allow-custom="false"
                            placeholder="Rechercher un service par son nom..."
                        />
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

            <div
                v-if="invoiceLinkModal.open"
                class="analytics-modal-backdrop invoice-link-overlay"
                @click.self="closeInvoiceLinkModal"
            >
                <div class="analytics-modal invoice-link-panel">
                    <div class="analytics-modal-head">
                        <div>
                            <div class="panel-kicker">Rattachement facture</div>
                            <h3>
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
    min-height: 118px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.82) !important;
    box-shadow:
        0 16px 36px rgba(15, 23, 42, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
}

.metric-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            135deg,
            rgba(255, 255, 255, 0.72),
            rgba(255, 255, 255, 0.28)
        ),
        radial-gradient(
            circle at 88% 18%,
            rgba(255, 255, 255, 0.95),
            transparent 34%
        );
    pointer-events: none;
    transition: opacity 0.24s ease;
}

.metric-card::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    right: -42px;
    bottom: -46px;
    border-radius: 999px;
    opacity: 0.34;
    pointer-events: none;
    transition:
        transform 0.28s ease,
        opacity 0.28s ease;
}

.metric-card:hover::after {
    opacity: 0.48;
    transform: scale(1.08);
}

.metric-card-body {
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 15px 16px;
}

.metric-red {
    background:
        linear-gradient(135deg, rgba(225, 29, 72, 0.16), #ffffff 72%),
        #ffffff;
}

.metric-red::after {
    background: #e11d48;
}

.metric-blue {
    background:
        linear-gradient(135deg, rgba(37, 99, 235, 0.16), #ffffff 72%),
        #ffffff;
}

.metric-blue::after {
    background: #2563eb;
}

.metric-purple {
    background:
        linear-gradient(135deg, rgba(124, 58, 237, 0.16), #ffffff 72%),
        #ffffff;
}

.metric-purple::after {
    background: #7c3aed;
}

.metric-green {
    background:
        linear-gradient(135deg, rgba(22, 163, 74, 0.16), #ffffff 72%),
        #ffffff;
}

.metric-green::after {
    background: #10b981;
}

.metric-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.metric-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.12rem;
    color: #fff;
    background: linear-gradient(135deg, #111827, #374151);
    box-shadow:
        0 12px 24px rgba(17, 24, 39, 0.16),
        inset 0 1px 0 rgba(255, 255, 255, 0.16);
    animation: iconBreath 3.6s ease-in-out infinite;
}

.metric-chip {
    font-size: 0.72rem;
    font-weight: 900;
    color: #6b7280;
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 999px;
    padding: 5px 9px;
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
    align-items: baseline;
    gap: 12px;
    min-width: 0;
}

.metric-value {
    font-size: clamp(1.45rem, 2vw, 1.85rem);
    font-weight: 950;
    color: #111827;
    line-height: 1.1;
    letter-spacing: 0;
    white-space: nowrap;
}

.metric-main .metric-label {
    color: #475569;
    font-size: clamp(0.8rem, 0.85vw, 0.92rem);
    font-weight: 950;
    line-height: 1.2;
}

.finance-card {
    position: relative;
    min-height: 160px;
    color: #fff;
    overflow: hidden;
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
    inset: auto -36px -48px auto;
    width: 128px;
    height: 128px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    pointer-events: none;
    transition: transform 0.28s ease;
}

.finance-card:hover::after {
    transform: scale(1.12);
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
    position: relative;
    z-index: 1;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 18px;
}

.finance-top-line {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 10px;
}

.finance-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    animation: iconBreath 3.4s ease-in-out infinite;
}

.finance-trend-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 0.78rem;
    font-weight: 950;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: rgba(255, 255, 255, 0.14);
    white-space: nowrap;
    animation: softPulse 3.8s ease-in-out infinite;
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
    font-size: clamp(1.55rem, 1.8vw, 1.9rem);
    font-weight: 950;
    line-height: 1.1;
    margin-top: 5px;
}

.finance-note {
    opacity: 0.88;
    margin-top: 5px;
    font-size: 0.82rem;
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
    min-height: 104px;
    border: 1px solid rgba(226, 232, 240, 0.86) !important;
}

.mini-stat-card-body {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 16px;
}

.mini-stat-head {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-weight: 900;
    margin-bottom: 6px;
    font-size: 0.82rem;
}

.mini-stat-head i {
    font-size: 1.2rem;
    color: #c1121f;
}

.mini-stat-value {
    font-size: 1.45rem;
    font-weight: 950;
    color: #111827;
    line-height: 1.1;
}

.mini-stat-sub {
    color: #9ca3af;
    font-size: 0.8rem;
    margin-top: 4px;
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

.invoice-link-overlay {
    z-index: 125;
}

.invoice-link-panel {
    width: min(1180px, calc(100vw - 32px));
    max-height: calc(100vh - 48px);
    overflow-y: auto;
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
        gap: 4px;
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

.planning-service-action {
    margin-top: 8px;
    border: 1px solid rgba(193, 18, 31, 0.2);
    border-radius: 10px;
    padding: 7px 10px;
    background: #fff1f2;
    color: #be123c;
    font-size: 0.78rem;
    font-weight: 850;
}

.planning-service-action:hover {
    background: #c1121f;
    color: #fff;
}

.planning-service-backdrop {
    z-index: 120;
}

.planning-service-modal {
    width: min(1040px, calc(100vw - 32px));
    max-height: calc(100vh - 48px);
    overflow-y: auto;
}

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
        grid-template-columns: 1fr;
    }

    .planning-service-footer {
        flex-direction: column-reverse;
    }

    .planning-service-footer button {
        width: 100%;
    }
}
</style>
