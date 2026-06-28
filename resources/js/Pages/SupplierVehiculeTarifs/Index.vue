<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import { formatDate } from "@/utils/dateFormat";

defineOptions({ layout: AppShell });

const props = defineProps({
    supplierVehicules: { type: Object, required: true },
    services: { type: Array, default: () => [] },
    typeServices: { type: Array, default: () => [] },
    seatCategories: { type: Array, default: () => [7, 17, 36, 40, 48] },
    filters: { type: Object, default: () => ({}) },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const errors = computed(() => page.props.errors || {});

const filters = reactive({
    supplier_search: props.filters.supplier_search || "",
});

const activeSupplier = ref(null);
const showTarifModal = ref(false);
const modalSearch = ref("");
const saving = ref(false);
const syncing = ref(false);
const rows = ref([]);

let filterTimer = null;

const suppliers = computed(() => props.supplierVehicules?.data || []);
const filteredRows = computed(() => {
    const term = modalSearch.value.trim().toLowerCase();
    if (!term) return rows.value;

    return rows.value.filter((row) => {
        const service = serviceById(row.service_id);
        const type = typeById(row.type_service_id);
        return `${service?.designation || ""} ${type?.designation || "Sans type"}`
            .toLowerCase()
            .includes(term);
    });
});

watch(
    () => filters.supplier_search,
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            router.get("/supplier-vehicule-tarifs", { ...filters }, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 350);
    },
);

function emptyPrices(source = {}) {
    return props.seatCategories.reduce((carry, seats) => {
        carry[String(seats)] = source?.[String(seats)] ?? "";
        return carry;
    }, {});
}

function serviceById(id) {
    return props.services.find((service) => Number(service.id) === Number(id));
}

function typeById(id) {
    return props.typeServices.find((type) => Number(type.id) === Number(id));
}

function serviceDefaultType(serviceId) {
    return serviceById(serviceId)?.type_service || "";
}

function openTarifModal(supplier) {
    activeSupplier.value = supplier;
    modalSearch.value = "";
    rows.value = (supplier.tarif_rows || []).map((row) => ({
        service_id: row.service_id || "",
        type_service_id: row.type_service_id || serviceDefaultType(row.service_id) || "",
        prices: emptyPrices(row.prices || {}),
    }));

    if (!rows.value.length) {
        addRow();
    }

    showTarifModal.value = true;
}

function closeTarifModal() {
    showTarifModal.value = false;
    activeSupplier.value = null;
    rows.value = [];
    modalSearch.value = "";
}

function addRow() {
    rows.value.push({
        service_id: "",
        type_service_id: "",
        prices: emptyPrices(),
    });
}

function duplicateRow(row) {
    rows.value.push({
        service_id: row.service_id,
        type_service_id: row.type_service_id,
        prices: emptyPrices(row.prices),
    });
}

function removeRow(index) {
    rows.value.splice(index, 1);
}

function onServiceChange(row) {
    if (!row.type_service_id) {
        row.type_service_id = serviceDefaultType(row.service_id) || "";
    }
}

function saveSupplierTarifs() {
    if (!activeSupplier.value) return;

    const payloadRows = rows.value
        .filter((row) => row.service_id)
        .map((row) => ({
            service_id: row.service_id,
            type_service_id: row.type_service_id || null,
            prices: { ...row.prices },
        }));

    saving.value = true;
    router.post(`/supplier-vehicule-tarifs/${activeSupplier.value.id}/tarifs`, { rows: payloadRows }, {
        preserveScroll: true,
        onSuccess: closeTarifModal,
        onFinish: () => {
            saving.value = false;
        },
    });
}

function syncFromPlannings(overwrite = false) {
    syncing.value = true;
    router.post("/supplier-vehicule-tarifs/sync-from-plannings", { overwrite }, {
        preserveScroll: true,
        onFinish: () => {
            syncing.value = false;
        },
    });
}

function resetFilters() {
    filters.supplier_search = "";
}

function formatMoney(value) {
    const amount = Number(value || 0);
    return new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function rowTotal(row) {
    return Object.values(row.prices || {}).reduce((total, value) => total + Number(value || 0), 0);
}

function configuredPricesCount(supplier) {
    return (supplier.tarif_rows || []).reduce((total, row) => {
        return total + Object.values(row.prices || {}).filter((value) => Number(value || 0) > 0).length;
    }, 0);
}
</script>

<template>
    <Head title="Tarifs fournisseurs" />

    <div class="tarifs-page">
        <section class="tarifs-hero">
            <div>
                <span>Grilles contractuelles</span>
                <h1>Tarifs fournisseurs</h1>
                <p>Configurez les prix par fournisseur, service, type et catégorie véhicule.</p>
            </div>

            <button
                type="button"
                class="hero-sync"
                :disabled="syncing"
                @click="syncFromPlannings(false)"
            >
                <i class="bx bx-refresh"></i>
                {{ syncing ? "Synchronisation..." : "Sync depuis plannings" }}
            </button>
        </section>

        <div v-if="flashSuccess" class="flash success">{{ flashSuccess }}</div>
        <div v-if="flashError" class="flash error">{{ flashError }}</div>
        <div v-if="Object.keys(errors).length" class="flash error">
            Vérifiez les champs saisis avant de continuer.
        </div>

        <section class="toolbar">
            <div class="search-box">
                <i class="bx bx-search"></i>
                <input
                    v-model="filters.supplier_search"
                    type="search"
                    placeholder="Rechercher un fournisseur..."
                />
            </div>

            <button type="button" class="ghost-btn" @click="resetFilters">
                Reset
            </button>
        </section>

        <section class="supplier-grid">
            <article
                v-for="supplier in suppliers"
                :key="supplier.id"
                class="supplier-card"
            >
                <div class="supplier-top">
                    <div class="supplier-avatar">
                        {{ supplier.name?.slice(0, 2).toUpperCase() || "SV" }}
                    </div>
                    <div>
                        <h2>{{ supplier.name }}</h2>
                        <p>{{ supplier.email || supplier.phone || "Contact non renseigné" }}</p>
                    </div>
                </div>

                <div class="supplier-metrics">
                    <div>
                        <span>Services</span>
                        <strong>{{ supplier.configured_services_count || 0 }}</strong>
                    </div>
                    <div>
                        <span>Prix saisis</span>
                        <strong>{{ configuredPricesCount(supplier) }}</strong>
                    </div>
                    <div>
                        <span>Dernière MAJ</span>
                        <strong>{{ supplier.latest_tarif_update ? formatDate(supplier.latest_tarif_update) : "-" }}</strong>
                    </div>
                </div>

                <button type="button" class="configure-btn" @click="openTarifModal(supplier)">
                    <i class="bx bx-slider-alt"></i>
                    Configurer les tarifs
                </button>
            </article>
        </section>

        <div v-if="!suppliers.length" class="empty-state">
            <i class="bx bx-buildings"></i>
            <h3>Aucun fournisseur trouvé</h3>
            <p>Modifiez la recherche ou ajoutez un fournisseur véhicule.</p>
        </div>

        <div v-if="supplierVehicules.links?.length" class="pagination-row">
            <span>
                Affichage {{ supplierVehicules.from || 0 }} - {{ supplierVehicules.to || 0 }}
                sur {{ supplierVehicules.total || 0 }}
            </span>

            <div class="pagination-actions">
                <Link
                    v-for="(link, index) in supplierVehicules.links"
                    :key="index"
                    :href="link.url || '#'"
                    preserve-scroll
                    :class="['page-link', { active: link.active, disabled: !link.url }]"
                    v-html="link.label"
                />
            </div>
        </div>

        <div v-if="showTarifModal" class="modal-backdrop">
            <section class="tarif-modal">
                <header class="modal-header">
                    <div>
                        <span>Grille fournisseur</span>
                        <h2>{{ activeSupplier?.name }}</h2>
                        <p>Une ligne = un service. Les colonnes représentent les catégories véhicule.</p>
                    </div>
                    <button type="button" class="close-btn" @click="closeTarifModal">
                        <i class="bx bx-x"></i>
                        Fermer
                    </button>
                </header>

                <div class="modal-actions">
                    <div class="search-box">
                        <i class="bx bx-search"></i>
                        <input
                            v-model="modalSearch"
                            type="search"
                            placeholder="Rechercher dans les lignes..."
                        />
                    </div>

                    <button type="button" class="secondary-btn" @click="addRow">
                        <i class="bx bx-plus"></i>
                        Ajouter une ligne
                    </button>
                </div>

                <div class="tarif-table-wrap">
                    <table class="tarif-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Type</th>
                                <th v-for="seats in seatCategories" :key="seats">
                                    {{ seats }} places
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(row, index) in filteredRows" :key="`${row.service_id}-${index}`">
                                <td>
                                    <select v-model="row.service_id" @change="onServiceChange(row)">
                                        <option value="">Choisir service</option>
                                        <option
                                            v-for="service in services"
                                            :key="service.id"
                                            :value="service.id"
                                        >
                                            {{ service.designation }}
                                        </option>
                                    </select>
                                </td>

                                <td>
                                    <select v-model="row.type_service_id">
                                        <option value="">Sans type</option>
                                        <option
                                            v-for="type in typeServices"
                                            :key="type.id"
                                            :value="type.id"
                                        >
                                            {{ type.designation }}
                                        </option>
                                    </select>
                                </td>

                                <td v-for="seats in seatCategories" :key="seats">
                                    <div class="money-input">
                                        <input
                                            v-model="row.prices[String(seats)]"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            placeholder="0.00"
                                        />
                                        <span>MAD</span>
                                    </div>
                                </td>

                                <td>
                                    <div class="row-buttons">
                                        <button type="button" title="Dupliquer" @click="duplicateRow(row)">
                                            <i class="bx bx-copy"></i>
                                        </button>
                                        <button type="button" title="Supprimer" @click="removeRow(rows.indexOf(row))">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="!filteredRows.length" class="modal-empty">
                        Aucune ligne ne correspond à la recherche.
                    </div>
                </div>

                <footer class="modal-footer">
                    <div>
                        <strong>{{ rows.length }}</strong> ligne(s)
                        <span>Prix total affiché: {{ formatMoney(rows.reduce((sum, row) => sum + rowTotal(row), 0)) }} MAD</span>
                    </div>

                    <button
                        type="button"
                        class="save-btn"
                        :disabled="saving"
                        @click="saveSupplierTarifs"
                    >
                        {{ saving ? "Enregistrement..." : "Enregistrer la grille" }}
                    </button>
                </footer>
            </section>
        </div>
    </div>
</template>

<style scoped>
.tarifs-page {
    min-height: 100vh;
    padding: 24px;
    background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

.tarifs-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    padding: 34px 38px;
    border-radius: 24px;
    color: #fff;
    background: linear-gradient(135deg, #111827 0%, #991b1b 52%, #ef4444 100%);
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.18);
}

.tarifs-hero span,
.modal-header span {
    color: rgba(255, 255, 255, 0.76);
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.tarifs-hero h1 {
    margin: 8px 0;
    color: #fff;
    font-size: 42px;
    font-weight: 950;
}

.tarifs-hero p,
.modal-header p {
    margin: 0;
    color: rgba(255, 255, 255, 0.84);
    font-size: 17px;
    font-weight: 700;
}

.hero-sync,
.configure-btn,
.secondary-btn,
.save-btn,
.ghost-btn,
.close-btn {
    border: 0;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    font-weight: 900;
}

.hero-sync,
.secondary-btn,
.save-btn {
    padding: 14px 20px;
    color: #fff;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    box-shadow: 0 16px 28px rgba(220, 38, 38, 0.22);
}

.hero-sync {
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.24);
}

.hero-sync:disabled,
.save-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.flash {
    margin-top: 16px;
    border-radius: 16px;
    padding: 14px 18px;
    font-weight: 900;
}

.flash.success {
    color: #047857;
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
}

.flash.error {
    color: #be123c;
    background: #fff1f2;
    border: 1px solid #fecdd3;
}

.toolbar {
    display: flex;
    gap: 14px;
    margin-top: 22px;
    padding: 18px;
    border-radius: 20px;
    background: #fff;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.search-box {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 16px;
    min-height: 54px;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
}

.search-box i {
    color: #94a3b8;
    font-size: 20px;
}

.search-box input,
.tarif-table select,
.money-input input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #0f172a;
    font-weight: 800;
}

.ghost-btn {
    padding: 0 22px;
    color: #0f172a;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.supplier-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 18px;
    margin-top: 22px;
}

.supplier-card {
    padding: 22px;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    background: #fff;
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.07);
}

