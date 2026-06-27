<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import { formatDate } from "@/utils/dateFormat";

defineOptions({ layout: AppShell });

const props = defineProps({
    year: Number,
    totalYear: [Number, String],
    totalMaintenances: Number,
    vehiclesCount: Number,
    topVehicles: Array,
    byType: Array,
    monthly: Array,
    latestMaintenances: Array,
});

const selectedYear = ref(props.year || new Date().getFullYear());

const years = computed(() => {
    const current = new Date().getFullYear();
    return [current, current - 1, current - 2, current - 3, current - 4];
});

const formatMoney = (value) => {
    return Number(value || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const monthName = (month) => {
    const names = [
        "",
        "January",
        "February",
        "March",
        "April",
        "May",
        "June",
        "July",
        "August",
        "September",
        "October",
        "November",
        "December",
    ];

    return names[Number(month)] || "-";
};

const mostExpensiveVehicle = computed(() => {
    return props.topVehicles?.[0] || null;
});

const maxMonthlyAmount = computed(() => {
    const values = props.monthly?.map((m) => Number(m.total_amount || 0)) || [];
    return Math.max(...values, 1);
});

const changeYear = () => {
    router.get(
        route("vehicle-maintenances.report"),
        { year: selectedYear.value },
        { preserveScroll: true, preserveState: true },
    );
};
</script>

<template>
    <Head title="Vehicle Maintenance Report" />

    <div class="report-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-bar-chart-alt-2"></i>
                        </div>

                        <div>
                            <div class="hero-kicker">Executive Dashboard</div>

                            <h1 class="hero-title">
                                General Maintenance Report
                            </h1>

                            <p class="hero-subtitle mb-0">
                                Complete analysis of maintenance expenses for
                                all vehicles.
                            </p>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <select
                            v-model="selectedYear"
                            class="form-select year-select"
                            @change="changeYear"
                        >
                            <option
                                v-for="item in years"
                                :key="item"
                                :value="item"
                            >
                                {{ item }}
                            </option>
                        </select>

                        <Link
                            :href="route('vehicules.index')"
                            class="btn btn-back"
                        >
                            <i class="bx bx-car me-2"></i>
                            Vehicles
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card red-card">
                        <div>
                            <div class="stat-label">Annual Expense</div>
                            <div class="stat-value">
                                {{ formatMoney(totalYear) }} DH
                            </div>
                            <div class="stat-note">Year {{ year }}</div>
                        </div>

                        <div class="stat-icon">
                            <i class="bx bx-money"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card orange-card">
                        <div>
                            <div class="stat-label">Operations</div>
                            <div class="stat-value">
                                {{ totalMaintenances }}
                            </div>
                            <div class="stat-note">Recorded maintenances</div>
                        </div>

                        <div class="stat-icon">
                            <i class="bx bx-wrench"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card dark-card">
                        <div>
                            <div class="stat-label">Vehicles</div>
                            <div class="stat-value">
                                {{ vehiclesCount }}
                            </div>
                            <div class="stat-note">Total fleet</div>
                        </div>

                        <div class="stat-icon">
                            <i class="bx bx-bus"></i>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="stat-card blue-card">
                        <div>
                            <div class="stat-label">Most Expensive</div>
                            <div class="stat-value small-value">
                                {{
                                    mostExpensiveVehicle?.vehicule?.matricule ||
                                    "-"
                                }}
                            </div>
                            <div class="stat-note">
                                {{
                                    mostExpensiveVehicle
                                        ? formatMoney(
                                              mostExpensiveVehicle.total_amount,
                                          ) + " DH"
                                        : "No data"
                                }}
                            </div>
                        </div>

                        <div class="stat-icon">
                            <i class="bx bx-trending-up"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="insight-card mb-4">
                <div class="insight-icon">
                    <i class="bx bx-bulb"></i>
                </div>

                <div>
                    <h5>Quick Insight for Management</h5>
                    <p class="mb-0">
                        This report helps identify the vehicles with the highest
                        maintenance costs, the most expensive intervention
                        types, and the periods where expenses increase.
                    </p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-7">
                    <div class="report-card">
                        <div class="card-header-custom">
                            <div>
                                <h5>Top Expensive Vehicles</h5>
                                <p>
                                    Vehicle ranking based on annual maintenance
                                    expenses.
                                </p>
                            </div>

                            <span class="header-badge">
                                Top {{ topVehicles?.length || 0 }}
                            </span>
                        </div>

                        <div class="table-responsive">
                            <table class="table custom-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Vehicle</th>
                                        <th>Type</th>
                                        <th>Operations</th>
                                        <th>Total</th>
                                        <th>Analysis</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(item, index) in topVehicles"
                                        :key="item.vehicule_id"
                                    >
                                        <td>
                                            <span class="rank-badge">
                                                {{ index + 1 }}
                                            </span>
                                        </td>

                                        <td>
                                            <div class="vehicle-title">
                                                {{
                                                    item.vehicule?.matricule ||
                                                    "-"
                                                }}
                                            </div>
                                            <div class="vehicle-subtitle">
                                                {{
                                                    item.vehicule?.marque || "-"
                                                }}
                                                {{
                                                    item.vehicule?.modele || ""
                                                }}
                                            </div>
                                        </td>

                                        <td>
                                            {{ item.vehicule?.type || "-" }}
                                        </td>

                                        <td>
                                            <span class="operation-badge">
                                                {{ item.total_operations }}
                                            </span>
                                        </td>

                                        <td class="money-cell">
                                            {{ formatMoney(item.total_amount) }}
                                            DH
                                        </td>

                                        <td>
                                            <span
                                                class="risk-badge"
                                                :class="{
                                                    danger: index === 0,
                                                    warning:
                                                        index === 1 ||
                                                        index === 2,
                                                }"
                                            >
                                                {{
                                                    index === 0
                                                        ? "Under Watch"
                                                        : index <= 2
                                                          ? "Important"
                                                          : "Normal"
                                                }}
                                            </span>
                                        </td>
                                    </tr>

                                    <tr v-if="!topVehicles?.length">
                                        <td colspan="6">
                                            <div class="empty-state">
                                                No maintenance recorded for this
                                                year.
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="report-card h-100">
                        <div class="card-header-custom">
                            <div>
                                <h5>Expenses by Type</h5>
                                <p>Cost breakdown by maintenance category.</p>
                            </div>
                        </div>

                        <div class="type-list">
                            <div
                                v-for="type in byType"
                                :key="type.type_maintenance"
                                class="type-item"
                            >
                                <div class="type-left">
                                    <div class="type-icon">
                                        <i class="bx bx-cog"></i>
                                    </div>

                                    <div>
                                        <div class="type-name">
                                            {{ type.type_maintenance }}
                                        </div>
                                        <div class="type-count">
                                            {{ type.total_operations }}
                                            operations
                                        </div>
                                    </div>
                                </div>

                                <div class="type-amount">
                                    {{ formatMoney(type.total_amount) }} DH
                                </div>
                            </div>

                            <div v-if="!byType?.length" class="empty-state">
                                No type data available.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="report-card">
                        <div class="card-header-custom">
                            <div>
                                <h5>Monthly Evolution</h5>
                                <p>
                                    Simple monthly view of maintenance expenses.
                                </p>
                            </div>
                        </div>

                        <div class="monthly-list">
                            <div
                                v-for="item in monthly"
                                :key="item.month"
                                class="month-row"
                            >
                                <div class="month-info">
                                    <span>{{ monthName(item.month) }}</span>
                                    <small>
                                        {{ item.total_operations }}
                                        operations
                                    </small>
                                </div>

                                <div class="month-bar-wrap">
                                    <div
                                        class="month-bar"
                                        :style="{
                                            width:
                                                Math.max(
                                                    8,
                                                    (Number(
                                                        item.total_amount || 0,
                                                    ) /
                                                        maxMonthlyAmount) *
                                                        100,
                                                ) + '%',
                                        }"
                                    ></div>
                                </div>

                                <div class="month-amount">
                                    {{ formatMoney(item.total_amount) }} DH
                                </div>
                            </div>

                            <div v-if="!monthly?.length" class="empty-state">
                                No monthly data available.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="report-card">
                        <div class="card-header-custom">
                            <div>
                                <h5>Latest Maintenances</h5>
                                <p>Last recorded maintenance operations.</p>
                            </div>
                        </div>

                        <div class="latest-list">
                            <div
                                v-for="item in latestMaintenances"
                                :key="item.id"
                                class="latest-item"
                            >
                                <div class="latest-icon">
                                    <i class="bx bx-wrench"></i>
                                </div>

                                <div class="latest-content">
                                    <div class="latest-title">
                                        {{ item.type_maintenance }}
                                        <span>
                                            {{ formatMoney(item.montant) }} DH
                                        </span>
                                    </div>

                                    <div class="latest-subtitle">
                                        {{ item.vehicule?.matricule || "-" }}
                                        •
                                        {{ formatDate(item.date_maintenance) }}
                                        •
                                        {{
                                            item.garage ||
                                            "Garage not specified"
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="!latestMaintenances?.length"
                                class="empty-state"
                            >
                                No recent maintenance.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.report-page {
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

.hero-kicker {
    display: inline-flex;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-weight: 800;
    font-size: 0.78rem;
    margin-bottom: 8px;
}

.hero-title {
    color: #fff;
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 6px;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.98rem;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.year-select {
    min-width: 130px;
    height: 48px;
    border-radius: 16px;
    border: 0;
    font-weight: 900;
    color: #991b1b;
}

.btn-back {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 900;
    text-decoration: none;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.stat-card {
    min-height: 150px;
    border-radius: 26px;
    padding: 24px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 15px 30px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    position: relative;
}

.stat-card::after {
    content: "";
    position: absolute;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    right: -40px;
    bottom: -50px;
    background: rgba(255, 255, 255, 0.14);
}

.red-card {
    background: linear-gradient(135deg, #be123c 0%, #e11d48 100%);
}

.orange-card {
    background: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);
}

.dark-card {
    background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
}

.blue-card {
    background: linear-gradient(135deg, #1d4ed8 0%, #0f172a 100%);
}

.stat-label {
    font-size: 0.88rem;
    font-weight: 800;
    opacity: 0.9;
}

.stat-value {
    font-size: 1.85rem;
    font-weight: 950;
    margin-top: 8px;
    line-height: 1.15;
}

.small-value {
    font-size: 1.25rem;
}

.stat-note {
    margin-top: 8px;
    font-weight: 800;
    opacity: 0.86;
    font-size: 0.82rem;
}

.stat-icon {
    font-size: 44px;
    opacity: 0.9;
    z-index: 2;
}

.insight-card,
.report-card {
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 24px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.insight-card {
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 18px;
}

.insight-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff7ed;
    color: #ea580c;
    font-size: 30px;
    flex-shrink: 0;
}

.insight-card h5 {
    margin-bottom: 6px;
    font-weight: 950;
    color: #0f172a;
}

.insight-card p {
    color: #64748b;
    font-weight: 650;
}

.report-card {
    padding: 24px;
}

.card-header-custom {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: flex-start;
    margin-bottom: 20px;
}

.card-header-custom h5 {
    font-weight: 950;
    color: #0f172a;
    margin-bottom: 5px;
}

.card-header-custom p {
    margin-bottom: 0;
    color: #64748b;
    font-weight: 650;
}

.header-badge {
    padding: 8px 12px;
    border-radius: 999px;
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    font-weight: 900;
    white-space: nowrap;
}

.custom-table thead th {
    padding: 16px;
    background: linear-gradient(180deg, #fff1f2 0%, #fff7ed 100%);
    color: #9f1239;
    font-size: 0.84rem;
    font-weight: 950;
    border-bottom: 1px solid #ffe4e6;
    white-space: nowrap;
}

.custom-table tbody td {
    padding: 17px 16px;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 700;
    color: #334155;
}

.custom-table tbody tr:hover td {
    background: #f8fafc;
}

.rank-badge {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    color: #fff;
    font-weight: 950;
}

.vehicle-title {
    font-weight: 950;
    color: #0f172a;
}

.vehicle-subtitle {
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 700;
}

.operation-badge {
    display: inline-flex;
    padding: 7px 12px;
    border-radius: 999px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #dbeafe;
    font-weight: 900;
}

.money-cell {
    color: #be123c !important;
    font-weight: 950 !important;
}

.risk-badge {
    display: inline-flex;
    padding: 7px 12px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
    font-weight: 900;
    white-space: nowrap;
}

.risk-badge.warning {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}

.risk-badge.danger {
    background: #fff1f2;
    color: #be123c;
    border-color: #fecdd3;
}

.type-list,
.latest-list,
.monthly-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.type-item,
.latest-item,
.month-row {
    background: #fff;
    border: 1px solid #eef2f7;
    border-radius: 18px;
    padding: 16px;
}

.type-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.type-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.type-icon,
.latest-icon {
    width: 44px;
    height: 44px;
    border-radius: 15px;
    background: #fff7ed;
    color: #ea580c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.type-name {
    color: #0f172a;
    font-weight: 950;
}

.type-count {
    color: #64748b;
    font-size: 0.82rem;
    font-weight: 700;
}

.type-amount {
    color: #be123c;
    font-weight: 950;
    white-space: nowrap;
}

.month-row {
    display: grid;
    grid-template-columns: 140px 1fr 130px;
    gap: 14px;
    align-items: center;
}

.month-info span {
    display: block;
    font-weight: 950;
    color: #0f172a;
}

.month-info small {
    color: #64748b;
    font-weight: 700;
}

.month-bar-wrap {
    height: 12px;
    border-radius: 999px;
    background: #f1f5f9;
    overflow: hidden;
}

.month-bar {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.month-amount {
    text-align: right;
    font-weight: 950;
    color: #be123c;
}

.latest-item {
    display: flex;
    align-items: center;
    gap: 14px;
}

.latest-content {
    flex: 1;
    min-width: 0;
}

.latest-title {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    font-weight: 950;
    color: #0f172a;
}

.latest-title span {
    color: #be123c;
    white-space: nowrap;
}

.latest-subtitle {
    color: #64748b;
    font-size: 0.86rem;
    font-weight: 700;
    margin-top: 4px;
}

.empty-state {
    padding: 35px 20px;
    text-align: center;
    color: #64748b;
    font-weight: 800;
}

@media (max-width: 768px) {
    .hero-card {
        padding: 20px;
    }

    .hero-title {
        font-size: 1.45rem;
    }

    .month-row {
        grid-template-columns: 1fr;
    }

    .month-amount {
        text-align: left;
    }

    .latest-title {
        flex-direction: column;
    }
}
</style>
