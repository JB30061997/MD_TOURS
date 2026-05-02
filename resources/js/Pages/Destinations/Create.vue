<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const form = useForm({
    name: "",
    city: "",
    country: "Morocco",
    type: "",
    status: "Active",
    notes: "",
});

const submit = () => {
    form.post(route("destinations.store"), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Destination created successfully",
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
    <Head title="Create Destination" />

    <div class="page-content">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-map-pin"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">New Destination</h1>
                            <p class="hero-subtitle mb-0">
                                Add a destination or place used in plannings.
                            </p>
                        </div>
                    </div>

                    <Link :href="route('destinations.index')" class="btn btn-back">
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
                            <input
                                v-model="form.name"
                                class="form-control input-modern"
                                type="text"
                                placeholder="Ex: Mohammed V Airport"
                            />
                            <div v-if="form.errors.name" class="error-text">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input
                                v-model="form.city"
                                class="form-control input-modern"
                                type="text"
                                placeholder="Ex: Casablanca"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input
                                v-model="form.country"
                                class="form-control input-modern"
                                type="text"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select v-model="form.type" class="form-select input-modern">
                                <option value="">Select...</option>
                                <option value="Airport">Airport</option>
                                <option value="Hotel">Hotel</option>
                                <option value="City">City</option>
                                <option value="Restaurant">Restaurant</option>
                                <option value="Monument">Monument</option>
                                <option value="Train station">Train station</option>
                                <option value="Port">Port</option>
                                <option value="Tourist site">Tourist site</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select v-model="form.status" class="form-select input-modern">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="form.notes"
                                class="form-control input-modern textarea-modern"
                                rows="4"
                                placeholder="Notes..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="actions mt-4">
                        <Link :href="route('destinations.index')" class="btn btn-cancel">
                            Cancel
                        </Link>

                        <button type="submit" class="btn btn-save" :disabled="form.processing">
                            <span
                                v-if="form.processing"
                                class="spinner-border spinner-border-sm me-2"
                            ></span>
                            <i v-else class="bx bx-save me-2"></i>
                            Save
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
        radial-gradient(circle at top left, rgba(225, 29, 72, 0.1), transparent 24%),
        radial-gradient(circle at top right, rgba(249, 115, 22, 0.08), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #991b1b, #be123c, #ea580c);
    box-shadow: 0 20px 40px rgba(190, 24, 93, 0.18);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18), transparent),
        radial-gradient(circle at 80% 30%, rgba(255, 255, 255, 0.12), transparent);
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
    width: 70px;
    height: 70px;
    border-radius: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
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
    border-radius: 24px;
    background: #fff;
    padding: 28px;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.05);
}

.form-label {
    font-weight: 800;
    color: #334155;
    margin-bottom: 8px;
}

.input-modern {
    border-radius: 14px;
    min-height: 52px;
    border: 1px solid #dfe3ec;
    background: #fff;
    font-weight: 600;
}

.input-modern:focus {
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

.btn-save:hover {
    color: #fff;
    transform: translateY(-2px);
}
</style>