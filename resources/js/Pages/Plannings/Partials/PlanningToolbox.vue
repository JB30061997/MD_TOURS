<script setup>
defineProps({
    query: { type: Object, required: true },
    supplierVehicules: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    vehicules: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    selectedFileName: { type: String, default: "" },
    importProcessing: { type: Boolean, default: false },
    hasImportFile: { type: Boolean, default: false },
});

defineEmits([
    "open-new-row",
    "handle-import-file",
    "submit-import",
    "clear-import-input",
    "apply-server-filters",
    "reset-filters",
]);
</script>

<template>
    <div class="toolbox-card card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-lg-4">
            <div class="top-tools-grid mb-4">
                <button type="button" class="btn btn-danger-red main-cta" @click="$emit('open-new-row')">
                    <i class="bx bx-plus-circle me-2"></i>
                    Nouveau Planning
                </button>

                <div class="import-box">
                    <label class="btn btn-outline-danger import-label-main mb-0">
                        <i class="bx bx-upload me-2"></i>
                        {{ selectedFileName || "Choisir Excel" }}
                        <input
                            id="planningImportInput"
                            type="file"
                            accept=".xlsx,.xls"
                            class="d-none"
                            @change="$emit('handle-import-file', $event)"
                        />
                    </label>

                    <button
                        type="button"
                        class="btn btn-danger-red import-btn"
                        @click="$emit('submit-import')"
                        :disabled="importProcessing || !hasImportFile"
                    >
                        <span v-if="importProcessing" class="spinner-border spinner-border-sm me-1"></span>
                        Import
                    </button>

                    <button type="button" class="btn btn-soft-secondary clear-btn" @click="$emit('clear-import-input')">
                        <i class="bx bx-x"></i>
                    </button>
                </div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">DU</label>
                    <input v-model="query.date_du" type="date" class="form-control form-control-modern" />
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">AU</label>
                    <input v-model="query.date_au" type="date" class="form-control form-control-modern" />
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">Fournisseur véhicule</label>
                    <select v-model="query.supplier_vehicule_id" class="form-select form-control-modern">
                        <option value="">Tous</option>
                        <option v-for="item in supplierVehicules" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">Chauffeur</label>
                    <select v-model="query.driver_id" class="form-select form-control-modern">
                        <option value="">Tous</option>
                        <option v-for="item in drivers" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">Service</label>
                    <select v-model="query.service_id" class="form-select form-control-modern">
                        <option value="">Tous</option>
                        <option v-for="item in services" :key="item.id" :value="item.id">{{ item.designation }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">Destination</label>
                    <select v-model="query.destination_id" class="form-select form-control-modern">
                        <option value="">Toutes</option>
                        <option v-for="item in destinations" :key="item.id" :value="item.id">{{ item.name }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">Bus</label>
                    <select v-model="query.vehicule_id" class="form-select form-control-modern">
                        <option value="">Tous</option>
                        <option v-for="item in vehicules" :key="item.id" :value="item.id">{{ item.matricule || item.name }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-6 col-xl-2">
                    <label class="form-label fw-semibold small text-muted">Recherche</label>
                    <input v-model="query.search" type="text" class="form-control form-control-modern" placeholder="Réf, client, destination..." />
                </div>

                <div class="col-6 col-xl-1">
                    <button type="button" class="btn btn-outline-dark w-100 rounded-3 py-3" @click="$emit('apply-server-filters')">
                        <i class="bx bx-filter-alt"></i>
                    </button>
                </div>

                <div class="col-6 col-xl-1">
                    <button type="button" class="btn btn-soft-secondary w-100 rounded-3 py-3" @click="$emit('reset-filters')">
                        <i class="bx bx-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
