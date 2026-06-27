<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import { formatDate } from "@/utils/dateFormat";

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
const payments = computed(() => props.invoice?.payments || []);
const totalPlannings = computed(() => plannings.value.length);
const paidAmount = computed(() => Number(props.invoice?.paid_amount || 0));
const remainingAmount = computed(() =>
    Number(props.invoice?.remaining_amount || 0),
);
const invoiceAmount = computed(() => Number(props.invoice?.total_amount || 0));
const linkedBudgetTotal = computed(() => sumMoney(plannings.value, "budget"));
const linkedSupplierPriceTotal = computed(() =>
    sumMoney(plannings.value, "supplier_price"),
);
const linkedDifference = computed(
    () => linkedSupplierPriceTotal.value - invoiceAmount.value,
);
const linkedTolerance = computed(() =>
    Math.max(10, Math.round(invoiceAmount.value * 0.01 * 100) / 100),
);
const linkedStatusLabel = computed(() => {
    const diff = Math.abs(linkedDifference.value);

    if (!totalPlannings.value) return "Aucun planning";
    if (diff <= 0.009) return "Exact";
    if (diff <= linkedTolerance.value) return "Très proche";

    return linkedDifference.value > 0 ? "Au-dessus" : "En dessous";
});

const paymentForm = useForm({
    amount: "",
    method: "transfer",
    payment_date: new Date().toISOString().slice(0, 10),
    reference: "",
    notes: "",
});

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

function sumMoney(rows, key) {
    return rows.reduce((sum, row) => sum + Number(row?.[key] || 0), 0);
}

function formatDestination(planning) {
    return (
        planning?.destination?.name ||
        planning?.destination?.city ||
        planning?.destination ||
        "-"
    );
}

function paymentMethodLabel(method) {
    return {
        cash: "Espèce",
        cheque: "Chèque",
        transfer: "Virement",
        other: "Autre",
    }[method] || method || "-";
}

function submitPayment() {
    paymentForm.post(`/supplier-vehicule-invoices/${props.invoice.id}/payments`, {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset("amount", "reference", "notes");
            paymentForm.method = "transfer";
            paymentForm.payment_date = new Date().toISOString().slice(0, 10);
        },
    });
}

