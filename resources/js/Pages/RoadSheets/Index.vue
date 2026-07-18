<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    plannings: { type: Object, default: () => ({ data: [] }) },
    filters: { type: Object, default: () => ({}) },
    drivers: { type: Array, default: () => [] },
});

const state = reactive({
    search: props.filters.search || "",
    driver_id: props.filters.driver_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
    status: props.filters.status || "",
    sort: props.filters.sort || "start_date",
    direction: props.filters.direction || "desc",
});
const selected = ref(null);
const rows = computed(() => props.plannings.data || []);
const summary = (planning) => planning.road_sheet_summary || {};
const ymd = (value) => (value ? String(value).slice(0, 10) : "-");
const destination = (planning) => planning.destination?.city || planning.destination?.name || "-";
const service = (planning) => planning.service?.designation || planning.type_service || "-";
const number = (value) => new Intl.NumberFormat("fr-FR").format(Number(value || 0));
const money = (value) => `${Number(value || 0).toFixed(2)} DH`;

const request = () => router.get(route("road-sheets.index"), { ...state }, { preserveState: true, replace: true });
const reset = () => {
    Object.assign(state, { search: "", driver_id: "", date_from: "", date_to: "", status: "", sort: "start_date", direction: "desc" });
    request();
};
const quickStatus = (status) => { state.status = status; request(); };
const openDays = (planning) => { selected.value = planning; };
const closeDays = () => { selected.value = null; };
</script>

