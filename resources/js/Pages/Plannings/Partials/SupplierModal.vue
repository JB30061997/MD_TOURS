<script setup>
defineProps({
    form: { type: Object, required: true },
    saving: { type: Boolean, default: false },
});

defineEmits(["save"]);
</script>

<template>
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">
                        Ajouter un supplier véhicule
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="form-control form-control-modern"
                                placeholder="Ex: Kos Tours"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="form-control form-control-modern"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                class="form-control form-control-modern"
                            />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Adresse</label>
                            <input
                                v-model="form.address"
                                type="text"
                                class="form-control form-control-modern"
                            />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="form-control form-control-modern"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">
                        Annuler
                    </button>

                    <button
                        type="button"
                        class="btn btn-danger-red rounded-3"
                        @click="$emit('save')"
                        :disabled="saving"
                    >
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>