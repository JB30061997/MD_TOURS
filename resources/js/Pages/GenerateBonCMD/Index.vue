<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import SearchSelect from "@/Components/SearchSelect.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    supplierVehicules: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    selectedSupplier: { type: Object, default: null },
    rows: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({ count: 0, unit_price: 0, total_price: 0 }) },
});

const filters = reactive({
    supplier_vehicule_id: props.filters.supplier_vehicule_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});

const supplierSearch = ref(props.selectedSupplier?.name || "");
const selectedIds = ref([]);

const rows = computed(() => props.rows || []);
const allSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.length);
const selectedRows = computed(() => rows.value.filter((row) => selectedIds.value.includes(row.id)));
const selectedTotal = computed(() => selectedRows.value.reduce((sum, row) => sum + Number(row.total_price || 0), 0));

watch(
    () => props.rows,
    () => {
        selectedIds.value = rows.value.map((row) => row.id);
    },
    { immediate: true },
);

const money = (value) =>
    new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0));

const search = () => {
    router.get(route("generate-boncmd.index"), { ...filters }, { preserveScroll: true });
};

const reset = () => {
    supplierSearch.value = "";
    router.get(route("generate-boncmd.index"), {}, { preserveScroll: true });
};

const toggleAll = () => {
    selectedIds.value = allSelected.value ? [] : rows.value.map((row) => row.id);
};

const generatePdf = () => {
    if (!selectedIds.value.length) {
        window.alert("Sélectionnez au moins une ligne pour générer le PDF.");
        return;
    }

    const params = new URLSearchParams();
    params.set("supplier_vehicule_id", filters.supplier_vehicule_id || "");
    params.set("date_from", filters.date_from || "");
    params.set("date_to", filters.date_to || "");
    selectedIds.value.forEach((id) => params.append("planning_ids[]", id));

    window.open(`${route("generate-boncmd.pdf")}?${params.toString()}`, "_blank");
};
</script>

