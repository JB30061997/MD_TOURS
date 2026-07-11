<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import SearchSelect from "@/Components/SearchSelect.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    drivers: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    selectedDriver: { type: Object, default: null },
    rows: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ count: 0, unique_days: 0, unique_references: 0 }) },
});

const filters = reactive({
    driver_id: props.filters.driver_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});

const driverSearch = ref(props.selectedDriver?.name || "");
const rows = computed(() => props.rows || []);

const search = () => {
    router.get(route("driver-primes.index"), { ...filters }, { preserveScroll: true });
};

const reset = () => {
    driverSearch.value = "";
    router.get(route("driver-primes.index"), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Primes chauffeurs" />

    <div class="prime-page">
        <section class="prime-hero">
            <div class="hero-copy">
                <span>Comptabilité chauffeurs</span>
                <h1>Primes chauffeurs</h1>
                <p>Filtrez par durée et chauffeur pour lister uniquement les services de type Circuit. Aucun montant n'est affiché ici.</p>
            </div>
            <div class="hero-badge">
                <i class="material-icons-outlined">workspace_premium</i>
                <strong>{{ summary.count || 0 }}</strong>
                <span>circuits</span>
            </div>
        </section>

        <section class="filter-card">
            <div class="driver-field">
                <label>Driver</label>
                <SearchSelect
                    v-model="filters.driver_id"
                    v-model:search="driverSearch"
                    :options="drivers"
                    placeholder="Rechercher driver..."
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
                <span>Driver sélectionné</span>
                <strong>{{ selectedDriver?.name || "-" }}</strong>
            </div>
            <div class="summary-card">
                <span>Services Circuit</span>
                <strong>{{ summary.count || 0 }}</strong>
            </div>
            <div class="summary-card">
                <span>Jours travaillés</span>
                <strong>{{ summary.unique_days || 0 }}</strong>
            </div>
            <div class="summary-card">
                <span>Dossiers</span>
                <strong>{{ summary.unique_references || 0 }}</strong>
            </div>
        </section>

        <section class="table-card">
            <div class="table-head">
                <div>
                    <h2>Détails des services Circuit</h2>
                    <p>Cette liste sert au calcul manuel des primes driver par le comptable.</p>
                </div>
                <span class="period-chip">{{ filters.date_from || "-" }} → {{ filters.date_to || "-" }}</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Référence</th>
                            <th>Service</th>
                            <th>Trajet</th>
                            <th>Véhicule</th>
                            <th>PAX</th>
                            <th>Clients</th>
                            <th>Fournisseurs</th>
                            <th>Guide</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows" :key="row.id">
                            <td class="date-cell">{{ row.date }}</td>
                            <td>{{ row.time }}</td>
                            <td><strong>{{ row.reference }}</strong></td>
                            <td>
                                <strong>{{ row.service }}</strong>
                                <small>{{ row.service_type }}</small>
                            </td>
                            <td>
                                <strong>{{ row.start_point }}</strong>
                                <small>→ {{ row.destination }}</small>
                            </td>
                            <td>
                                <strong>{{ row.vehicle }}</strong>
                                <small>{{ row.vehicle_places }} places</small>
                            </td>
                            <td>{{ row.pax }}</td>
                            <td class="clients-cell">{{ row.clients?.length ? row.clients.join(', ') : '-' }}</td>
                            <td>
                                <strong>{{ row.supplier_vehicle }}</strong>
                                <small>{{ row.supplier_client }}</small>
                            </td>
                            <td>{{ row.guide }}</td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="10" class="empty-cell">Sélectionnez un driver et une période pour afficher les circuits.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<style scoped>
.prime-page { display: flex; flex-direction: column; gap: 22px; color: #111827; }
.prime-hero { min-height: 176px; border-radius: 28px; padding: 34px; display: flex; align-items: center; justify-content: space-between; gap: 24px; background: linear-gradient(135deg, #111827 0%, #7f1d1d 48%, #dc2626 100%); color: #fff; box-shadow: 0 22px 60px rgba(153, 27, 31, .22); }
.hero-copy span { text-transform: uppercase; letter-spacing: .16em; font-weight: 950; color: rgba(255,255,255,.72); }
.hero-copy h1 { margin: 8px 0; color: #fff; font-size: 42px; font-weight: 950; }
.hero-copy p { margin: 0; max-width: 780px; color: rgba(255,255,255,.82); font-weight: 750; }
.hero-badge { min-width: 170px; min-height: 120px; border-radius: 24px; display: grid; place-items: center; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.18); box-shadow: inset 0 1px 0 rgba(255,255,255,.16); }
.hero-badge i { font-size: 30px; }
.hero-badge strong { font-size: 36px; line-height: 1; }
.hero-badge span { font-weight: 900; color: rgba(255,255,255,.78); }
.filter-card { display: grid; grid-template-columns: minmax(320px, 1.35fr) minmax(180px, .65fr) minmax(180px, .65fr) 160px 110px; gap: 14px; align-items: end; padding: 20px; border-radius: 24px; background: #fff; box-shadow: 0 16px 40px rgba(15,23,42,.08); }
.filter-card label, .driver-field { display: flex; flex-direction: column; gap: 8px; color: #64748b; font-weight: 900; }
.filter-card input { height: 48px; border: 1px solid #dbe2ea; border-radius: 14px; padding: 0 14px; font-weight: 850; outline: none; }
.filter-card input:focus { border-color: #c1121f; box-shadow: 0 0 0 4px rgba(193,18,31,.08); }
.search-btn, .reset-btn { height: 48px; border: 0; border-radius: 16px; font-weight: 900; display: inline-flex; align-items: center; justify-content: center; gap: 10px; }
.search-btn { background: #c1121f; color: #fff; }
.reset-btn { background: #f1f5f9; color: #475569; }
.summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.summary-card { border-radius: 22px; padding: 20px; background: #fff; box-shadow: 0 16px 36px rgba(15,23,42,.07); border: 1px solid #edf2f7; }
.summary-card span { display: block; color: #64748b; font-weight: 900; margin-bottom: 8px; }
.summary-card strong { font-size: 26px; font-weight: 950; word-break: break-word; }
.summary-card.dark { background: #111827; color: #fff; }
.summary-card.dark span { color: #cbd5e1; }
.table-card { overflow: hidden; border-radius: 26px; background: #fff; box-shadow: 0 20px 55px rgba(15,23,42,.09); }
.table-head { padding: 24px 26px; display: flex; justify-content: space-between; align-items: center; gap: 16px; border-bottom: 1px solid #f1f5f9; }
.table-head h2 { margin: 0; font-size: 24px; font-weight: 950; }
.table-head p { margin: 6px 0 0; color: #64748b; font-weight: 750; }
.period-chip { padding: 12px 16px; border-radius: 999px; background: #fff1f2; color: #991b1f; border: 1px solid #fecdd3; font-weight: 950; white-space: nowrap; }
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 1320px; }
th { background: #fff1f2; color: #991b1f; padding: 16px 14px; text-align: left; font-weight: 950; border-bottom: 1px solid #fecdd3; }
td { padding: 16px 14px; border-bottom: 1px solid #edf2f7; vertical-align: middle; font-weight: 750; }
tbody tr:hover td { background: #fffafa; }
td strong { display: block; color: #111827; font-weight: 950; }
td small { display: block; margin-top: 4px; color: #64748b; font-weight: 750; }
.date-cell { color: #991b1f; font-weight: 950; white-space: nowrap; }
.clients-cell { max-width: 280px; color: #334155; }
.empty-cell { text-align: center; color: #94a3b8; font-weight: 900; padding: 54px; }
@media (max-width: 1200px) { .filter-card, .summary-grid { grid-template-columns: 1fr 1fr; } .prime-hero { flex-direction: column; align-items: flex-start; } }
</style>