function destroyPayment(paymentId) {
    if (!confirm("Voulez-vous vraiment supprimer ce paiement ?")) return;

    router.delete(
        `/supplier-vehicule-invoices/${props.invoice.id}/payments/${paymentId}`,
        {
            preserveScroll: true,
        },
    );
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
                <div class="stat-icon">✅</div>
                <div class="stat-label">Montant payé</div>
                <div class="stat-value green">
                    {{ formatMoney(paidAmount) }} MAD
                </div>
                <div class="stat-sub">Total des paiements reçus</div>
            </div>

            <div class="stat-card stat-soft">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Reste à payer</div>
                <div class="stat-value orange">
                    {{ formatMoney(remainingAmount) }} MAD
                </div>
                <div class="stat-sub">Solde restant sur la facture</div>
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

                <div class="box-card payments-card">
                    <div class="section-head section-head-space">
                        <div>
                            <h2>Paiements</h2>
                            <p>
                                Ajoutez les paiements partiels ou complets liés
                                à cette facture.
                            </p>
                        </div>

                        <div class="payment-balance">
                            <span>Reste</span>
                            <strong>{{ formatMoney(remainingAmount) }} MAD</strong>
                        </div>
                    </div>

                    <div class="payment-form-grid">
                        <div class="field">
                            <label>Montant</label>
                            <input
                                v-model="paymentForm.amount"
                                type="number"
                                min="0"
                                step="0.01"
                                class="input"
                                placeholder="0.00"
                            />
                            <div
                                v-if="paymentForm.errors.amount"
                                class="error-text"
                            >
                                {{ paymentForm.errors.amount }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Méthode</label>
                            <select v-model="paymentForm.method" class="input">
                                <option value="transfer">Virement</option>
                                <option value="cheque">Chèque</option>
                                <option value="cash">Espèce</option>
                                <option value="other">Autre</option>
                            </select>
                            <div
                                v-if="paymentForm.errors.method"
                                class="error-text"
                            >
                                {{ paymentForm.errors.method }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Date paiement</label>
                            <input
                                v-model="paymentForm.payment_date"
                                type="date"
                                class="input"
                            />
                            <div
                                v-if="paymentForm.errors.payment_date"
                                class="error-text"
                            >
                                {{ paymentForm.errors.payment_date }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Référence</label>
                            <input
                                v-model="paymentForm.reference"
                                type="text"
                                class="input"
                                placeholder="N° chèque, virement..."
                            />
                            <div
                                v-if="paymentForm.errors.reference"
                                class="error-text"
                            >
                                {{ paymentForm.errors.reference }}
                            </div>
                        </div>
                    </div>

                    <div class="field payment-notes-field">
                        <label>Notes</label>
                        <textarea
                            v-model="paymentForm.notes"
                            class="input textarea"
                            rows="3"
                            placeholder="Notes sur ce paiement..."
                        ></textarea>
                        <div v-if="paymentForm.errors.notes" class="error-text">
                            {{ paymentForm.errors.notes }}
                        </div>
                    </div>

                    <button
                        type="button"
                        class="payment-submit-btn"
                        :disabled="paymentForm.processing"
                        @click="submitPayment"
                    >
                        <span v-if="paymentForm.processing">Ajout...</span>
                        <span v-else>Ajouter le paiement</span>
                    </button>

                    <div v-if="payments.length" class="payments-table-wrap">
                        <table class="payments-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Méthode</th>
                                    <th>Référence</th>
                                    <th>Montant</th>
                                    <th>Notes</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="payment in payments"
                                    :key="payment.id"
                                >
                                    <td>
                                        {{ formatDate(payment.payment_date) }}
                                    </td>
                                    <td>
                                        <span class="method-badge">
                                            {{
                                                paymentMethodLabel(
                                                    payment.method,
                                                )
                                            }}
                                        </span>
                                    </td>
                                    <td>{{ payment.reference || "-" }}</td>
                                    <td class="money-cell">
                                        {{ formatMoney(payment.amount) }} MAD
                                    </td>
                                    <td>{{ payment.notes || "-" }}</td>
                                    <td class="payment-action-cell">
                                        <button
                                            type="button"
                                            class="payment-delete-btn"
                                            @click="destroyPayment(payment.id)"
                                        >
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="empty-payment-box">
                        Aucun paiement enregistré pour cette facture.
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
                        <div class="planning-totals-strip">
                            <div class="total-chip invoice">
                                <span>Montant facture</span>
                                <strong>{{ formatMoney(invoiceAmount) }} MAD</strong>
                            </div>
                            <div class="total-chip">
                                <span>Budget lié</span>
                                <strong>{{ formatMoney(linkedBudgetTotal) }} MAD</strong>
                            </div>
                            <div class="total-chip supplier">
                                <span>Supplier Price lié</span>
                                <strong>{{ formatMoney(linkedSupplierPriceTotal) }} MAD</strong>
                            </div>
                            <div
                                class="total-chip"
                                :class="{
                                    good: Math.abs(linkedDifference) <= linkedTolerance,
                                    warning: Math.abs(linkedDifference) > linkedTolerance,
                                }"
                            >
                                <span>{{ linkedStatusLabel }}</span>
                                <strong>{{ formatMoney(Math.abs(linkedDifference)) }} MAD</strong>
                            </div>
                        </div>

                        <table class="planning-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Référence</th>
                                    <th>Service</th>
                                    <th>Départ</th>
                                    <th>Destination</th>
                                    <th>
                                        <div class="th-total">
                                            <span>Budget</span>
                                            <strong>{{ formatMoney(linkedBudgetTotal) }} MAD</strong>
                                        </div>
                                    </th>
                                    <th>
                                        <div class="th-total">
                                            <span>Supplier Price</span>
                                            <strong>{{ formatMoney(linkedSupplierPriceTotal) }} MAD</strong>
                                            <small>Facture {{ formatMoney(invoiceAmount) }} MAD</small>
                                        </div>
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="planning in plannings"
                                    :key="planning.id"
                                >
                                    <td>{{ formatDate(planning.date_du) }}</td>
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
                                    <td>{{ formatDestination(planning) }}</td>
                                    <td class="money-cell">
                                        {{ formatMoney(planning.budget) }}
                                        MAD
                                    </td>
                                    <td class="money-cell supplier-price-cell">
                                        {{
                                            formatMoney(
                                                planning.supplier_price,
                                            )
                                        }}
                                        MAD
                                    </td>
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
                        <span>Budget lié</span>
                        <strong>{{ formatMoney(linkedBudgetTotal) }} MAD</strong>
                    </div>

                    <div class="summary-line">
                        <span>Supplier Price lié</span>
                        <strong class="green-text">
                            {{ formatMoney(linkedSupplierPriceTotal) }} MAD
                        </strong>
                    </div>

                    <div class="summary-line">
                        <span>Écart facture</span>
                        <strong
                            :class="{
                                'green-text': Math.abs(linkedDifference) <= linkedTolerance,
                                'red-text': Math.abs(linkedDifference) > linkedTolerance,
                            }"
                        >
                            {{ formatMoney(Math.abs(linkedDifference)) }} MAD
                        </strong>
                    </div>

                    <div class="summary-line">
                        <span>Payé</span>
                        <strong class="green-text">
                            {{ formatMoney(paidAmount) }} MAD
                        </strong>
                    </div>

                    <div class="summary-line">
                        <span>Reste</span>
                        <strong class="orange-text">
                            {{ formatMoney(remainingAmount) }} MAD
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
    color: #fff !important;
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
    grid-template-columns: repeat(5, 1fr);
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
    color: #b91c1c;
}

.green {
    color: #047857;
}

.orange {
    color: #ea580c;
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

.section-head-space {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
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
    color: #b91c1c !important;
}

.green-text {
    color: #047857 !important;
}

.orange-text {
    color: #ea580c !important;
}

.payments-card {
    border-color: #fecdd3;
}

.payment-balance {
    display: inline-flex;
    flex-direction: column;
    gap: 4px;
    min-width: 150px;
    padding: 12px 16px;
    border-radius: 16px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    text-align: right;
}

.payment-balance span {
    color: #9a3412;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
}

.payment-balance strong {
    color: #ea580c;
    font-size: 16px;
    font-weight: 900;
}

.payment-form-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    color: #374151;
    font-size: 13px;
    font-weight: 800;
}

.input {
    width: 100%;
    min-height: 48px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #f8fafc;
    padding: 11px 13px;
    color: #111827;
    outline: none;
    transition: 0.2s ease;
}

.input:focus {
    border-color: #dc2626;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
}

.textarea {
    min-height: 90px;
    resize: vertical;
}

.payment-notes-field {
    margin-top: 14px;
}

.error-text {
    color: #dc2626;
    font-size: 12px;
    font-weight: 800;
}

.payment-submit-btn {
    margin-top: 14px;
    border: 0;
    min-height: 48px;
    padding: 0 18px;
    border-radius: 14px;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    font-weight: 900;
    cursor: pointer;
    box-shadow: 0 12px 22px rgba(220, 38, 38, 0.18);
}

.payment-submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.payments-table-wrap {
    margin-top: 20px;
    overflow-x: auto;
    border: 1px solid #f1d5d9;
    border-radius: 18px;
}

.payments-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 820px;
}

