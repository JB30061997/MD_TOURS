<script setup>
defineProps({
    form: { type: Object, required: true },
    saving: { type: Boolean, default: false },
});
defineEmits(["save"]);
</script>

<template>
    <div class="modal fade" id="serviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Ajouter un service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Désignation</label>
                            <input v-model="form.designation" type="text" class="form-control form-control-modern" />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Type service</label>
                            <input v-model="form.type_service" type="text" class="form-control form-control-modern" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger-red rounded-3" @click="$emit('save')" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
