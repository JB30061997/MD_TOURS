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
    status: "Available",
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
    status: "Available",
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
    Mercedes: ["C-Class", "E-Class", "Vito", "Sprinter"],
    Volkswagen: ["Golf", "Polo", "Caddy", "Transporter", "Crafter"],
    Fiat: ["Punto", "Tipo", "Doblo", "Ducato"],
    Hyundai: ["i10", "i20", "Accent", "Tucson", "H1"],
    Toyota: ["Yaris", "Corolla", "Hilux", "Hiace", "Land Cruiser"],
    Kia: ["Picanto", "Rio", "Sportage", "Sorento"],
    Nissan: ["Micra", "Qashqai", "Navara", "NV200"],
    Opel: ["Corsa", "Astra", "Combo", "Vivaro"],
    Seat: ["Ibiza", "Leon", "Ateca"],
    Skoda: ["Fabia", "Octavia", "Superb"],
    BMW: ["Series 1", "Series 3", "Series 5", "X1", "X3"],
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
    "Sedan",
    "SUV",
    "4x4",
    "Van",
    "Minibus",
    "Bus",
    "Utility Vehicle",
    "Pick-up",
    "Small Truck",
    "Luxury / VIP",
];

const vehicleColors = [
    "White",
    "Black",
    "Gray",
    "Dark Gray",
    "Silver",
    "Blue",
    "Dark Blue",
    "Red",
    "Burgundy",
    "Green",
    "Yellow",
    "Brown",
    "Beige",
    "Orange",
    "Purple",
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
    form.status = "Available";
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
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Vehicle added successfully",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
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
    editForm.status = vehicule.status || "Available";
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
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Vehicle updated successfully",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
        },
    });
};

const destroyVehicule = (id) => {
    Swal.fire({
        title: "Delete this vehicle?",
        text: "This action is irreversible.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("vehicules.destroy", id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Vehicle deleted successfully",
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true,
                    });
                },
            });
        }
    });
};

const statusClass = (status) => {
    if (status === "Available") return "status-available";
    if (status === "Under maintenance") return "status-maintenance";
    if (status === "Unavailable") return "status-unavailable";
    return "status-neutral";
};

const formatDate = (value) => {
    if (!value) return "-";
    return String(value).split("T")[0];
};
</script>

