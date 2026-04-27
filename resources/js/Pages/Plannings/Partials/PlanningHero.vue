<script setup>
defineProps({
    total: { type: Number, default: 0 },
});
</script>

<template>
    <div class="planning-hero card border-0 shadow-lg mb-4 overflow-hidden">
        <div class="hero-glow"></div>
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <div>
                    <h1 class="planning-title mb-2">Gestion des plannings</h1>
                </div>

                <div class="hero-stats-box">
                    <div class="planning-badge">
                        <i class="bx bx-calendar-check me-1"></i>
                        {{ total }} résultat(s)
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
