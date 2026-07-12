<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";
import { formatDate } from "@/utils/dateFormat";

defineOptions({ layout: AppShell });

const props = defineProps({
    vehicule: Object,
    totalYear: Number,
    maintenanceTypes: { type: Array, default: () => [] },
});

const maintenances = computed(() => props.vehicule?.maintenances || []);

const form = useForm({
    vehicule_id: props.vehicule.id,
    type_maintenance: "",
    date_maintenance: "",
    kilometrage: "",
    montant: "",
    garage: "",
    prochaine_date: "",
    prochain_kilometrage: "",
    status: "effectue",
    notes: "",
});

const saveMaintenance = () => {
    form.post(route("vehicle-maintenances.store"), {
        preserveScroll: true,

        onSuccess: () => {
            form.reset();

            form.vehicule_id = props.vehicule.id;
            form.status = "effectue";

            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Maintenance added successfully",
                showConfirmButton: false,
                timer: 2500,
            });
        },
    });
};

const deleteMaintenance = (id) => {
    Swal.fire({
        title: "Delete maintenance?",
        text: "This action is irreversible.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        confirmButtonText: "Delete",
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route("vehicle-maintenances.destroy", id), {
                preserveScroll: true,

                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Maintenance deleted",
                        showConfirmButton: false,
                        timer: 2500,
                    });
                },
            });
        }
    });
};

const totalMaintenanceCost = computed(() => {
    return maintenances.value.reduce((sum, item) => {
        return sum + Number(item.montant || 0);
    }, 0);
});
</script>

<template>
    <Head title="Vehicle Maintenance" />

    <div class="maintenance-page">
        <div class="container-fluid py-4">
            <!-- HERO -->
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-wrench"></i>
                        </div>

                        <div>
                            <div class="hero-kicker">Vehicle Maintenance</div>

                            <h1 class="hero-title">
                                {{ vehicule.matricule }}
                            </h1>

                            <p class="hero-subtitle mb-0">
                                Smart maintenance tracking and cost monitoring.
                            </p>
                        </div>
                    </div>

                    <Link :href="route('vehicules.index')" class="btn btn-back">
                        <i class="bx bx-arrow-back me-2"></i>
                        Back
                    </Link>
                </div>
            </div>

            <!-- STATS -->
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="stats-card red-card">
                        <div>
                            <div class="stats-label">Total This Year</div>

                            <div class="stats-value">{{ totalYear }} DH</div>
                        </div>

                        <div class="stats-icon">
                            <i class="bx bx-money"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="stats-card orange-card">
                        <div>
                            <div class="stats-label">Total Maintenances</div>

                            <div class="stats-value">
                                {{ maintenances.length }}
                            </div>
                        </div>

                        <div class="stats-icon">
                            <i class="bx bx-list-ul"></i>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="stats-card dark-card">
                        <div>
                            <div class="stats-label">Total Cost</div>

                            <div class="stats-value">
                                {{ totalMaintenanceCost }} DH
                            </div>
                        </div>

                        <div class="stats-icon">
                            <i class="bx bx-bar-chart"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <div class="maintenance-card mb-4">
                <div class="section-title">
                    <i class="bx bx-plus-circle"></i>
                    Add Maintenance
                </div>

                <div class="row g-3">
                    <div class="col-lg-3">
                        <label class="form-label">Type</label>

                        <select
                            v-model="form.type_maintenance"
                            class="form-select custom-input"
                        >
                            <option value="">Select...</option>
                            <option
                                v-for="type in maintenanceTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Date</label>

                        <input
                            v-model="form.date_maintenance"
                            type="date"
                            class="form-control custom-input"
                        />
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Kilometrage</label>

                        <input
                            v-model="form.kilometrage"
                            type="number"
                            class="form-control custom-input"
                        />
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Amount</label>

                        <input
                            v-model="form.montant"
                            type="number"
                            class="form-control custom-input"
                        />
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Garage</label>

                        <input
                            v-model="form.garage"
                            class="form-control custom-input"
                        />
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Next Date</label>

                        <input
                            v-model="form.prochaine_date"
                            type="date"
                            class="form-control custom-input"
                        />
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label">Next Kilometrage</label>

                        <input
                            v-model="form.prochain_kilometrage"
                            type="number"
                            class="form-control custom-input"
                        />
                    </div>

                    <div class="col-lg-12">
                        <label class="form-label">Notes</label>

                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="form-control custom-input"
                        ></textarea>
                    </div>

                    <div class="col-lg-12">
                        <button
                            class="btn btn-save-maintenance"
                            @click="saveMaintenance"
                        >
                            <i class="bx bx-save me-2"></i>
                            Save Maintenance
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="maintenance-card">
                <div class="section-title">
                    <i class="bx bx-history"></i>
                    Maintenance History
                </div>

                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Kilometrage</th>
                                <th>Amount</th>
                                <th>Garage</th>
                                <th>Next Date</th>
                                <th>Next KM</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="maintenance in maintenances"
                                :key="maintenance.id"
                            >
                                <td>
                                    <span class="type-badge">
                                        {{ maintenance.type_maintenance }}
                                    </span>
                                </td>

                                <td>
                                    {{
                                        formatDate(maintenance.date_maintenance)
                                    }}
                                </td>

                                <td>
                                    {{ maintenance.kilometrage || "-" }}
                                </td>

                                <td class="amount-cell">
                                    {{ maintenance.montant }} DH
                                </td>

                                <td>
                                    {{ maintenance.garage || "-" }}
                                </td>

                                <td>
                                    {{ formatDate(maintenance.prochaine_date) }}
                                </td>

                                <td>
                                    {{
                                        maintenance.prochain_kilometrage || "-"
                                    }}
                                </td>

                                <td>
                                    <span class="status-badge">
                                        {{ maintenance.status }}
                                    </span>
                                </td>

                                <td class="notes-cell">
                                    {{ maintenance.notes || "-" }}
                                </td>

                                <td>
                                    <button
                                        class="btn btn-delete"
                                        @click="
                                            deleteMaintenance(maintenance.id)
                                        "
                                    >
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="maintenances.length === 0">
                                <td colspan="10">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bx bx-wrench"></i>
                                        </div>

                                        <h5>No maintenance yet</h5>

                                        <p class="text-muted mb-0">
                                            Start by adding the first
                                            maintenance.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.maintenance-page {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.1),
            transparent 24%
        ),
        radial-gradient(
            circle at top right,
            rgba(249, 115, 22, 0.08),
            transparent 22%
        ),
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
        radial-gradient(
            circle at 20% 20%,
            rgba(255, 255, 255, 0.18),
            transparent 25%
        ),
        radial-gradient(
            circle at 80% 30%,
            rgba(255, 255, 255, 0.12),
            transparent 25%
        );
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
}