<template>
    <Head title="Generate BonCMD" />

    <div class="boncmd-page">
        <section class="boncmd-hero">
            <div>
                <span>Fournisseurs véhicules</span>
                <h1>Generate BonCMD</h1>
                <p>Sélectionnez un fournisseur et une période, puis générez un bon de commande PDF uniquement avec les lignes cochées.</p>
            </div>
            <button type="button" class="hero-btn" :disabled="!selectedIds.length" @click="generatePdf">
                <i class="material-icons-outlined">picture_as_pdf</i>
                Generate PDF Bon Commande
            </button>
        </section>

        <section class="search-card">
            <div class="supplier-field">
                <label>Fournisseur véhicule</label>
                <SearchSelect
                    v-model="filters.supplier_vehicule_id"
                    v-model:search="supplierSearch"
                    :options="supplierVehicules"
                    placeholder="Rechercher fournisseur véhicule..."
                    :allow-custom="false"
                />
            </div>
            <label>
                <span>Date début</span>
                <input v-model="filters.date_from" type="date" />
            </label>
            <label>
                <span>Date fin</span>
                <input v-model="filters.date_to" type="date" />
            </label>
            <button type="button" class="search-btn" @click="search">
                <i class="material-icons-outlined">search</i>
                Chercher
            </button>
            <button type="button" class="reset-btn" @click="reset">Reset</button>
        </section>

        <section class="summary-grid">
            <div class="summary-card dark">
                <span>Fournisseur</span>
                <strong>{{ selectedSupplier?.name || "-" }}</strong>
            </div>
            <div class="summary-card">
                <span>Services trouvés</span>
                <strong>{{ totals.count || 0 }}</strong>
            </div>
            <div class="summary-card">
                <span>Lignes sélectionnées</span>
                <strong>{{ selectedIds.length }}</strong>
            </div>
            <div class="summary-card total">
                <span>Total sélectionné</span>
                <strong>{{ money(selectedTotal) }} MAD</strong>
            </div>
        </section>

        <section class="table-card">
            <div class="table-head">
                <div>
                    <h2>Services du fournisseur</h2>
                    <p>Les lignes cochées seront incluses dans le PDF.</p>
                </div>
                <button type="button" class="select-btn" @click="toggleAll">
                    {{ allSelected ? "Tout désélectionner" : "Tout sélectionner" }}
                </button>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="check"><input type="checkbox" :checked="allSelected" @change="toggleAll" /></th>
                            <th>Du</th>
                            <th>AU</th>
                            <th>Bus</th>
                            <th>Services</th>
                            <th>Prix Unitaire TTC</th>
                            <th>Prix Total TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td class="check"><input v-model="selectedIds" type="checkbox" :value="row.id" /></td>
                            <td class="date-cell">{{ row.du }}</td>
                            <td>
                                <strong>{{ row.reference }}</strong>
                                <small>{{ row.clients?.join(', ') || '-' }}</small>
                            </td>
                            <td>{{ row.bus }}</td>
                            <td class="service-cell">{{ row.service }}</td>
                            <td class="money">{{ money(row.unit_price) }}</td>
                            <td class="money total-money">{{ money(row.total_price) }}</td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="7" class="empty-cell">Choisissez un fournisseur et une période pour afficher les services.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length">
                        <tr>
                            <td colspan="5">TOTAL SÉLECTIONNÉ</td>
                            <td class="money">{{ money(selectedTotal) }}</td>
                            <td class="money total-money">{{ money(selectedTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>
</template>

<style scoped>
.boncmd-page { display: flex; flex-direction: column; gap: 22px; color: #111827; }
.boncmd-hero { min-height: 190px; border-radius: 28px; padding: 34px; display: flex; align-items: center; justify-content: space-between; gap: 24px; background: linear-gradient(135deg, #111827 0%, #991b1f 52%, #ef4444 100%); color: #fff; box-shadow: 0 22px 60px rgba(153,27,31,.24); }
.boncmd-hero span { text-transform: uppercase; letter-spacing: .16em; font-weight: 900; opacity: .74; }
.boncmd-hero h1 { margin: 8px 0; color: #fff; font-size: 42px; font-weight: 950; }
.boncmd-hero p { margin: 0; max-width: 760px; font-weight: 750; color: rgba(255,255,255,.82); }
.hero-btn, .search-btn, .reset-btn, .select-btn { border: 0; border-radius: 16px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: .18s ease; }
.hero-btn { min-width: 260px; padding: 16px 22px; color: #991b1f; background: #fff; box-shadow: 0 14px 30px rgba(0,0,0,.16); }
.hero-btn:disabled { opacity: .5; cursor: not-allowed; }
.search-card { display: grid; grid-template-columns: minmax(320px, 1.4fr) minmax(180px, .6fr) minmax(180px, .6fr) 170px 110px; gap: 14px; align-items: end; padding: 20px; border-radius: 24px; background: #fff; box-shadow: 0 16px 40px rgba(15,23,42,.08); }
.search-card label, .supplier-field { display: flex; flex-direction: column; gap: 8px; color: #64748b; font-weight: 900; }
.search-card input { height: 48px; border: 1px solid #dbe2ea; border-radius: 14px; padding: 0 14px; font-weight: 850; outline: none; }
.search-card input:focus { border-color: #c1121f; box-shadow: 0 0 0 4px rgba(193,18,31,.08); }
.search-btn { height: 48px; background: #c1121f; color: #fff; }
.reset-btn { height: 48px; background: #f1f5f9; color: #475569; }
.summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.summary-card { border-radius: 22px; padding: 20px; background: #fff; box-shadow: 0 16px 36px rgba(15,23,42,.07); border: 1px solid #edf2f7; }
.summary-card span { display: block; color: #64748b; font-weight: 900; margin-bottom: 8px; }
.summary-card strong { font-size: 26px; font-weight: 950; }
.summary-card.dark { background: #111827; color: #fff; }
.summary-card.dark span { color: #cbd5e1; }
.summary-card.total strong { color: #047857; }
.table-card { overflow: hidden; border-radius: 26px; background: #fff; box-shadow: 0 20px 55px rgba(15,23,42,.09); }
.table-head { padding: 24px 26px; display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 1px solid #f1f5f9; }
.table-head h2 { margin: 0; font-size: 24px; font-weight: 950; }
.table-head p { margin: 6px 0 0; color: #64748b; font-weight: 750; }
.select-btn { padding: 13px 18px; color: #991b1f; background: #fff1f2; border: 1px solid #fecdd3; }
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 1080px; }
th { background: #fff1f2; color: #991b1f; padding: 17px 14px; text-align: left; font-weight: 950; border-bottom: 1px solid #fecdd3; }
td { padding: 17px 14px; border-bottom: 1px solid #edf2f7; vertical-align: middle; font-weight: 750; }
tbody tr:hover td { background: #fffafa; }
.check { width: 52px; text-align: center; }
.check input { width: 18px; height: 18px; accent-color: #c1121f; }
.date-cell { color: #991b1f; font-weight: 950; white-space: nowrap; }
td strong { display: block; color: #111827; font-weight: 950; }
td small { display: block; margin-top: 4px; max-width: 320px; color: #64748b; font-weight: 750; }
.service-cell { max-width: 430px; }
.money { text-align: right; font-weight: 950; white-space: nowrap; }
.total-money { color: #047857; }
tfoot td { background: #111827; color: #fff; border-bottom: 0; font-size: 16px; }
tfoot .total-money { color: #34d399; }
.empty-cell { text-align: center; color: #94a3b8; font-weight: 900; padding: 54px; }
@media (max-width: 1200px) { .search-card, .summary-grid { grid-template-columns: 1fr 1fr; } .boncmd-hero { flex-direction: column; align-items: flex-start; } }
</style>
