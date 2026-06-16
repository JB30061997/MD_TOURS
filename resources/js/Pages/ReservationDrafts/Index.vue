<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";
import SearchSelect from "@/Components/SearchSelect.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    drafts: Object,
    filters: Object,
    counts: Object,
    supplierVehicules: { type: Array, default: () => [] },
    supplierClients: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },
});

const page = usePage();

const draftForms = reactive({});
const selectedMail = ref(null);

const normalizePayload = (draft) => ({
    date_du: draft.parsed_payload?.date_du || "",
    date_au: draft.parsed_payload?.date_au || "",
    ref_dossier: draft.parsed_payload?.ref_dossier || "",
    nbr_personnes: draft.parsed_payload?.nbr_personnes || "",
    flight: draft.parsed_payload?.flight || "",
    heure: draft.parsed_payload?.heure || "",
    point_depart: draft.parsed_payload?.point_depart || "",
    site: draft.parsed_payload?.site || "",
    destination_name: draft.parsed_payload?.destination_name || "",
    service_name: draft.parsed_payload?.service_name || "",
    passenger_names: draft.parsed_payload?.passenger_names || "",
    supplier_client_name: draft.parsed_payload?.supplier_client_name || "",
    service_id: draft.parsed_payload?.service_id || "",
    supplier_client_id: draft.parsed_payload?.supplier_client_id || "",
    supplier_vehicule_id: draft.parsed_payload?.supplier_vehicule_id || "",
    supplier_vehicule_name: draft.parsed_payload?.supplier_vehicule_name || "",
    driver_id: draft.parsed_payload?.driver_id || "",
    driver_name: draft.parsed_payload?.driver_name || "",
    guide_id: draft.parsed_payload?.guide_id || "",
    guide_name: draft.parsed_payload?.guide_name || "",
    destination_id: draft.parsed_payload?.destination_id || "",
    vehicule_id: draft.parsed_payload?.vehicule_id || "",
    vehicule_name: draft.parsed_payload?.vehicule_name || "",
    budget: draft.parsed_payload?.budget || "",
    supplier_price: draft.parsed_payload?.supplier_price || "",
    notes: draft.validation_notes || "",
});

const hydrateForms = () => {
    (props.drafts?.data || []).forEach((draft) => {
        if (!draftForms[draft.id]) {
            draftForms[draft.id] = normalizePayload(draft);
        }
    });
};

hydrateForms();

watch(() => props.drafts?.data, hydrateForms);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: flash.success,
                showConfirmButton: false,
                timer: 2600,
                timerProgressBar: true,
            });
        }

        if (flash?.error) {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: flash.error,
                confirmButtonColor: "#c1121f",
            });
        }
    },
    { immediate: true },
);

const statusTabs = computed(() => [
    { key: "pending", label: "À valider", count: props.counts?.pending || 0 },
    { key: "validated", label: "Validées", count: props.counts?.validated || 0 },
    { key: "rejected", label: "Ignorées", count: props.counts?.rejected || 0 },
    { key: "all", label: "Toutes", count: (props.counts?.pending || 0) + (props.counts?.validated || 0) + (props.counts?.rejected || 0) },
]);