.payments-table thead th {
    background: #fff4f5;
    color: #7f1d1d;
    font-size: 13px;
    font-weight: 900;
    text-align: left;
    padding: 14px;
    border-bottom: 1px solid #f3d2d6;
}

.payments-table tbody td {
    padding: 14px;
    border-bottom: 1px solid #f8e4e7;
    color: #111827;
    font-size: 14px;
}

.method-badge {
    display: inline-flex;
    align-items: center;
    padding: 7px 11px;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    font-size: 12px;
    font-weight: 900;
}

.payment-action-cell {
    text-align: right;
}

.payment-delete-btn {
    border: 0;
    padding: 9px 12px;
    border-radius: 12px;
    background: #fef2f2;
    color: #dc2626;
    font-size: 12px;
    font-weight: 900;
    cursor: pointer;
}

.empty-payment-box {
    margin-top: 18px;
    border: 2px dashed #fecdd3;
    border-radius: 18px;
    padding: 24px;
    text-align: center;
    color: #9f1239;
    font-weight: 800;
    background: #fffafa;
}

.table-wrap {
    overflow-x: auto;
    border: 1px solid #f1d5d9;
    border-radius: 20px;
}

.planning-totals-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    padding: 16px;
    background: linear-gradient(180deg, #fff 0%, #fff8f8 100%);
    border-bottom: 1px solid #f1d5d9;
}

.total-chip {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 14px 16px;
    background: #fff;
}

.total-chip span {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-bottom: 7px;
}

.total-chip strong {
    color: #0f172a;
    font-size: 18px;
    font-weight: 900;
    white-space: nowrap;
}

.total-chip.invoice {
    border-color: #fecdd3;
    background: linear-gradient(135deg, #fff1f2, #fff);
}

.total-chip.supplier strong,
.total-chip.good strong {
    color: #047857;
}

.total-chip.warning strong {
    color: #b91c1c;
}

.planning-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1080px;
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

.th-total {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.th-total strong {
    color: #047857;
    font-size: 13px;
    font-weight: 900;
}

.th-total small {
    color: #7f1d1d;
    font-size: 11px;
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

.money-cell {
    color: #047857 !important;
    font-weight: 900;
    white-space: nowrap;
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
    color: #b91c1c;
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

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .planning-totals-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 992px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .details-grid {
        grid-template-columns: 1fr 1fr;
    }

    .payment-form-grid {
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

    .stats-grid,
    .payment-form-grid {
        grid-template-columns: 1fr;
    }

    .section-head-space {
        flex-direction: column;
    }

    .hero-card h1 {
        font-size: 28px;
    }

    .planning-totals-strip {
        grid-template-columns: 1fr;
        padding: 12px;
    }
}
</style>
