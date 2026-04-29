<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    vehicules: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: "" }) },
});

const search = ref(props.filters.search || "");
const showNewRow = ref(false);
const editingId = ref(null);

const rows = computed(() => props.vehicules?.data || []);

const form = useForm({
    matricule: "",
    marque: "",
    modele: "",
    type: "",
    couleur: "",
    annee: "",
    nombre_places: "",
    carburant: "",
    boite_vitesse: "",
    numero_assurance: "",
    date_expiration_assurance: "",
    date_visite_technique: "",
    date_expiration_visite: "",
    status: "Disponible",
    notes: "",
});

const editForm = useForm({
    matricule: "",
    marque: "",
    modele: "",
    type: "",
    couleur: "",
    annee: "",
    nombre_places: "",
    carburant: "",
    boite_vitesse: "",
    numero_assurance: "",
    date_expiration_assurance: "",
    date_visite_technique: "",
    date_expiration_visite: "",
    status: "Disponible",
    notes: "",
});

let searchTimer = null;

const vehicleBrands = {
    Renault: [
        "Clio 4",
        "Clio 5",
        "Megane",
        "Captur",
        "Kangoo",
        "Trafic",
        "Master",
    ],
    Dacia: ["Logan", "Sandero", "Duster", "Dokker", "Lodgy"],
    Peugeot: ["208", "301", "308", "Partner", "Expert", "Boxer"],
    Citroen: ["C3", "C4", "Berlingo", "Jumpy", "Jumper"],
    Ford: ["Fiesta", "Focus", "Tourneo", "Transit"],
    Mercedes: ["Classe C", "Classe E", "Vito", "Sprinter"],
    Volkswagen: ["Golf", "Polo", "Caddy", "Transporter", "Crafter"],
    Fiat: ["Punto", "Tipo", "Doblo", "Ducato"],
    Hyundai: ["i10", "i20", "Accent", "Tucson", "H1"],
    Toyota: ["Yaris", "Corolla", "Hilux", "Hiace", "Land Cruiser"],
    Kia: ["Picanto", "Rio", "Sportage", "Sorento"],
    Nissan: ["Micra", "Qashqai", "Navara", "NV200"],
    Opel: ["Corsa", "Astra", "Combo", "Vivaro"],
    Seat: ["Ibiza", "Leon", "Ateca"],
    Skoda: ["Fabia", "Octavia", "Superb"],
    BMW: ["Série 1", "Série 3", "Série 5", "X1", "X3"],
    Audi: ["A3", "A4", "A6", "Q3", "Q5"],
};

const brandOptions = Object.keys(vehicleBrands);

const getModelsByBrand = (brand) => {
    return vehicleBrands[brand] || [];
};

const onBrandChange = (targetForm) => {
    targetForm.modele = "";
};

const vehicleTypes = [
    "Berline",
    "SUV",
    "4x4",
    "Van",
    "Minibus",
    "Bus",
    "Utilitaire",
    "Pick-up",
    "Camionnette",
    "Luxe / VIP",
];

const vehicleColors = [
    "Blanc",
    "Noir",
    "Gris",
    "Gris foncé",
    "Argent",
    "Bleu",
    "Bleu foncé",
    "Rouge",
    "Bordeaux",
    "Vert",
    "Jaune",
    "Marron",
    "Beige",
    "Orange",
    "Violet",
];

watch(search, (value) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(
            route("vehicules.index"),
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 350);
});

const resetForm = () => {
    form.reset();
    form.status = "Disponible";
    form.clearErrors();
};

const openNewRow = () => {
    resetForm();
    showNewRow.value = true;
    editingId.value = null;
};

const cancelNewRow = () => {
    showNewRow.value = false;
    resetForm();
};

