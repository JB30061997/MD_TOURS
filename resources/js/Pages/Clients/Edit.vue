<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    supplierClients: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    supplier_client_id: props.client.supplier_client_id || "",
    full_name: props.client.full_name || "",
    phone: props.client.phone || "",
    email: props.client.email || "",
    notes: props.client.notes || "",
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: "put",
    })).post(route("clients.update", props.client.id), {
        preserveScroll: true,

        onError: () => {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: "Veuillez vérifier les champs du formulaire.",
                confirmButtonColor: "#c1121f",
            });
        },
    });
};
</script>

<template>
    <Head title="Modifier Client" />

    <div class="page-content">
        <div class="container-fluid py-4">
            <div class="form-card">
                <div class="form-header">
                    <div>
                        <h3 class="form-title">Modifier le client</h3>
                        <p class="form-subtitle">
                            Mise à jour des informations et du fournisseur
                            client.
                        </p>
                    </div>

                    <Link href="/clients" class="btn btn-back">
                        <i class="bx bx-arrow-back me-1"></i>
                        Retour
                    </Link>
                </div>

                <form @submit.prevent="submit">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Fournisseur client</label>
                            <select
                                v-model="form.supplier_client_id"
                                class="form-control form-control-modern"
                            >
                                <option value="">
                                    Sélectionner un fournisseur client
                                </option>
                                <option
                                    v-for="supplier in supplierClients"
                                    :key="supplier.id"
                                    :value="supplier.id"
                                >
                                    {{ supplier.name }}
                                </option>
                            </select>

                            <div
                                v-if="form.errors.supplier_client_id"
                                class="text-danger small mt-1"
                            >
                                {{ form.errors.supplier_client_id }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nom complet</label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                class="form-control form-control-modern"
                            />

                            <div
                                v-if="form.errors.full_name"
                                class="text-danger small mt-1"
                            >
                                {{ form.errors.full_name }}
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
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="form-control form-control-modern textarea-modern"
                            ></textarea>

                            <div
                                v-if="form.errors.notes"
                                class="text-danger small mt-1"
                            >
                                {{ form.errors.notes }}
                            </div>
                        </div>

                        <div class="col-12 d-flex gap-2 flex-wrap">
                            <button
                                type="submit"
                                class="btn btn-danger-red px-4"
                                :disabled="form.processing"
                            >
                                <span
                                    v-if="form.processing"
                                    class="spinner-border spinner-border-sm me-2"
                                ></span>
                                <i v-else class="bx bx-save me-1"></i>
                                Mettre à jour
                            </button>

                            <Link
                                href="/clients"
                                class="btn btn-light-soft px-4"
                            >
                                Annuler
                            </Link>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.1),
            transparent 24%
        ),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 100vh;
}

.form-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 28px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.07);
    padding: 30px;
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    flex-wrap: wrap;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eef2f7;
}

.form-title {
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 6px;
}

.form-subtitle {
    color: #64748b;
    margin-bottom: 0;
}

.form-label {
    font-weight: 800;
    color: #334155;
    margin-bottom: 8px;
}

.form-control-modern {
    border-radius: 16px;
    min-height: 52px;
    border: 1px solid #dfe3ec;
    background: #fff;
    box-shadow: none;
}

.textarea-modern {
    min-height: 120px;
}

.form-control-modern:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 0.18rem rgba(193, 18, 31, 0.12);
}

.btn-danger-red {
    min-height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #d11a2a 0%, #a20e19 100%);
    color: #fff;
    border: 0;
    font-weight: 800;
}

.btn-danger-red:hover {
    color: #fff;
    background: linear-gradient(135deg, #b91422 0%, #8f0a14 100%);
}

.btn-back,
.btn-light-soft {
    min-height: 46px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
}

.btn-back:hover,
.btn-light-soft:hover {
    background: #f1f5f9;
    color: #0f172a;
}
</style>
