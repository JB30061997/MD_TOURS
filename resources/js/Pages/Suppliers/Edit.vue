<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
    layout: AppShell
})

const props = defineProps({
    supplier: Object
})

const form = useForm({
    name: props.supplier.name || '',
    type: props.supplier.type || '',
    phone: props.supplier.phone || '',
    email: props.supplier.email || '',
    address: props.supplier.address || '',
    notes: props.supplier.notes || '',
})

const submit = () => {
    form.put(`/suppliers/${props.supplier.id}`)
}
</script>

<template>
    <Head title="Modifier Supplier" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Modifier le supplier</h3>
                            <p class="text-muted mb-0">Mise à jour des données du fournisseur.</p>
                        </div>
                        <Link href="/suppliers" class="btn btn-light rounded-3">Retour</Link>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input v-model="form.name" type="text" class="form-control form-control-modern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <input v-model="form.type" type="text" class="form-control form-control-modern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input v-model="form.phone" type="text" class="form-control form-control-modern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input v-model="form.email" type="email" class="form-control form-control-modern">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adresse</label>
                                <input v-model="form.address" type="text" class="form-control form-control-modern">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea v-model="form.notes" rows="4" class="form-control form-control-modern"></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-danger-red rounded-3 px-4" :disabled="form.processing">Mettre à jour</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content { background: #f4f6fb; min-height: 100vh; }
.form-control-modern { border-radius: 14px; min-height: 48px; border: 1px solid #dfe3ec; }
.form-control-modern:focus { border-color: #c1121f; box-shadow: 0 0 0 .18rem rgba(193,18,31,.12); }
.btn-danger-red { background: linear-gradient(135deg, #d11a2a 0%, #a20e19 100%); color: #fff; border: 0; }
</style>