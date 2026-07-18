<script setup>
import { Head, router, Link } from "@inertiajs/vue3";
import { reactive } from "vue";
import DriverLayout from "@/Layouts/DriverLayout.vue";
import PlanningCard from "@/Components/Driver/PlanningCard.vue";
defineOptions({ layout: DriverLayout });
const props = defineProps({ plannings: Object, filters: Object });
const form = reactive({
    search: props.filters?.search || "",
    period: props.filters?.period || "",
    date: props.filters?.date || "",
});
const submit = () =>
    router.get(route("driver.plannings.index"), form, {
        preserveState: true,
        replace: true,
    });
const reset = () => {
    form.search = "";
    form.period = "";
    form.date = "";
    submit();
};
</script>
<template>
    <Head title="My Planning" />
    <div class="page-head">
        <div>
            <small>PLANNINGS</small>
            <h1>Mes missions</h1>
            <p>Recherchez et consultez uniquement vos dossiers affectés.</p>
        </div>
        <span class="material-icons-outlined">event_note</span>
    </div>
    <form class="filters" @submit.prevent="submit">
        <label class="search"
            ><span class="material-icons-outlined">search</span
            ><input
                v-model="form.search"
                placeholder="Référence, client ou destination" /></label
        ><select v-model="form.period">
            <option value="">Toutes les périodes</option>
            <option value="past">Past</option>
            <option value="today">Today</option>
            <option value="upcoming">Upcoming</option></select
        ><input v-model="form.date" type="date" /><button>Filtrer</button
        ><button type="button" class="clear" @click="reset">Effacer</button>
    </form>
    <div v-if="plannings.data.length" class="cards">
        <PlanningCard
            v-for="planning in plannings.data"
            :key="planning.id"
            :planning="planning"
        />
    </div>
    <div v-else class="empty">Aucun planning trouvé.</div>
    <div class="pagination">
        <Link
            v-for="link in plannings.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="{ active: link.active, disabled: !link.url }"
            v-html="link.label"
        />
    </div>
</template>
<style scoped>
.page-head,
.page-head :deep(*) {
    color: #ffffff !important;
}

.page-head {
    margin-bottom: 14px;
    padding: 20px;
    border-radius: 24px;
    background: linear-gradient(135deg, #450a0a, #7f1d1d, #2563eb);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.page-head small {
    color: #fecaca;
    font-weight: 900;
}
.page-head h1 {
    margin: 4px 0;
    font-size: 26px;
}
.page-head p {
    margin: 0;
    color: #fee2e2;
}
.page-head > span {
    font-size: 40px;
}
.filters {
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #fff;
    display: grid;
    grid-template-columns: minmax(220px, 2fr) 1fr 1fr auto auto;
    gap: 8px;
}
.filters input,
.filters select {
    width: 100%;
    height: 44px;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    padding: 0 11px;
    color: #334155;
    background: #f8fafc;
}
.search {
    display: flex;
    align-items: center;
    padding-left: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 13px;
    background: #f8fafc;
}
.search input {
    border: 0;
}
.search span {
    color: #64748b;
}
.filters button {
    height: 44px;
    padding: 0 16px;
    border: 0;
    border-radius: 13px;
    background: #dc2626;
    color: #fff;
    font-weight: 900;
}
.filters .clear {
    background: #f1f5f9;
    color: #64748b;
}
.cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.empty {
    padding: 35px;
    border-radius: 18px;
    background: #fff;
    text-align: center;
    color: #64748b;
}
.pagination {
    margin-top: 18px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    justify-content: center;
}
.pagination a {
    min-width: 35px;
    height: 35px;
    padding: 0 8px;
    border-radius: 10px;
    background: #fff;
    color: #64748b;
    display: grid;
    place-items: center;
    text-decoration: none;
}
.pagination a.active {
    background: #dc2626;
    color: #fff;
}
.pagination a.disabled {
    opacity: 0.4;
}
@media (max-width: 1000px) {
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .search {
        grid-column: 1/-1;
    }
}
@media (max-width: 760px) {
    .cards,
    .filters {
        grid-template-columns: 1fr;
    }
    .search {
        grid-column: auto;
    }
    .page-head {
        padding: 16px;
    }
    .page-head p {
        font-size: 12px;
    }
}
</style>
