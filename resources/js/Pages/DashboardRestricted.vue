<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const page = usePage();
const userName = computed(() => page.props?.auth?.user?.name || "Utilisateur");
</script>

<template>
    <Head title="Accueil" />

    <main class="restricted-dashboard">
        <section class="welcome-panel">
            <div class="icon-wrap">
                <i class="material-icons-outlined">lock_open</i>
            </div>
            <span class="eyebrow">Accueil</span>
            <h1>Bienvenue, {{ userName }}</h1>
            <p>
                Welcome. You do not have access to dashboard data yet.
            </p>
        </section>
    </main>
</template>

<style scoped>
.restricted-dashboard {
    align-items: center;
    display: flex;
    min-height: calc(100vh - 170px);
    padding: 24px;
}

.welcome-panel {
    background: #fff;
    border: 1px solid #e6ebf2;
    border-radius: 8px;
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
    margin: 0 auto;
    max-width: 720px;
    padding: 42px;
    text-align: center;
    width: 100%;
}

.icon-wrap {
    align-items: center;
    background: linear-gradient(135deg, #fff1f2, #eff6ff);
    border: 1px solid #fecdd3;
    border-radius: 8px;
    color: #c1121f;
    display: inline-flex;
    height: 72px;
    justify-content: center;
    margin-bottom: 18px;
    width: 72px;
}

.icon-wrap i {
    font-size: 36px;
}

.eyebrow {
    color: #7b8798;
    display: block;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.08em;
    margin-bottom: 8px;
    text-transform: uppercase;
}

h1 {
    color: #111827;
    font-size: 34px;
    font-weight: 950;
    margin: 0 0 12px;
}

p {
    color: #64748b;
    font-size: 18px;
    font-weight: 750;
    line-height: 1.6;
    margin: 0;
}
</style>
