<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppShell from '@/Layouts/AppShell.vue';

defineOptions({
    layout: AppShell
})

const props = defineProps({
    driver: Object
})

const form = useForm({
    name: props.driver.name || '',
    phone: props.driver.phone || '',
    email: props.driver.email || '',
    status: props.driver.status || 'Disponible',
    notes: props.driver.notes || '',
})

const submit = () => {
    form.put(`/drivers/${props.driver.id}`)
}
</script>

<template>
    <Head title="Modifier Driver" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Modifier le driver</h3>
                            <p class="text-muted mb-0">Mettre à jour les informations du chauffeur.</p>
                        </div>
                        <Link href="/drivers" class="btn btn-light rounded-3">Retour</Link>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input v-model="form.name" type="text" class="form-control form-control-modern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input v-model="form.phone" type="text" class="form-control form-control-modern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input v-model="form.email" type="email" class="form-control form-control-modern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select v-model="form.status" class="form-select form-control-modern">
                                    <option value="Disponible">Disponible</option>
                                    <option value="Occupé">Occupé</option>
                                    <option value="Indisponible">Indisponible</option>
                                </select>
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