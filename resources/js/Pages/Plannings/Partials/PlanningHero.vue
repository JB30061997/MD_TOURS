<script setup>
const props = defineProps({
    total: { type: Number, default: 0 },
    query: { type: Object, required: true },
});

const buildPrintQuery = () => {
    const params = new URLSearchParams();

    Object.entries(props.query).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== "") {
            params.append(key, value);
        }
    });

    return params.toString();
};

const printSupplierClients = () => {
    window.open(`/plannings/print/supplier-clients?${buildPrintQuery()}`, "_blank");
};

const printSupplierVehicules = () => {
    window.open(`/plannings/print/supplier-vehicules?${buildPrintQuery()}`, "_blank");
};
</script>

<template>
    <div class="planning-hero card border-0 shadow-lg mb-4 overflow-hidden">
        <div class="hero-glow"></div>
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h1 class="planning-title mb-2">Gestion des plannings</h1>
                </div>

                <button type="button" class="btn btn-dark fw-bold rounded-3" @click="printSupplierClients">
                    <i class="bx bx-printer me-1"></i>
                    Print Tours Supplier Client
                </button>

                <button type="button" class="btn btn-danger fw-bold rounded-3" @click="printSupplierVehicules">
                    <i class="bx bx-printer me-1"></i>
                    Print Tours Supplier Vehicle
                </button>

                <div class="hero-stats-box">
                    <div class="planning-badge">
                        <i class="bx bx-calendar-check me-1"></i>
                        {{ total }} résultat(s)
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>