<script setup>
defineProps({ form: { type: Object, required: true }, saving: { type: Boolean, default: false }, errors: { type: Object, default: () => ({}) } });
defineEmits(["save"]);
</script>
<template>
    <div class="modal fade" id="supplierClientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0"><div><small class="text-danger fw-bold">CRÉATION RAPIDE</small><h5 class="modal-title fw-bold">Ajouter un Client Supplier</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" /></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-12"><label class="form-label">Nom *</label><input v-model="form.name" class="form-control form-control-modern" /><small v-if="errors.name" class="text-danger">{{ errors.name }}</small></div>
                <div class="col-md-6"><label class="form-label">Téléphone</label><input v-model="form.phone" class="form-control form-control-modern" /></div>
                <div class="col-md-6"><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-control form-control-modern" /><small v-if="errors.email" class="text-danger">{{ errors.email }}</small></div>
                <div class="col-12"><label class="form-label">Adresse</label><input v-model="form.address" class="form-control form-control-modern" /></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea v-model="form.notes" rows="2" class="form-control form-control-modern" /></div>
            </div></div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button><button type="button" class="btn btn-danger-red" :disabled="saving" @click="$emit('save')"><span v-if="saving" class="spinner-border spinner-border-sm me-1" />Enregistrer</button></div>
        </div></div>
    </div>
</template>
