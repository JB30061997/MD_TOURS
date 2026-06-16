<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import Swal from "sweetalert2";

const props = defineProps({
    reservateur: Object,
    reservations: Object,
});

const page = usePage();
const editing = ref(null);
const viewing = ref(null);

const emptyForm = () => ({
    service: "",
    lieu_depart: "",
    lieu_arrivee: "",
    date_service: "",
    heure_souhaitee: "",
    nombre_personnes: "",
    contact: "",
    informations_complementaires: "",
});

const form = reactive(emptyForm());

const formTitle = computed(() =>
    editing.value ? "Modifier une réservation" : "Créer une réservation",
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

const resetForm = () => {
    editing.value = null;
    Object.assign(form, emptyForm());
};

const editReservation = (reservation) => {
    editing.value = reservation;
    Object.assign(form, {
        service: reservation.service || "",
        lieu_depart: reservation.lieu_depart || "",
        lieu_arrivee: reservation.lieu_arrivee || "",
        date_service: (reservation.date_service || "").slice(0, 10),
        heure_souhaitee: (reservation.heure_souhaitee || "").slice(0, 5),
        nombre_personnes: reservation.nombre_personnes || "",
        contact: reservation.contact || "",
        informations_complementaires:
            reservation.informations_complementaires || "",
    });
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: resetForm,
    };

    if (editing.value) {
        router.put(route("reservateur.reservations.update", editing.value.id), form, options);
        return;
    }

    router.post(route("reservateur.reservations.store"), form, options);
};

const cancelReservation = (reservation) => {
    Swal.fire({
        title: "Annuler cette réservation ?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Oui, annuler",
        cancelButtonText: "Retour",
        confirmButtonColor: "#c1121f",
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route("reservateur.reservations.cancel", reservation.id), {}, { preserveScroll: true });
        }
    });
};

const logout = () => {
    router.post(route("reservateur.logout"));
};

const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("fr-FR", {
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
          })
        : "-";

const canChange = (reservation) => reservation.statut !== "confirmee";
</script>

<template>
    <Head title="Espace Réservateur" />

    <main class="portal-page">
        <header class="portal-hero">
            <div>
                <span>MD TOURS PORTAL</span>
                <h1>Bonjour {{ reservateur.nom }}</h1>
                <p>Référence : {{ reservateur.reference }}</p>
            </div>
            <button type="button" @click="logout">
                <i class="bx bx-log-out"></i>
                Déconnexion
            </button>
        </header>

        <section class="form-card">
            <div class="section-head">
                <div>
                    <span>Réservation</span>
                    <h2>{{ formTitle }}</h2>
                </div>
                <button v-if="editing" type="button" class="ghost-btn" @click="resetForm">
                    Annuler modification
                </button>
            </div>

            <form class="reservation-form" @submit.prevent="submit">
                <label>
                    <span>Type de service</span>
                    <input v-model="form.service" type="text" required />
                </label>
                <label>
                    <span>Lieu de départ</span>
                    <input v-model="form.lieu_depart" type="text" required />
                </label>
                <label>
                    <span>Lieu d’arrivée</span>
                    <input v-model="form.lieu_arrivee" type="text" required />
                </label>
                <label>
                    <span>Date du service</span>
                    <input v-model="form.date_service" type="date" required />
                </label>
                <label>
                    <span>Heure souhaitée</span>
                    <input v-model="form.heure_souhaitee" type="time" />
                </label>
                <label>
                    <span>Nombre de personnes</span>
                    <input v-model="form.nombre_personnes" type="number" min="1" />
                </label>
                <label class="wide">
                    <span>Contact téléphone / email</span>
                    <input v-model="form.contact" type="text" />
                </label>
                <label class="wide">
                    <span>Informations complémentaires</span>
                    <textarea v-model="form.informations_complementaires" rows="3"></textarea>
                </label>
                <button class="submit-btn" type="submit">
                    {{ editing ? "Enregistrer les modifications" : "Créer la réservation" }}
                </button>
            </form>
        </section>

        <section class="table-card">
            <div class="section-head">
                <div>
                    <span>Mes Réservations</span>
                    <h2>Historique</h2>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Référence réservation</th>
                            <th>Service</th>
                            <th>Date de réservation</th>
                            <th>Date du service</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="reservation in reservations.data" :key="reservation.id">
                            <td>
                                <strong class="ref">{{ reservation.reference }}</strong>
                            </td>
                            <td>{{ reservation.service }}</td>
                            <td>{{ formatDate(reservation.created_at) }}</td>
                            <td>{{ formatDate(reservation.date_service) }}</td>
                            <td>
                                <span class="status" :class="reservation.statut">
                                    {{ reservation.statut.replace("_", " ") }}
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button type="button" @click="viewing = reservation">
                                        Voir
                                    </button>
                                    <button
                                        v-if="canChange(reservation)"
                                        type="button"
                                        @click="editReservation(reservation)"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        v-if="canChange(reservation)"
                                        type="button"
                                        class="danger"
                                        @click="cancelReservation(reservation)"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!reservations.data?.length">
                            <td colspan="6" class="empty-row">
                                Aucune réservation pour le moment.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div v-if="viewing" class="modal-backdrop" @click.self="viewing = null">
            <section class="view-modal">
                <header>
                    <h2>{{ viewing.reference }}</h2>
                    <button type="button" @click="viewing = null">
                        <i class="bx bx-x"></i>
                    </button>
                </header>
                <dl>
                    <div><dt>Service</dt><dd>{{ viewing.service }}</dd></div>
                    <div><dt>Départ</dt><dd>{{ viewing.lieu_depart }}</dd></div>
                    <div><dt>Arrivée</dt><dd>{{ viewing.lieu_arrivee }}</dd></div>
                    <div><dt>Date service</dt><dd>{{ formatDate(viewing.date_service) }}</dd></div>
                    <div><dt>Heure</dt><dd>{{ viewing.heure_souhaitee || "-" }}</dd></div>
                    <div><dt>Personnes</dt><dd>{{ viewing.nombre_personnes || "-" }}</dd></div>
                    <div class="wide"><dt>Contact</dt><dd>{{ viewing.contact || "-" }}</dd></div>
                    <div class="wide"><dt>Informations</dt><dd>{{ viewing.informations_complementaires || "-" }}</dd></div>
                </dl>
            </section>
        </div>
    </main>