const saveVehicule = () => {
    form.post(route("vehicules.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showNewRow.value = false;
            resetForm();

            Swal.fire({
                icon: "success",
                title: "Succès",
                text: "Véhicule ajouté avec succès.",
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const startEdit = (vehicule) => {
    showNewRow.value = false;
    editingId.value = vehicule.id;

    editForm.clearErrors();
    editForm.matricule = vehicule.matricule || "";
    editForm.marque = vehicule.marque || "";
    editForm.modele = vehicule.modele || "";
    editForm.type = vehicule.type || "";
    editForm.couleur = vehicule.couleur || "";
    editForm.annee = vehicule.annee || "";
    editForm.nombre_places = vehicule.nombre_places || "";
    editForm.carburant = vehicule.carburant || "";
    editForm.boite_vitesse = vehicule.boite_vitesse || "";
    editForm.numero_assurance = vehicule.numero_assurance || "";
    editForm.date_expiration_assurance =
        vehicule.date_expiration_assurance || "";
    editForm.date_visite_technique = vehicule.date_visite_technique || "";
    editForm.date_expiration_visite = vehicule.date_expiration_visite || "";
    editForm.status = vehicule.status || "Disponible";
    editForm.notes = vehicule.notes || "";
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.clearErrors();
};

const updateVehicule = (id) => {
    editForm.put(route("vehicules.update", id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;

            Swal.fire({
                icon: "success",
                title: "Modifié",
                text: "Véhicule modifié avec succès.",
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const destroyVehicule = (id) => {
    Swal.fire({
        title: "Supprimer ce véhicule ?",
        text: "Cette action est irréversible.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("vehicules.destroy", id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: "success",
                        title: "Supprimé",
                        text: "Véhicule supprimé avec succès.",
                        timer: 1600,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const statusClass = (status) => {
    if (status === "Disponible") return "status-disponible";
    if (status === "En maintenance") return "status-maintenance";
    if (status === "Indisponible") return "status-indisponible";
    return "status-neutral";
};

const formatDate = (value) => {
    if (!value) return "-";
    return String(value).split("T")[0];
};
</script>

<template>
    <Head title="Véhicules" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="hero-card card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                    >
                        <div>
                            <div class="hero-kicker">Gestion du parc</div>
                            <h1 class="hero-title">Véhicules</h1>
                            <p class="hero-subtitle mb-0">
                                Liste complète des véhicules, assurances,
                                visites techniques et disponibilités.
                            </p>
                        </div>

                        <button class="btn btn-add" @click="openNewRow">
                            <i class="bx bx-plus me-1"></i>
                            Nouveau véhicule
                        </button>
                    </div>
                </div>
            </div>

            <div class="toolbar-card card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="search-box">
                        <i class="bx bx-search"></i>
                        <input
                            v-model="search"
                            type="text"
                            class="form-control"
                            placeholder="Rechercher par matricule, marque, modèle, type ou statut..."
                        />
                    </div>
                </div>
            </div>

            <div class="vehicule-table-card card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table align-middle table-hover custom-vehicule-table mb-0"
                        >
                            <thead>
                                <tr>
                                    <th>Matricule</th>
                                    <th>Marque</th>
                                    <th>Modèle</th>
                                    <th>Type</th>
                                    <th>Couleur</th>
                                    <th>Année</th>
                                    <th>Places</th>
                                    <th>Carburant</th>
                                    <th>Boîte</th>
                                    <th>N° Assurance</th>
                                    <th>Exp. Assurance</th>
                                    <th>Visite tech.</th>
                                    <th>Exp. Visite</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="showNewRow" class="new-row">
                                    <td>
                                        <input
                                            v-model="form.matricule"
                                            class="form-control table-input"
                                            placeholder="Matricule"
                                        />
                                        <small class="text-danger">{{
                                            form.errors.matricule
                                        }}</small>
                                    </td>
                                    <td>
                                        <select
                                            v-model="form.marque"
                                            class="form-select table-input"
                                            @change="onBrandChange(form)"
                                        >
                                            <option value="">Marque...</option>
                                            <option
                                                v-for="brand in brandOptions"
                                                :key="brand"
                                                :value="brand"
                                            >
                                                {{ brand }}
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <select
                                            v-model="form.modele"
                                            class="form-select table-input"
                                            :disabled="!form.marque"
                                        >
                                            <option value="">Modèle...</option>
                                            <option
                                                v-for="model in getModelsByBrand(
                                                    form.marque,
                                                )"
                                                :key="model"
                                                :value="model"
                                            >
                                                {{ model }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <select
                                            v-model="form.type"
                                            class="form-select table-input"
                                        >
                                            <option value="">Type...</option>

                                            <option
                                                v-for="type in vehicleTypes"
                                                :key="type"
                                                :value="type"
                                            >
                                                {{ type }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <select
                                            v-model="form.couleur"
                                            class="form-select table-input"
                                        >
                                            <option value="">Couleur...</option>

                                            <option
                                                v-for="color in vehicleColors"
                                                :key="color"
                                                :value="color"
                                            >
                                                {{ color }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            v-model="form.annee"
                                            type="number"
                                            class="form-control table-input tiny-input"
                                            placeholder="2026"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="form.nombre_places"
                                            type="number"
                                            class="form-control table-input tiny-input"
                                            placeholder="Places"
                                        />
                                    </td>
                                    <td>
                                        <select
                                            v-model="form.carburant"
                                            class="form-select table-input"
                                        >
                                            <option value="">-</option>
                                            <option>Diesel</option>
                                            <option>Essence</option>
                                            <option>Hybride</option>
                                            <option>Électrique</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select
                                            v-model="form.boite_vitesse"
                                            class="form-select table-input"
                                        >
                                            <option value="">-</option>
                                            <option>Manuelle</option>
                                            <option>Automatique</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            v-model="form.numero_assurance"
                                            class="form-control table-input"
                                            placeholder="N° assurance"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="
                                                form.date_expiration_assurance
                                            "
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="form.date_visite_technique"
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            v-model="
                                                form.date_expiration_visite
                                            "
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>
                                    <td>
                                        <select
                                            v-model="form.status"
                                            class="form-select table-input"
                                        >
                                            <option>Disponible</option>
                                            <option>En maintenance</option>
                                            <option>Indisponible</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            v-model="form.notes"
                                            class="form-control table-input notes-input"
                                            placeholder="Notes"
                                        />
                                    </td>
                                    <td class="actions-cell">
                                        <div class="row-actions">
                                            <button
                                                class="btn btn-save-action btn-sm"
                                                :disabled="form.processing"
                                                @click="saveVehicule"
                                            >
                                                <span
                                                    v-if="form.processing"
                                                    class="spinner-border spinner-border-sm me-1"
                                                ></span>
                                                Enregistrer
                                            </button>
                                            <button
                                                class="btn btn-cancel-action btn-sm"
                                                @click="cancelNewRow"
                                            >
                                                Annuler
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-for="vehicule in rows" :key="vehicule.id">
                                    <template v-if="editingId === vehicule.id">
                                        <td>
                                            <input
                                                v-model="editForm.matricule"
                                                class="form-control table-input"
                                            />
                                            <small class="text-danger">{{
                                                editForm.errors.matricule
                                            }}</small>
                                        </td>
                                        <td>
                                            <select
                                                v-model="editForm.marque"
                                                class="form-select table-input"
                                                @change="
                                                    onBrandChange(editForm)
                                                "
                                            >
                                                <option value="">
                                                    Marque...
                                                </option>
                                                <option
                                                    v-for="brand in brandOptions"
                                                    :key="brand"
                                                    :value="brand"
                                                >
                                                    {{ brand }}
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            <select
                                                v-model="editForm.modele"
                                                class="form-select table-input"
                                                :disabled="!editForm.marque"
                                            >
                                                <option value="">
                                                    Modèle...
                                                </option>
                                                <option
                                                    v-for="model in getModelsByBrand(
                                                        editForm.marque,
                                                    )"
                                                    :key="model"
                                                    :value="model"
                                                >
                                                    {{ model }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <select
                                                v-model="editForm.type"
                                                class="form-select table-input"
                                            >
                                                <option value="">
                                                    Type...
                                                </option>

                                                <option
                                                    v-for="type in vehicleTypes"
                                                    :key="type"
                                                    :value="type"
                                                >
                                                    {{ type }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <select
                                                v-model="editForm.couleur"
                                                class="form-select table-input"
                                            >
                                                <option value="">
                                                    Couleur...
                                                </option>

                                                <option
                                                    v-for="color in vehicleColors"
                                                    :key="color"
                                                    :value="color"
                                                >
                                                    {{ color }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                v-model="editForm.annee"
                                                type="number"
                                                class="form-control table-input tiny-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                v-model="editForm.nombre_places"
                                                type="number"
                                                class="form-control table-input tiny-input"
                                            />
                                        </td>
                                        <td>
                                            <select
                                                v-model="editForm.carburant"
                                                class="form-select table-input"
                                            >
                                                <option value="">-</option>
                                                <option>Diesel</option>
                                                <option>Essence</option>
                                                <option>Hybride</option>
                                                <option>Électrique</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select
                                                v-model="editForm.boite_vitesse"
                                                class="form-select table-input"
                                            >
                                                <option value="">-</option>
                                                <option>Manuelle</option>
                                                <option>Automatique</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                v-model="
                                                    editForm.numero_assurance
                                                "
                                                class="form-control table-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                v-model="
                                                    editForm.date_expiration_assurance
                                                "
                                                type="date"
                                                class="form-control table-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                v-model="
                                                    editForm.date_visite_technique
                                                "
                                                type="date"
                                                class="form-control table-input"
                                            />
                                        </td>
                                        <td>
                                            <input
                                                v-model="
                                                    editForm.date_expiration_visite
                                                "
                                                type="date"
                                                class="form-control table-input"
                                            />
                                        </td>
                                        <td>
                                            <select
                                                v-model="editForm.status"
                                                class="form-select table-input"
                                            >
                                                <option>Disponible</option>
                                                <option>En maintenance</option>
                                                <option>Indisponible</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input
                                                v-model="editForm.notes"
                                                class="form-control table-input notes-input"
                                            />
                                        </td>
                                        <td class="actions-cell">
                                            <div class="row-actions">
                                                <button
                                                    class="btn btn-save-action btn-sm"
                                                    :disabled="
                                                        editForm.processing
                                                    "
                                                    @click="
                                                        updateVehicule(
                                                            vehicule.id,
                                                        )
                                                    "
                                                >
                                                    Modifier
                                                </button>
                                                <button
                                                    class="btn btn-cancel-action btn-sm"
                                                    @click="cancelEdit"
                                                >
                                                    Annuler
                                                </button>
                                            </div>
                                        </td>
                                    </template>

                                    <template v-else>
                                        <td>
                                            <span class="matricule-badge">{{
                                                vehicule.matricule
                                            }}</span>
                                        </td>
                                        <td>{{ vehicule.marque || "-" }}</td>
                                        <td>{{ vehicule.modele || "-" }}</td>
                                        <td>{{ vehicule.type || "-" }}</td>
                                        <td>{{ vehicule.couleur || "-" }}</td>
                                        <td>{{ vehicule.annee || "-" }}</td>
                                        <td>
                                            {{ vehicule.nombre_places || "-" }}
                                        </td>
                                        <td>{{ vehicule.carburant || "-" }}</td>
                                        <td>
                                            {{ vehicule.boite_vitesse || "-" }}
                                        </td>
                                        <td>
                                            {{
                                                vehicule.numero_assurance || "-"
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatDate(
                                                    vehicule.date_expiration_assurance,
                                                )
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatDate(
                                                    vehicule.date_visite_technique,
                                                )
                                            }}
                                        </td>
                                        <td>
                                            {{
                                                formatDate(
                                                    vehicule.date_expiration_visite,
                                                )
                                            }}
                                        </td>
                                        <td>
                                            <span
                                                class="status-badge"
                                                :class="
                                                    statusClass(vehicule.status)
                                                "
                                            >
                                                {{ vehicule.status || "-" }}
                                            </span>
                                        </td>
                                        <td class="notes-cell">
                                            {{ vehicule.notes || "-" }}
                                        </td>
                                        <td class="actions-cell">
                                            <div class="row-actions">
                                                <button
                                                    class="btn btn-edit-action btn-sm"
                                                    @click="startEdit(vehicule)"
                                                >
                                                    <i
                                                        class="bx bx-edit me-1"
                                                    ></i>
                                                    Modifier
                                                </button>
                                                <button
                                                    class="btn btn-delete-action btn-sm"
                                                    @click="
                                                        destroyVehicule(
                                                            vehicule.id,
                                                        )
                                                    "
                                                >
                                                    <i
                                                        class="bx bx-trash me-1"
                                                    ></i>
                                                    Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </template>
                                </tr>

                                <tr v-if="rows.length === 0 && !showNewRow">
                                    <td
                                        colspan="16"
                                        class="text-center py-5 text-muted"
                                    >
                                        Aucun véhicule trouvé.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="vehicules?.links?.length"
                        class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top"
                    >
                        <div class="text-muted small">
                            Page {{ vehicules.current_page }} /
                            {{ vehicules.last_page }}
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <Link
                                v-for="(link, index) in vehicules.links"
                                :key="index"
                                :href="link.url || ''"
                                v-html="link.label"
                                class="btn btn-sm"
                                :class="
                                    link.active
                                        ? 'btn-danger-red'
                                        : 'btn-outline-secondary'
                                "
                                :disabled="!link.url"
                                preserve-scroll
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(220, 38, 38, 0.05),
            transparent 25%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(37, 99, 235, 0.06),
            transparent 25%
        ),
        #f4f6fb;
}

.hero-card {
    border-radius: 26px;
    background:
        radial-gradient(
            circle at 90% 20%,
            rgba(255, 255, 255, 0.2),
            transparent 22%
        ),
        linear-gradient(135deg, #c1121f, #7f1024 48%, #1d4ed8);
    color: #fff;
}

.hero-kicker {
    display: inline-flex;
    padding: 7px 13px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    font-weight: 800;
    font-size: 13px;
    margin-bottom: 10px;
}

.hero-title {
    font-size: 32px;
    font-weight: 950;
    margin: 0;
    color: #fff;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.88);
    font-weight: 600;
}

.btn-add {
    min-height: 46px;
    border-radius: 15px;
    border: 0;
    padding: 10px 18px;
    background: #fff;
    color: #b91c1c;
    font-weight: 900;
    box-shadow: 0 16px 30px rgba(0, 0, 0, 0.18);
}

.toolbar-card,
.vehicule-table-card {
    border-radius: 24px;
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 20px;
}

.search-box input {
    height: 50px;
    border-radius: 16px;
    padding-left: 46px;
    border: 1px solid #e2e8f0;
    font-weight: 700;
}

.table-responsive {
    overflow-x: auto;
}

.custom-vehicule-table {
    min-width: 2200px;
    border-collapse: separate;
    border-spacing: 0;
}

.custom-vehicule-table thead th {
    background: #fff3f4;
    color: #9f101d;
    font-size: 14px;
    font-weight: 950;
    padding: 18px 16px;
    border-bottom: 1px solid #f3d4d7;
    white-space: nowrap;
}

.custom-vehicule-table tbody td {
    padding: 15px 14px;
    border-bottom: 1px solid #eef1f5;
    background: #fff;
    vertical-align: middle;
    min-width: 130px;
    font-weight: 650;
    color: #334155;
}

.new-row td {
    background: #fffafa !important;
    vertical-align: top;
}

.table-input {
    min-width: 145px;
    height: 44px;
    border-radius: 14px;
    border: 1px solid #d9e1ec;
    background: #fff;
    box-shadow: none;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}

.table-input:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
}

.tiny-input {
    min-width: 90px;
}

.notes-input {
    min-width: 220px;
}

.notes-cell {
    max-width: 260px;
    white-space: normal;
}

.matricule-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 13px;
    border-radius: 999px;
    background: linear-gradient(135deg, #f8fbff, #eef4ff);
    border: 1px solid #dbe7ff;
    color: #1d4ed8;
    font-size: 13px;
    font-weight: 950;
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    padding: 8px 13px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 950;
    white-space: nowrap;
}

.status-disponible {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.status-maintenance {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}

.status-indisponible {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.status-neutral {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.actions-cell {
    min-width: 400px;
}

.row-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
}

.btn-save-action {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 900;
    padding: 10px 14px;
}

.btn-edit-action {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    font-weight: 900;
    padding: 10px 14px;
}

.btn-cancel-action {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-weight: 900;
    padding: 10px 14px;
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 900;
    padding: 10px 14px;
}

.btn-danger-red {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}

.btn-delete-action:hover,
.btn-save-action:hover,
.btn-add:hover {
    color: #fff;
}

.btn-add:hover {
    background: #dc2626;
}
</style>