<template>
    <Head title="Liste des fiches de route" />
    <div class="page">
        <div class="container-fluid py-4">
            <header class="hero">
                <div class="hero-icon"><i class="bx bx-trip"></i></div>
                <div><small>MD TOURS</small><h1>Liste des fiches de route</h1><p>Suivi des journées, des kilomètres et de l’avancement des chauffeurs.</p></div>
            </header>

            <section class="filters-card">
                <div class="quick-filters">
                    <button :class="{ active: state.status === '' }" @click="quickStatus('')">Toutes</button>
                    <button class="pending" :class="{ active: state.status === 'pending' }" @click="quickStatus('pending')">À compléter</button>
                    <button class="partial" :class="{ active: state.status === 'partial' }" @click="quickStatus('partial')">Partielles</button>
                    <button class="completed" :class="{ active: state.status === 'completed' }" @click="quickStatus('completed')">Renseignées</button>
                </div>
                <div class="filter-grid">
                    <label class="search"><span>Recherche</span><div><i class="bx bx-search"></i><input v-model="state.search" placeholder="Référence, chauffeur, client, service, destination..." @keyup.enter="request" /></div></label>
                    <label><span>Chauffeur</span><select v-model="state.driver_id"><option value="">Tous</option><option v-for="driver in drivers" :key="driver.id" :value="driver.id">{{ driver.name }}</option></select></label>
                    <label><span>Début à partir du</span><input v-model="state.date_from" type="date" /></label>
                    <label><span>Fin jusqu’au</span><input v-model="state.date_to" type="date" /></label>
                    <label><span>Trier par</span><select v-model="state.sort"><option value="start_date">Date de début</option><option value="end_date">Date de fin</option><option value="driver">Chauffeur</option><option value="remaining_days">Jours restants</option><option value="real_total_distance">Distance réelle</option><option value="status">Statut</option></select></label>
                    <label><span>Ordre</span><select v-model="state.direction"><option value="desc">Décroissant</option><option value="asc">Croissant</option></select></label>
                    <div class="filter-actions"><button class="apply" @click="request"><i class="bx bx-filter-alt"></i> Appliquer</button><button class="reset" @click="reset">Réinitialiser</button></div>
                </div>
            </section>

            <section class="table-card">
                <div class="table-heading"><div><h2>Fiches opérationnelles</h2><p>{{ plannings.total || 0 }} résultat(s)</p></div></div>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Début</th><th>Fin</th><th>Référence</th><th>Service</th><th>Driver</th><th>Départ</th><th>Destination</th><th>Jours</th><th>Kilométrage</th><th>Statut</th><th>Actions</th></tr></thead>
                        <tbody>
                            <tr v-for="planning in rows" :key="planning.id">
                                <td class="date">{{ ymd(planning.date_du) }}</td>
                                <td class="date">{{ ymd(planning.date_au || planning.date_du) }}</td>
                                <td><strong class="reference">{{ planning.ref_dossier || '-' }}</strong></td>
                                <td><span class="wrap">{{ service(planning) }}</span></td>
                                <td><span class="driver"><i class="bx bx-user"></i>{{ planning.driver?.name || '-' }}</span></td>
                                <td><span class="route-text"><i class="bx bx-map"></i>{{ planning.point_depart || '-' }}</span></td>
                                <td><span class="route-text"><i class="bx bx-map-pin"></i>{{ destination(planning) }}</span></td>
                                <td class="days-cell">
                                    <strong>{{ summary(planning).completed_days || 0 }} / {{ summary(planning).total_days || 1 }} remplis</strong>
                                    <small>{{ summary(planning).remaining_days || 0 }} restant(s)</small>
                                    <div class="progress"><span :style="{ width: `${summary(planning).progress || 0}%` }"></span></div>
                                </td>
                                <td class="km-cell"><span>Circuit <b>{{ number(summary(planning).circuit_distance) }} km</b></span><span>Avant <b>{{ number(summary(planning).pre_service_distance) }} km</b></span><span class="real">Réel <b>{{ number(summary(planning).real_total_distance) }} km</b></span></td>
                                <td><span class="status" :class="summary(planning).global_status">{{ summary(planning).global_status_label || 'À compléter' }}</span></td>
                                <td><div class="actions"><button title="Voir toutes les journées" @click="openDays(planning)"><i class="bx bx-list-ul"></i><span>Voir les jours</span></button><Link :href="route('road-sheets.show', planning.id)" title="Ouvrir la fiche"><i class="bx bx-edit"></i></Link></div></td>
                            </tr>
                            <tr v-if="!rows.length"><td colspan="11" class="empty">Aucune fiche ne correspond aux critères.</td></tr>
                        </tbody>
                    </table>
                </div>
                <nav v-if="plannings.links?.length" class="pagination"><Link v-for="link in plannings.links" :key="link.label" :href="link.url || '#'" :class="{ active: link.active, disabled: !link.url }" v-html="link.label" /></nav>
            </section>
        </div>

        <div v-if="selected" class="modal-backdrop" @click.self="closeDays">
            <section class="days-modal" role="dialog" aria-modal="true">
                <header><div><small>{{ selected.ref_dossier || 'Fiche de route' }}</small><h2>Journées du circuit</h2><p>{{ ymd(selected.date_du) }} → {{ ymd(selected.date_au || selected.date_du) }}</p></div><button aria-label="Fermer" @click="closeDays"><i class="bx bx-x"></i></button></header>
                <div class="modal-body">
                    <article class="pre-service"><div class="section-title"><i class="bx bx-navigation"></i><div><h3>Déplacement avant service</h3><p>Séparé des journées du circuit</p></div></div><div class="pre-grid"><div><span>Lieu initial</span><b>{{ summary(selected).pre_service?.origin || '-' }}</b></div><div><span>Départ officiel</span><b>{{ summary(selected).pre_service?.official_start || '-' }}</b></div><div><span>Compteur départ</span><b>{{ summary(selected).pre_service?.odometer_start ?? '-' }}<template v-if="summary(selected).pre_service?.odometer_start != null"> km</template></b></div><div><span>Compteur arrivée</span><b>{{ summary(selected).pre_service?.odometer_end ?? '-' }}<template v-if="summary(selected).pre_service?.odometer_end != null"> km</template></b></div><div><span>Distance avant service</span><b>{{ number(summary(selected).pre_service_distance) }} km</b></div><div><span>Note</span><b>{{ summary(selected).pre_service?.note || '-' }}</b></div></div></article>
                    <div class="days-list">
                        <article v-for="day in summary(selected).days" :key="day.day_number" class="day-card" :class="{ missing: !day.completed }">
                            <div class="day-head"><div><strong>Jour {{ day.day_number }}</strong><span>{{ ymd(day.date) }}</span></div><span class="day-status" :class="{ done: day.completed }">{{ day.status_label }}</span></div>
                            <div class="day-data"><div><span>Km départ</span><b>{{ day.departure_kms ?? '-' }}</b></div><div><span>Km arrivée</span><b>{{ day.arrival_kms ?? '-' }}</b></div><div><span>Distance</span><b>{{ number(day.distance) }} km</b></div><div><span>Carburant</span><b>{{ money(day.gasoline) }}</b></div><div><span>Jawaz</span><b>{{ money(day.jawaz) }}</b></div><div><span>Autres dépenses</span><b>{{ money(day.other_expenses) }}</b></div><div class="notes"><span>Notes</span><b>{{ day.notes || '-' }}</b></div></div>
                        </article>
                    </div>
                </div>
                <footer><Link :href="route('road-sheets.show', selected.id)"><i class="bx bx-edit"></i> Ouvrir la fiche</Link><button @click="closeDays">Fermer</button></footer>
            </section>
        </div>
    </div>