<template>
    <Head title="Vehicles" />

    <div class="vehicles-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-bus"></i>
                        </div>

                        <div>
                            <!-- <div class="hero-kicker">Fleet Management</div> -->
                            <h1 class="hero-title">Vehicles</h1>
                            <p class="hero-subtitle mb-0">
                                Full list of vehicles, insurance, technical
                                inspections and availability.
                            </p>
                        </div>
                    </div>

                    <button class="btn btn-add" @click="openNewRow">
                        <i class="bx bx-plus-circle me-2"></i>
                        New Vehicle
                    </button>
                </div>
            </div>

            <div class="toolbar-card mb-4">
                <div class="search-box">
                    <i class="bx bx-search"></i>
                    <input
                        v-model="search"
                        type="text"
                        class="form-control"
                        placeholder="Search by registration number, brand, model, type or status..."
                    />
                </div>
            </div>

            <div class="vehicule-table-card">
                <div class="table-responsive">
                    <table class="table align-middle table-hover custom-vehicule-table mb-0">
                        <thead>
                            <tr>
                                <th>Registration No.</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Type</th>
                                <th>Color</th>
                                <th>Year</th>
                                <th>Seats</th>
                                <th>Fuel</th>
                                <th>Gearbox</th>
                                <th>Insurance No.</th>
                                <th>Insurance Exp.</th>
                                <th>Technical Insp.</th>
                                <th>Inspection Exp.</th>
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
                                        placeholder="Registration"
                                    />
                                    <small class="text-danger">
                                        {{ form.errors.matricule }}
                                    </small>
                                </td>

                                <td>
                                    <select
                                        v-model="form.marque"
                                        class="form-select table-input"
                                        @change="onBrandChange(form)"
                                    >
                                        <option value="">Brand...</option>
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
                                        <option value="">Model...</option>
                                        <option
                                            v-for="model in getModelsByBrand(form.marque)"
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
                                        <option value="">Color...</option>
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
                                        placeholder="Seats"
                                    />
                                </td>

                                <td>
                                    <select
                                        v-model="form.carburant"
                                        class="form-select table-input"
                                    >
                                        <option value="">-</option>
                                        <option>Diesel</option>
                                        <option>Gasoline</option>
                                        <option>Hybrid</option>
                                        <option>Electric</option>
                                    </select>
                                </td>

                                <td>
                                    <select
                                        v-model="form.boite_vitesse"
                                        class="form-select table-input"
                                    >
                                        <option value="">-</option>
                                        <option>Manual</option>
                                        <option>Automatic</option>
                                    </select>
                                </td>

                                <td>
                                    <input
                                        v-model="form.numero_assurance"
                                        class="form-control table-input"
                                        placeholder="Insurance No."
                                    />
                                </td>

                                <td>
                                    <input
                                        v-model="form.date_expiration_assurance"
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
                                        v-model="form.date_expiration_visite"
                                        type="date"
                                        class="form-control table-input"
                                    />
                                </td>

                                <td>
                                    <select
                                        v-model="form.status"
                                        class="form-select table-input"
                                    >
                                        <option>Available</option>
                                        <option>Under maintenance</option>
                                        <option>Unavailable</option>
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
                                            Save
                                        </button>

                                        <button
                                            class="btn btn-cancel-action btn-sm"
                                            @click="cancelNewRow"
                                        >
                                            Cancel
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
                                        <small class="text-danger">
                                            {{ editForm.errors.matricule }}
                                        </small>
                                    </td>

                                    <td>
                                        <select
                                            v-model="editForm.marque"
                                            class="form-select table-input"
                                            @change="onBrandChange(editForm)"
                                        >
                                            <option value="">Brand...</option>
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
                                            <option value="">Model...</option>
                                            <option
                                                v-for="model in getModelsByBrand(editForm.marque)"
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
                                            v-model="editForm.couleur"
                                            class="form-select table-input"
                                        >
                                            <option value="">Color...</option>
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
                                            <option>Gasoline</option>
                                            <option>Hybrid</option>
                                            <option>Electric</option>
                                        </select>
                                    </td>

                                    <td>
                                        <select
                                            v-model="editForm.boite_vitesse"
                                            class="form-select table-input"
                                        >
                                            <option value="">-</option>
                                            <option>Manual</option>
                                            <option>Automatic</option>
                                        </select>
                                    </td>

                                    <td>
                                        <input
                                            v-model="editForm.numero_assurance"
                                            class="form-control table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="editForm.date_expiration_assurance"
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="editForm.date_visite_technique"
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>

                                    <td>
                                        <input
                                            v-model="editForm.date_expiration_visite"
                                            type="date"
                                            class="form-control table-input"
                                        />
                                    </td>

                                    <td>
                                        <select
                                            v-model="editForm.status"
                                            class="form-select table-input"
                                        >
                                            <option>Available</option>
                                            <option>Under maintenance</option>
                                            <option>Unavailable</option>
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
                                                :disabled="editForm.processing"
                                                @click="updateVehicule(vehicule.id)"
                                            >
                                                Update
                                            </button>

                                            <button
                                                class="btn btn-cancel-action btn-sm"
                                                @click="cancelEdit"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </template>

                                <template v-else>
                                    <td>
                                        <span class="matricule-badge">
                                            {{ vehicule.matricule }}
                                        </span>
                                    </td>

                                    <td>{{ vehicule.marque || "-" }}</td>
                                    <td>{{ vehicule.modele || "-" }}</td>
                                    <td>{{ vehicule.type || "-" }}</td>
                                    <td>{{ vehicule.couleur || "-" }}</td>
                                    <td>{{ vehicule.annee || "-" }}</td>
                                    <td>{{ vehicule.nombre_places || "-" }}</td>
                                    <td>{{ vehicule.carburant || "-" }}</td>
                                    <td>{{ vehicule.boite_vitesse || "-" }}</td>
                                    <td>{{ vehicule.numero_assurance || "-" }}</td>
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
                                            :class="statusClass(vehicule.status)"
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
                                                <i class="bx bx-edit me-1"></i>
                                                Edit
                                            </button>

                                            <button
                                                class="btn btn-delete-action btn-sm"
                                                @click="destroyVehicule(vehicule.id)"
                                            >
                                                <i class="bx bx-trash me-1"></i>
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>

                            <tr v-if="rows.length === 0 && !showNewRow">
                                <td colspan="16">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bx bx-search-alt"></i>
                                        </div>
                                        <h5 class="mb-2">No vehicles found</h5>
                                        <p class="text-muted mb-0">
                                            Try adjusting your search or add a
                                            new vehicle.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="vehicules?.links?.length" class="pagination-area">
                    <div class="pagination-info">
                        Page {{ vehicules.current_page }} /
                        {{ vehicules.last_page }}
                    </div>

                    <div class="pagination-list">
                        <Link
                            v-for="(link, index) in vehicules.links"
                            :key="index"
                            :href="link.url || ''"
                            v-html="link.label"
                            class="page-btn"
                            :class="{
                                active: link.active,
                                disabled: !link.url,
                            }"
                            preserve-scroll
                            preserve-state
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.vehicles-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(225, 29, 72, 0.1), transparent 24%),
        radial-gradient(circle at top right, rgba(249, 115, 22, 0.08), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
    box-shadow: 0 20px 40px rgba(190, 24, 93, 0.18);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18), transparent 25%),
        radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.12), transparent 25%);
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-size: 34px;
    backdrop-filter: blur(8px);
}

