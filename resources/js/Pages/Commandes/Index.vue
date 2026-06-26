<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import SearchSelect from "@/Components/SearchSelect.vue";

const props = defineProps({
    commandes: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    suppliers: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    nextVoucherNumber: { type: String, default: "" },
});

const page = usePage();
const showModal = ref(false);
const editingId = ref(null);
const sendingId = ref(null);

const filters = reactive({
    search: props.filters.search || "",
    supplier_id: props.filters.supplier_id || "",
    date_from: props.filters.date_from || "",
    date_to: props.filters.date_to || "",
});

const emptyForm = () => ({
    supplier_id: "",
    voucher_number: props.nextVoucherNumber || "",
    start_date: "",
    end_date: "",
    service_id: "",
    supplier_price: "",
    start_point: "",
    start_point_flight: "",
    start_point_city: "",
    start_point_time: "",
    end_point: "",
    end_point_flight: "",
    end_point_city: "",
    end_point_time: "",
    driver_id: "",
    vehicule_id: "",
    guide_id: "",
    passenger: "",
    number_pax: "",
    reference: "",
    date: new Date().toISOString().slice(0, 10),
    signature: "MD Tours",
});

const form = reactive(emptyForm());
const selectSearch = reactive({
    supplier: "",
    service: "",
    driver: "",
    vehicule: "",
    guide: "",
});

const vehiculeOptions = computed(() =>
    props.vehicules.map((vehicule) => ({
        ...vehicule,
        label: [vehicule.matricule, vehicule.marque, vehicule.modele]
            .filter(Boolean)
            .join(" "),
    })),
);

const rows = computed(() => props.commandes?.data || []);
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
const formTitle = computed(() =>
    editingId.value ? "Modifier la commande" : "Ajouter une commande",
);

let filterTimer;
watch(
    () => ({ ...filters }),
    () => {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => applyFilters(), 350);
    },
    { deep: true },
);

