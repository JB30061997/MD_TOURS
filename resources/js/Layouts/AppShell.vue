<script setup>
import { watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import Topbar from '@/Layouts/topbar.vue'
import Sidebar from '@/Layouts/sidebar.vue'
import Footer from '@/Layouts/footer.vue'

const page = usePage();

watch(
    () => page.props.flash?.authorization_error,
    (message) => {
        if (!message) return;

        Swal.fire({
            icon: "warning",
            title: "Access denied",
            text: message,
            confirmButtonColor: "#c1121f",
        });
    },
    { immediate: true },
);
</script>

<template>
    <div id="__app-shell" data-bs-theme="blue-theme">
        <!-- Header -->
        <Topbar />

        <!-- Sidebar -->
        <Sidebar />

        <!-- Main -->
        <main class="main-wrapper">
            <div class="main-content">
                <!-- HNA kayji header dyal kol page -->
                <slot name="header"></slot>

                <slot />
            </div>
        </main>

        <!-- Footer -->
        <Footer />

        <div class="overlay btn-toggle"></div>
    </div>
</template>
