<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import axios from "axios";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    supplierVehicules: {
        type: Array,
        default: () => [],
    },
});

const loadingPlannings = ref(false);
const plannings = ref([]);
const selectedPlanningIds = ref([]);
const fetchError = ref("");
const successMessage = ref("");
const selectedFileName = ref("");

const form = useForm({
    supplier_vehicule_id: "",
    period_start: "",
    period_end: "",
    invoice_number: "",
    invoice_date: "",
    total_amount: "",
    pdf_file: null,
    notes: "",
    planning_ids: [],
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
        (s) => String(s.id) === String(form.supplier_vehicule_id),
    );
    return supplier ? supplier.name : "";
});

/* searchable select fournisseur véhicule */
const supplierSearch = ref("");
const showSupplierDropdown = ref(false);
const supplierSelectRef = ref(null);

const filteredSuppliers = computed(() => {
    const q = String(supplierSearch.value || "")
        .trim()
        .toLowerCase();

    if (!q) return props.supplierVehicules;

    return props.supplierVehicules.filter((supplier) => {
        return String(supplier.name || "")
            .toLowerCase()
            .includes(q);
    });
});

function openSupplierDropdown() {
    showSupplierDropdown.value = true;
}

function selectSupplier(supplier) {
    form.supplier_vehicule_id = supplier.id;
    supplierSearch.value = supplier.name || "";
    showSupplierDropdown.value = false;
}

function clearSupplierSelection() {
    form.supplier_vehicule_id = "";
    supplierSearch.value = "";
    showSupplierDropdown.value = false;
}

function syncSupplierSearchFromModel() {
    const supplier = props.supplierVehicules.find(
        (s) => String(s.id) === String(form.supplier_vehicule_id),
    );
    supplierSearch.value = supplier ? supplier.name : "";
}

function handleClickOutside(event) {
    if (!supplierSelectRef.value) return;

    if (!supplierSelectRef.value.contains(event.target)) {
        showSupplierDropdown.value = false;
        syncSupplierSearchFromModel();
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    syncSupplierSearchFromModel();
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

watch(
    () => form.supplier_vehicule_id,
    () => {
        syncSupplierSearchFromModel();
    },
);

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

watch(
    () => [form.supplier_vehicule_id, form.period_start, form.period_end],
    () => {
        resetPlanningsState();
    },
);

function handleFileChange(event) {
    const file = event.target.files[0] || null;
    form.pdf_file = file;
    selectedFileName.value = file ? file.name : "";
}

function removeSelectedFile() {
    form.pdf_file = null;
    selectedFileName.value = "";

    const input = document.getElementById("pdf_file_input");
    if (input) {
        input.value = "";
    }
}

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
            "3ammar fournisseur véhicule + date début + date fin 9bel ma tjib les plannings.";
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
            successMessage.value = `${plannings.value.length} planning(s) trouvés pour ${selectedSupplierName.value}.`;
        } else {
            successMessage.value = "Makayn 7ta planning f had période.";
        }
    } catch (error) {
        console.error(error);
        fetchError.value =
            error?.response?.data?.message ||
            "W9a3 mochkil f jlb les plannings.";
    } finally {
        loadingPlannings.value = false;
    }
}

function submit() {
    form.planning_ids = [...selectedPlanningIds.value];

    form.post("/supplier-vehicule-invoices", {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            resetPlanningsState();
            selectedFileName.value = "";
            clearSupplierSelection();

            const input = document.getElementById("pdf_file_input");
            if (input) {
                input.value = "";
            }
        },
    });
}
</script>