const goStatus = (status) => {
    router.get(
        route("reservation-drafts.index"),
        { status },
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const validateDraft = (draft) => {
    router.post(route("reservation-drafts.validate", draft.id), draftForms[draft.id], {
        preserveScroll: true,
    });
};

const rejectDraft = (draft) => {
    router.post(
        route("reservation-drafts.reject", draft.id),
        { validation_notes: draftForms[draft.id]?.notes || "" },
        { preserveScroll: true },
    );
};

const openMailModal = (draft) => {
    selectedMail.value = draft;
};

const closeMailModal = () => {
    selectedMail.value = null;
};

const mailBody = computed(() => {
    const message = selectedMail.value?.mail_message;

    return message?.body_text || (message?.body_html || "").replace(/<[^>]*>/g, " ") || "";
});
</script>

<template>
    <Head title="Planning TH" />

    <div class="drafts-page">
        <div class="drafts-hero">
            <div>
                <span>MD TOURS MAIL AGENT</span>
                <h1>Plannings TH détectés</h1>
                <p>
                    Les réservations trouvées dans les emails restent ici en attente.
                    Vérifiez les champs, puis validez pour créer le planning réel.
                </p>
            </div>
            <Link :href="route('mailbox.index')" class="hero-btn">
                <i class="bx bx-envelope"></i>
                Mailbox
            </Link>
        </div>

        <div class="status-tabs">
            <button
                v-for="tab in statusTabs"
                :key="tab.key"
                type="button"
                :class="{ active: (filters?.status || 'pending') === tab.key }"
                @click="goStatus(tab.key)"
            >
                {{ tab.label }}
                <strong>{{ tab.count }}</strong>
            </button>
        </div>

        <div v-if="drafts?.data?.length" class="draft-list">
            <article v-for="draft in drafts.data" :key="draft.id" class="draft-card">
                <header class="draft-head">
                    <div>
                        <div class="source-line">
                            <span class="status-dot"></span>
                            <strong>{{ draft.source_from || "Email inconnu" }}</strong>
                            <small>{{ draft.created_at }}</small>
                        </div>
                        <h2>{{ draft.source_subject || "Sans objet" }}</h2>
                    </div>

                    <div class="confidence">
                        <span>Confiance</span>
                        <strong>{{ draft.confidence }}%</strong>
                    </div>
                </header>

                <div class="draft-grid">
                    <label>
                        <span>Date début</span>
                        <input v-model="draftForms[draft.id].date_du" type="date" />
                    </label>
                    <label>
                        <span>Date fin</span>
                        <input v-model="draftForms[draft.id].date_au" type="date" />
                    </label>
                    <label>
                        <span>Référence</span>
                        <input v-model="draftForms[draft.id].ref_dossier" type="text" />
                    </label>
                    <label>
                        <span>PAX</span>
                        <input v-model="draftForms[draft.id].nbr_personnes" type="number" min="0" />
                    </label>
                    <label>
                        <span>Service</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].service_id"
                            v-model:search="draftForms[draft.id].service_name"
                            :options="services"
                            label-key="designation"
                            placeholder="Chercher un service..."
                        />
                    </label>
                    <label>
                        <span>Flight</span>
                        <input v-model="draftForms[draft.id].flight" type="text" />
                    </label>
                    <label>
                        <span>Time</span>
                        <input v-model="draftForms[draft.id].heure" type="time" />
                    </label>
                    <label>
                        <span>Start point</span>
                        <input v-model="draftForms[draft.id].point_depart" type="text" />
                    </label>
                    <label>
                        <span>Destination</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].destination_id"
                            v-model:search="draftForms[draft.id].destination_name"
                            :options="destinations"
                            label-key="name"
                            placeholder="Chercher destination..."
                        />
                    </label>
                    <label>
                        <span>City / Location</span>
                        <input v-model="draftForms[draft.id].site" type="text" />
                    </label>
                    <label>
                        <span>Client supplier</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].supplier_client_id"
                            v-model:search="draftForms[draft.id].supplier_client_name"
                            :options="supplierClients"
                            label-key="name"
                            placeholder="Chercher client supplier..."
                        />
                    </label>
                    <label>
                        <span>Vehicle</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].vehicule_id"
                            v-model:search="draftForms[draft.id].vehicule_name"
                            :options="vehicules"
                            label-key="matricule"
                            placeholder="Chercher véhicule..."
                            :allow-custom="false"
                        />
                    </label>
                    <label>
                        <span>Vehicle supplier</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].supplier_vehicule_id"
                            v-model:search="draftForms[draft.id].supplier_vehicule_name"
                            :options="supplierVehicules"
                            label-key="name"
                            placeholder="Chercher fournisseur véhicule..."
                            :allow-custom="false"
                        />
                    </label>
                    <label>
                        <span>MD Driver</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].driver_id"
                            v-model:search="draftForms[draft.id].driver_name"
                            :options="drivers"
                            label-key="name"
                            placeholder="Chercher chauffeur..."
                            :allow-custom="false"
                        />
                    </label>
                    <label>
                        <span>Guide</span>
                        <SearchSelect
                            v-model="draftForms[draft.id].guide_id"
                            v-model:search="draftForms[draft.id].guide_name"
                            :options="guides"
                            label-key="name"
                            placeholder="Chercher guide..."
                            :allow-custom="false"
                        />
                    </label>
                    <label>
                        <span>Budget</span>
                        <input v-model="draftForms[draft.id].budget" type="number" step="0.01" />
                    </label>
                    <label>
                        <span>Supplier price</span>
                        <input v-model="draftForms[draft.id].supplier_price" type="number" step="0.01" />
                    </label>
                    <label class="wide">
                        <span>Passenger names</span>
                        <textarea v-model="draftForms[draft.id].passenger_names" rows="2"></textarea>
                    </label>
                    <label class="wide">
                        <span>Notes agent / validation</span>
                        <textarea v-model="draftForms[draft.id].notes" rows="2"></textarea>
                    </label>
                </div>

                <footer class="draft-actions">
                    <button
                        v-if="draft.mail_message_id"
                        class="mail-link"
                        type="button"
                        @click="openMailModal(draft)"
                    >
                        <i class="bx bx-envelope-open"></i>
                        Voir email source
                    </button>

                    <div>
                        <button
                            v-if="draft.status === 'pending'"
                            class="reject-btn"
                            type="button"
                            @click="rejectDraft(draft)"
                        >
                            Ignorer
                        </button>
                        <button
                            v-if="draft.status === 'pending'"
                            class="validate-btn"
                            type="button"
                            @click="validateDraft(draft)"
                        >
                            <i class="bx bx-check"></i>
                            Valider planning
                        </button>
                        <Link
                            v-if="draft.planning_id"
                            :href="route('plannings.index', { search: draft.parsed_payload?.ref_dossier })"
                            class="mail-link"
                        >
                            Planning #{{ draft.planning_id }}
                        </Link>
                    </div>
                </footer>
            </article>
        </div>

        <div v-else class="empty-drafts">
            <i class="bx bx-bot"></i>
            <h3>Aucun planning TH à valider</h3>
            <p>Le prochain sync mail ajoutera ici les réservations détectées.</p>
        </div>

        <div v-if="drafts?.links?.length" class="pagination-area">
            <Link
                v-for="(link, index) in drafts.links"
                :key="index"
                :href="link.url || ''"
                v-html="link.label"
                class="page-btn"
                :class="{ active: link.active, disabled: !link.url }"
                preserve-scroll
                preserve-state
            />
        </div>

        <div v-if="selectedMail" class="mail-modal-backdrop" @click.self="closeMailModal">
            <section class="mail-modal">
                <header class="mail-modal-head">
                    <div>
                        <span>Email source</span>
                        <h2>{{ selectedMail.source_subject || "Sans objet" }}</h2>
                        <p>
                            {{ selectedMail.source_from || "Email inconnu" }}
                            <small>{{ selectedMail.created_at }}</small>
                        </p>
                    </div>

                    <button type="button" class="modal-close" @click="closeMailModal">
                        <i class="bx bx-x"></i>
                    </button>
                </header>

                <div class="mail-modal-toolbar">
                    <Link
                        :href="route('mailbox.show', selectedMail.mail_message_id)"
                        class="mail-link"
                    >
                        <i class="bx bx-link-external"></i>
                        Ouvrir page email
                    </Link>
                </div>

                <pre class="mail-body">{{ mailBody }}</pre>
            </section>
        </div>
    </div>
