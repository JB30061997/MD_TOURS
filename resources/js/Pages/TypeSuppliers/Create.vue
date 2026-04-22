<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
    layout: AppShell
})

const form = useForm({
    designation: "",
    description: "",
});

const submit = () => {
    form.post("/type-suppliers");
};
</script>

<template>
    <Head title="Ajouter Type Supplier" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <div
                        class="d-flex justify-content-between align-items-center mb-4"
                    >
                        <div>
                            <h3 class="fw-bold mb-1">
                                Ajouter un type supplier
                            </h3>
                            <p class="text-muted mb-0">
                                Créer une nouvelle catégorie fournisseur.
                            </p>
                        </div>

                        <Link
                            href="/type-suppliers"
                            class="btn btn-light rounded-3"
                        >
                            Retour
                        </Link>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Désignation</label>
                                <input
                                    v-model="form.designation"
                                    type="text"
                                    class="form-control form-control-modern"
                                    placeholder="Ex: Hôtel, Transport..."
                                />
                                <div
                                    v-if="form.errors.designation"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.designation }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="4"
                                    class="form-control form-control-modern"
                                    placeholder="Description..."
                                ></textarea>
                                <div
                                    v-if="form.errors.description"
                                    class="text-danger small mt-1"
                                >
                                    {{ form.errors.description }}
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
