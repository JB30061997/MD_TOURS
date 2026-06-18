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
const activeSection = ref("reservations");

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
    editing.value ? "Modifier une réservation" : "Créer une nouvelle réservation",
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
                confirmButtonColor: "#3f46d3",
            });
        }
    },
    { immediate: true },
);

const resetForm = () => {
    editing.value = null;
    Object.assign(form, emptyForm());
};

const showReservations = () => {
    activeSection.value = "reservations";
    resetForm();
};

const showCreateForm = () => {
    activeSection.value = "create";
    resetForm();
};

const editReservation = (reservation) => {
    activeSection.value = "create";
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
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            activeSection.value = "reservations";
        },
    };

    if (editing.value) {
        router.put(
            route("reservateur.reservations.update", editing.value.id),
            form,
            options,
        );
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
        confirmButtonColor: "#ef4444",
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(
                route("reservateur.reservations.cancel", reservation.id),
                {},
                { preserveScroll: true },
            );
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

const copyReference = async () => {
    await navigator.clipboard.writeText(props.reservateur.reference);
    Swal.fire({
        toast: true,
        position: "top-end",
        icon: "success",
        title: "Référence copiée",
        showConfirmButton: false,
        timer: 1600,
    });
};
</script>

<template>
    <Head title="Espace Réservateur" />

    <main class="portal-layout">
        <aside class="sidebar">
            <div class="side-brand">
                <div class="logo">MD</div>
                <div>
                    <strong>MD TOURS</strong>
                    <span>Reservateur Portal</span>
                </div>
            </div>

            <div class="reference-box">
                <span>Référence</span>
                <button type="button" @click="copyReference">
                    <b>{{ reservateur.reference }}</b>
                    <i class="bx bx-copy"></i>
                </button>
            </div>

            <nav>
                <button
                    type="button"
                    :class="{ active: activeSection === 'reservations' }"
                    @click="showReservations"
                >
                    <i class="bx bx-home"></i>
                    Mes Réservations
                </button>

                <button
                    type="button"
                    :class="{ active: activeSection === 'create' }"
                    @click="showCreateForm"
                >
                    <i class="bx bx-plus-circle"></i>
                    Créer Réservation
                </button>
            </nav>

            <button class="logout-btn" type="button" @click="logout">
                <i class="bx bx-log-out"></i>
                Déconnexion
            </button>
        </aside>

        <section class="content">
            <header class="top-header">
                <div>
                    <h1>
                        {{
                            activeSection === "reservations"
                                ? "Mes Réservations"
                                : formTitle
                        }}
                    </h1>
                    <p>
                        {{
                            activeSection === "reservations"
                                ? "Bienvenue ! Voici la liste de vos réservations."
                                : "Remplissez le formulaire pour envoyer votre demande."
                        }}
                    </p>
                </div>

                <button
                    v-if="activeSection === 'reservations'"
                    type="button"
                    class="create-btn"
                    @click="showCreateForm"
                >
                    <i class="bx bx-plus"></i>
                    Créer une réservation
                </button>

                <button
                    v-else
                    type="button"
                    class="create-btn soft"
                    @click="showReservations"
                >
                    <i class="bx bx-list-ul"></i>
                    Mes réservations
                </button>
            </header>

            <section
                v-if="activeSection === 'reservations'"
                class="card table-card"
            >
                <div class="card-title">
                    <h2>Liste de vos réservations</h2>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Référence Réservation</th>
                                <th>Service</th>
                                <th>Date de réservation</th>
                                <th>Date de service</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="reservation in reservations.data"
                                :key="reservation.id"
                            >
                                <td>{{ reservation.reference }}</td>
                                <td>{{ reservation.service }}</td>
                                <td>{{ formatDate(reservation.created_at) }}</td>
                                <td>{{ formatDate(reservation.date_service) }}</td>
                                <td>
                                    <span
                                        class="status"
                                        :class="reservation.statut"
                                    >
                                        {{ reservation.statut.replace("_", " ") }}
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button
                                            type="button"
                                            class="icon-btn"
                                            @click="viewing = reservation"
                                        >
                                            <i class="bx bx-show"></i>
                                        </button>

                                        <button
                                            v-if="canChange(reservation)"
                                            type="button"
                                            class="icon-btn"
                                            @click="editReservation(reservation)"
                                        >
                                            <i class="bx bx-pencil"></i>
                                        </button>

                                        <button
                                            v-if="canChange(reservation)"
                                            type="button"
                                            class="icon-btn danger"
                                            @click="cancelReservation(reservation)"
                                        >
                                            <i class="bx bx-trash"></i>
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

                <div class="info-line">
                    <i class="bx bx-info-circle"></i>
                    Vous pouvez modifier ou annuler une réservation uniquement si
                    elle n’est pas confirmée.
                </div>
            </section>

            <section
                v-if="activeSection === 'create'"
                class="card form-card"
            >
                <div class="card-title form-title">
                    <h2>{{ formTitle }}</h2>

                    <button
                        v-if="editing"
                        type="button"
                        class="reset-btn"
                        @click="resetForm"
                    >
                        Annuler modification
                    </button>
                </div>

                <form class="reservation-form" @submit.prevent="submit">
                    <label>
                        <span>Type de service *</span>
                        <input
                            v-model="form.service"
                            type="text"
                            required
                            placeholder="Sélectionnez un service"
                        />
                    </label>

                    <label>
                        <span>Lieu de départ *</span>
                        <input
                            v-model="form.lieu_depart"
                            type="text"
                            required
                            placeholder="Entrez le lieu de départ"
                        />
                    </label>

                    <label class="textarea-label">
                        <span>Informations complémentaires</span>
                        <textarea
                            v-model="form.informations_complementaires"
                            rows="5"
                            placeholder="Précisez vos demandes..."
                        ></textarea>
                    </label>

                    <label>
                        <span>Date du service *</span>
                        <input
                            v-model="form.date_service"
                            type="date"
                            required
                        />
                    </label>

                    <label>
                        <span>Lieu d’arrivée *</span>
                        <input
                            v-model="form.lieu_arrivee"
                            type="text"
                            required
                            placeholder="Entrez le lieu d’arrivée"
                        />
                    </label>

                    <label>
                        <span>Contact Téléphone / Email</span>
                        <input
                            v-model="form.contact"
                            type="text"
                            placeholder="Entrez votre contact"
                        />
                    </label>

                    <label>
                        <span>Nombre de personnes *</span>
                        <input
                            v-model="form.nombre_personnes"
                            type="number"
                            min="1"
                            placeholder="Entrez le nombre"
                        />
                    </label>

                    <label>
                        <span>Heure souhaitée</span>
                        <input v-model="form.heure_souhaitee" type="time" />
                    </label>

                    <div class="submit-zone">
                        <button class="submit-btn" type="submit">
                            <i class="bx bx-plus"></i>
                            {{
                                editing
                                    ? "Enregistrer les modifications"
                                    : "Enregistrer la réservation"
                            }}
                        </button>
                    </div>
                </form>
            </section>
        </section>

        <div v-if="viewing" class="modal-backdrop" @click.self="viewing = null">
            <section class="view-modal">
                <header>
                    <div>
                        <span>Détails réservation</span>
                        <h2>{{ viewing.reference }}</h2>
                    </div>

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
.portal-layout {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 270px 1fr;
    background: #f6f7fb;
    color: #0f172a;
}

.sidebar {
    position: sticky;
    top: 0;
    height: 100vh;
    padding: 26px 16px;
    display: flex;
    flex-direction: column;
    background:
        radial-gradient(circle at 20% 20%, rgba(76, 86, 220, 0.25), transparent 32%),
        linear-gradient(180deg, #17245b 0%, #0f1c48 58%, #101942 100%);
    color: #fff;
}

.side-brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 8px 28px;
}

.logo {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-weight: 950;
    background: linear-gradient(135deg, #4755e7, #6d5dfc);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.24);
}

.side-brand strong {
    display: block;
    font-size: 0.95rem;
    font-weight: 950;
    letter-spacing: 0.08em;
}

.side-brand span {
    display: block;
    margin-top: 3px;
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.78rem;
    font-weight: 700;
}

.reference-box {
    padding: 0 0 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.reference-box > span {
    display: block;
    margin: 0 8px 8px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.86rem;
    font-weight: 800;
}

.reference-box button {
    width: 100%;
    height: 48px;
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 10px;
    padding: 0 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #fff;
    background: rgba(255, 255, 255, 0.05);
    cursor: pointer;
}

.reference-box b {
    font-weight: 950;
}

nav {
    display: grid;
    gap: 10px;
    margin-top: 26px;
}

nav button,
.logout-btn {
    height: 52px;
    border: 0;
    border-radius: 8px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255, 255, 255, 0.86);
    background: transparent;
    font-weight: 850;
    cursor: pointer;
    transition: 0.2s ease;
    text-align: left;
}

nav button.active,
nav button:hover {
    color: #fff;
    background: linear-gradient(135deg, #4051dc, #5360ec);
    box-shadow: 0 14px 30px rgba(64, 81, 220, 0.35);
}

nav i,
.logout-btn i {
    font-size: 1.35rem;
}

.logout-btn {
    margin-top: auto;
}

.logout-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

.content {
    padding: 32px 42px 46px;
}

.top-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 28px;
    border-bottom: 1px solid #e6e8f0;
}

.top-header h1 {
    margin: 0;
    color: #121a36;
    font-size: 2rem;
    font-weight: 950;
    letter-spacing: -0.04em;
}

.top-header p {
    margin: 6px 0 0;
    color: #64748b;
    font-weight: 650;
}

.create-btn {
    height: 52px;
    border: 0;
    padding: 0 22px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-weight: 900;
    background: linear-gradient(135deg, #4251d8, #3f46c8);
    box-shadow: 0 14px 26px rgba(66, 81, 216, 0.25);
    cursor: pointer;
}

.create-btn.soft {
    color: #4251d8;
    background: #eef2ff;
    box-shadow: none;
}

.create-btn i {
    font-size: 1.35rem;
}

.card {
    margin-top: 28px;
    border: 1px solid #eceef5;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

.card-title {
    min-height: 62px;
    padding: 0 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #edf0f6;
}

.card-title h2 {
    margin: 0;
    color: #17203a;
    font-size: 1.05rem;
    font-weight: 950;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #f5f6fb;
}

th {
    padding: 15px 22px;
    color: #17203a;
    font-size: 0.78rem;
    font-weight: 950;
    text-align: left;
    white-space: nowrap;
}

td {
    padding: 14px 22px;
    border-top: 1px solid #edf0f6;
    color: #26324d;
    font-size: 0.88rem;
    font-weight: 700;
    white-space: nowrap;
}

.status {
    display: inline-flex;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 950;
    text-transform: capitalize;
}

.status.confirmee {
    color: #047857;
    background: #dcfce7;
}

.status.en_attente {
    color: #d97706;
    background: #fff7ed;
}

.status.annulee {
    color: #e11d48;
    background: #ffe4e6;
}

.actions {
    display: flex;
    gap: 10px;
}

.icon-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #e7eaf2;
    border-radius: 7px;
    display: grid;
    place-items: center;
    color: #4251d8;
    background: #fff;
    cursor: pointer;
}

.icon-btn.danger {
    color: #ef4444;
}

.info-line {
    padding: 16px 22px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
    font-size: 0.86rem;
    font-weight: 650;
}

.info-line i {
    color: #475569;
    font-size: 1.15rem;
}

.form-card {
    padding-bottom: 26px;
}

.reservation-form {
    padding: 18px 22px 0;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 18px 40px;
}

label span {
    display: block;
    margin-bottom: 7px;
    color: #26324d;
    font-size: 0.78rem;
    font-weight: 900;
}

input,
textarea {
    width: 100%;
    border: 1px solid #dde2ec;
    border-radius: 7px;
    padding: 0 12px;
    color: #17203a;
    background: #fff;
    font-size: 0.9rem;
    font-weight: 650;
    outline: 0;
    transition: 0.2s ease;
}

input {
    height: 42px;
}

textarea {
    min-height: 112px;
    padding-top: 12px;
    resize: vertical;
}

input:focus,
textarea:focus {
    border-color: #4251d8;
    box-shadow: 0 0 0 3px rgba(66, 81, 216, 0.1);
}

.textarea-label {
    grid-row: span 2;
}

.submit-zone {
    grid-column: 1 / -1;
    display: flex;
    justify-content: center;
    padding-top: 4px;
}

.submit-btn {
    min-width: 300px;
    height: 52px;
    border: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #fff;
    background: linear-gradient(135deg, #4251d8, #3f46c8);
    box-shadow: 0 14px 26px rgba(66, 81, 216, 0.25);
    font-weight: 950;
    cursor: pointer;
}

.reset-btn {
    border: 0;
    border-radius: 8px;
    padding: 10px 14px;
    color: #475569;
    background: #f1f5f9;
    font-weight: 900;
    cursor: pointer;
}

.empty-row {
    padding: 42px;
    text-align: center;
    color: #94a3b8;
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
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
}

.view-modal header {
    display: flex;
    justify-content: space-between;
    padding: 22px;
    border-bottom: 1px solid #eef2f7;
}

.view-modal header span,
dt {
    display: block;
    color: #4251d8;
    font-size: 0.74rem;
    font-weight: 950;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.view-modal header h2 {
    margin: 5px 0 0;
    color: #111827;
}

.view-modal header button {
    width: 42px;
    height: 42px;
    border: 0;
    border-radius: 12px;
    background: #f8fafc;
    cursor: pointer;
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

@media (max-width: 980px) {
    .portal-layout {
        grid-template-columns: 1fr;
    }

    .sidebar {
        position: relative;
        height: auto;
    }

    .content {
        padding: 24px 18px 34px;
    }

    .reservation-form {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .textarea-label,
    .submit-zone {
        grid-column: auto;
        grid-row: auto;
    }

    .top-header {
        flex-direction: column;
    }
}
</style>