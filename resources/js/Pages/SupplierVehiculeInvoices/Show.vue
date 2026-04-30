<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

const plannings = computed(() => props.invoice?.plannings || []);
const totalPlannings = computed(() => plannings.value.length);

const supplierName = computed(() => {
    return (
        props.invoice?.supplier_vehicule?.name ||
        props.invoice?.supplierVehicule?.name ||
        "-"
    );
});

const supplierPhone = computed(() => {
    return (
        props.invoice?.supplier_vehicule?.phone ||
        props.invoice?.supplierVehicule?.phone ||
        "-"
    );
});

const supplierEmail = computed(() => {
    return (
        props.invoice?.supplier_vehicule?.email ||
        props.invoice?.supplierVehicule?.email ||
        "-"
    );
});

const supplierStatus = computed(() => {
    return (
        props.invoice?.supplier_vehicule?.status ||
        props.invoice?.supplierVehicule?.status ||
        "-"
    );
});

function formatMoney(value) {
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));
}

function formatDate(value) {
    if (!value) return "-";

    if (typeof value === "string") {
        return value.slice(0, 10);
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return "-";

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");

    return `${year}-${month}-${day}`;
}

function goDelete() {
    if (!confirm("Voulez-vous vraiment supprimer cette facture ?")) return;

    router.delete(`/supplier-vehicule-invoices/${props.invoice.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Facture #${invoice.id}`" />

    <div class="show-page">
        <div class="hero-card">
            <div class="hero-overlay"></div>

            <div class="hero-left">
                <div class="hero-chip">Pilotage fournisseurs véhicules</div>
                <h1 class="text-white">Facture #{{ invoice.id }}</h1>
            </div>

            <div class="hero-actions">
                <Link href="/supplier-vehicule-invoices" class="hero-btn light">
                    Retour
                </Link>

                <Link
                    :href="`/supplier-vehicule-invoices/${invoice.id}/edit`"
                    class="hero-btn edit"
                >
                    Modifier
                </Link>

                <button class="hero-btn delete" @click="goDelete">
                    Supprimer
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-red">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Montant total</div>
                <div class="stat-value">
                    {{ formatMoney(invoice.total_amount) }} MAD
                </div>
                <div class="stat-sub">Montant global de la facture</div>
            </div>

            <div class="stat-card stat-soft">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Plannings liés</div>
                <div class="stat-value blue">
                    {{ totalPlannings }}
                </div>
                <div class="stat-sub">Services liés à cette facture</div>
            </div>

            <div class="stat-card stat-soft">
                <div class="stat-icon">🧾</div>
                <div class="stat-label">N° facture</div>
                <div class="stat-value">
                    {{ invoice.invoice_number || "-" }}
                </div>
                <div class="stat-sub">Référence de la facture</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="main-column">
                <div class="box-card glass-card">
                    <div class="section-head">
                        <div>
                            <h2>Détails de la facture</h2>
                            <p>
                                Les informations principales liées à cette
                                facture fournisseur véhicule.
                            </p>
                        </div>
                    </div>

                    <div class="details-grid">
                        <div class="info-card">
                            <span class="info-label">ID Facture</span>
                            <strong class="info-value"
                                >#{{ invoice.id }}</strong
                            >
                        </div>

                        <div class="info-card">
                            <span class="info-label">N° Facture</span>
                            <strong class="info-value">
                                {{ invoice.invoice_number || "-" }}
                            </strong>
                        </div>

                        <div class="info-card">
                            <span class="info-label">Date Facture</span>
                            <strong class="info-value">
                                {{ formatDate(invoice.invoice_date) }}
                            </strong>
                        </div>

                        <div class="info-card amount-card">
                            <span class="info-label">Montant</span>
                            <strong class="info-value red-text">
                                {{ formatMoney(invoice.total_amount) }} MAD
                            </strong>
                        </div>

                        <div class="info-card">
                            <span class="info-label">Période début</span>
                            <strong class="info-value">
                                {{ formatDate(invoice.period_start) }}
                            </strong>
                        </div>

                        <div class="info-card">
                            <span class="info-label">Période fin</span>
                            <strong class="info-value">
                                {{ formatDate(invoice.period_end) }}
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="box-card">
                    <div class="section-head">
                        <div>
                            <h2>Plannings liés</h2>
                            <p>
                                Liste des services et plannings sélectionnés
                                pour cette facture.
                            </p>
                        </div>
                    </div>

                    <div v-if="plannings.length" class="table-wrap">
                        <table class="planning-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date du</th>
                                    <th>Date au</th>
                                    <th>Réf dossier</th>
                                    <th>Service</th>
                                    <th>Départ</th>
                                    <th>Destination</th>
                                    <th>Bus</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="planning in plannings"
                                    :key="planning.id"
                                >
                                    <td>
                                        <strong>#{{ planning.id }}</strong>
                                    </td>
                                    <td>{{ formatDate(planning.date_du) }}</td>
                                    <td>{{ formatDate(planning.date_au) }}</td>
                                    <td>{{ planning.ref_dossier || "-" }}</td>
                                    <td>
                                        <span class="service-badge">
                                            {{
                                                planning.service?.designation ||
                                                "-"
                                            }}
                                        </span>
                                    </td>
                                    <td>{{ planning.point_depart || "-" }}</td>
                                    <td>{{ planning.destination || "-" }}</td>
                                    <td>{{ planning.bus || "-" }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="empty-box">
                        <div class="empty-icon">📭</div>
                        <div>Aucun planning lié à cette facture.</div>
                    </div>
                </div>

                <div v-if="invoice.notes" class="box-card notes-card">
                    <div class="section-head">
                        <div>
                            <h2>Notes</h2>
                            <p>
                                Remarques ou commentaires saisis lors de
                                l’enregistrement.
                            </p>
                        </div>
                    </div>

                    <div class="notes-content">
                        {{ invoice.notes }}
                    </div>
                </div>
            </div>

            <div class="side-column">
                <div class="profile-card">
                    <div class="profile-top">
                        <div class="avatar-circle">
                            {{ supplierName?.charAt(0) || "?" }}
                        </div>

                        <div>
                            <h3>{{ supplierName }}</h3>
                            <p>Fournisseur véhicule concerné</p>
                        </div>
                    </div>

                    <div class="profile-line">
                        <span>Téléphone</span>
                        <strong>{{ supplierPhone }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>Email</span>
                        <strong>{{ supplierEmail }}</strong>
                    </div>

                    <div class="profile-line">
                        <span>Status</span>
                        <strong>{{ supplierStatus }}</strong>
                    </div>
                </div>

                <div class="summary-card">
                    <h3>Résumé</h3>

                    <div class="summary-line">
                        <span>Facture ID</span>
                        <strong>#{{ invoice.id }}</strong>
                    </div>

                    <div class="summary-line">
                        <span>Fournisseur</span>
                        <strong>{{ supplierName }}</strong>
                    </div>

                    <div class="summary-line">
                        <span>Période</span>
                        <strong>
                            {{ formatDate(invoice.period_start) }} →
                            {{ formatDate(invoice.period_end) }}
                        </strong>
                    </div>

                    <div class="summary-line">
                        <span>Date facture</span>
                        <strong>{{ formatDate(invoice.invoice_date) }}</strong>
                    </div>

                    <div class="summary-line">
                        <span>Montant</span>
                        <strong class="red-text">
                            {{ formatMoney(invoice.total_amount) }} MAD
                        </strong>
                    </div>

                    <div class="summary-line">
                        <span>Plannings</span>
                        <strong class="blue-text">{{ totalPlannings }}</strong>
                    </div>

                    <div v-if="invoice.pdf_path" class="summary-line">
                        <span>Fichier PDF</span>
                        <a
                            :href="`/storage/${invoice.pdf_path}`"
                            target="_blank"
                            class="pdf-link"
                        >
                            Voir PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.show-page {
    padding: 24px;
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(220, 38, 38, 0.1),
            transparent 25%
        ),
        radial-gradient(
            circle at top right,
            rgba(239, 68, 68, 0.08),
            transparent 20%
        ),
        #f6f7fb;
}

.hero-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #d3142a 0%, #a40f2a 45%, #6b0f1a 100%);
    color: white;
    border-radius: 30px;
    padding: 34px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    gap: 24px;
    align-items: center;
    box-shadow: 0 24px 50px rgba(157, 23, 35, 0.22);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(255, 255, 255, 0.14),
            transparent 20%
        ),
        radial-gradient(
            circle at 80% 0%,
            rgba(255, 255, 255, 0.08),
            transparent 22%
        );
    pointer-events: none;
}

.hero-left,
.hero-actions {
    position: relative;
    z-index: 2;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.16);
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 14px;
}

.hero-card h1 {
    margin: 8px 0 10px;
    font-size: 38px;
    font-weight: 900;
    letter-spacing: -0.5px;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.hero-btn {
    border: 0;
    text-decoration: none;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: 0.2s ease;
}

.hero-btn:hover {
    transform: translateY(-1px);
}

.hero-btn.light {
    background: white;
    color: #b91c1c;
}

.hero-btn.edit {
    background: #111827;
    color: white;
}

.hero-btn.delete {
    background: rgba(255, 255, 255, 0.12);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.16);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.stat-card {
    border-radius: 24px;
    padding: 22px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
    border: 1px solid #edf2f7;
}

.stat-red {
    background: linear-gradient(135deg, #ef4444, #b91c1c);
    color: white;
    border: none;
}

.stat-soft {
    background: white;
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 14px;
    margin-bottom: 10px;
    opacity: 0.9;
}

.stat-soft .stat-label {
    color: #64748b;
}

.stat-value {
    font-size: 28px;
    font-weight: 900;
    color: inherit;
    word-break: break-word;
}

.stat-sub {
    margin-top: 10px;
    font-size: 13px;
    opacity: 0.88;
}

.stat-soft .stat-sub {
    color: #94a3b8;
}

.blue {
    color: #2563eb;
}

.content-grid {
    display: grid;
    grid-template-columns: 1.8fr 0.9fr;
    gap: 24px;
}

.main-column,
.side-column {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.box-card,
.profile-card,
.summary-card {
    background: white;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    border: 1px solid #edf2f7;
}

.glass-card {
    background: linear-gradient(180deg, #ffffff 0%, #fff9fa 100%);
}

.section-head {
    margin-bottom: 18px;
}

.section-head h2 {
    margin: 0;
    font-size: 24px;
    color: #111827;
    font-weight: 900;
    letter-spacing: -0.3px;
}

.section-head p {
    margin: 8px 0 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.7;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.info-card {
    background: linear-gradient(180deg, #fff 0%, #fffafb 100%);
    border: 1px solid #f1d5d9;
    border-radius: 18px;
    padding: 18px;
}

.amount-card {
    background: linear-gradient(135deg, #fff1f2, #ffe4e6);
}

.info-label {
    display: block;
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 700;
}

.info-value {
    font-size: 18px;
    color: #111827;
    font-weight: 900;
    word-break: break-word;
}

.red-text {
    color: #b91c1c !important;
}

.blue-text {
    color: #2563eb !important;
}

.table-wrap {
    overflow-x: auto;
    border: 1px solid #f1d5d9;
    border-radius: 20px;
}

.planning-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1000px;
}

.planning-table thead th {
    background: #fff4f5;
    padding: 16px;
    text-align: left;
    font-size: 13px;
    color: #7f1d1d;
    border-bottom: 1px solid #f3d2d6;
    white-space: nowrap;
    font-weight: 800;
}

.planning-table tbody td {
    padding: 16px;
    border-bottom: 1px solid #f8e4e7;
    vertical-align: middle;
    color: #111827;
    font-size: 14px;
}

.planning-table tbody tr:hover {
    background: #fff9fa;
}

.service-badge {
    display: inline-flex;
    align-items: center;
    background: #fee2e2;
    color: #b91c1c;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.empty-box {
    text-align: center;
    padding: 50px;
    background: linear-gradient(180deg, #fffafa 0%, #fff 100%);
    border: 2px dashed #f1c3ca;
    border-radius: 20px;
    color: #9f1239;
    font-weight: 700;
}

.empty-icon {
    font-size: 34px;
    margin-bottom: 10px;
}

.notes-card {
    border-left: 5px solid #dc2626;
}

.notes-content {
    background: linear-gradient(180deg, #fffafa 0%, #fff 100%);
    border-radius: 18px;
    padding: 18px;
    line-height: 1.8;
    color: #374151;
    white-space: pre-line;
    border: 1px solid #f3d2d6;
}

.profile-card,
.summary-card {
    background: linear-gradient(180deg, #ffffff 0%, #fff8f8 100%);
}

.profile-top {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.avatar-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #b91c1c);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 900;
    box-shadow: 0 12px 24px rgba(185, 28, 28, 0.18);
}

.profile-top h3 {
    margin: 0;
    font-size: 22px;
    color: #111827;
    font-weight: 900;
}

.profile-top p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
}

.profile-line,
.summary-line {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 14px;
}

.profile-line span,
.summary-line span {
    color: #6b7280;
    font-weight: 700;
}

.profile-line strong,
.summary-line strong {
    color: #111827;
    text-align: right;
    word-break: break-word;
}

.summary-card h3 {
    margin: 0 0 18px;
    font-size: 22px;
    color: #111827;
    font-weight: 900;
}

.pdf-link {
    color: #2563eb;
    font-weight: 900;
    text-decoration: none;
}

.pdf-link:hover {
    text-decoration: underline;
}

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .details-grid {
        grid-template-columns: 1fr 1fr;
    }

    .hero-card {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 640px) {
    .show-page {
        padding: 14px;
    }

    .hero-card,
    .box-card,
    .profile-card,
    .summary-card {
        padding: 18px;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .hero-card h1 {
        font-size: 28px;
    }
}
</style>
