<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    reservateurs: Object,
    filters: Object,
    nextReference: String,
});

const page = usePage();
const showModal = ref(false);
const editing = ref(null);

const query = reactive({
    search: props.filters?.search || "",
    statut: props.filters?.statut || "",
});

const form = reactive({
    nom: "",
    telephone: "",
    email: "",
    adresse: "",
    statut: "actif",
});

const modalTitle = computed(() =>
    editing.value ? "Modifier le Réservateur" : "Ajouter un Réservateur",
);

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
                timer: 2500,
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

const applyFilters = () => {
    router.get(route("reservateurs.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetForm = () => {
    Object.assign(form, {
        nom: "",
        telephone: "",
        email: "",
        adresse: "",
        statut: "actif",
    });
};

const openCreate = () => {
    editing.value = null;
    resetForm();
    showModal.value = true;
};

const openEdit = (reservateur) => {
    editing.value = reservateur;
    Object.assign(form, {
        nom: reservateur.nom || "",
        telephone: reservateur.telephone || "",
        email: reservateur.email || "",
        adresse: reservateur.adresse || "",
        statut: reservateur.statut || "actif",
    });
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editing.value = null;
    resetForm();
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (editing.value) {
        router.put(route("reservateurs.update", editing.value.id), form, options);
        return;
    }

    router.post(route("reservateurs.store"), form, options);
};

const showGeneratedReference = () => {
    Swal.fire({
        icon: "info",
        title: "Référence automatique",
        text: `La référence sera générée automatiquement au format ${props.nextReference} et verrouillée après création.`,
        confirmButtonColor: "#c1121f",
    });
};

const toggleReservateur = (reservateur) => {
    const action = reservateur.statut === "actif" ? "désactiver" : "réactiver";

    Swal.fire({
        title: `${action} ce réservateur ?`,
        icon: "question",
        showCancelButton: true,
        confirmButtonText: "Oui",
        cancelButtonText: "Annuler",
        confirmButtonColor: "#c1121f",
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route("reservateurs.toggle", reservateur.id), {}, { preserveScroll: true });
        }
    });
};

const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("fr-FR", {
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
          })
        : "-";
</script>

<template>
    <Head title="Gestion des Réservateurs" />

    <div class="reservateurs-page">
        <div class="hero-card">
            <div>
                <span>MD TOURS PARTNERS</span>
                <h1>Gestion des Réservateurs</h1>
                <p>
                    Gérez les personnes et partenaires autorisés à créer leurs
                    réservations via le portail dédié.
                </p>
            </div>
            <button class="primary-btn" type="button" @click="openCreate">
                <i class="bx bx-plus"></i>
                Ajouter un Réservateur
            </button>
        </div>

        <div class="toolbar-card">
            <div class="search-wrap">
                <i class="bx bx-search"></i>
                <input
                    v-model="query.search"
                    type="text"
                    placeholder="Recherche par nom, référence, téléphone ou email..."
                    @keyup.enter="applyFilters"
                />
            </div>

            <select v-model="query.statut" @change="applyFilters">
                <option value="">Tous les statuts</option>
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
            </select>

            <button class="search-btn" type="button" @click="applyFilters">
                Recherche
            </button>
        </div>

        <div class="table-card">
            <div class="table-head">
                <div>
                    <h2>Liste des réservateurs</h2>
                    <p>
                        {{ reservateurs.from || 0 }}-{{ reservateurs.to || 0 }}
                        sur {{ reservateurs.total || 0 }}
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Nom du réservateur</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Nombre de réservations</th>
                            <th>Date de création</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in reservateurs.data" :key="item.id">
                            <td>
                                <strong class="reference">{{ item.reference }}</strong>
                            </td>
                            <td>{{ item.nom }}</td>
                            <td>{{ item.telephone || "-" }}</td>
                            <td>{{ item.email || "-" }}</td>
                            <td>{{ item.reservations_count || 0 }}</td>
                            <td>{{ formatDate(item.created_at) }}</td>
                            <td>
                                <span class="status-badge" :class="item.statut">
                                    {{ item.statut }}
                                </span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <button type="button" @click="openEdit(item)">
                                        Modifier
                                    </button>
                                    <button
                                        type="button"
                                        class="soft-danger"
                                        @click="toggleReservateur(item)"
                                    >
                                        {{
                                            item.statut === "actif"
                                                ? "Désactiver"
                                                : "Réactiver"
                                        }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!reservateurs.data?.length">
                            <td colspan="8" class="empty-row">
                                Aucun réservateur trouvé.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
            <section class="form-modal">
                <header>
                    <div>
                        <span>Réservateur</span>
                        <h2>{{ modalTitle }}</h2>
                    </div>
                    <button type="button" class="close-btn" @click="closeModal">
                        <i class="bx bx-x"></i>
                    </button>
                </header>

                <div class="reference-box">
                    <span>Référence</span>
                    <strong>{{ editing?.reference || nextReference }}</strong>
                    <small>
                        Générée automatiquement, unique et non modifiable.
                    </small>
                    <button
                        v-if="!editing"
                        type="button"
                        class="generate-btn"
                        @click="showGeneratedReference"
                    >
                        Générer automatiquement la référence
                    </button>
                </div>

                <div class="form-grid">
                    <label>
                        <span>Nom du réservateur</span>
                        <input v-model="form.nom" type="text" />
                    </label>
                    <label>
                        <span>Téléphone</span>
                        <input v-model="form.telephone" type="text" />
                    </label>
                    <label>
                        <span>Email</span>
                        <input v-model="form.email" type="email" />
                    </label>
                    <label>
                        <span>Statut</span>
                        <select v-model="form.statut">
                            <option value="actif">Actif</option>
                            <option value="inactif">Inactif</option>
                        </select>
                    </label>
                    <label class="wide">
                        <span>Adresse</span>
                        <textarea v-model="form.adresse" rows="3"></textarea>
                    </label>
                </div>

                <footer>
                    <button type="button" class="secondary-btn" @click="closeModal">
                        Annuler
                    </button>
                    <button type="button" class="primary-btn" @click="submit">
                        Enregistrer
                    </button>
                </footer>
            </section>
        </div>
    </div>
</template>

<style scoped>
.reservateurs-page {
    min-height: 100vh;
    padding: 24px;
    background: linear-gradient(90deg, rgba(193, 18, 31, 0.07), transparent 34%), #f8fafc;
}

.hero-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border-radius: 28px;
    padding: 34px;
    color: #fff;
    background: linear-gradient(135deg, #7f0d18 0%, #c1121f 55%, #f97316 100%);
    box-shadow: 0 24px 50px rgba(193, 18, 31, 0.16);
}

.hero-card span,
.form-modal header span {
    letter-spacing: 0.14em;
    font-size: 0.76rem;
    font-weight: 950;
    text-transform: uppercase;
    opacity: 0.8;
}

.hero-card h1 {
    margin: 8px 0;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 950;
}

.hero-card p {
    max-width: 680px;
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-weight: 750;
}

.primary-btn,
.secondary-btn,
.search-btn,
.action-group button {
    border: 0;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 950;
    transition: 0.2s ease;
}

.primary-btn {
    padding: 13px 18px;
    color: #fff;
    background: #c1121f;
    box-shadow: 0 12px 24px rgba(193, 18, 31, 0.18);
}

.hero-card .primary-btn {
    color: #9f101d;
    background: #fff;
}

.secondary-btn {
    padding: 13px 18px;
    color: #475569;
    background: #f1f5f9;
}

.toolbar-card,
.table-card {
    margin-top: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
}

.toolbar-card {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) 220px 140px;
    gap: 12px;
    padding: 18px;
}