<template>
    <Head title="Nouvelle Facture Fournisseur Véhicule" />

    <div class="fuel-page">
        <div class="hero-card">
            <div class="hero-left">
                <div class="hero-chip">Pilotage fournisseurs véhicules</div>
                <h1 class="text-white">
                    Nouvelle facture fournisseur véhicule
                </h1>
            </div>
        </div>

        <div class="top-kpi-grid">
            <div class="kpi-card soft-pink">
                <div class="kpi-badge">Période</div>
                <div class="kpi-title">Total plannings</div>
                <div class="kpi-value">{{ totalPlannings }}</div>
                <div class="kpi-sub">Tous les plannings filtrés</div>
            </div>

            <div class="kpi-card soft-blue">
                <div class="kpi-badge">Fournisseur</div>
                <div class="kpi-title">Fournisseur véhicule</div>
                <div class="kpi-value kpi-text">
                    {{ selectedSupplierName || "-" }}
                </div>
                <div class="kpi-sub">Fournisseur sélectionné</div>
            </div>

            <div class="kpi-card soft-purple">
                <div class="kpi-badge">Facture</div>
                <div class="kpi-title">Sélectionnés</div>
                <div class="kpi-value">{{ selectedCount }}</div>
                <div class="kpi-sub">Plannings liés à la facture</div>
            </div>

            <div class="kpi-card soft-green">
                <div class="kpi-badge">PDF</div>
                <div class="kpi-title">Fichier</div>
                <div class="kpi-value kpi-text">
                    {{ selectedFileName || "-" }}
                </div>
                <div class="kpi-sub">Facture attachée</div>
            </div>
        </div>

        <div class="fuel-grid">
            <div class="main-column">
                <div class="card block-card">
                    <div class="section-head">
                        <div>
                            <h2>Informations principales</h2>
                            <p>
                                Renseignez le fournisseur véhicule, la facture
                                et les détails.
                            </p>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="field">
                            <label>Fournisseur véhicule</label>

                            <div
                                class="searchable-select"
                                ref="supplierSelectRef"
                            >
                                <div class="searchable-input-wrap">
                                    <input
                                        v-model="supplierSearch"
                                        type="text"
                                        class="input"
                                        placeholder="Rechercher un fournisseur véhicule..."
                                        @focus="openSupplierDropdown"
                                        @input="showSupplierDropdown = true"
                                    />

                                    <button
                                        v-if="supplierSearch"
                                        type="button"
                                        class="clear-search-btn"
                                        @click="clearSupplierSelection"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div
                                    v-if="showSupplierDropdown"
                                    class="searchable-dropdown"
                                >
                                    <div
                                        v-if="filteredSuppliers.length"
                                        class="searchable-options"
                                    >
                                        <button
                                            v-for="supplier in filteredSuppliers"
                                            :key="supplier.id"
                                            type="button"
                                            class="searchable-option"
                                            :class="{
                                                active:
                                                    String(
                                                        form.supplier_vehicule_id,
                                                    ) === String(supplier.id),
                                            }"
                                            @mousedown.prevent="
                                                selectSupplier(supplier)
                                            "
                                        >
                                            {{ supplier.name }}
                                        </button>
                                    </div>

                                    <div v-else class="searchable-empty">
                                        Aucun fournisseur trouvé
                                    </div>
                                </div>
                            </div>

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
                                <span v-if="loadingPlannings"
                                    >Chargement...</span
                                >
                                <span v-else>Afficher les plannings</span>
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
                        <label>Facture PDF</label>

                        <div class="upload-box">
                            <input
                                id="pdf_file_input"
                                type="file"
                                accept="application/pdf"
                                class="hidden-file-input"
                                @change="handleFileChange"
                            />

                            <label
                                for="pdf_file_input"
                                class="upload-btn text-white"
                            >
                                Choisir un fichier PDF
                            </label>

                            <div v-if="selectedFileName" class="selected-file">
                                <div class="file-chip">
                                    <span class="file-icon">📄</span>
                                    <span class="file-name">
                                        {{ selectedFileName }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="remove-file-btn"
                                    @click="removeSelectedFile"
                                >
                                    Retirer
                                </button>
                            </div>

                            <p v-else class="upload-hint">
                                Ajoutez ici la facture PDF reçue.
                            </p>
                        </div>

                        <div v-if="form.errors.pdf_file" class="error-text">
                            {{ form.errors.pdf_file }}
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
                    <div class="section-head section-head-space">
                        <div>
                            <h2>Plannings du fournisseur véhicule</h2>
                            <p>
                                Sélectionnez uniquement les services concernés
                                par cette facture.
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
                        <div class="empty-icon">📅</div>
                        <h3>Aucun planning affiché</h3>
                        <p>
                            Choisissez un fournisseur véhicule et une période,
                            puis cliquez sur
                            <strong>Afficher les plannings</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="side-column">
                <div class="card summary-card">
                    <h3>Résumé rapide</h3>

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

                    <div class="summary-item">
                        <span>PDF</span>
                        <strong>{{ selectedFileName || "-" }}</strong>
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
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer la facture</span>
                    </button>
                </div>

                <div class="card helper-card">
                    <h3>Conseils</h3>
                    <ul>
                        <li>
                            Choisissez d’abord le fournisseur véhicule et la
                            période.
                        </li>
                        <li>Ajoutez la facture PDF.</li>
                        <li>Chargez les plannings automatiquement.</li>
                        <li>
                            Gardez cochés uniquement les services concernés.
                        </li>
                        <li>Saisissez le montant global de la facture.</li>
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
    min-height: 100vh;
    padding: 26px;
    background:
        radial-gradient(
            circle at top left,
            rgba(239, 68, 68, 0.08),
            transparent 22%
        ),
        radial-gradient(
            circle at top right,
            rgba(59, 130, 246, 0.08),
            transparent 25%
        ),
        #f3f5fb;
}

