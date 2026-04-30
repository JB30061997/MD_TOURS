<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import axios from "axios";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
    supplierVehicules: {
        type: Array,
        default: () => [],
    },
    plannings: {
        type: Array,
        default: () => [],
    },
    selectedPlanningIds: {
        type: Array,
        default: () => [],
    },
});

const loadingPlannings = ref(false);
const plannings = ref(props.plannings || []);
const selectedPlanningIds = ref([...(props.selectedPlanningIds || [])]);
const fetchError = ref("");
const successMessage = ref("");

const form = useForm({
    supplier_vehicule_id: props.invoice?.supplier_vehicule_id || "",
    period_start: props.invoice?.period_start || "",
    period_end: props.invoice?.period_end || "",
    invoice_number: props.invoice?.invoice_number || "",
    invoice_date: props.invoice?.invoice_date || "",
    total_amount: props.invoice?.total_amount || "",
    notes: props.invoice?.notes || "",
    planning_ids: [...(props.selectedPlanningIds || [])],
});

const canFetchPlannings = computed(() => {
    return form.supplier_vehicule_id && form.period_start && form.period_end;
});

const totalPlannings = computed(() => plannings.value.length);
const selectedCount = computed(() => selectedPlanningIds.value.length);

const isAllSelected = computed(() => {
    return (
        totalPlannings.value > 0 && selectedCount.value === totalPlannings.value
    );
});

const hasPlannings = computed(() => plannings.value.length > 0);

const selectedSupplierName = computed(() => {
    const supplier = props.supplierVehicules.find(
        (item) => String(item.id) === String(form.supplier_vehicule_id),
    );

    return supplier ? supplier.name : "";
});

function formatDate(value) {
    if (!value) return "-";
    return value;
}

function resetPlanningsState() {
    plannings.value = [];
    selectedPlanningIds.value = [];
    form.planning_ids = [];
    fetchError.value = "";
    successMessage.value = "";
}

let firstLoad = true;

watch(
    () => [form.supplier_vehicule_id, form.period_start, form.period_end],
    () => {
        if (firstLoad) {
            firstLoad = false;
            return;
        }

        resetPlanningsState();
    },
);

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedPlanningIds.value = [];
    } else {
        selectedPlanningIds.value = plannings.value.map((item) => item.id);
    }

    form.planning_ids = [...selectedPlanningIds.value];
}

function syncPlanningSelection() {
    form.planning_ids = [...selectedPlanningIds.value];
}

async function loadPlannings() {
    fetchError.value = "";
    successMessage.value = "";

    if (!canFetchPlannings.value) {
        fetchError.value =
            "Veuillez sélectionner un fournisseur véhicule, une date de début et une date de fin avant de charger les plannings.";
        return;
    }

    loadingPlannings.value = true;
    resetPlanningsState();

    try {
        const response = await axios.get(
            "/supplier-vehicule-invoices-plannings",
            {
                params: {
                    supplier_vehicule_id: form.supplier_vehicule_id,
                    period_start: form.period_start,
                    period_end: form.period_end,
                },
            },
        );

        plannings.value = response.data.plannings || [];

        if (plannings.value.length > 0) {
            selectedPlanningIds.value = plannings.value.map((item) => item.id);
            form.planning_ids = [...selectedPlanningIds.value];
            successMessage.value = `${plannings.value.length} planning(s) trouvé(s) pour ${selectedSupplierName.value}.`;
        } else {
            successMessage.value = "Aucun planning trouvé pour cette période.";
        }
    } catch (error) {
        console.error(error);
        fetchError.value =
            error?.response?.data?.message ||
            "Une erreur est survenue lors du chargement des plannings.";
    } finally {
        loadingPlannings.value = false;
    }
}