.hero-kicker {
    display: inline-flex;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-weight: 800;
    font-size: 0.78rem;
    margin-bottom: 8px;
}

.hero-title {
    color: #fff;
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 6px;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.98rem;
}

.btn-add {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-add:hover {
    transform: translateY(-2px);
    color: #7f1d1d;
    background: #fff;
}

.toolbar-card,
.vehicule-table-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 24px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.toolbar-card {
    padding: 20px;
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
    min-height: 56px;
    border-radius: 18px;
    padding-left: 46px;
    border: 1px solid #e2e8f0;
    font-weight: 700;
}

.search-box input:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
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
    padding: 16px 18px;
    background: linear-gradient(180deg, #fff1f2 0%, #fff7ed 100%);
    color: #9f1239;
    font-size: 0.85rem;
    font-weight: 900;
    border-bottom: 1px solid #ffe4e6;
    white-space: nowrap;
}

.custom-vehicule-table tbody td {
    padding: 18px;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    vertical-align: middle;
    min-width: 130px;
    font-weight: 650;
    color: #334155;
}

.custom-vehicule-table tbody tr:hover td {
    background: rgba(248, 250, 252, 0.95);
}

.new-row td {
    background: #fffafa !important;
    vertical-align: top;
}

.table-input {
    min-width: 145px;
    min-height: 44px;
    border-radius: 14px;
    border: 1px solid #d9e1ec;
    background: #fff;
    box-shadow: none;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}

.table-input:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
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
    background: #eff6ff;
    border: 1px solid #dbeafe;
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

.status-available {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.status-maintenance {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}

.status-unavailable {
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

.btn-save-action,
.btn-edit-action,
.btn-cancel-action,
.btn-delete-action {
    border-radius: 12px;
    font-weight: 900;
    padding: 10px 14px;
}

.btn-save-action {
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    color: #fff;
    border: 0;
}

.btn-edit-action {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}

.btn-cancel-action {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 0;
}

.btn-delete-action:hover,
.btn-save-action:hover {
    color: #fff;
    transform: translateY(-2px);
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-icon {
    width: 78px;
    height: 78px;
    margin: 0 auto 16px;
    border-radius: 24px;
    background: linear-gradient(135deg, #fda4af 0%, #fdba74 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
}

.pagination-area {
    padding: 18px 24px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    border-top: 1px solid #eef2f7;
}

.pagination-info {
    color: #64748b;
    font-size: 0.88rem;
    font-weight: 700;
}

.pagination-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.page-btn {
    min-width: 42px;
    height: 42px;
    padding: 0 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-weight: 700;
}

.page-btn.active {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.page-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
    background: #f8fafc;
}

@media (max-width: 768px) {
    .hero-card {
        padding: 20px;
    }

    .hero-title {
        font-size: 1.5rem;
    }
}
</style>