/* hero */
.hero-card {
    display: grid;
    grid-template-columns: 1.35fr 0.95fr;
    gap: 26px;
    background: linear-gradient(135deg, #dc2626 0%, #8b1148 48%, #2f56d3 100%);
    border-radius: 28px;
    padding: 38px;
    color: #fff;
    box-shadow: 0 18px 45px rgba(31, 41, 55, 0.18);
    margin-bottom: 22px;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    padding: 10px 16px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 18px;
}

.hero-card h1 {
    margin: 0;
    font-size: 44px;
    line-height: 1.1;
    font-weight: 900;
    letter-spacing: -0.5px;
}

.hero-subtitle {
    margin: 14px 0 0;
    max-width: 720px;
    font-size: 16px;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.92);
}

.hero-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 26px;
}

.hero-stat {
    min-width: 140px;
    padding: 14px 18px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
}

.hero-stat-label {
    display: block;
    font-size: 12px;
    opacity: 0.86;
    margin-bottom: 6px;
}

.hero-stat strong {
    font-size: 18px;
    font-weight: 900;
}

.hero-filter-card {
    align-self: center;
    background: rgba(255, 255, 255, 0.12);
    border-radius: 22px;
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(10px);
}

.hero-filter-title {
    font-size: 15px;
    font-weight: 800;
    margin-bottom: 14px;
}

.hero-filter-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.hero-filter-actions {
    display: grid;
    grid-template-columns: 1fr 160px;
    gap: 10px;
    margin-top: 14px;
}

.hero-input {
    background: rgba(255, 255, 255, 0.95) !important;
}

.hero-btn-primary {
    background: #ffffff;
    color: #111827;
    box-shadow: none;
}

.hero-btn-light {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: #fff;
}

/* top cards */
.top-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 22px;
}

.kpi-card {
    position: relative;
    border-radius: 22px;
    padding: 22px;
    background: #fff;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
    border: 1px solid rgba(226, 232, 240, 0.9);
}

.soft-pink {
    background: #f8eef1;
}

.soft-blue {
    background: #eef2fb;
}

.soft-purple {
    background: #f2eefb;
}

.soft-green {
    background: #edf7f3;
}

.kpi-badge {
    position: absolute;
    top: 18px;
    right: 18px;
    background: rgba(255, 255, 255, 0.72);
    border-radius: 999px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 800;
    color: #6b7280;
}

.kpi-title {
    color: #6b7280;
    font-size: 14px;
    font-weight: 700;
    margin-top: 36px;
    margin-bottom: 8px;
}

.kpi-value {
    font-size: 38px;
    line-height: 1;
    font-weight: 900;
    color: #111827;
}

.kpi-value.kpi-text {
    font-size: 22px;
    line-height: 1.25;
    word-break: break-word;
}

.kpi-sub {
    margin-top: 10px;
    color: #9ca3af;
    font-size: 13px;
}

.fuel-grid {
    display: grid;
    grid-template-columns: 1.75fr 0.92fr;
    gap: 22px;
}

.main-column,
.side-column {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
}

.block-card,
.summary-card,
.helper-card,
.error-card {
    padding: 24px;
}

.section-head {
    margin-bottom: 18px;
}

.section-head-space {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
}

.section-head h2 {
    margin: 0 0 6px;
    font-size: 24px;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.3px;
}

.section-head p {
    margin: 0;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
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
    color: #374151;
}

.field-button {
    justify-content: flex-end;
}

.full-width {
    margin-top: 18px;
}

.input {
    width: 100%;
    min-height: 50px;
    border: 1px solid #dbe2ee;
    background: #ffffff;
    border-radius: 16px;
    padding: 12px 14px;
    font-size: 14px;
    color: #111827;
    outline: none;
    transition: 0.22s ease;
    box-sizing: border-box;
}

.input::placeholder {
    color: #9ca3af;
}

.input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
}

.textarea {
    min-height: 120px;
    resize: vertical;
    padding-top: 14px;
}

.separator {
    height: 1px;
    background: linear-gradient(to right, transparent, #e5e7eb, transparent);
    margin: 24px 0;
}

/* searchable select */
.searchable-select {
    position: relative;
}

.searchable-input-wrap {
    position: relative;
}

.clear-search-btn {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    font-weight: 800;
}

.searchable-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    z-index: 60;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
    max-height: 260px;
    overflow: hidden;
}

.searchable-options {
    max-height: 260px;
    overflow-y: auto;
    padding: 8px;
}