.btn-back {
    background: #fff;
    color: #991b1b;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 900;
    border: 0;
}

.stats-card {
    border-radius: 24px;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #fff;
    min-height: 140px;
    box-shadow: 0 15px 30px rgba(15, 23, 42, 0.08);
}

.red-card {
    background: linear-gradient(135deg, #be123c 0%, #e11d48 100%);
}

.orange-card {
    background: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);
}

.dark-card {
    background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
}

.stats-label {
    font-size: 0.9rem;
    font-weight: 700;
    opacity: 0.9;
}

.stats-value {
    font-size: 2rem;
    font-weight: 900;
    margin-top: 6px;
}

.stats-icon {
    font-size: 42px;
    opacity: 0.9;
}

.maintenance-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.section-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.15rem;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 22px;
}

.custom-input {
    min-height: 52px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    font-weight: 700;
}

.custom-input:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.btn-save-maintenance {
    border: 0;
    border-radius: 16px;
    padding: 14px 24px;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.custom-table thead th {
    padding: 16px;
    background: linear-gradient(180deg, #fff1f2 0%, #fff7ed 100%);
    color: #9f1239;
    font-size: 0.84rem;
    font-weight: 900;
    border-bottom: 1px solid #ffe4e6;
}

.custom-table tbody td {
    padding: 18px;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 700;
    color: #334155;
}

.custom-table tbody tr:hover td {
    background: #f8fafc;
}

.type-badge {
    display: inline-flex;
    padding: 8px 14px;
    border-radius: 999px;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    color: #1d4ed8;
    font-weight: 900;
}

.status-badge {
    display: inline-flex;
    padding: 8px 14px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
    color: #047857;
    font-weight: 900;
}

.amount-cell {
    color: #be123c;
    font-weight: 900;
}

.notes-cell {
    max-width: 240px;
    white-space: normal;
}

.btn-delete {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    border: 0;
    color: #fff;
    background: linear-gradient(135deg, #ef4444, #dc2626);
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 18px;
    border-radius: 24px;
    background: linear-gradient(135deg, #fda4af 0%, #fdba74 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
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
