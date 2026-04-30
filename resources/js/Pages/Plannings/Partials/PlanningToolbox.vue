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
    "open-import-modal",
    "handle-import-file",
    "submit-import",
    "clear-import-input",
    "apply-server-filters",
    "reset-filters",
]);
</script>

<template>
    <div class="toolbox-card card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="compact-grid">
                <button
                    type="button"
                    class="btn btn-danger-red compact-btn main-cta"
                    @click="$emit('open-new-row')"
                >
                    <i class="bx bx-plus-circle me-2"></i>
                    Nouveau Planning
                </button>

                <button
                    type="button"
                    class="btn btn-outline-danger compact-btn import-modal-btn"
                    @click="$emit('open-import-modal')"
                >
                    <i class="bx bx-upload me-2"></i>
                    Import Excel
                </button>

                <div class="field-box">
                    <label>DU</label>
                    <input
                        v-model="query.date_du"
                        type="date"
                        class="form-control form-control-modern compact-input"
                    />
                </div>

                <div class="field-box">
                    <label>AU</label>
                    <input
                        v-model="query.date_au"
                        type="date"
                        class="form-control form-control-modern compact-input"
                    />
                </div>

                <div class="field-box">
                    <label>Fournisseur véhicule</label>
                    <select
                        v-model="query.supplier_vehicule_id"
                        class="form-select form-control-modern compact-input"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="item in supplierVehicules"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                </div>

                <div class="field-box">
                    <label>Chauffeur</label>
                    <select
                        v-model="query.driver_id"
                        class="form-select form-control-modern compact-input"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="item in drivers"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                </div>

                <div class="field-box">
                    <label>Service</label>
                    <select
                        v-model="query.service_id"
                        class="form-select form-control-modern compact-input"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="item in services"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.designation }}
                        </option>
                    </select>
                </div>

                <div class="field-box">
                    <label>Destination</label>
                    <select
                        v-model="query.destination_id"
                        class="form-select form-control-modern compact-input"
                    >
                        <option value="">Toutes</option>
                        <option
                            v-for="item in destinations"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.name }}
                        </option>
                    </select>
                </div>

                <div class="field-box">
                    <label>Bus</label>
                    <select
                        v-model="query.vehicule_id"
                        class="form-select form-control-modern compact-input"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="item in vehicules"
                            :key="item.id"
                            :value="item.id"
                        >
                            {{ item.matricule || item.name }}
                        </option>
                    </select>
                </div>

                <div class="field-box">
                    <label>Recherche</label>
                    <input
                        v-model="query.search"
                        type="text"
                        class="form-control form-control-modern compact-input"
                        placeholder="Réf, client..."
                    />
                </div>

                <button
                    type="button"
                    class="btn btn-outline-dark compact-icon-btn"
                    @click="$emit('apply-server-filters')"
                >
                    <i class="bx bx-filter-alt"></i>
                </button>

                <button
                    type="button"
                    class="btn btn-soft-secondary compact-icon-btn"
                    @click="$emit('reset-filters')"
                >
                    <i class="bx bx-refresh"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.toolbox-card {
    background: #fff;
    border-radius: 24px;
}

.compact-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(150px, 1fr));
    gap: 14px;
    align-items: end;
}

.field-box label {
    display: block;
    margin-bottom: 6px;
    color: #666;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: 0.3px;
}

.compact-input,
.compact-btn,
.compact-icon-btn {
    height: 44px !important;
    min-height: 44px !important;
    border-radius: 13px !important;
    font-size: 14px !important;
    font-weight: 700 !important;
}

.compact-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 16px !important;
}

.compact-icon-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 !important;
}

.main-cta {
    box-shadow: 0 10px 24px rgba(193, 18, 31, 0.18);
}

.import-modal-btn {
    border-color: #ff2f68;
    color: #ff2f68;
    background: #fff;
}

.import-modal-btn:hover {
    background: #ff2f68;
    color: #fff;
}

.form-control-modern,
.form-select.form-control-modern {
    border: 1px solid #e3e8ef;
    background-color: #fff;
    color: #5b616b;
    box-shadow: none;
}

.form-control-modern:focus,
.form-select.form-control-modern:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 4px rgba(193, 18, 31, 0.08);
}

.btn-danger-red {
    background: linear-gradient(135deg, #d9142d, #a90818);
    color: #fff;
    border: 0;
}

.btn-danger-red:hover {
    color: #fff;
}

.btn-soft-secondary {
    background: #eef1f6;
    color: #5b6472;
    border: 1px solid #e3e8ef;
}

@media (max-width: 1400px) {
    .compact-grid {
        grid-template-columns: repeat(4, minmax(150px, 1fr));
    }
}

@media (max-width: 768px) {
    .compact-grid {
        grid-template-columns: repeat(2, minmax(120px, 1fr));
    }

    .main-cta,
    .import-modal-btn {
        grid-column: span 2;
    }
}
</style>
