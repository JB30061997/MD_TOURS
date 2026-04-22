<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
    layout: AppShell
})

const props = defineProps({
    typeSuppliers: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: "",
    type: "",
    phone: "",
    email: "",
    address: "",
    notes: "",
});

const submit = () => {
    form.post("/suppliers");
};
</script>

<template>
    <Head title="Ajouter Supplier" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <div
                        class="d-flex justify-content-between align-items-center mb-4"
                    >
                        <div>
                            <h3 class="fw-bold mb-1">Ajouter un supplier</h3>
                            <p class="text-muted mb-0">
                                Créer un fournisseur avec son type.
                            </p>
                        </div>

                        <Link href="/suppliers" class="btn btn-light rounded-3">
                            Retour
                        </Link>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="form-control form-control-modern"
                                    placeholder="Nom du fournisseur"
                                />
                                <div
                                    v-if="form.errors.name"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Type supplier</label>
                                <select
                                    v-model="form.type"
                                    class="form-select form-control-modern"
                                >
                                    <option value="">Sélectionner...</option>
                                    <option
                                        v-for="item in typeSuppliers"
                                        :key="item.id"
                                        :value="item.id"
                                    >
                                        {{ item.designation }}
                                    </option>
                                </select>
                                <div
                                    v-if="form.errors.type"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.type }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    class="form-control form-control-modern"
                                />
                                <div
                                    v-if="form.errors.phone"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.phone }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="form-control form-control-modern"
                                />
                                <div
                                    v-if="form.errors.email"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="form-control form-control-modern"
                                />
                                <div
                                    v-if="form.errors.address"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="4"
                                    class="form-control form-control-modern"
                                ></textarea>
                                <div
                                    v-if="form.errors.notes"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.notes }}
                                </div>
                            </div>

                            <div class="col-12">
                                <button
                                    class="btn btn-danger-red rounded-3 px-4"
                                    :disabled="form.processing"
                                >
                                    <span
                                        v-if="form.processing"
                                        class="spinner-border spinner-border-sm me-2"
                                    ></span>
                                    Enregistrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background: #f4f6fb;
    min-height: 100vh;
}

.form-control-modern {
    border-radius: 14px;
    min-height: 48px;
    border: 1px solid #dfe3ec;
}

.form-control-modern:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 0.18rem rgba(193, 18, 31, 0.12);
}

.btn-danger-red {
    background: linear-gradient(135deg, #d11a2a 0%, #a20e19 100%);
    color: #fff;
    border: 0;
}
</style>
