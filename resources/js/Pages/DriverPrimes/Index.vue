<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import AppShell from "@/Layouts/AppShell.vue";
import { openWithLoader } from "@/utils/openWithLoader";

defineOptions({ layout: AppShell });

const props = defineProps({
    typeServices: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    rows: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
});

const filters = reactive({
    type_service_ids: (props.filters.type_service_ids || []).map(String),
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});
const typeSearch = ref("");
const typePickerOpen = ref(false);
const rows = computed(() => props.rows || []);
const selectedTypes = computed(() => props.typeServices.filter((type) => filters.type_service_ids.includes(String(type.id))));
const visibleTypes = computed(() => {
    const term = typeSearch.value.trim().toLowerCase();
    return props.typeServices.filter((type) => !term || type.designation.toLowerCase().includes(term));
});

const toggleType = (id) => {
    const value = String(id);
    filters.type_service_ids = filters.type_service_ids.includes(value)
        ? filters.type_service_ids.filter((item) => item !== value)
        : [...filters.type_service_ids, value];
};
const search = () => router.get(route("driver-primes.index"), { ...filters }, { preserveScroll: true });
const reset = () => router.get(route("driver-primes.index"), {}, { preserveScroll: true });
const generatePdf = () => {
    const params = new URLSearchParams({ date_from: filters.date_from, date_to: filters.date_to });
    filters.type_service_ids.forEach((id) => params.append("type_service_ids[]", id));
    openWithLoader(`${route("driver-primes.pdf")}?${params.toString()}`, "Génération du PDF...");
};
</script>

<template>
    <Head title="Rapport des prestations par chauffeur" />
    <div class="prime-page">
        <section class="prime-hero">
            <div class="hero-copy">
                <span>Rapport opérationnel</span><h1>Rapport des prestations par chauffeur</h1>
                <p>Analysez les prestations par types de service et période, puis exportez un relevé détaillé par chauffeur.</p>
            </div>
            <div class="hero-badge"><i class="material-icons-outlined">workspace_premium</i><strong>{{ summary.count || 0 }}</strong><span>prestations</span></div>
        </section>

        <section class="filter-card">
            <div class="type-field">
                <label>Types de service</label>
                <div class="multi-select" :class="{ open: typePickerOpen }">
                    <button type="button" class="multi-trigger" @click="typePickerOpen = !typePickerOpen">
                        <span v-if="!selectedTypes.length" class="placeholder">Sélectionner un ou plusieurs types...</span>
                        <span v-else class="selected-count">{{ selectedTypes.length }} type{{ selectedTypes.length > 1 ? 's' : '' }} sélectionné{{ selectedTypes.length > 1 ? 's' : '' }}</span>
                        <i class="bx bx-chevron-down"></i>
                    </button>
                    <div v-if="typePickerOpen" class="multi-menu">
                        <div class="multi-search"><i class="bx bx-search"></i><input v-model="typeSearch" type="search" placeholder="Rechercher un type de service..." /></div>
                        <div class="multi-options">
                            <label v-for="type in visibleTypes" :key="type.id" class="multi-option">
                                <input type="checkbox" :checked="filters.type_service_ids.includes(String(type.id))" @change="toggleType(type.id)" />
                                <span>{{ type.designation }}</span><i v-if="filters.type_service_ids.includes(String(type.id))" class="bx bx-check"></i>
                            </label>
                            <div v-if="!visibleTypes.length" class="multi-empty">Aucun type trouvé</div>
                        </div>
                    </div>
                </div>
                <div v-if="selectedTypes.length" class="type-tags">
                    <button v-for="type in selectedTypes" :key="type.id" type="button" class="type-tag" @click="toggleType(type.id)">{{ type.designation }} <i class="bx bx-x"></i></button>
                </div>
            </div>
            <label><span>Date début</span><input v-model="filters.date_from" type="date" /></label>
            <label><span>Date fin</span><input v-model="filters.date_to" type="date" /></label>
            <button type="button" class="search-btn" :disabled="!filters.type_service_ids.length" @click="search"><i class="material-icons-outlined">search</i>Chercher</button>
            <button type="button" class="reset-btn" @click="reset">Réinitialiser</button>
        </section>

        <section class="summary-grid">
            <div class="summary-card dark"><span>Chauffeurs</span><strong>{{ summary.drivers_count || 0 }}</strong></div>
            <div class="summary-card"><span>Prestations</span><strong>{{ summary.count || 0 }}</strong></div>
            <div class="summary-card"><span>Jours concernés</span><strong>{{ summary.unique_days || 0 }}</strong></div>
            <div class="summary-card"><span>Dossiers</span><strong>{{ summary.unique_references || 0 }}</strong></div>
        </section>

        <section class="table-card">
            <div class="table-head">
                <div><h2>Détail des prestations</h2><p>Résultats triés par chauffeur, date et heure.</p></div>
                <div class="table-actions"><span class="period-chip">{{ filters.date_from || '-' }} → {{ filters.date_to || '-' }}</span><button v-if="rows.length" type="button" class="pdf-btn" @click="generatePdf"><i class="bx bxs-file-pdf"></i>Générer PDF</button></div>
            </div>
            <div class="table-responsive"><table><thead><tr>
                <th>Chauffeur</th><th>Date</th><th>Heure</th><th>Référence</th><th>Service</th><th>Type de service</th><th>Trajet</th><th>Véhicule</th><th>PAX</th><th>Clients</th><th>Fournisseurs</th><th>Guide</th>
            </tr></thead><tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td><strong class="driver-name" :class="{ missing: !row.driver_id }">{{ row.driver }}</strong></td><td class="date-cell">{{ row.date }}</td><td>{{ row.time }}</td><td><strong>{{ row.reference }}</strong></td><td><strong>{{ row.service }}</strong></td><td><span class="service-type-chip">{{ row.service_type }}</span></td>
                    <td><strong>{{ row.start_point }}</strong><small>→ {{ row.destination }}</small></td><td>{{ row.vehicle }}</td><td>{{ row.pax }}</td><td class="clients-cell">{{ row.clients?.length ? row.clients.join(', ') : '-' }}</td><td><strong>{{ row.supplier_vehicle }}</strong><small>{{ row.supplier_client }}</small></td><td>{{ row.guide }}</td>
                </tr>
                <tr v-if="!rows.length"><td colspan="12" class="empty-cell">Sélectionnez au moins un type de service et une période pour afficher les prestations.</td></tr>
            </tbody></table></div>
        </section>
    </div>
