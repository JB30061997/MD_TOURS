<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    services: { type: Object, required: true },
    supplierVehicules: { type: Array, default: () => [] },
    tarifs: { type: Object, default: () => ({}) },
    typeServices: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const errors = computed(() => page.props.errors || {});

const filters = reactive({
    service_search: props.filters.service_search || "",
    supplier_search: props.filters.supplier_search || "",
    per_page: props.filters.per_page || 12,
});

const draftTarifs = reactive({});
const dirtyCells = reactive({});
const saving = ref(false);
const showServiceModal = ref(false);
const showSupplierModal = ref(false);
const editingService = ref(null);

const serviceForm = reactive({
    designation: "",
    type_service: "",
});

const supplierForm = reactive({
    name: "",
    email: "",
    phone: "",
});

const rows = computed(() => props.services?.data || []);
const suppliers = computed(() => props.supplierVehicules || []);
const dirtyCount = computed(() => Object.keys(dirtyCells).length);
const hasMatrix = computed(() => rows.value.length && suppliers.value.length);

let filterTimer = null;

watch(
    () => props.tarifs,
    () => resetDraftTarifs(),
    { immediate: true, deep: true },
);

watch(
    () => ({ ...filters }),
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

function resetDraftTarifs() {
    Object.keys(draftTarifs).forEach((key) => delete draftTarifs[key]);
    Object.keys(dirtyCells).forEach((key) => delete dirtyCells[key]);

    Object.entries(props.tarifs || {}).forEach(([key, value]) => {
        draftTarifs[key] = value ?? "";
    });
}

function keyFor(serviceId, supplierId) {
    return `${serviceId}:${supplierId}`;
}

function priceValue(serviceId, supplierId) {
    const key = keyFor(serviceId, supplierId);
    return draftTarifs[key] ?? "";
}

function setPrice(serviceId, supplierId, value) {
    const key = keyFor(serviceId, supplierId);
    draftTarifs[key] = value;

    const original = props.tarifs?.[key] ?? "";
    const normalizedOriginal = normalizePrice(original);
    const normalizedValue = normalizePrice(value);

    if (normalizedOriginal === normalizedValue) {
        delete dirtyCells[key];
    } else {
        dirtyCells[key] = true;
    }
}

function normalizePrice(value) {
    if (value === null || value === undefined || value === "") return "";

    const amount = Number(String(value).replace(",", "."));
    return Number.isFinite(amount) ? amount.toFixed(2) : "";
}

function formatMoney(value) {
    const amount = Number(value || 0);

    return `${new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount)} MAD`;
}

function serviceAverage(service) {
    const values = suppliers.value
        .map((supplier) => Number(draftTarifs[keyFor(service.id, supplier.id)] || 0))
        .filter((value) => value > 0);

    if (!values.length) return null;

    return values.reduce((total, value) => total + value, 0) / values.length;
}

function supplierFilledCount(supplier) {
    return rows.value.filter((service) => Number(draftTarifs[keyFor(service.id, supplier.id)] || 0) > 0).length;
}

function applyFilters() {
    router.get("/supplier-vehicule-tarifs", { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filters.service_search = "";
    filters.supplier_search = "";
    filters.per_page = 12;
}

function saveTarifs() {
    if (!dirtyCount.value) return;

    const changedTarifs = {};
    Object.keys(dirtyCells).forEach((key) => {
        changedTarifs[key] = draftTarifs[key] === "" ? null : draftTarifs[key];
    });

    saving.value = true;
    router.post("/supplier-vehicule-tarifs/matrix", { tarifs: changedTarifs }, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
    });
}

function openCreateService() {
    editingService.value = null;
    serviceForm.designation = "";
    serviceForm.type_service = "";
    showServiceModal.value = true;
}

function openEditService(service) {
    editingService.value = service;
    serviceForm.designation = service.designation || "";
    serviceForm.type_service = service.type_service || service.typeService?.id || "";
    showServiceModal.value = true;
}

function closeServiceModal() {
    showServiceModal.value = false;
    editingService.value = null;
    serviceForm.designation = "";
    serviceForm.type_service = "";
}

function saveService() {
    const url = editingService.value
        ? `/supplier-vehicule-tarifs/services/${editingService.value.id}`
        : "/supplier-vehicule-tarifs/services";
    const method = editingService.value ? "put" : "post";

    router[method](url, { ...serviceForm }, {
        preserveScroll: true,
        onSuccess: closeServiceModal,
    });
}

function deleteService(service) {
    if (!window.confirm(`Supprimer le service "${service.designation}" ?`)) {
        return;
    }

    router.delete(`/supplier-vehicule-tarifs/services/${service.id}`, {
        preserveScroll: true,
    });
}

function openSupplierModal() {
    supplierForm.name = "";
    supplierForm.email = "";
    supplierForm.phone = "";
    showSupplierModal.value = true;
}

function closeSupplierModal() {
    showSupplierModal.value = false;
    supplierForm.name = "";
    supplierForm.email = "";
    supplierForm.phone = "";
}

function saveSupplier() {
    router.post("/supplier-vehicule-tarifs/suppliers", { ...supplierForm }, {
        preserveScroll: true,
        onSuccess: closeSupplierModal,
    });
}
</script>

<template>
    <Head title="Tarifs fournisseurs" />

    <div class="tarifs-page">
        <section class="tarifs-hero">
            <div>
                <span>Gestion contractuelle</span>
                <h1>Tarifs fournisseurs</h1>
                <p>Prix contractuels par service et fournisseur véhicule.</p>
            </div>

            <div class="hero-actions">
                <button type="button" class="secondary-action" @click="openSupplierModal">
                    <i class="bx bx-buildings"></i>
                    Fournisseur
                </button>
                <button type="button" class="primary-action" @click="openCreateService">
                    <i class="bx bx-plus"></i>
                    Service
                </button>
            </div>
        </section>

        <div v-if="flashSuccess" class="flash success">{{ flashSuccess }}</div>
        <div v-if="flashError" class="flash error">{{ flashError }}</div>
        <div v-if="Object.keys(errors).length" class="flash error">
            Vérifiez les champs saisis avant de continuer.
        </div>

        <section class="filters-card">
            <div class="filter-search">
                <i class="bx bx-search"></i>
                <input
                    v-model="filters.service_search"
                    type="search"
                    placeholder="Recherche service..."
                />
            </div>

            <div class="filter-search">
                <i class="bx bx-search"></i>
                <input
                    v-model="filters.supplier_search"
                    type="search"
                    placeholder="Recherche fournisseur..."
                />
            </div>

            <select v-model="filters.per_page">
                <option :value="10">10 services</option>
                <option :value="12">12 services</option>
                <option :value="20">20 services</option>
                <option :value="30">30 services</option>
            </select>

            <button type="button" class="ghost-action" @click="resetFilters">
                Reset
            </button>
        </section>

        <section class="summary-grid">
            <div class="summary-card dark">
                <span>Services affichés</span>
                <strong>{{ services.total || 0 }}</strong>
            </div>
            <div class="summary-card">
                <span>Fournisseurs</span>
                <strong>{{ suppliers.length }}</strong>
            </div>
            <div class="summary-card">
                <span>Cellules modifiées</span>
                <strong>{{ dirtyCount }}</strong>
            </div>
            <div class="summary-card action-card">
                <button
                    type="button"
                    class="save-action"
                    :disabled="!dirtyCount || saving"
                    @click="saveTarifs"
                >
                    {{ saving ? "Enregistrement..." : "Enregistrer les tarifs" }}
                </button>
            </div>
        </section>

        <section class="matrix-card">
            <div class="matrix-header">
                <div>
                    <h2>Tableau des tarifs</h2>
                    <p>
                        Page {{ services.current_page || 1 }} / {{ services.last_page || 1 }}.
                        Saisissez les prix directement dans les cellules.
                    </p>
                </div>

                <span class="matrix-pill">
                    {{ rows.length }} service(s) x {{ suppliers.length }} fournisseur(s)
                </span>
            </div>

            <div v-if="hasMatrix" class="matrix-scroll">
                <table class="tarifs-table">
                    <thead>
                        <tr>
                            <th class="service-column">Services</th>
                            <th
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                class="supplier-column"
                            >
                                <div class="supplier-head">
                                    <strong>{{ supplier.name }}</strong>
                                    <small>{{ supplierFilledCount(supplier) }} prix</small>
                                </div>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="service in rows" :key="service.id">
                            <td class="service-column">
                                <div class="service-cell">
                                    <div>
                                        <strong>{{ service.designation }}</strong>
                                        <small>{{ service.type_service?.designation || service.typeService?.designation || "Service" }}</small>
                                        <span v-if="serviceAverage(service)" class="average-pill">
                                            Moyenne {{ formatMoney(serviceAverage(service)) }}
                                        </span>
                                    </div>

                                    <div class="row-actions">
                                        <button type="button" @click="openEditService(service)">
                                            <i class="bx bx-edit-alt"></i>
                                        </button>
                                        <button type="button" @click="deleteService(service)">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>

                            <td
                                v-for="supplier in suppliers"
                                :key="supplier.id"
                                :class="[
                                    'price-cell',
                                    { dirty: dirtyCells[keyFor(service.id, supplier.id)] },
                                ]"
                            >
                                <input
                                    :value="priceValue(service.id, supplier.id)"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    @input="setPrice(service.id, supplier.id, $event.target.value)"
                                />
                                <span>MAD</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="empty-state">
                <i class="bx bx-search-alt"></i>
                <h3>Aucune matrice disponible</h3>
                <p>Ajoutez des services ou des fournisseurs pour commencer.</p>
            </div>

            <div v-if="services.links?.length" class="pagination-row">
                <span>
                    Affichage {{ services.from || 0 }} - {{ services.to || 0 }}
                    sur {{ services.total || 0 }}
                </span>

                <div class="pagination-actions">
                    <Link
                        v-for="(link, index) in services.links"
                        :key="index"
                        :href="link.url || '#'"
                        preserve-scroll
                        :class="[
                            'page-link',
                            { active: link.active, disabled: !link.url },
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </section>

        <div v-if="showServiceModal" class="modal-backdrop">
            <div class="form-modal">
                <button type="button" class="modal-close" @click="closeServiceModal">
                    <i class="bx bx-x"></i>
                </button>

                <h2>{{ editingService ? "Modifier service" : "Ajouter service" }}</h2>
                <p>Ce service apparaitra comme ligne dans la matrice.</p>

                <label>
                    Nom du service
                    <input v-model="serviceForm.designation" type="text" placeholder="Ex: Transfer Marrakech - Fès" />
                </label>

                <label>
                    Type
                    <select v-model="serviceForm.type_service">
                        <option value="">Sans type</option>
                        <option
                            v-for="typeService in typeServices"
                            :key="typeService.id"
                            :value="typeService.id"
                        >
                            {{ typeService.designation }}
                        </option>
                    </select>
                </label>

                <button type="button" class="save-action modal-save" @click="saveService">
                    Enregistrer service
                </button>
            </div>
        </div>

        <div v-if="showSupplierModal" class="modal-backdrop">
            <div class="form-modal">
                <button type="button" class="modal-close" @click="closeSupplierModal">
                    <i class="bx bx-x"></i>
                </button>

                <h2>Ajouter fournisseur</h2>
                <p>Le fournisseur sera ajouté à la matrice des tarifs.</p>

                <label>
                    Nom fournisseur
                    <input v-model="supplierForm.name" type="text" placeholder="Ex: MD Tours" />
                </label>

                <label>
                    Email
                    <input v-model="supplierForm.email" type="email" placeholder="supplier@email.com" />
                </label>

                <label>
                    Téléphone
                    <input v-model="supplierForm.phone" type="text" placeholder="+212..." />
                </label>

                <button type="button" class="save-action modal-save" @click="saveSupplier">
                    Ajouter fournisseur
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tarifs-page {
    min-height: 100vh;
    padding: 24px;
    background:
        radial-gradient(circle at top right, rgba(220, 38, 38, 0.12), transparent 28%),
        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

.tarifs-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 34px 38px;
    border-radius: 24px;
    background: linear-gradient(135deg, #111827 0%, #991b1b 52%, #ef4444 100%);
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.18);
    color: #fff;
}

.tarifs-hero span,
.matrix-header p,
.form-modal p {
    color: rgba(255, 255, 255, 0.78);
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.tarifs-hero h1 {
    margin: 8px 0;
    color: #fff;
    font-size: 42px;
    font-weight: 950;
}

.tarifs-hero p {
    margin: 0;
    color: rgba(255, 255, 255, 0.84);
    font-size: 18px;
    font-weight: 700;
}

.hero-actions,
.pagination-actions,
.row-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.primary-action,
.secondary-action,
.ghost-action,
.save-action {
    border: 0;
    border-radius: 16px;
    padding: 14px 20px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.primary-action,
.save-action {
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    box-shadow: 0 16px 28px rgba(220, 38, 38, 0.22);
}

.secondary-action {
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.24);
}

.ghost-action {
    background: #fff;
    color: #0f172a;
    border: 1px solid #e2e8f0;
}

.save-action:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.flash {
    margin-top: 16px;
    border-radius: 16px;
    padding: 14px 18px;
    font-weight: 900;
}

.flash.success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.flash.error {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.filters-card,
.summary-grid,
.matrix-card {
    margin-top: 22px;
}

.filters-card {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(240px, 1fr) 160px auto;
    gap: 12px;
    padding: 18px;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.08);
}

.filter-search {
    position: relative;
}

.filter-search i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 20px;
}

.filter-search input,
.filters-card select,
.form-modal input,
.form-modal select {
    width: 100%;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    min-height: 52px;
    padding: 0 16px;
    background: #fff;
    color: #0f172a;
    font-weight: 800;
    outline: none;
}

.filter-search input {
    padding-left: 48px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.summary-card {
    border-radius: 22px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 16px 32px rgba(15, 23, 42, 0.07);
}

.summary-card.dark {
    background: linear-gradient(135deg, #0f172a, #1f2937);
    color: #fff;
}

.summary-card span {
    display: block;
    color: #64748b;
    font-weight: 900;
    margin-bottom: 6px;
}

.summary-card.dark span {
    color: rgba(255, 255, 255, 0.72);
}

.summary-card strong {
    font-size: 34px;
    font-weight: 950;
    color: #0f172a;
}

.summary-card.dark strong {
    color: #fff;
}

.action-card {
    display: flex;
    align-items: center;
    justify-content: stretch;
}

.action-card .save-action {
    width: 100%;
}

.matrix-card {
    overflow: hidden;
    border-radius: 26px;
    background: #fff;
    box-shadow: 0 24px 50px rgba(15, 23, 42, 0.1);
}

.matrix-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 24px 28px;
    border-bottom: 1px solid #fee2e2;
}

.matrix-header h2 {
    margin: 0 0 6px;
    color: #0f172a;
    font-size: 26px;
    font-weight: 950;
}

.matrix-header p {
    margin: 0;
    color: #64748b;
    letter-spacing: 0;
    text-transform: none;
}

.matrix-pill {
    padding: 12px 16px;
    border-radius: 999px;
    background: #fff1f2;
    color: #991b1b;
    font-weight: 950;
    white-space: nowrap;
}

.matrix-scroll {
    width: 100%;
    overflow-x: auto;
}

.tarifs-table {
    width: 100%;
    min-width: 1040px;
    border-collapse: collapse;
}

.tarifs-table th {
    background: #fff1f2;
    color: #991b1b;
    font-size: 14px;
    font-weight: 950;
    text-align: left;
    padding: 18px;
    border-bottom: 1px solid #fecdd3;
}

.tarifs-table td {
    border-bottom: 1px solid #f1f5f9;
    padding: 16px 18px;
    vertical-align: middle;
}

.service-column {
    position: sticky;
    left: 0;
    z-index: 2;
    width: 320px;
    min-width: 320px;
    background: #fff;
    box-shadow: 8px 0 18px rgba(15, 23, 42, 0.04);
}

thead .service-column {
    background: #fff1f2;
    z-index: 3;
}

.supplier-column {
    min-width: 190px;
}

.supplier-head strong {
    display: block;
    color: #7f1d1d;
}

.supplier-head small,
.service-cell small {
    display: block;
    color: #64748b;
    font-weight: 800;
    margin-top: 3px;
}

.service-cell {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.service-cell strong {
    color: #0f172a;
    font-size: 16px;
    font-weight: 950;
}

.average-pill {
    display: inline-flex;
    width: fit-content;
    margin-top: 9px;
    border-radius: 999px;
    padding: 6px 10px;
    background: #ecfdf5;
    color: #047857;
    font-size: 12px;
    font-weight: 950;
}

.row-actions button {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 12px;
    background: #f8fafc;
    color: #334155;
}

.row-actions button:last-child {
    color: #dc2626;
}

.price-cell {
    background: #fff;
    transition: background 0.2s ease;
}

.price-cell.dirty {
    background: #fffbeb;
}

.price-cell input {
    width: 116px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 12px;
    color: #0f172a;
    font-weight: 950;
    outline: none;
}

.price-cell input:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
}

.price-cell span {
    margin-left: 8px;
    color: #047857;
    font-size: 12px;
    font-weight: 950;
}

.empty-state {
    padding: 58px 24px;
    text-align: center;
    color: #64748b;
}

.empty-state i {
    font-size: 46px;
    color: #dc2626;
}

.empty-state h3 {
    margin: 12px 0 6px;
    color: #0f172a;
    font-weight: 950;
}

.pagination-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 24px;
    border-top: 1px solid #f1f5f9;
    color: #64748b;
    font-weight: 900;
}

.page-link {
    min-width: 42px;
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    padding: 0 12px;
    background: #f8fafc;
    color: #0f172a;
    text-decoration: none;
    font-weight: 950;
}

.page-link.active {
    background: #dc2626;
    color: #fff;
}

.page-link.disabled {
    pointer-events: none;
    opacity: 0.45;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: grid;
    place-items: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(10px);
}

.form-modal {
    position: relative;
    width: min(560px, 100%);
    border-radius: 26px;
    padding: 30px;
    background: #fff;
    box-shadow: 0 26px 70px rgba(15, 23, 42, 0.28);
}

.modal-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 16px;
    background: #f1f5f9;
    color: #0f172a;
    font-size: 22px;
}

.form-modal h2 {
    margin: 0 0 8px;
    color: #0f172a;
    font-weight: 950;
}

.form-modal p {
    margin: 0 0 22px;
    color: #64748b;
    letter-spacing: 0;
    text-transform: none;
}

.form-modal label {
    display: block;
    margin-bottom: 16px;
    color: #334155;
    font-weight: 950;
}

.form-modal input,
.form-modal select {
    margin-top: 8px;
}

.modal-save {
    width: 100%;
    min-height: 54px;
}

@media (max-width: 1200px) {
    .filters-card,
    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .tarifs-page {
        padding: 14px;
    }

    .tarifs-hero,
    .matrix-header,
    .pagination-row {
        flex-direction: column;
        align-items: stretch;
    }

    .hero-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .filters-card,
    .summary-grid {
        grid-template-columns: 1fr;
    }
}
</style>