const applyFilters = () => {
    router.get("/commandes", { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filters.search = "";
    filters.supplier_id = "";
    filters.date_from = "";
    filters.date_to = "";
};

const resetForm = () => {
    Object.assign(form, emptyForm());
    selectSearch.supplier = "";
    selectSearch.service = "";
    selectSearch.driver = "";
    selectSearch.vehicule = "";
    selectSearch.guide = "";
    editingId.value = null;
};

const setSearchLabels = (commande) => {
    selectSearch.supplier = commande.supplier?.name || "";
    selectSearch.service = commande.service?.designation || "";
    selectSearch.driver = commande.driver?.name || "";
    selectSearch.vehicule = [commande.vehicule?.matricule, commande.vehicule?.marque, commande.vehicule?.modele]
        .filter(Boolean)
        .join(" ");
    selectSearch.guide = commande.guide?.name || "";
};

const openCreate = () => {
    resetForm();
    showModal.value = true;
};

const openEdit = (commande) => {
    resetForm();
    editingId.value = commande.id;

    Object.assign(form, {
        supplier_id: commande.supplier_id || "",
        voucher_number: commande.voucher_number || "",
        start_date: commande.start_date || "",
        end_date: commande.end_date || "",
        service_id: commande.service_id || "",
        supplier_price: commande.supplier_price || "",
        start_point: commande.start_point || "",
        start_point_flight: commande.start_point_flight || "",
        start_point_city: commande.start_point_city || "",
        start_point_time: normalizeTime(commande.start_point_time),
        end_point: commande.end_point || "",
        end_point_flight: commande.end_point_flight || "",
        end_point_city: commande.end_point_city || "",
        end_point_time: normalizeTime(commande.end_point_time),
        driver_id: commande.driver_id || "",
        vehicule_id: commande.vehicule_id || "",
        guide_id: commande.guide_id || "",
        passenger: commande.passenger || "",
        number_pax: commande.number_pax || "",
        reference: commande.reference || "",
        date: commande.date || "",
        signature: commande.signature || "",
    });

    setSearchLabels(commande);
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const saveCommande = () => {
    const url = editingId.value ? `/commandes/${editingId.value}` : "/commandes";
    const method = editingId.value ? "put" : "post";

    router[method](url, { ...form }, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};

const destroyCommande = (commande) => {
    if (!window.confirm(`Supprimer la commande ${commande.voucher_number} ?`)) {
        return;
    }

    router.delete(`/commandes/${commande.id}`, {
        preserveScroll: true,
    });
};

const generatePdf = (commande) => {
    window.open(`/commandes/${commande.id}/pdf`, "_blank");
};

const sendEmail = (commande) => {
    if (!commande.supplier?.email) {
        window.alert("Ce supplier n'a pas d'adresse email.");
        return;
    }

    sendingId.value = commande.id;
    router.post(`/commandes/${commande.id}/send-email`, {}, {
        preserveScroll: true,
        onFinish: () => {
            sendingId.value = null;
        },
    });
};

const formatMoney = (value) =>
    new Intl.NumberFormat("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

const normalizeTime = (value) => {
    if (!value) return "";
    return String(value).slice(0, 5);
};
</script>

<template>
    <Head title="Commandes" />

    <div class="commandes-page">
        <section class="commandes-hero">
            <div>
                <span>Gestion opérationnelle</span>
                <h1>Commandes</h1>
                <p>Bons de commande, PDF supplier et envoi email automatique.</p>
            </div>

            <button type="button" class="primary-action" @click="openCreate">
                <i class="bx bx-plus"></i>
                Ajouter
            </button>
        </section>

        <div v-if="flashSuccess" class="flash success">{{ flashSuccess }}</div>
        <div v-if="flashError" class="flash error">{{ flashError }}</div>

        <section class="filters-card">
            <div class="filter-search">
                <i class="bx bx-search"></i>
                <input
                    v-model="filters.search"
                    type="search"
                    placeholder="Recherche voucher, supplier, passenger, référence..."
                />
            </div>

            <select v-model="filters.supplier_id">
                <option value="">Tous les suppliers</option>
                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                    {{ supplier.name }}
                </option>
            </select>

            <input v-model="filters.date_from" type="date" />
            <input v-model="filters.date_to" type="date" />

            <button type="button" class="soft-action" @click="resetFilters">
                Reset
            </button>
        </section>

        <section class="table-card">
            <div class="table-head">
                <div>
                    <h2>Liste des commandes</h2>
                    <p>{{ commandes.total || 0 }} résultat(s)</p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Supplier</th>
                            <th>Voucher</th>
                            <th>Période</th>
                            <th>Service</th>
                            <th>Passenger</th>
                            <th>Prix</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="commande in rows" :key="commande.id">
                            <td class="id-cell">#{{ commande.id }}</td>
                            <td>
                                <strong>{{ commande.supplier?.name || "-" }}</strong>
                                <small>{{ commande.supplier?.email || "-" }}</small>
                            </td>
                            <td>
                                <span class="voucher-pill">{{ commande.voucher_number }}</span>
                                <small>{{ commande.reference || "-" }}</small>
                            </td>
                            <td>
                                {{ commande.start_date || "-" }}
                                <span class="date-arrow">→</span>
                                {{ commande.end_date || "-" }}
                            </td>
                            <td>{{ commande.service?.designation || "-" }}</td>
                            <td>
                                <strong>{{ commande.passenger || "-" }}</strong>
                                <small>{{ commande.number_pax || 0 }} pax</small>
                            </td>
                            <td class="money">{{ formatMoney(commande.supplier_price) }} MAD</td>
                            <td>
                                <div class="actions">
                                    <button title="Modifier" @click="openEdit(commande)">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button title="PDF" @click="generatePdf(commande)">
                                        <i class="bx bx-file"></i>
                                    </button>
                                    <button
                                        title="Envoyer email"
                                        :disabled="sendingId === commande.id"
                                        @click="sendEmail(commande)"
                                    >
                                        <i class="bx bx-envelope"></i>
                                    </button>
                                    <button class="danger" title="Supprimer" @click="destroyCommande(commande)">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="8" class="empty-cell">Aucune commande trouvée.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="commandes.links?.length" class="pagination-row">
                <span>Page {{ commandes.current_page }} / {{ commandes.last_page }}</span>
                <div>
                    <Link
                        v-for="(link, index) in commandes.links"
                        :key="index"
                        :href="link.url || '#'"
                        class="page-link"
                        :class="{ active: link.active, disabled: !link.url }"
                        preserve-scroll
                        v-html="link.label"
                    />
                </div>
            </div>
        </section>

        <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
            <section class="commande-modal">
                <header>
                    <div>
                        <span>Bon de commande</span>
                        <h2>{{ formTitle }}</h2>
                    </div>
                    <button type="button" class="close-btn" @click="closeModal">
                        <i class="bx bx-x"></i>
                        Fermer
                    </button>
                </header>

                <div class="form-grid">
                    <label>
                        <span>Supplier *</span>
                        <SearchSelect
                            v-model="form.supplier_id"
                            v-model:search="selectSearch.supplier"
                            :options="suppliers"
                            placeholder="Rechercher supplier..."
                            :allow-custom="false"
                        />
                    </label>

                    <label>
                        <span>Voucher number *</span>
                        <input v-model="form.voucher_number" type="text" />
                    </label>

                    <label>
                        <span>Start Date</span>
                        <input v-model="form.start_date" type="date" />
                    </label>

                    <label>
                        <span>End Date</span>
                        <input v-model="form.end_date" type="date" />
                    </label>

                    <label>
                        <span>Service Type</span>
                        <SearchSelect
                            v-model="form.service_id"
                            v-model:search="selectSearch.service"
                            :options="services"
                            label-key="designation"
                            placeholder="Rechercher service..."
                            :allow-custom="false"
                        />
                    </label>

                    <label>
                        <span>Supplier Price</span>
                        <input v-model="form.supplier_price" type="number" step="0.01" min="0" />
                    </label>

                    <label>
                        <span>Start Point</span>
                        <input v-model="form.start_point" type="text" />
                    </label>

                    <label>
                        <span>Start Point Flight</span>
                        <input v-model="form.start_point_flight" type="text" />
                    </label>

                    <label>
                        <span>Start Point City</span>
                        <input v-model="form.start_point_city" type="text" />
                    </label>

                    <label>
                        <span>Start Point Time</span>
                        <input v-model="form.start_point_time" type="time" />
                    </label>

                    <label>
                        <span>End Point</span>
                        <input v-model="form.end_point" type="text" />
                    </label>

                    <label>
                        <span>End Point Flight</span>
                        <input v-model="form.end_point_flight" type="text" />
                    </label>

                    <label>
                        <span>End Point City</span>
                        <input v-model="form.end_point_city" type="text" />
                    </label>

                    <label>
                        <span>End Point Time</span>
                        <input v-model="form.end_point_time" type="time" />
                    </label>

                    <label>
                        <span>MD Driver</span>
                        <SearchSelect
                            v-model="form.driver_id"
                            v-model:search="selectSearch.driver"
                            :options="drivers"
                            placeholder="Rechercher driver..."
                            :allow-custom="false"
                        />
                    </label>

                    <label>
                        <span>MD Tours Vehicle</span>
                        <SearchSelect
                            v-model="form.vehicule_id"
                            v-model:search="selectSearch.vehicule"
                            :options="vehiculeOptions"
                            label-key="label"
                            placeholder="Rechercher véhicule..."
                            :allow-custom="false"
                        />
                    </label>

                    <label>
                        <span>Tour Guide</span>
                        <SearchSelect
                            v-model="form.guide_id"
                            v-model:search="selectSearch.guide"
                            :options="guides"
                            placeholder="Rechercher guide..."
                            :allow-custom="false"
                        />
                    </label>

                    <label>
                        <span>Passenger</span>
                        <input v-model="form.passenger" type="text" />
                    </label>

                    <label>
                        <span>Number Pax</span>
                        <input v-model="form.number_pax" type="number" min="0" />
                    </label>

                    <label>
                        <span>Reference</span>
                        <input v-model="form.reference" type="text" />
                    </label>

                    <label>
                        <span>Date</span>
                        <input v-model="form.date" type="date" />
                    </label>

                    <label>
                        <span>Signature</span>
                        <input v-model="form.signature" type="text" />
                    </label>
                </div>

                <footer>
                    <button type="button" class="soft-action" @click="closeModal">Annuler</button>
                    <button type="button" class="primary-action" @click="saveCommande">
                        {{ editingId ? "Mettre à jour" : "Enregistrer" }}
                    </button>
                </footer>
            </section>
        </div>
    </div>
</template>

<style scoped>
.commandes-page {
    padding: 18px 20px 36px;
    background: #f5f7fb;
    min-height: calc(100vh - 90px);
}

.commandes-hero {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-radius: 28px;
    padding: 32px;
    color: #ffffff;
    background:
        radial-gradient(circle at 85% 12%, rgba(255, 255, 255, 0.22), transparent 28%),
        linear-gradient(135deg, #0f172a, #b91c1c 62%, #ef4444);
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
}

.commandes-hero span,
.commande-modal header span {
    display: block;
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.82rem;
    font-weight: 950;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.commandes-hero h1 {
    margin: 4px 0;
    font-size: 2.4rem;
    font-weight: 950;
}

.commandes-hero p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-weight: 800;
}

.primary-action,
.soft-action,
.actions button,
.close-btn {
    border: 0;
    border-radius: 16px;
    font-weight: 950;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.primary-action {
    min-height: 50px;
    padding: 0 22px;
    color: #ffffff;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    box-shadow: 0 16px 34px rgba(185, 28, 28, 0.24);
}

.soft-action {
    min-height: 46px;
    padding: 0 18px;
    color: #334155;
    background: #ffffff;
    border: 1px solid #e2e8f0;
}

.primary-action:hover,
.soft-action:hover,
.actions button:hover,
.close-btn:hover {
    transform: translateY(-1px);
}

.flash {
    margin-top: 16px;
    border-radius: 16px;
    padding: 14px 16px;
    font-weight: 900;
}

.flash.success {
    color: #047857;
    background: #dcfce7;
}

.flash.error {
    color: #b91c1c;
    background: #fee2e2;
}

.filters-card,
.table-card {
    margin-top: 18px;
    border-radius: 24px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
}

.filters-card {
    display: grid;
    grid-template-columns: minmax(280px, 1.5fr) minmax(180px, 0.8fr) 170px 170px auto;
    gap: 12px;
    padding: 18px;
}

.filter-search {
    position: relative;
}

.filter-search i {
    position: absolute;
    top: 15px;
    left: 14px;
    color: #94a3b8;
}

.filter-search input,
.filters-card select,
.filters-card > input,
.form-grid input {
    width: 100%;
    min-height: 46px;
    border: 1px solid #dbe2ea;
    border-radius: 15px;
    padding: 0 14px;
    color: #111827;
    font-weight: 800;
    outline: none;
}

.filter-search input {
    padding-left: 42px;
}

.table-card {
    overflow: hidden;
}

.table-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 22px 24px;
}

.table-head h2 {
    margin: 0;
    color: #0f172a;
    font-weight: 950;
}

.table-head p {
    margin: 4px 0 0;
    color: #64748b;
    font-weight: 800;
}

.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    color: #991b1b;
    background: #fff1f2;
    text-align: left;
    padding: 16px 18px;
    font-size: 0.82rem;
    font-weight: 950;
    letter-spacing: 0.03em;
}

td {
    padding: 18px;
    border-top: 1px solid #eef2f7;
    color: #334155;
    font-weight: 800;
    vertical-align: middle;
}

td strong,
td small {
    display: block;
}

td small {
    color: #94a3b8;
    font-weight: 800;
    margin-top: 4px;
}

.id-cell {
    color: #64748b;
    font-weight: 950;
}

.voucher-pill {
    display: inline-flex;
    border-radius: 999px;
    padding: 8px 12px;
    color: #be123c;
    background: #ffe4e6;
    font-weight: 950;
}

.date-arrow {
    color: #be123c;
    font-weight: 950;
    padding: 0 5px;
}

.money {
    color: #047857;
    font-weight: 950;
    white-space: nowrap;
}

.actions {
    display: flex;
    gap: 8px;
}

.actions button {
    width: 42px;
    height: 42px;
    color: #1d4ed8;
    background: #eff6ff;
}

.actions button.danger {
    color: #dc2626;
    background: #fef2f2;
}

.actions button:disabled {
    opacity: 0.55;
    cursor: wait;
}

.empty-cell {
    text-align: center;
    color: #94a3b8;
    padding: 42px;
}

.pagination-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 24px;
    border-top: 1px solid #eef2f7;
    color: #64748b;
    font-weight: 900;
}

.pagination-row > div {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.page-link {
    min-width: 38px;
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: #334155;
    background: #f8fafc;
    text-decoration: none;
    padding: 0 10px;
}

.page-link.active {
    color: #ffffff;
    background: #be123c;
}

.page-link.disabled {
    pointer-events: none;
    opacity: 0.45;
}

.modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(15, 23, 42, 0.62);
    backdrop-filter: blur(12px);
}

.commande-modal {
    width: min(1280px, 96vw);
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 28px;
    background: #ffffff;
    box-shadow: 0 34px 90px rgba(15, 23, 42, 0.34);
    padding: 26px;
}

.commande-modal header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 20px;
    border-radius: 22px;
    color: #ffffff;
    background: linear-gradient(135deg, #0f172a, #be123c);
    margin-bottom: 20px;
}

.commande-modal header h2 {
    margin: 4px 0 0;
    font-weight: 950;
}

.close-btn {
    min-height: 48px;
    padding: 0 16px;
    color: #0f172a;
    background: #ffffff;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.form-grid label {
    display: block;
    min-width: 0;
}

.form-grid label > span {
    display: block;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 950;
    margin-bottom: 7px;
}

.commande-modal footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #eef2f7;
}

@media (max-width: 1100px) {
    .filters-card,
    .form-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 720px) {
    .commandes-page {
        padding: 12px;
    }

    .commandes-hero,
    .table-head,
    .pagination-row,
    .commande-modal header {
        align-items: flex-start;
        flex-direction: column;
    }

    .filters-card,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .commande-modal {
        width: 100%;
        padding: 14px;
    }
}
</style>
