<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    supplierClient: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    user_id: props.supplierClient.user_id || "",
    name: props.supplierClient.name || "",
    phone: props.supplierClient.phone || "",
    email: props.supplierClient.email || "",
    address: props.supplierClient.address || "",
    notes: props.supplierClient.notes || "",
    is_active: Boolean(props.supplierClient.is_active),
});

const submit = () => {
    form.put(route("supplier-clients.update", props.supplierClient.id), {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Client supplier updated successfully",
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};
</script>

<template>
    <Head title="Edit Client Supplier" />

    <div class="edit-page">
        <div class="container-fluid py-4">
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-edit"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">
                                Edit Client Supplier
                            </h1>
                            <p class="hero-subtitle mb-0">
                                {{ supplierClient.name }}
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="route('supplier-clients.index')"
                        class="btn btn-back"
                    >
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
                            />
                            <div v-if="form.errors.name" class="error-text">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Linked User</label>
                            <select
                                v-model="form.user_id"
                                class="form-select input-modern"
                            >
                                <option value="">No user</option>
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
                            <label class="form-label">Phone</label>
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
                            <label class="form-label">Status</label>
                            <select
                                v-model="form.is_active"
                                class="form-select input-modern"
                            >
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Address</label>
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
                            :href="route('supplier-clients.index')"
                            class="btn btn-cancel"
                        >
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
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.edit-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(225,29,72,0.1), transparent 24%),
        radial-gradient(circle at top right, rgba(249,115,22,0.08), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg,#991b1b,#be123c,#ea580c);
    box-shadow: 0 20px 40px rgba(190,24,93,0.18);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 20%, rgba(255,255,255,0.18), transparent),
        radial-gradient(circle at 80% 30%, rgba(255,255,255,0.12), transparent);
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
    width: 70px;
    height: 70px;
    border-radius: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-size: 30px;
}

.hero-title {
    color: #fff;
    font-weight: 900;
}

.hero-subtitle {
    color: rgba(255,255,255,0.85);
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
    box-shadow: 0 12px 28px rgba(0,0,0,0.05);
}

.input-modern {
    border-radius: 14px;
}

.btn-save {
    background: linear-gradient(135deg,#be123c,#ea580c);
    color: #fff;
}

.btn-cancel {
    background: #f1f5f9;
}
</style>