.supplier-top {
    display: flex;
    align-items: center;
    gap: 14px;
}

.supplier-avatar {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    color: #fff;
    font-weight: 950;
    background: linear-gradient(135deg, #dc2626, #fb923c);
}

.supplier-card h2 {
    margin: 0;
    color: #0f172a;
    font-size: 21px;
    font-weight: 950;
}

.supplier-card p {
    margin: 4px 0 0;
    color: #64748b;
    font-weight: 800;
}

.supplier-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin: 20px 0;
}

.supplier-metrics div {
    padding: 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.supplier-metrics span {
    display: block;
    color: #64748b;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
}

.supplier-metrics strong {
    display: block;
    margin-top: 6px;
    color: #0f172a;
    font-size: 20px;
    font-weight: 950;
}

.configure-btn {
    width: 100%;
    padding: 14px;
    color: #fff;
    background: #111827;
}

.empty-state {
    margin-top: 22px;
    padding: 50px;
    text-align: center;
    color: #64748b;
    background: #fff;
    border-radius: 22px;
}

.empty-state i {
    font-size: 42px;
    color: #dc2626;
}

.empty-state h3 {
    color: #0f172a;
    font-weight: 950;
}

.pagination-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-top: 22px;
    color: #64748b;
    font-weight: 900;
}