</template>

<style scoped>
.prime-page{display:flex;flex-direction:column;gap:22px;color:#111827}.prime-hero{min-height:176px;border-radius:28px;padding:34px;display:flex;align-items:center;justify-content:space-between;gap:24px;background:linear-gradient(135deg,#111827 0%,#7f1d1d 48%,#dc2626 100%);color:#fff;box-shadow:0 22px 60px rgba(153,27,31,.22)}.hero-copy span{text-transform:uppercase;letter-spacing:.16em;font-weight:950;color:rgba(255,255,255,.72)}.hero-copy h1{margin:8px 0;color:#fff;font-size:42px;font-weight:950}.hero-copy p{margin:0;max-width:780px;color:rgba(255,255,255,.82);font-weight:750}.hero-badge{min-width:170px;min-height:120px;border-radius:24px;display:grid;place-items:center;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18)}.hero-badge i{font-size:30px}.hero-badge strong{font-size:36px;line-height:1}.hero-badge span{font-weight:900;color:rgba(255,255,255,.78)}
.filter-card{display:grid;grid-template-columns:minmax(360px,1.5fr) minmax(180px,.65fr) minmax(180px,.65fr) 150px 120px;gap:14px;align-items:start;padding:20px;border-radius:24px;background:#fff;box-shadow:0 16px 40px rgba(15,23,42,.08)}.filter-card>label,.type-field{display:flex;flex-direction:column;gap:8px;color:#64748b;font-weight:900}.filter-card input[type=date]{height:48px;border:1px solid #dbe2ea;border-radius:14px;padding:0 14px;font-weight:850;outline:none}.search-btn,.reset-btn,.pdf-btn{height:48px;border:0;border-radius:16px;font-weight:900;display:inline-flex;align-items:center;justify-content:center;gap:9px}.search-btn{background:#c1121f;color:#fff}.search-btn:disabled{opacity:.45;cursor:not-allowed}.reset-btn{background:#f1f5f9;color:#475569}
.multi-select{position:relative}.multi-trigger{width:100%;height:48px;padding:0 14px;display:flex;align-items:center;justify-content:space-between;border:1px solid #dbe2ea;border-radius:14px;background:#fff;color:#334155;font-weight:850}.multi-select.open .multi-trigger{border-color:#c1121f;box-shadow:0 0 0 4px rgba(193,18,31,.08)}.placeholder{color:#94a3b8}.multi-menu{position:absolute;z-index:60;top:calc(100% + 8px);left:0;right:0;padding:10px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 24px 60px rgba(15,23,42,.2)}.multi-search{height:42px;display:flex;align-items:center;gap:8px;padding:0 11px;border:1px solid #e2e8f0;border-radius:12px}.multi-search input{min-width:0;flex:1;border:0;outline:0}.multi-options{max-height:240px;overflow:auto;margin-top:8px}.multi-option{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:10px;padding:10px;border-radius:10px;cursor:pointer}.multi-option:hover{background:#f8fafc}.multi-option input{width:17px;height:17px;accent-color:#c1121f}.multi-empty{padding:24px;text-align:center;color:#94a3b8}.type-tags{display:flex;flex-wrap:wrap;gap:7px}.type-tag{display:inline-flex;align-items:center;gap:5px;padding:6px 9px;border:1px solid #fecdd3;border-radius:999px;background:#fff1f2;color:#9f1239;font-size:12px;font-weight:900}
.summary-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.summary-card{border-radius:22px;padding:20px;background:#fff;box-shadow:0 16px 36px rgba(15,23,42,.07);border:1px solid #edf2f7}.summary-card span{display:block;color:#64748b;font-weight:900;margin-bottom:8px}.summary-card strong{font-size:26px;font-weight:950}.summary-card small{display:block;margin-top:5px;color:#b45309;font-weight:850}.summary-card.dark{background:#111827;color:#fff}.summary-card.dark span{color:#cbd5e1}
.table-card{overflow:hidden;border-radius:26px;background:#fff;box-shadow:0 20px 55px rgba(15,23,42,.09)}.table-head{padding:24px 26px;display:flex;justify-content:space-between;align-items:center;gap:16px;border-bottom:1px solid #f1f5f9}.table-head h2{margin:0;font-size:24px;font-weight:950}.table-head p{margin:6px 0 0;color:#64748b;font-weight:750}.table-actions{display:flex;align-items:center;gap:10px}.period-chip{padding:12px 16px;border-radius:999px;background:#fff1f2;color:#991b1f;border:1px solid #fecdd3;font-weight:950;white-space:nowrap}.pdf-btn{padding:0 18px;background:#111827;color:#fff}.pdf-btn i{font-size:20px;color:#fda4af}.table-responsive{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:1650px}th{background:#fff1f2;color:#991b1f;padding:16px 14px;text-align:left;font-weight:950;border-bottom:1px solid #fecdd3}td{padding:16px 14px;border-bottom:1px solid #edf2f7;vertical-align:middle;font-weight:750}tbody tr:hover td{background:#fffafa}td strong{display:block;color:#111827;font-weight:950}td small{display:block;margin-top:4px;color:#64748b;font-weight:750}.driver-name{white-space:nowrap}.driver-name.missing{color:#b45309}.service-type-chip{display:inline-flex;padding:6px 9px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-weight:900;white-space:nowrap}.date-cell{color:#991b1f;font-weight:950;white-space:nowrap}.clients-cell{max-width:280px;color:#334155}.empty-cell{text-align:center;color:#94a3b8;font-weight:900;padding:54px}
@media(max-width:1200px){.filter-card,.summary-grid{grid-template-columns:1fr 1fr}.prime-hero{flex-direction:column;align-items:flex-start}.table-head{align-items:flex-start;flex-direction:column}.table-actions{flex-wrap:wrap}}@media(max-width:700px){.filter-card,.summary-grid{grid-template-columns:1fr}.hero-copy h1{font-size:32px}.prime-hero{padding:24px}.table-actions{width:100%}.pdf-btn{flex:1}}
</style>
