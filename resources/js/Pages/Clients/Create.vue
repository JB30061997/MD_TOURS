<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    supplierClients: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    supplier_client_id: "",
    full_name: "",
    phone: "",
    email: "",
    notes: "",
});

const submit = () => {
    form.post("/clients", {
        preserveScroll: true,
        onError: () => {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Please check the form fields.",
                confirmButtonColor: "#c1121f",
            });
        },
    });
};
</script>

<template>
    <Head title="Add Client" />

    <div class="page-content">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-user-plus"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Add Client</h1>
                            <p class="hero-subtitle mb-0">
                                Fill in the client information and select the
                                related client supplier.
                            </p>
                        </div>
                    </div>

                    <Link href="/clients" class="btn btn-back">
                        <i class="bx bx-arrow-back me-2"></i>
                        Back
                    </Link>
                </div>
            </div>

            <div class="form-card">
                <form @submit.prevent="submit">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Client Supplier</label>
                            <select
                                v-model="form.supplier_client_id"
                                class="form-control form-control-modern"
                            >
                                <option value="">
                                    Select a client supplier
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
                                class="error-text"
                            >
                                {{ form.errors.supplier_client_id }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input
                                v-model="form.full_name"
                                type="text"
                                class="form-control form-control-modern"
                                placeholder="Ex: Mohamed Amine"
                            />

                            <div v-if="form.errors.full_name" class="error-text">
                                {{ form.errors.full_name }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="form-control form-control-modern"
                                placeholder="Ex: 06 00 00 00 00"
                            />

                            <div v-if="form.errors.phone" class="error-text">
                                {{ form.errors.phone }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="form-control form-control-modern"
                                placeholder="Ex: client@email.com"
                            />

                            <div v-if="form.errors.email" class="error-text">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="form-control form-control-modern textarea-modern"
                                placeholder="Additional notes..."
                            ></textarea>

                            <div v-if="form.errors.notes" class="error-text">
                                {{ form.errors.notes }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="actions">
                                <Link href="/clients" class="btn btn-cancel">
                                    Cancel
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
                                    Save
                                </button>
                            </div>
                        </div>
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
    align-items: center;
    gap: 20px;
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

.btn-back {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-back:hover {
    transform: translateY(-2px);
    color: #7f1d1d;
    background: #fff;
}

.form-card {
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.8);
    border-radius: 28px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.07);
    padding: 30px;
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
    font-weight: 600;
}

.form-control-modern:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
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

.btn-cancel:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.btn-save {
    border: none;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    box-shadow: 0 14px 24px rgba(190, 18, 60, 0.2);
}

.btn-save:hover {
    color: #fff;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .hero-card {
        padding: 20px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .actions {
        justify-content: flex-start;
    }
}
</style>