function submit() {
    form.planning_ids = [...selectedPlanningIds.value];

    form.put(`/supplier-vehicule-invoices/${props.invoice.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Modifier Facture Fournisseur Véhicule" />

    <div class="fuel-page">
        <div class="page-header-card">
            <div>
                <p class="eyebrow">Gestion fournisseurs véhicules</p>
                <h1>Modifier facture fournisseur véhicule</h1>
                <p class="subtitle">
                    Modifiez les informations de la facture, rechargez les
                    plannings si nécessaire, puis mettez à jour les services
                    liés.
                </p>
            </div>

            <div class="header-badge warning-badge">
                <span class="badge-dot"></span>
                Mode modification
            </div>
        </div>

        <div class="fuel-grid">
            <div class="main-column">
                <div class="card block-card">
                    <div class="card-head">
                        <div>
                            <h2>Informations principales</h2>
                            <p>
                                Vous pouvez modifier le fournisseur véhicule, la
                                période et les détails de la facture.
                            </p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label>Fournisseur véhicule</label>
                            <select
                                v-model="form.supplier_vehicule_id"
                                class="input"
                            >
                                <option value="">
                                    -- Sélectionner un fournisseur véhicule --
                                </option>
                                <option
                                    v-for="supplier in supplierVehicules"
                                    :key="supplier.id"
                                    :value="supplier.id"
                                >
                                    {{ supplier.name }}
                                </option>
                            </select>
                            <div
                                v-if="form.errors.supplier_vehicule_id"
                                class="error-text"
                            >
                                {{ form.errors.supplier_vehicule_id }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Date début</label>
                            <input
                                v-model="form.period_start"
                                type="date"
                                class="input"
                            />
                            <div
                                v-if="form.errors.period_start"
                                class="error-text"
                            >
                                {{ form.errors.period_start }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Date fin</label>
                            <input
                                v-model="form.period_end"
                                type="date"
                                class="input"
                            />
                            <div
                                v-if="form.errors.period_end"
                                class="error-text"
                            >
                                {{ form.errors.period_end }}
                            </div>
                        </div>

                        <div class="field field-button">
                            <label>&nbsp;</label>
                            <button
                                type="button"
                                class="btn btn-primary btn-fetch"
                                :disabled="
                                    loadingPlannings || !canFetchPlannings
                                "
                                @click="loadPlannings"
                            >
                                <span v-if="loadingPlannings">
                                    Chargement...
                                </span>
                                <span v-else>Recharger les plannings</span>
                            </button>
                        </div>
                    </div>

                    <div class="separator"></div>

                    <div class="form-grid second-grid">
                        <div class="field">
                            <label>N° facture</label>
                            <input
                                v-model="form.invoice_number"
                                type="text"
                                class="input"
                                placeholder="Ex: FV26/0001"
                            />
                            <div
                                v-if="form.errors.invoice_number"
                                class="error-text"
                            >
                                {{ form.errors.invoice_number }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Date facture</label>
                            <input
                                v-model="form.invoice_date"
                                type="date"
                                class="input"
                            />
                            <div
                                v-if="form.errors.invoice_date"
                                class="error-text"
                            >
                                {{ form.errors.invoice_date }}
                            </div>
                        </div>

                        <div class="field">
                            <label>Montant total</label>
                            <input
                                v-model="form.total_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="input"
                                placeholder="0.00"
                            />
                            <div
                                v-if="form.errors.total_amount"
                                class="error-text"
                            >
                                {{ form.errors.total_amount }}
                            </div>
                        </div>
                    </div>

                    <div class="field full-width">
                        <label>Notes</label>
                        <textarea
                            v-model="form.notes"
                            class="input textarea"
                            rows="4"
                            placeholder="Notes sur la facture, observations, remarques..."
                        ></textarea>
                        <div v-if="form.errors.notes" class="error-text">
                            {{ form.errors.notes }}
                        </div>
                    </div>
                </div>

                <div class="card block-card">
                    <div class="card-head card-head-space">
                        <div>
                            <h2>Plannings du fournisseur véhicule</h2>
                            <p>
                                Gardez cochés uniquement les plannings liés à
                                cette facture.
                            </p>
                        </div>

                        <div class="table-actions" v-if="hasPlannings">
                            <button
                                type="button"
                                class="btn btn-light"
                                @click="toggleSelectAll"
                            >
                                {{
                                    isAllSelected
                                        ? "Tout désélectionner"
                                        : "Tout sélectionner"
                                }}
                            </button>
                        </div>
                    </div>

                    <div v-if="fetchError" class="alert alert-danger">
                        {{ fetchError }}
                    </div>

                    <div v-if="successMessage" class="alert alert-success">
                        {{ successMessage }}
                    </div>

                    <div v-if="loadingPlannings" class="loading-box">
                        <div class="spinner"></div>
                        <p>Chargement des plannings en cours...</p>
                    </div>

                    <div v-else-if="hasPlannings" class="table-wrap">
                        <table class="planning-table">
                            <thead>
                                <tr>
                                    <th class="check-col">
                                        <input
                                            type="checkbox"
                                            :checked="isAllSelected"
                                            @change="toggleSelectAll"
                                        />
                                    </th>
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
                                    <td class="check-col">
                                        <input
                                            type="checkbox"
                                            :value="planning.id"
                                            v-model="selectedPlanningIds"
                                            @change="syncPlanningSelection"
                                        />
                                    </td>

                                    <td>{{ formatDate(planning.date_du) }}</td>
                                    <td>{{ formatDate(planning.date_au) }}</td>
                                    <td>{{ planning.ref_dossier || "-" }}</td>

                                    <td>
                                        <span class="pill">
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

                    <div v-else class="empty-state">
                        <div class="empty-icon">🛠️</div>
                        <h3>Aucun planning affiché</h3>
                        <p>
                            Cliquez sur
                            <strong>Recharger les plannings</strong> pour
                            afficher la nouvelle liste selon le fournisseur
                            véhicule et la période choisis.
                        </p>
                    </div>
                </div>
            </div>

            <div class="side-column">
                <div class="card summary-card">
                    <h3>Résumé rapide</h3>

                    <div class="summary-item">
                        <span>ID facture</span>
                        <strong>#{{ invoice.id }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Fournisseur véhicule</span>
                        <strong>{{ selectedSupplierName || "-" }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Période</span>
                        <strong>
                            {{ form.period_start || "-" }} →
                            {{ form.period_end || "-" }}
                        </strong>
                    </div>

                    <div class="summary-item">
                        <span>Plannings trouvés</span>
                        <strong>{{ totalPlannings }}</strong>
                    </div>

                    <div class="summary-item">
                        <span>Plannings sélectionnés</span>
                        <strong>{{ selectedCount }}</strong>
                    </div>

                    <div class="summary-item highlight">
                        <span>Montant total</span>
                        <strong>{{ form.total_amount || "0.00" }} MAD</strong>
                    </div>

                    <button
                        type="button"
                        class="btn btn-success btn-save"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <span v-if="form.processing">Mise à jour...</span>
                        <span v-else>Mettre à jour la facture</span>
                    </button>
                </div>

                <div class="card helper-card">
                    <h3>Conseils</h3>
                    <ul>
                        <li>
                            Modifiez d’abord le fournisseur véhicule ou la
                            période si nécessaire.
                        </li>
                        <li>
                            Rechargez les plannings pour récupérer la nouvelle
                            liste.
                        </li>
                        <li>
                            Gardez uniquement les services concernés cochés.
                        </li>
                        <li>Vérifiez le montant avant la mise à jour.</li>
                    </ul>
                </div>

                <div
                    v-if="Object.keys(form.errors).length"
                    class="card error-card"
                >
                    <h3>Vérification</h3>
                    <ul class="error-list">
                        <li v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fuel-page {
    padding: 24px;
    background:
        radial-gradient(
            circle at top left,
            rgba(71, 118, 230, 0.1),
            transparent 30%
        ),
        radial-gradient(
            circle at top right,
            rgba(245, 158, 11, 0.1),
            transparent 25%
        ),
        #f4f7fb;
    min-height: 100vh;
}

.page-header-card {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #2563eb 100%);
    color: #fff;
    border-radius: 24px;
    padding: 28px 30px;
    box-shadow: 0 20px 40px rgba(21, 37, 84, 0.18);
    margin-bottom: 24px;
}

.eyebrow {
    margin: 0 0 6px;
    font-size: 13px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    opacity: 0.8;
}

.page-header-card h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 800;
}

.subtitle {
    margin: 10px 0 0;
    max-width: 760px;
    font-size: 15px;
    line-height: 1.7;
    opacity: 0.92;
}

.header-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 12px 16px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 14px;
}

.warning-badge {
    background: rgba(251, 191, 36, 0.18);
    border-color: rgba(251, 191, 36, 0.3);
}

.badge-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #f59e0b;
    box-shadow: 0 0 0 6px rgba(245, 158, 11, 0.18);
}

.fuel-grid {
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

.card {
    background: #ffffff;
    border-radius: 24px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(226, 232, 240, 0.9);
}

.block-card {
    padding: 24px;
}

.card-head {
    margin-bottom: 18px;
}

.card-head-space {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
}

.card-head h2 {
    margin: 0 0 6px;
    font-size: 22px;
    font-weight: 800;
    color: #0f172a;
}

.card-head p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
}

.second-grid {
    grid-template-columns: repeat(3, 1fr);
}

.field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field label {
    font-size: 13px;
    font-weight: 800;
    color: #334155;
}

.field-button {
    justify-content: flex-end;
}

.full-width {
    margin-top: 18px;
}

.input {
    width: 100%;
    min-height: 48px;
    border: 1px solid #dbe4f0;
    background: #f8fafc;
    border-radius: 16px;
    padding: 12px 14px;
    font-size: 14px;
    color: #0f172a;
    outline: none;
    transition: 0.2s ease;
    box-sizing: border-box;
}

.input:focus {
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.textarea {
    min-height: 120px;
    resize: vertical;
    padding-top: 14px;
}

.separator {
    height: 1px;
    background: linear-gradient(to right, transparent, #dbe4f0, transparent);
    margin: 22px 0;
}

.btn {
    border: 0;
    border-radius: 16px;
    min-height: 48px;
    padding: 0 18px;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    transition: 0.2s ease;
}

.btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    box-shadow: 0 12px 20px rgba(37, 99, 235, 0.22);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, #059669, #10b981);
    color: #fff;
    box-shadow: 0 12px 20px rgba(16, 185, 129, 0.22);
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-1px);
}

.btn-light {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.btn-fetch {
    width: 100%;
}

.btn-save {
    width: 100%;
    margin-top: 22px;
}

.alert {
    border-radius: 18px;
    padding: 14px 16px;
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 16px;
}

.alert-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

.error-text {
    color: #dc2626;
    font-size: 12px;
    font-weight: 700;
}

.loading-box {
    min-height: 220px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #475569;
}

.spinner {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 4px solid #dbeafe;
    border-top-color: #2563eb;
    animation: spin 0.8s linear infinite;
}

.table-wrap {
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
}

.planning-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}

.planning-table thead th {
    background: #eff6ff;
    color: #1e3a8a;
    font-size: 13px;
    font-weight: 800;
    text-align: left;
    padding: 15px 14px;
    border-bottom: 1px solid #dbeafe;
    white-space: nowrap;
}

.planning-table tbody td {
    padding: 14px;
    border-bottom: 1px solid #edf2f7;
    color: #0f172a;
    font-size: 14px;
    vertical-align: middle;
}

.planning-table tbody tr:hover {
    background: #f8fbff;
}

.check-col {
    width: 55px;
    text-align: center;
}

.pill {
    display: inline-flex;
    align-items: center;
    padding: 7px 12px;
    border-radius: 999px;
    background: #eef2ff;
    color: #4338ca;
    font-size: 12px;
    font-weight: 800;
}

.empty-state {
    min-height: 240px;
    border: 2px dashed #dbe4f0;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #64748b;
    text-align: center;
    padding: 24px;
}

.empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}

.empty-state h3 {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 20px;
}

.empty-state p {
    margin: 0;
    max-width: 520px;
    line-height: 1.7;
}

.summary-card,
.helper-card,
.error-card {
    padding: 22px;
}

.summary-card h3,
.helper-card h3,
.error-card h3 {
    margin: 0 0 18px;
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 13px 0;
    border-bottom: 1px solid #edf2f7;
    font-size: 14px;
}

.summary-item span {
    color: #64748b;
    font-weight: 600;
}

.summary-item strong {
    color: #0f172a;
    text-align: right;
}

.summary-item.highlight strong {
    color: #059669;
    font-size: 16px;
}

.helper-card ul,
.error-list {
    margin: 0;
    padding-left: 18px;
}

.helper-card li,
.error-list li {
    margin-bottom: 10px;
    color: #475569;
    line-height: 1.6;
    font-size: 14px;
}

.error-card {
    border: 1px solid #fecaca;
    background: #fffafa;
}

.error-list li {
    color: #b91c1c;
}

.table-actions {
    display: flex;
    gap: 10px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1200px) {
    .fuel-grid {
        grid-template-columns: 1fr;
    }

    .side-column {
        order: -1;
    }
}

@media (max-width: 992px) {
    .form-grid,
    .second-grid {
        grid-template-columns: 1fr 1fr;
    }

    .page-header-card {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .fuel-page {
        padding: 14px;
    }

    .block-card,
    .summary-card,
    .helper-card,
    .error-card {
        padding: 16px;
    }

    .form-grid,
    .second-grid {
        grid-template-columns: 1fr;
    }

    .page-header-card h1 {
        font-size: 24px;
    }

    .subtitle {
        font-size: 14px;
    }
}
</style>
