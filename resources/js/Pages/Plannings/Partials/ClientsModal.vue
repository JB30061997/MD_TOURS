<script setup>
defineProps({
    title: { type: String, default: "" },
    clients: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="modal fade" id="clientsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Clients du planning {{ title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">
                    <div v-if="clients.length">
                        <ul class="list-group list-group-flush">
                            <li v-for="(client, index) in clients" :key="index" class="list-group-item px-0">
                                <i class="bx bx-user-circle me-2 text-primary"></i>
                                {{ client }}
                            </li>
                        </ul>
                    </div>
                    <div v-else class="text-muted text-center py-3">Aucun client lié à ce planning.</div>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</template>
