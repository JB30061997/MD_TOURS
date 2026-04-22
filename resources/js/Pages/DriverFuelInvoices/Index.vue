<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    invoices: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const state = reactive({
    search: props.filters?.search || "",
});

const rows = computed(() => props.invoices?.data || []);
const totalInvoices = computed(() => props.invoices?.total || 0);

const totalAmount = computed(() => {
    return rows.value.reduce((sum, item) => {
        return sum + Number(item.total_amount || 0);
    }, 0);
});

function doSearch() {
    router.get(
        "/driver-fuel-invoices",
        {
            search: state.search,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function clearSearch() {
    state.search = "";
    doSearch();
}

function destroyInvoice(id) {
    if (!confirm("Voulez-vous vraiment supprimer cette facture ?")) return;

    router.delete(`/driver-fuel-invoices/${id}`, {
        preserveScroll: true,
    });
}

function formatMoney(value) {
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
}

function formatDate(value) {
    if (!value) return "-";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat("fr-FR").format(date);
}

function formatPeriod(start, end) {
    return `${formatDate(start)} → ${formatDate(end)}`;
}
</script>

<template>
    <Head title="Factures Carburant" />

    <div class="invoice-page">
        <!-- HERO -->
        <div class="hero-card">
            <div class="hero-content">
                <p class="mini-title">MD TOURS • Carburant</p>
                <h1 class="text-white">Factures Chauffeurs</h1>
                <!-- <p class="hero-text">
                    Gérez les consommations carburant des chauffeurs,
                    suivez les plannings liés et contrôlez rapidement les dépenses.
                </p> -->
            </div>

            <Link href="/driver-fuel-invoices/create" class="create-btn">
                + Nouvelle Facture
            </Link>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card stat-card-red">
                <div class="stat-label">Total Factures</div>
                <div class="stat-value">{{ totalInvoices }}</div>
            </div>

            <div class="stat-card stat-card-green">
                <div class="stat-label">Montant visible</div>
                <div class="stat-value amount">
                    {{ formatMoney(totalAmount) }} MAD
                </div>
            </div>

            <div class="stat-card stat-card-light">
                <div class="stat-label">Recherche active</div>
                <div class="stat-value muted">
                    {{ state.search ? "Oui" : "Non" }}
                </div>
            </div>
        </div>

        <!-- SEARCH BOX -->
        <div class="box-card">
            <div class="section-head">
                <div>
                    <h2>Liste des factures</h2>
                    <p>
                        Consultez, modifiez ou supprimez les factures carburant.
                    </p>
                </div>
            </div>

            <div class="search-row">
                <input
                    v-model="state.search"
                    type="text"
                    class="search-input"
                    placeholder="Rechercher par chauffeur, facture, montant..."
                    @keyup.enter="doSearch"
                />

                <button class="btn primary-btn" @click="doSearch">
                    Recherche
                </button>

                <button class="btn light-btn" @click="clearSearch">
                    Reset
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="box-card table-card">
            <div v-if="rows.length" class="table-wrap">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Chauffeur</th>
                            <th>Période</th>
                            <th>N° Facture</th>
                            <th>Date Facture</th>
                            <th>Montant</th>
                            <th>Plannings</th>
                            <th>File</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="invoice in rows" :key="invoice.id">
                            <td class="fw-cell">#{{ invoice.id }}</td>

                            <td>
                                <div class="driver-box">
                                    <div class="avatar-circle">
                                        {{
                                            invoice.driver?.name?.charAt(0) ||
                                            "?"
                                        }}
                                    </div>

                                    <div class="driver-info">
                                        <div class="driver-name">
                                            {{ invoice.driver?.name || "-" }}
                                        </div>
                                        <div class="driver-sub">Chauffeur</div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="period-badge">
                                    {{
                                        formatPeriod(
                                            invoice.period_start,
                                            invoice.period_end,
                                        )
                                    }}
                                </span>
                            </td>

                            <td>
                                <span class="invoice-badge">
                                    {{ invoice.invoice_number || "-" }}
                                </span>
                            </td>

                            <td>
                                {{ formatDate(invoice.invoice_date) }}
                            </td>

                            <td>
                                <span class="amount-text">
                                    {{ formatMoney(invoice.total_amount) }} MAD
                                </span>
                            </td>

                            <td>
                                <span class="planning-count">
                                    {{ invoice.plannings?.length || 0 }}
                                </span>
                            </td>

                            <td>
                                <a
                                    v-if="invoice.pdf_path"
                                    :href="`/storage/${invoice.pdf_path}`"
                                    target="_blank"
                                    class="pdf-btn"
                                >
                                    📄 Voir PDF
                                </a>

                                <span v-else class="no-file">
                                    — Aucun fichier —
                                </span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <Link
                                        :href="`/driver-fuel-invoices/${invoice.id}`"
                                        class="action-btn view-btn"
                                    >
                                        Voir
                                    </Link>

                                    <Link
                                        :href="`/driver-fuel-invoices/${invoice.id}/edit`"
                                        class="action-btn edit-btn"
                                    >
                                        Modifier
                                    </Link>

                                    <button
                                        class="action-btn delete-btn"
                                        @click="destroyInvoice(invoice.id)"
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="empty-box">
                <div class="empty-icon">🧾</div>
                <h3>Aucune facture trouvée</h3>
                <p>
                    Commencez par ajouter une nouvelle facture carburant pour un
                    chauffeur.
                </p>

                <Link
                    href="/driver-fuel-invoices/create"
                    class="empty-create-btn"
                >
                    + Ajouter une facture
                </Link>
            </div>

            <!-- PAGINATION -->
            <div v-if="invoices.links?.length" class="pagination-wrap">
                <template v-for="(link, index) in invoices.links" :key="index">
                    <button
                        v-if="link.url"
                        class="page-btn"
                        :class="{ active: link.active }"
                        @click="router.visit(link.url)"
                        v-html="link.label"
                    />

                    <span v-else class="page-disabled" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.invoice-page {
    padding: 24px;
    background:
        radial-gradient(
            circle at top left,
            rgba(193, 18, 31, 0.08),
            transparent 28%
        ),
        radial-gradient(
            circle at top right,
            rgba(239, 68, 68, 0.06),
            transparent 20%
        ),
        #f5f6fa;
    min-height: 100vh;
}

.hero-card {
    background: linear-gradient(135deg, #7f1d1d 0%, #b91c1c 55%, #dc2626 100%);
    color: #fff;
    border-radius: 26px;
    padding: 30px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    box-shadow: 0 18px 40px rgba(185, 28, 28, 0.18);
}

.hero-content {
    max-width: 760px;
}

.mini-title {
    margin: 0 0 8px;
    font-size: 12px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    opacity: 0.85;
}

.hero-card h1 {
    margin: 0 0 10px;
    font-size: 36px;
    font-weight: 800;
}

.hero-text {
    margin: 0;
    font-size: 15px;
    line-height: 1.8;
    opacity: 0.95;
}

.create-btn {
    background: #fff;
    color: #b91c1c;
    text-decoration: none;
    padding: 14px 22px;
    border-radius: 16px;
    font-weight: 800;
    white-space: nowrap;
    box-shadow: 0 8px 20px rgba(255, 255, 255, 0.18);
    transition: 0.2s ease;
}

.create-btn:hover {
    transform: translateY(-2px);
    color: #991b1b;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.stat-card {
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
    border: 1px solid #edf2f7;
    background: #fff;
}

.stat-card-red {
    background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
    border-color: #fecaca;
}

.stat-card-green {
    background: linear-gradient(135deg, #ffffff 0%, #ecfdf5 100%);
    border-color: #bbf7d0;
}

.stat-card-light {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
}

.stat-label {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 30px;
    font-weight: 800;
    color: #0f172a;
}

.stat-value.amount {
    color: #059669;
}

.stat-value.muted {
    color: #2563eb;
}

.box-card {
    background: #fff;
    border-radius: 24px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
    border: 1px solid #edf2f7;
}

.table-card {
    padding-bottom: 18px;
}

.section-head h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
}

.section-head p {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 14px;
}

.search-row {
    margin-top: 20px;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    min-height: 54px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 0 16px;
    font-size: 14px;
    background: #f8fafc;
    transition: 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
    background: #fff;
}

.btn {
    border: 0;
    border-radius: 16px;
    padding: 0 20px;
    min-height: 54px;
    font-weight: 800;
    cursor: pointer;
    transition: 0.2s ease;
}

.primary-btn {
    background: linear-gradient(135deg, #b91c1c, #dc2626);
    color: white;
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.18);
}

.primary-btn:hover {
    transform: translateY(-1px);
}

.light-btn {
    background: #f1f5f9;
    color: #334155;
}

.table-wrap {
    overflow-x: auto;
}

.invoice-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 1200px;
}

.invoice-table thead th {
    background: #f8fafc;
    padding: 16px;
    text-align: left;
    font-size: 13px;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 800;
}

.invoice-table tbody td {
    padding: 18px 16px;
    border-bottom: 1px solid #edf2f7;
    vertical-align: middle;
    color: #334155;
    font-size: 14px;
}

.invoice-table tbody tr:hover {
    background: #fff8f8;
}

.fw-cell {
    font-weight: 800;
    color: #0f172a;
}

.driver-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #b91c1c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 15px;
}

.driver-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.driver-name {
    font-weight: 800;
    color: #0f172a;
}

.driver-sub {
    font-size: 12px;
    color: #94a3b8;
}

.period-badge {
    display: inline-flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 10px 12px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
}

.invoice-badge {
    display: inline-flex;
    align-items: center;
    background: #fee2e2;
    color: #b91c1c;
    padding: 9px 12px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 13px;
}

.amount-text {
    font-weight: 800;
    color: #059669;
}

.planning-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 999px;
    background: #ecfdf5;
    color: #047857;
    font-weight: 800;
}

.action-group {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.action-btn {
    border: 0;
    padding: 10px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    transition: 0.2s ease;
}

.view-btn {
    background: #eff6ff;
    color: #1d4ed8;
}

.edit-btn {
    background: #fff7ed;
    color: #ea580c;
}

.delete-btn {
    background: #fef2f2;
    color: #dc2626;
}

.action-btn:hover {
    transform: translateY(-1px);
}

.empty-box {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 2px dashed #e2e8f0;
    border-radius: 22px;
}

.empty-icon {
    font-size: 40px;
    margin-bottom: 12px;
}

.empty-box h3 {
    margin: 0 0 8px;
    font-size: 22px;
    color: #0f172a;
}

.empty-box p {
    margin: 0 0 20px;
    color: #64748b;
}

.empty-create-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #b91c1c, #dc2626);
    color: #fff;
    text-decoration: none;
    padding: 12px 18px;
    border-radius: 14px;
    font-weight: 800;
}

.pagination-wrap {
    margin-top: 22px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.page-btn {
    border: 0;
    background: #f8fafc;
    color: #334155;
    min-width: 42px;
    height: 42px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 700;
    transition: 0.2s ease;
}

.page-btn.active {
    background: linear-gradient(135deg, #b91c1c, #dc2626);
    color: white;
    box-shadow: 0 10px 20px rgba(220, 38, 38, 0.18);
}

.page-disabled {
    min-width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .hero-card {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 640px) {
    .invoice-page {
        padding: 14px;
    }

    .hero-card,
    .box-card {
        padding: 18px;
    }

    .hero-card h1 {
        font-size: 28px;
    }

    .hero-text {
        font-size: 14px;
    }

    .search-row {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}

.pdf-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    padding: 8px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    transition: 0.2s;
}

.pdf-btn:hover {
    background: #dbeafe;
}

.no-file {
    color: #94a3b8;
    font-size: 13px;
    font-weight: 600;
}
</style>