.pagination-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.page-link {
    min-width: 42px;
    padding: 10px 14px;
    border-radius: 12px;
    color: #0f172a;
    background: #fff;
    text-decoration: none;
}

.page-link.active {
    color: #fff;
    background: #dc2626;
}

.page-link.disabled {
    opacity: 0.45;
    pointer-events: none;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 3000;
    padding: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(10px);
}

.tarif-modal {
    width: min(1600px, 98vw);
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 28px;
    background: #fff;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 28px 32px;
    color: #fff;
    background: linear-gradient(135deg, #111827, #991b1b);
}

.modal-header h2 {
    margin: 6px 0;
    color: #fff;
    font-size: 34px;
    font-weight: 950;
}

.close-btn {
    align-self: flex-start;
    padding: 14px 18px;
    color: #fff;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.modal-actions {
    display: flex;
    gap: 14px;
    padding: 18px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.tarif-table-wrap {
    overflow: auto;
    padding: 0 24px 24px;
}

.tarif-table {
    width: 100%;
    min-width: 1080px;
    border-collapse: separate;
    border-spacing: 0;
}

.tarif-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 18px 14px;
    color: #991b1b;
    background: #fff1f2;
    border-bottom: 1px solid #fecdd3;
    text-align: left;
    font-weight: 950;
}

.tarif-table td {
    padding: 14px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.tarif-table select,
.money-input {
    min-height: 48px;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    background: #fff;
}

.tarif-table select {
    padding: 0 12px;
}

.money-input {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0 12px;
}

.money-input span {
    color: #64748b;
    font-size: 12px;
    font-weight: 950;
}

.row-buttons {
    display: flex;
    gap: 8px;
}

.row-buttons button {
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 12px;
    color: #991b1b;
    background: #fff1f2;
}

.modal-empty {
    padding: 28px;
    color: #64748b;
    text-align: center;
    font-weight: 900;
}

.modal-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    padding: 18px 24px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
}

.modal-footer div {
    color: #64748b;
    font-weight: 900;
}

.modal-footer strong {
    color: #0f172a;
    font-size: 22px;
}

.modal-footer span {
    margin-left: 14px;
}

@media (max-width: 900px) {
    .tarifs-page {
        padding: 14px;
    }

    .tarifs-hero,
    .modal-header,
    .modal-actions,
    .modal-footer,
    .toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .tarifs-hero h1,
    .modal-header h2 {
        font-size: 30px;
    }

    .supplier-metrics {
        grid-template-columns: 1fr;
    }
}
</style>