.search-wrap {
    display: flex;
    align-items: center;
    border: 1px solid #dbe2ea;
    border-radius: 16px;
    padding: 0 14px;
}

.search-wrap i {
    color: #94a3b8;
    font-size: 22px;
}

.search-wrap input,
.toolbar-card select,
.form-grid input,
.form-grid select,
.form-grid textarea {
    width: 100%;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    padding: 12px 14px;
    color: #111827;
    font-weight: 750;
    outline: 0;
}

.search-wrap input {
    border: 0;
}

.search-btn {
    color: #fff;
    background: #111827;
}

.table-head {
    padding: 20px 22px;
    border-bottom: 1px solid #eef2f7;
}

.table-head h2 {
    margin: 0;
    color: #111827;
    font-size: 1.2rem;
    font-weight: 950;
}

.table-head p {
    margin: 4px 0 0;
    color: #64748b;
    font-weight: 750;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    padding: 14px 16px;
    background: #fff1f2;
    color: #9f101d;
    font-size: 0.75rem;
    font-weight: 950;
    text-transform: uppercase;
    white-space: nowrap;
}

td {
    padding: 16px;
    border-top: 1px solid #eef2f7;
    color: #334155;
    font-weight: 750;
    vertical-align: middle;
}

.reference {
    color: #111827;
    background: #f1f5f9;
    border-radius: 999px;
    padding: 7px 10px;
}

.status-badge {
    border-radius: 999px;
    padding: 7px 10px;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
}

.status-badge.actif {
    color: #047857;
    background: #ecfdf5;
}

.status-badge.inactif {
    color: #be123c;
    background: #fff1f2;
}

.action-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.action-group button {
    padding: 9px 12px;
    color: #334155;
    background: #f8fafc;
}

.action-group button:hover {
    color: #c1121f;
    background: #fff1f2;
}

.action-group .soft-danger {
    color: #be123c;
    background: #fff1f2;
}

.empty-row {
    text-align: center;
    color: #94a3b8;
    padding: 42px;
}

.modal-backdrop {
    position: fixed;
    z-index: 2000;
    inset: 0;
    padding: 24px;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-modal {
    width: min(760px, 100%);
    max-height: 92vh;
    overflow: auto;
    border-radius: 26px;
    background: #fff;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
}

.form-modal header,
.form-modal footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 22px 24px;
    border-bottom: 1px solid #eef2f7;
}

.form-modal footer {
    justify-content: flex-end;
    border-top: 1px solid #eef2f7;
    border-bottom: 0;
}

.form-modal h2 {
    margin: 4px 0 0;
    color: #111827;
    font-weight: 950;
}

.close-btn {
    width: 44px;
    height: 44px;
    border: 0;
    border-radius: 14px;
    background: #f8fafc;
    color: #475569;
    font-size: 24px;
}

.reference-box {
    margin: 22px 24px 0;
    border-radius: 18px;
    padding: 16px 18px;
    background: #f8fafc;
}

.reference-box span,
.form-grid span {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
    margin-bottom: 7px;
}

.reference-box strong {
    display: block;
    color: #111827;
    font-size: 1.4rem;
    font-weight: 950;
}

.reference-box small {
    display: block;
    color: #94a3b8;
    font-weight: 750;
}

.generate-btn {
    margin-top: 12px;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    color: #9f101d;
    background: #fff1f2;
    font-weight: 900;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    padding: 22px 24px;
}

.form-grid .wide {
    grid-column: span 2;
}

@media (max-width: 900px) {
    .hero-card,
    .toolbar-card {
        grid-template-columns: 1fr;
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .reservateurs-page {
        padding: 14px;
    }

    .form-grid,
    .form-grid .wide {
        grid-template-columns: 1fr;
        grid-column: span 1;
    }
}
</style>