</template>

<style scoped>
.portal-page {
    min-height: 100vh;
    padding: 24px;
    background: linear-gradient(90deg, rgba(193, 18, 31, 0.07), transparent 34%), #f8fafc;
}

.portal-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border-radius: 28px;
    padding: 32px;
    color: #fff;
    background: linear-gradient(135deg, #7f0d18 0%, #c1121f 55%, #f97316 100%);
    box-shadow: 0 24px 50px rgba(193, 18, 31, 0.16);
}

.portal-hero span,
.section-head span,
label span,
dt {
    display: block;
    color: #9f101d;
    font-size: 0.76rem;
    font-weight: 950;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.portal-hero span {
    color: rgba(255, 255, 255, 0.82);
}

.portal-hero h1 {
    margin: 8px 0;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 950;
}

.portal-hero p {
    margin: 0;
    color: rgba(255, 255, 255, 0.82);
    font-weight: 850;
}

.portal-hero button,
.submit-btn,
.ghost-btn,
.actions button {
    border: 0;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 950;
}

.portal-hero button {
    padding: 13px 18px;
    color: #9f101d;
    background: #fff;
}

.form-card,
.table-card {
    margin-top: 20px;
    border: 1px solid #e5e7eb;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.06);
}

.section-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 22px;
    border-bottom: 1px solid #eef2f7;
}

.section-head h2 {
    margin: 4px 0 0;
    color: #111827;
    font-weight: 950;
}

.reservation-form {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    padding: 22px;
}

.reservation-form .wide {
    grid-column: span 3;
}

input,
textarea {
    width: 100%;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    padding: 12px 14px;
    color: #111827;
    font-weight: 750;
    outline: 0;
}

input:focus,
textarea:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 4px rgba(193, 18, 31, 0.08);
}

.submit-btn {
    grid-column: span 3;
    min-height: 52px;
    color: #fff;
    background: #c1121f;
}

.ghost-btn {
    padding: 11px 14px;
    color: #475569;
    background: #f8fafc;
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
}

.ref {
    color: #111827;
    background: #f1f5f9;
    border-radius: 999px;
    padding: 7px 10px;
}

.status {
    border-radius: 999px;
    padding: 7px 10px;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: uppercase;
}

.status.en_attente {
    color: #92400e;
    background: #fffbeb;
}

.status.confirmee {
    color: #047857;
    background: #ecfdf5;
}

.status.annulee {
    color: #be123c;
    background: #fff1f2;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.actions button {
    padding: 9px 12px;
    color: #334155;
    background: #f8fafc;
}

.actions .danger {
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
    display: grid;
    place-items: center;
}

.view-modal {
    width: min(680px, 100%);
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
}

.view-modal header {
    display: flex;
    justify-content: space-between;
    padding: 22px;
    border-bottom: 1px solid #eef2f7;
}

.view-modal header button {
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 12px;
    background: #f8fafc;
}

dl {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    padding: 22px;
}

dl .wide {
    grid-column: span 2;
}

dd {
    margin: 6px 0 0;
    color: #111827;
    font-weight: 850;
}

@media (max-width: 860px) {
    .portal-hero {
        align-items: flex-start;
        flex-direction: column;
    }

    .reservation-form,
    .reservation-form .wide,
    .submit-btn,
    dl,
    dl .wide {
        grid-template-columns: 1fr;
        grid-column: span 1;
    }
}
</style>