.searchable-option {
    width: 100%;
    border: 0;
    background: transparent;
    text-align: left;
    padding: 12px 14px;
    border-radius: 12px;
    cursor: pointer;
    color: #111827;
    font-size: 14px;
    font-weight: 700;
    transition: 0.18s ease;
}

.searchable-option:hover {
    background: #f5f7ff;
    color: #2f56d3;
}

.searchable-option.active {
    background: linear-gradient(135deg, #eef2ff, #eff6ff);
    color: #2f56d3;
}

.searchable-empty {
    padding: 18px;
    text-align: center;
    color: #9ca3af;
    font-size: 14px;
    font-weight: 700;
}

/* upload */
.upload-box {
    background: linear-gradient(180deg, #fbfcff 0%, #f8fafc 100%);
    border: 1px dashed #cfd8e3;
    border-radius: 20px;
    padding: 16px;
}

.hidden-file-input {
    display: none;
}

.upload-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 46px;
    padding: 0 18px;
    background: linear-gradient(135deg, #3b82f6, #4f46e5);
    color: #fff;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 10px 18px rgba(79, 70, 229, 0.18);
}

.upload-hint {
    margin: 12px 0 0;
    color: #6b7280;
    font-size: 13px;
}

.selected-file {
    margin-top: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.file-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 10px 14px;
    border-radius: 999px;
    max-width: 100%;
}

.file-icon {
    font-size: 16px;
}

.file-name {
    font-size: 13px;
    font-weight: 800;
    word-break: break-all;
}

.remove-file-btn {
    border: 0;
    background: #fff1f2;
    color: #e11d48;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
}

.btn {
    border: 0;
    border-radius: 16px;
    min-height: 50px;
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
    background: linear-gradient(135deg, #ef4444, #4f46e5);
    color: #fff;
    box-shadow: 0 12px 22px rgba(79, 70, 229, 0.18);
}

.btn-primary:hover:not(:disabled),
.btn-success:hover:not(:disabled) {
    transform: translateY(-1px);
}

.btn-success {
    background: linear-gradient(135deg, #ef4444, #2f56d3);
    color: #fff;
    box-shadow: 0 12px 22px rgba(47, 86, 211, 0.18);
}

.btn-light {
    background: #f9fafb;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.btn-fetch,
.btn-save {
    width: 100%;
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
    min-height: 240px;
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
    border: 4px solid #e5e7eb;
    border-top-color: #4f46e5;
    animation: spin 0.8s linear infinite;
}

/* table */
.table-wrap {
    overflow-x: auto;
    border: 1px solid #edf0f5;
    border-radius: 20px;
}

.planning-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}

.planning-table thead th {
    background: #f5f7ff;
    color: #374151;
    font-size: 13px;
    font-weight: 800;
    text-align: left;
    padding: 15px 14px;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}

.planning-table tbody td {
    padding: 15px 14px;
    border-bottom: 1px solid #f1f5f9;
    color: #111827;
    font-size: 14px;
    vertical-align: middle;
}

.planning-table tbody tr:hover {
    background: #fafbff;
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
    border: 2px dashed #e5e7eb;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    text-align: center;
    padding: 24px;
}

.empty-icon {
    font-size: 42px;
    margin-bottom: 12px;
}

.empty-state h3 {
    margin: 0 0 8px;
    color: #111827;
    font-size: 20px;
    font-weight: 900;
}

.empty-state p {
    margin: 0;
    max-width: 520px;
    line-height: 1.7;
}

/* right cards */
.summary-card h3,
.helper-card h3,
.error-card h3 {
    margin: 0 0 18px;
    font-size: 20px;
    font-weight: 900;
    color: #111827;
}

.summary-card {
    background: linear-gradient(180deg, #ffffff 0%, #fbfbfe 100%);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 13px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.summary-item span {
    color: #6b7280;
    font-weight: 700;
}

.summary-item strong {
    color: #111827;
    text-align: right;
    font-weight: 900;
}

.summary-item.highlight strong {
    color: #2f56d3;
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

@media (max-width: 1300px) {
    .hero-card {
        grid-template-columns: 1fr;
    }

    .top-kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .fuel-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .form-grid,
    .second-grid,
    .hero-filter-grid,
    .hero-filter-actions {
        grid-template-columns: 1fr 1fr;
    }

    .hero-card {
        padding: 26px;
    }

    .hero-card h1 {
        font-size: 34px;
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
    .second-grid,
    .hero-filter-grid,
    .hero-filter-actions,
    .top-kpi-grid {
        grid-template-columns: 1fr;
    }

    .hero-card h1 {
        font-size: 26px;
    }

    .hero-subtitle {
        font-size: 14px;
    }

    .section-head-space {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