</template>

<style scoped>
.page{min-height:100vh;background:#f6f7fb;color:#172033}.hero{padding:22px 24px;border-radius:22px;background:linear-gradient(135deg,#8d0c18,#d32332);color:#fff;display:flex;align-items:center;gap:16px;box-shadow:0 14px 30px #991b1b26}.hero *{color:#fff}.hero-icon{width:52px;height:52px;border-radius:16px;background:#ffffff22;display:grid;place-items:center;font-size:27px}.hero small{font-size:10px;font-weight:900;letter-spacing:.18em}.hero h1{margin:2px 0;font-size:25px}.hero p{margin:0;opacity:.86}.filters-card,.table-card{margin-top:16px;border:1px solid #e5e7eb;border-radius:20px;background:#fff;box-shadow:0 8px 24px #0f172a0a}.filters-card{padding:16px}.quick-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}.quick-filters button{border:1px solid #e2e8f0;border-radius:99px;background:#f8fafc;padding:7px 13px;color:#475569;font-size:11px;font-weight:900}.quick-filters button.active{background:#172033;color:#fff;border-color:#172033}.quick-filters .pending.active{background:#d97706;border-color:#d97706}.quick-filters .partial.active{background:#6d28d9;border-color:#6d28d9}.quick-filters .completed.active{background:#15803d;border-color:#15803d}.filter-grid{display:grid;grid-template-columns:2fr repeat(5,1fr) auto;gap:10px;align-items:end}.filter-grid label>span{display:block;margin-bottom:5px;color:#64748b;font-size:9px;font-weight:900;text-transform:uppercase}.filter-grid input,.filter-grid select{width:100%;height:40px;border:1px solid #dfe3ea;border-radius:11px;background:#f8fafc;padding:0 10px;color:#172033;font-size:11px;font-weight:700}.search>div{position:relative}.search i{position:absolute;left:11px;top:12px;color:#94a3b8}.search input{padding-left:32px}.filter-actions{display:flex;gap:6px}.filter-actions button{height:40px;border:0;border-radius:11px;padding:0 12px;font-size:10px;font-weight:900}.apply{background:#c51625;color:#fff}.reset{background:#eef2f7;color:#475569}.table-card{overflow:hidden}.table-heading{padding:16px 18px;border-bottom:1px solid #edf0f4}.table-heading h2{margin:0;font-size:17px}.table-heading p{margin:3px 0 0;color:#64748b;font-size:11px}table{width:100%;min-width:1450px;border-collapse:collapse}th{padding:11px 10px;background:#f8fafc;color:#64748b;font-size:9px;text-align:left;text-transform:uppercase;letter-spacing:.05em}td{padding:12px 10px;border-top:1px solid #edf0f4;vertical-align:top;font-size:11px}tbody tr:hover{background:#fffafa}.date,.reference{white-space:nowrap;font-weight:900}.reference{color:#b91c1c}.wrap,.route-text{display:block;max-width:150px;overflow-wrap:anywhere}.driver,.route-text{display:flex;gap:5px;align-items:flex-start}.driver i,.route-text i{color:#c51625;font-size:14px}.days-cell{min-width:125px}.days-cell strong,.days-cell small{display:block}.days-cell small{margin-top:3px;color:#64748b}.progress{height:5px;margin-top:7px;border-radius:99px;background:#e5e7eb;overflow:hidden}.progress span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#c51625,#16a34a)}.km-cell{min-width:135px}.km-cell span{display:flex;justify-content:space-between;gap:8px;margin-bottom:3px;color:#64748b}.km-cell b{color:#172033}.km-cell .real{padding-top:4px;border-top:1px dashed #dfe3ea;color:#15803d}.status{display:inline-flex;max-width:130px;padding:6px 9px;border-radius:99px;font-size:9px;font-weight:900;line-height:1.25}.status.pending{background:#fff3d6;color:#b45309}.status.partial{background:#ede9fe;color:#6d28d9}.status.completed{background:#dcfce7;color:#15803d}.actions{display:flex;gap:6px}.actions button,.actions a{min-height:34px;border:0;border-radius:10px;background:#fff0f1;color:#b91c1c;padding:0 9px;display:inline-flex;align-items:center;gap:5px;font-size:9px;font-weight:900;text-decoration:none}.actions a{min-width:34px;justify-content:center}.empty{padding:40px;text-align:center;color:#64748b}.pagination{padding:14px;display:flex;justify-content:center;gap:5px}.pagination a{min-width:34px;height:34px;padding:0 8px;border:1px solid #e2e8f0;border-radius:9px;color:#475569;display:grid;place-items:center;text-decoration:none;font-size:10px}.pagination a.active{background:#c51625;color:#fff;border-color:#c51625}.pagination a.disabled{pointer-events:none;opacity:.4}.modal-backdrop{position:fixed;z-index:3000;inset:0;padding:24px;background:#0f172ab8;display:grid;place-items:center}.days-modal{width:min(1050px,100%);max-height:92vh;border-radius:22px;background:#f8fafc;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 70px #0005}.days-modal>header{padding:17px 20px;background:linear-gradient(135deg,#8d0c18,#d32332);display:flex;justify-content:space-between;align-items:center}.days-modal>header *{color:#fff}.days-modal header small{font-weight:900}.days-modal h2{margin:2px 0;font-size:20px}.days-modal header p{margin:0;opacity:.85}.days-modal header button{width:38px;height:38px;border:0;border-radius:12px;background:#ffffff20;font-size:25px}.modal-body{padding:16px;overflow:auto}.pre-service{padding:14px;border:1px solid #ddd6fe;border-radius:17px;background:#faf8ff}.section-title{display:flex;align-items:center;gap:9px}.section-title i{width:35px;height:35px;border-radius:11px;background:#ede9fe;color:#6d28d9;display:grid;place-items:center;font-size:19px}.section-title h3,.section-title p{margin:0}.section-title h3{font-size:14px}.section-title p{color:#64748b;font-size:9px}.pre-grid,.day-data{margin-top:12px;display:grid;grid-template-columns:repeat(6,1fr);gap:8px}.pre-grid>div,.day-data>div{padding:9px;border-radius:11px;background:#fff}.pre-grid span,.pre-grid b,.day-data span,.day-data b{display:block}.pre-grid span,.day-data span{color:#64748b;font-size:8px;font-weight:900;text-transform:uppercase}.pre-grid b,.day-data b{margin-top:3px;font-size:10px;overflow-wrap:anywhere}.days-list{margin-top:12px;display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.day-card{border:1px solid #bbf7d0;border-radius:16px;background:#fff;overflow:hidden}.day-card.missing{border-color:#fed7aa;background:#fffbeb}.day-head{padding:10px 12px;border-bottom:1px solid #edf0f4;display:flex;align-items:center;justify-content:space-between}.day-head strong,.day-head span{display:block}.day-head>div>span{color:#64748b;font-size:9px}.day-status{padding:5px 8px;border-radius:99px;background:#ffedd5;color:#c2410c;font-size:8px;font-weight:900}.day-status.done{background:#dcfce7;color:#15803d}.day-data{padding:0 10px 10px;grid-template-columns:repeat(3,1fr)}.day-data .notes{grid-column:1/-1}.days-modal>footer{padding:12px 16px;border-top:1px solid #e2e8f0;background:#fff;display:flex;justify-content:flex-end;gap:8px}.days-modal footer a,.days-modal footer button{height:38px;border:0;border-radius:11px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:900;text-decoration:none}.days-modal footer a{background:#c51625;color:#fff}.days-modal footer button{background:#eef2f7;color:#475569}
@media(max-width:1200px){.filter-grid{grid-template-columns:repeat(3,1fr)}.search{grid-column:span 2}.filter-actions{grid-column:span 3;justify-content:flex-end}}
@media(max-width:760px){.container-fluid{padding-left:10px!important;padding-right:10px!important}.hero{padding:17px}.hero h1{font-size:20px}.filter-grid{grid-template-columns:1fr 1fr}.search,.filter-actions{grid-column:1/-1}.filter-actions button{flex:1}.modal-backdrop{padding:8px}.days-modal{max-height:96vh}.pre-grid{grid-template-columns:repeat(2,1fr)}.days-list{grid-template-columns:1fr}.day-data{grid-template-columns:repeat(2,1fr)}.actions button span{display:none}}
</style>
