<script setup>
import Topbar from '@/Layouts/topbar.vue'
import Sidebar from '@/Layouts/sidebar.vue'
import Footer from '@/Layouts/footer.vue'
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
