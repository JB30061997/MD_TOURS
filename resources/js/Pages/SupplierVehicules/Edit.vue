<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    supplierVehicule: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    user_id: props.supplierVehicule.user_id || "",
    name: props.supplierVehicule.name || "",
    phone: props.supplierVehicule.phone || "",
    email: props.supplierVehicule.email || "",
    address: props.supplierVehicule.address || "",
    notes: props.supplierVehicule.notes || "",
    is_active: Boolean(props.supplierVehicule.is_active),
});

const submit = () => {
    form.put(route("supplier-vehicules.update", props.supplierVehicule.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: "success",
                title: "Succès",
                text: "Supplier véhicule modifié avec succès.",
                timer: 2000,
                showConfirmButton: false,
            });
        },
    });
};
</script>

<template>
    <Head title="Modifier Supplier Véhicule" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="hero-card mb-4">
                <div>
                    <h1>Modifier Supplier Véhicule</h1>
                    <p>{{ supplierVehicule.name }}</p>
                </div>

                <Link
                    :href="route('supplier-vehicules.index')"
                    class="btn btn-hero"
                >
                    <i class="bx bx-arrow-back me-2"></i>
                    Retour
                </Link>
            </div>

            <div class="form-card">
                <form @submit.prevent="submit">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input
                                v-model="form.name"
                                class="form-control input-modern"
                                type="text"
                            />
                            <div v-if="form.errors.name" class="error-text">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">User lié</label>
                            <select
                                v-model="form.user_id"
                                class="form-select input-modern"
                            >
                                <option value="">Aucun user</option>
                                <option
                                    v-for="user in users"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }} - {{ user.email }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input
                                v-model="form.phone"
                                class="form-control input-modern"
                                type="text"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input
                                v-model="form.email"
                                class="form-control input-modern"
                                type="email"
                            />
                            <div v-if="form.errors.email" class="error-text">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select
                                v-model="form.is_active"
                                class="form-select input-modern"
                            >
                                <option :value="true">Actif</option>
                                <option :value="false">Inactif</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Adresse</label>
                            <input
                                v-model="form.address"
                                class="form-control input-modern"
                                type="text"
                            />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="form.notes"
                                class="form-control input-modern textarea-modern"
                                rows="4"
                            ></textarea>
                        </div>
                    </div>

                    <div class="actions mt-4">
                        <Link
                            :href="route('supplier-vehicules.index')"
                            class="btn btn-cancel"
                        >
                            Annuler
                        </Link>

                        <button
                            type="submit"
                            class="btn btn-save"
                            :disabled="form.processing"
                        >
                            <span
                                v-if="form.processing"
                                class="spinner-border spinner-border-sm me-2"
                            ></span>
                            <i v-else class="bx bx-save me-2"></i>
                            Mettre à jour
                        </button>
                    </div>
                </form>
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
            rgba(193, 18, 31, 0.06),
            transparent 18%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(29, 78, 216, 0.06),
            transparent 18%
        ),
        #f4f6fb;
}

.hero-card {
    border-radius: 28px;
    padding: 32px;
    color: #fff;
    background: linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    box-shadow: 0 20px 45px rgba(127, 16, 36, 0.18);
}

.hero-card h1 {
    font-weight: 900;
    margin: 0;
}

.hero-card p {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.85);
    font-weight: 700;
}

.btn-hero {
    min-height: 48px;
    border-radius: 16px;
    padding: 0 20px;
    color: #fff;
    font-weight: 900;
    background: rgba(255, 255, 255, 0.16);
    border: none;
}

.btn-hero:hover {
    color: #fff;
}

.form-card {
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.95);
    padding: 32px;
    border: 1px solid #eef2f7;
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.06);
}

.form-label {
    font-weight: 900;
    color: #334155;
    margin-bottom: 10px;
}

.input-modern {
    min-height: 52px;
    border-radius: 16px;
    border: 1px solid #dbe2ea;
    font-weight: 600;
    background: linear-gradient(180deg, #fff, #fbfcff);
}

.input-modern:focus {
    border-color: rgba(29, 78, 216, 0.35);
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08);
}

.textarea-modern {
    min-height: 120px;
}

.error-text {
    margin-top: 8px;
    color: #dc2626;
    font-size: 0.85rem;
    font-weight: 800;
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-cancel,
.btn-save {
    min-height: 48px;
    border-radius: 16px;
    padding: 0 22px;
    font-weight: 900;
}

.btn-cancel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
}

.btn-save {
    border: none;
    color: #fff;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    box-shadow: 0 14px 24px rgba(143, 18, 48, 0.2);
}

.btn-save:hover {
    color: #fff;
}
</style>