</template>

<style scoped>
.drafts-page {
    min-height: 100vh;
    padding: 24px;
    background:
        linear-gradient(90deg, rgba(193, 18, 31, 0.08), transparent 36%),
        #f8fafc;
}

.drafts-hero {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    border-radius: 28px;
    padding: 34px;
    color: #fff;
    background: linear-gradient(135deg, #9f101d 0%, #c1121f 52%, #f97316 100%);
    box-shadow: 0 24px 50px rgba(193, 18, 31, 0.16);
}

.drafts-hero span {
    letter-spacing: 0.16em;
    font-weight: 950;
    font-size: 0.78rem;
    opacity: 0.82;
}

.drafts-hero h1 {
    margin: 8px 0;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 950;
}

.drafts-hero p {
    margin: 0;
    max-width: 720px;
    color: rgba(255, 255, 255, 0.84);
    font-weight: 700;
}

.hero-btn,
.mail-link,
.validate-btn,
.reject-btn {
    border: 0;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    font-weight: 950;
    transition: 0.2s ease;
}

.hero-btn {
    padding: 16px 20px;
    background: #fff;
    color: #9f101d;
}

.status-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 22px 0;
}

.status-tabs button {
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 12px 18px;
    background: #fff;
    color: #475569;
    font-weight: 900;
}

.status-tabs button strong {
    margin-left: 10px;
    color: #0f172a;
}

.status-tabs button.active {
    color: #fff;
    background: #c1121f;
    border-color: #c1121f;
}

.status-tabs button.active strong {
    color: #fff;
}

.draft-list {
    display: grid;
    gap: 18px;
}

.draft-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    padding: 22px;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
}

.draft-head,
.draft-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.source-line {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #64748b;
    font-size: 0.9rem;
}

.source-line strong,
.draft-head h2 {
    color: #111827;
}

