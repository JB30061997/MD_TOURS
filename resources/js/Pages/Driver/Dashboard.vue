<script setup>
import { Head } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import DriverLayout from "@/Layouts/DriverLayout.vue";
import StatCard from "@/Components/Driver/StatCard.vue";
import PlanningCard from "@/Components/Driver/PlanningCard.vue";
defineOptions({ layout: DriverLayout });
const props = defineProps({ driver: Object, plannings: Array, stats: Object });
const active = ref("today");
const today = new Date().toISOString().slice(0, 10);
const period = (p) => {
    const from = String(p.date_du || "").slice(0, 10),
        to = String(p.date_au || p.date_du || "").slice(0, 10);
    if (from <= today && to >= today) return "today";
    return from > today ? "upcoming" : "past";
};
const visible = computed(() =>
    (props.plannings || []).filter((p) => period(p) === active.value),
);
</script>
<template>
    <Head title="Dashboard Driver" />
    <section class="hero">
        <div>
            <small>ESPACE DRIVER</small>
            <h1>Bonjour, {{ driver.name }}</h1>
            <p>Vos missions et itinéraires en un coup d’œil.</p>
        </div>
        <div class="hero-icon">
            <span class="material-icons-outlined">directions_car</span>
        </div>
    </section>
    <div class="stats">
        <StatCard
            label="Total services"
            :value="stats.total"
            icon="route"
            tone="red"
        /><StatCard
            label="Aujourd’hui"
            :value="stats.today"
            icon="today"
            tone="green"
        /><StatCard
            label="À venir"
            :value="stats.upcoming"
            icon="event_upcoming"
            tone="blue"
        /><StatCard
            label="Passés"
            :value="stats.past"
            icon="history"
            tone="violet"
        />
    </div>
    <div class="filters">
        <button
            v-for="item in [
                ['past', 'Past'],
                ['today', 'Today'],
                ['upcoming', 'Upcoming'],
            ]"
            :key="item[0]"
            :class="[item[0], { active: active === item[0] }]"
            @click="active = item[0]"
        >
            {{ item[1] }}<b>{{ stats[item[0]] }}</b>
        </button>
    </div>
    <div v-if="visible.length" class="cards">
        <PlanningCard
            v-for="planning in visible"
            :key="planning.id"
            :planning="planning"
        />
    </div>
    <div v-else class="empty">
        <span class="material-icons-outlined">event_busy</span>
        <p>Aucun service dans cette période.</p>
    </div>
</template>
<style scoped>
.hero,
.hero :deep(*) {
    color: #ffffff !important;
}

.hero {
    min-height: 150px;
    padding: 24px;
    border-radius: 28px;
    background: linear-gradient(135deg, #450a0a, #7f1d1d 58%, #dc2626);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    overflow: hidden;
    box-shadow: 0 14px 30px #7f1d1d22;
}
.hero small {
    color: #fecaca;
    font-weight: 900;
    letter-spacing: 1.5px;
}
.hero h1 {
    margin: 7px 0 4px;
    font-size: clamp(24px, 3vw, 34px);
}
.hero p {
    margin: 0;
    color: #fee2e2;
    font-weight: 700;
}
.hero-icon {
    width: 64px;
    height: 64px;
    border: 1px solid #ffffff22;
    border-radius: 21px;
    background: #ffffff18;
    display: grid;
    place-items: center;
}
.hero-icon span {
    font-size: 31px;
}
.stats {
    margin: 18px 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.filters {
    margin-bottom: 16px;
    padding: 5px;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #fff;
    display: flex;
    gap: 5px;
}
.filters button {
    flex: 1;
    min-height: 44px;
    border: 0;
    border-radius: 13px;
    color: #475569;
    font-weight: 900;
}
.filters b {
    margin-left: 7px;
    padding: 3px 6px;
    border-radius: 99px;
    background: #ffffff99;
}
.filters .past {
    background: #ede9fe;
}
.filters .today {
    background: #dcfce7;
}
.filters .upcoming {
    background: #dbeafe;
}
.filters .past.active {
    background: #7c3aed;
    color: #fff;
}
.filters .today.active {
    background: #16a34a;
    color: #fff;
}
.filters .upcoming.active {
    background: #2563eb;
    color: #fff;
}
.cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.empty {
    padding: 40px;
    border-radius: 20px;
    background: #fff;
    text-align: center;
    color: #64748b;
}
.empty span {
    font-size: 34px;
}
@media (max-width: 800px) {
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .cards {
        grid-template-columns: 1fr;
    }
    .hero {
        padding: 18px;
        border-radius: 22px;
    }
    .hero-icon {
        width: 48px;
        height: 48px;
    }
}
@media (max-width: 420px) {
    .filters button {
        font-size: 11px;
    }
    .hero p {
        font-size: 12px;
    }
}
</style>
