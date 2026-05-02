<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    typeService: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    designation: props.typeService.designation || "",
    description: props.typeService.description || "",
});

const submit = () => {
    form.put(`/type-services/${props.typeService.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Service type updated successfully",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
        },
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
    <Head title="Edit Service Type" />

    <div class="page-content">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-edit"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Edit Service Type</h1>
                            <p class="hero-subtitle mb-0">
                                Update service type information.
                            </p>
                        </div>
                    </div>

                    <Link href="/type-services" class="btn btn-back">
                        <i class="bx bx-arrow-back me-2"></i>
                        Back
                    </Link>
                </div>
            </div>

            <div class="form-card">
                <form @submit.prevent="submit">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input
                                v-model="form.designation"
                                type="text"
                                class="form-control form-control-modern"
                            />
                            <div v-if="form.errors.designation" class="error-text">
                                {{ form.errors.designation }}
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                class="form-control form-control-modern textarea-modern"
                            ></textarea>
                            <div v-if="form.errors.description" class="error-text">
                                {{ form.errors.description }}
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="actions">
                                <Link href="/type-services" class="btn btn-cancel">
                                    Cancel
                                </Link>

                                <button class="btn btn-save" :disabled="form.processing">
                                    <span
                                        v-if="form.processing"
                                        class="spinner-border spinner-border-sm me-2"
                                    ></span>
                                    <i v-else class="bx bx-save me-2"></i>
                                    Update
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
        linear-gradient(180deg, #f8fafc, #f1f5f9);
}

.hero-card {
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #991b1b, #be123c, #ea580c);
    color: #fff;
}

.hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.hero-icon {
    width: 70px;
    height: 70px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 30px;
}

.hero-title {
    color: #fff;
    font-weight: 900;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.85);
}

.btn-back {
    background: #fff;
    color: #991b1b;
    border-radius: 14px;
    padding: 10px 18px;
    font-weight: 800;
}

.form-card {
    background: #fff;
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.05);
}

.form-label {
    font-weight: 800;
    color: #334155;
}

.form-control-modern {
    border-radius: 14px;
    min-height: 52px;
    border: 1px solid #dfe3ec;
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
}

.btn-cancel {
    background: #f1f5f9;
    color: #334155;
    border-radius: 14px;
    padding: 10px 20px;
    font-weight: 800;
}

.btn-save {
    background: linear-gradient(135deg, #be123c, #ea580c);
    color: #fff;
    border-radius: 14px;
    padding: 10px 20px;
    font-weight: 900;
}
</style>