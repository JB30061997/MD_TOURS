<script setup>
import AppShell from '@/Layouts/AppShell.vue';
import Reports from '@/Components/Reports/Index.vue';

defineOptions({ layout: AppShell })

const props = defineProps({
    stats: { type: Object, default: () => ({}) }
})
</script>

<template>
    <div class="row g-4">
        <div class="col-12">
            <Reports v-bind="props" />
        </div>
    </div>
</template>
