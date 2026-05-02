<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const form = useForm({
    name: "",
    phone: "",
    email: "",
    status: "Available",
    notes: "",
});

const submit = () => {
    form.post("/drivers", {
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Driver created successfully",
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
        },
        onError: () => {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Something went wrong",
                confirmButtonColor: "#c1121f",
            });
        },
    });
};
</script>

<template>
    <Head title="Add Driver" />

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
                            <h1 class="hero-title">Add Driver</h1>
                            <p class="hero-subtitle mb-0">
                                Create a new driver.
                            </p>
                        </div>
                    </div>

                    <Link href="/drivers" class="btn btn-back">
                        <i class="bx bx-arrow-back me-2"></i>
                        Back
                    </Link>
                </div>
            </div>

            <div class="form-card">
                <form @submit.prevent="submit">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input v-model="form.name" type="text" class="form-control form-control-modern" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input v-model="form.phone" type="text" class="form-control form-control-modern" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input v-model="form.email" type="email" class="form-control form-control-modern" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select form-control-modern">
                                <option value="Available">Available</option>
                                <option value="Busy">Busy</option>
                                <option value="Unavailable">Unavailable</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea v-model="form.notes" rows="4" class="form-control form-control-modern textarea-modern"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="actions">
                                <Link href="/drivers" class="btn btn-cancel">
                                    Cancel
                                </Link>

                                <button class="btn btn-save" :disabled="form.processing">
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
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
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    align-items: center;
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
    justify-content: center;
    align-items: center;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-size: 34px;
}

.hero-title {
    color: #fff;
    font-weight: 900;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.82);
}

.btn-back {
    border: 0;
    color: #991b1b;
    background: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
}

.form-card {
    background: rgba(255, 255, 255, 0.92);
    border-radius: 28px;
    padding: 30px;
}

.form-label {
    font-weight: 800;
    color: #334155;
}

.form-control-modern {
    border-radius: 16px;
    min-height: 52px;
    border: 1px solid #dfe3ec;
}

.form-control-modern:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.btn-save {
    background: linear-gradient(135deg, #be123c, #ea580c);
    color: #fff;
    border-radius: 14px;
    padding: 10px 20px;
}
</style>