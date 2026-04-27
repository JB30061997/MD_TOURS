<script setup>
import { computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import Swal from "sweetalert2";

const page = usePage();

const flash = computed(() => page.props.flash || {});

const toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
});

watch(
    flash,
    (value) => {
        if (value?.success) {
            toast.fire({ icon: "success", title: value.success });
        }

        if (value?.error) {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: value.error,
                confirmButtonText: "OK",
                confirmButtonColor: "#c1121f",
            });
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div
        v-if="flash?.import_errors && flash.import_errors.length"
        class="alert alert-warning rounded-4 shadow-sm mb-4"
    >
        <div class="fw-bold mb-2">
            <i class="bx bx-error-circle me-1"></i>
            Détails des lignes ignorées / en erreur :
        </div>

        <ul class="mb-0 ps-3">
            <li v-for="(item, index) in flash.import_errors" :key="index">
                {{ item }}
            </li>
        </ul>
    </div>
</template>