.source-line small {
    color: #94a3b8;
    font-weight: 800;
}

.status-dot {
    width: 11px;
    height: 11px;
    border-radius: 999px;
    background: #c1121f;
    box-shadow: 0 0 0 6px #fee2e2;
}

.draft-head h2 {
    margin: 8px 0 0;
    font-size: 1.2rem;
    font-weight: 950;
}

.confidence {
    min-width: 120px;
    border-radius: 18px;
    padding: 12px 16px;
    background: #f8fafc;
    text-align: center;
}

.confidence span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 900;
}

.confidence strong {
    color: #087f5b;
    font-size: 1.35rem;
}

.draft-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-top: 18px;
}

.draft-grid label {
    min-width: 0;
}

.draft-grid label.wide {
    grid-column: span 2;
}

.draft-grid span {
    display: block;
    margin-bottom: 7px;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
}

.draft-grid input,
.draft-grid textarea {
    width: 100%;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    padding: 12px 14px;
    color: #111827;
    font-weight: 750;
    outline: none;
    transition: 0.18s ease;
}

.draft-grid input:focus,
.draft-grid textarea:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 4px rgba(193, 18, 31, 0.08);
}

.draft-actions {
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid #eef2f7;
}

.draft-actions > div {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
}

.mail-link {
    padding: 11px 14px;
    color: #475569;
    background: #f8fafc;
}

.mail-link:hover {
    color: #be123c;
    background: #fff1f2;
}

.reject-btn {
    padding: 12px 16px;
    background: #fff1f2;
    color: #be123c;
}

.validate-btn {
    padding: 12px 18px;
    background: linear-gradient(135deg, #9f101d 0%, #f97316 100%);
    color: #fff;
    box-shadow: 0 12px 24px rgba(193, 18, 31, 0.16);
}

.empty-drafts {
    min-height: 300px;
    border: 1px dashed #cbd5e1;
    border-radius: 24px;
    display: grid;
    place-items: center;
    align-content: center;
    background: #fff;
    color: #64748b;
    text-align: center;
}

.empty-drafts i {
    font-size: 54px;
    color: #c1121f;
}

.empty-drafts h3 {
    margin: 10px 0 4px;
    color: #111827;
    font-weight: 950;
}

.pagination-area {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 18px;
}

.page-btn {
    min-width: 42px;
    min-height: 42px;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 14px;
    background: #fff;
    color: #64748b;
    font-weight: 900;
    text-decoration: none;
}

.page-btn.active {
    color: #fff;
    border-color: #c1121f;
    background: #c1121f;
}

.page-btn.disabled {
    pointer-events: none;
    opacity: 0.5;
}

.mail-modal-backdrop {
    position: fixed;
    z-index: 2000;
    inset: 0;
    padding: 28px;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.mail-modal {
    width: min(1100px, 100%);
    max-height: min(780px, 92vh);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 26px;
    background: #fff;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
}

.mail-modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding: 24px 28px;
    border-bottom: 1px solid #eef2f7;
}

.mail-modal-head span {
    display: block;
    color: #be123c;
    font-size: 0.76rem;
    font-weight: 950;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.mail-modal-head h2 {
    margin: 8px 0 6px;
    color: #111827;
    font-size: 1.45rem;
    font-weight: 950;
}

.mail-modal-head p {
    margin: 0;
    color: #64748b;
    font-weight: 800;
}

.mail-modal-head small {
    margin-left: 10px;
    color: #94a3b8;
}

.modal-close {
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 14px;
    background: #f8fafc;
    color: #475569;
    font-size: 24px;
}

.modal-close:hover {
    color: #be123c;
    background: #fff1f2;
}

.mail-modal-toolbar {
    padding: 14px 28px;
    border-bottom: 1px solid #eef2f7;
    background: #fbfdff;
}

.mail-body {
    margin: 0;
    padding: 28px;
    overflow: auto;
    white-space: pre-wrap;
    color: #1f2937;
    font-family: inherit;
    font-size: 1rem;
    line-height: 1.7;
}

@media (max-width: 1180px) {
    .draft-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .drafts-page {
        padding: 14px;
    }

    .drafts-hero,
    .draft-head,
    .draft-actions {
        align-items: flex-start;
        flex-direction: column;
    }

    .draft-grid,
    .draft-grid label.wide {
        grid-template-columns: 1fr;
        grid-column: span 1;
    }

    .mail-modal-backdrop {
        padding: 12px;
    }

    .mail-modal-head {
        flex-direction: column;
    }
}
</style>
