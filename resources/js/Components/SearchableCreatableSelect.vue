<script setup>
import axios from "axios";
import { computed, nextTick, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    options: { type: Array, default: () => [] },
    endpoint: { type: String, required: true },
    labelKey: { type: String, default: "name" },
    placeholder: { type: String, default: "Rechercher..." },
    createLabel: { type: String, default: "élément" },
    canCreate: { type: Boolean, default: false },
    fields: { type: Array, default: () => [] },
    error: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue", "created"]);
const root = ref(null);
const searchInput = ref(null);
const open = ref(false);
const modalOpen = ref(false);
const search = ref("");
const remoteOptions = ref([]);
const loading = ref(false);
const saving = ref(false);
const errors = ref({});
const activeIndex = ref(-1);
const form = ref({});
let timer;
let controller;

const allOptions = computed(() => {
    const map = new Map([...props.options, ...remoteOptions.value].map((item) => [String(item.id), item]));
    return [...map.values()];
});
const filtered = computed(() => {
    const needle = search.value.trim().toLocaleLowerCase();
    if (!needle) return allOptions.value.slice(0, 30);
    return allOptions.value.filter((item) => String(item[props.labelKey] || "").toLocaleLowerCase().includes(needle)).slice(0, 30);
});
const exactMatch = computed(() => filtered.value.some((item) => String(item[props.labelKey] || "").trim().toLocaleLowerCase() === search.value.trim().toLocaleLowerCase()));

watch(() => props.modelValue, (id) => {
    const selected = allOptions.value.find((item) => String(item.id) === String(id));
    if (selected) search.value = selected[props.labelKey];
}, { immediate: true });

watch(search, () => {
    clearTimeout(timer);
    timer = setTimeout(load, 280);
});

const load = async () => {
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    try {
        const response = await axios.get(props.endpoint, { params: { q: search.value }, signal: controller.signal });
        remoteOptions.value = response.data.data || [];
    } catch (error) {
        if (error.code !== "ERR_CANCELED") errors.value = { general: "Recherche indisponible." };
    } finally { loading.value = false; }
};

const select = (item) => {
    emit("update:modelValue", item.id);
    search.value = item[props.labelKey];
    open.value = false;
};

const showCreate = () => {
    form.value = Object.fromEntries(props.fields.map((field) => [field.key, field.key === props.labelKey ? search.value.trim() : (field.default ?? "")]));
    errors.value = {};
    modalOpen.value = true;
    open.value = false;
    nextTick(() => document.querySelector(".quick-create-modal input")?.focus());
};

const save = async () => {
    saving.value = true;
    errors.value = {};
    try {
        const response = await axios.post(props.endpoint, form.value);
        remoteOptions.value = [response.data.data, ...remoteOptions.value];
        select(response.data.data);
        modalOpen.value = false;
        emit("created", response.data.data);
    } catch (error) {
        if (error.response?.status === 409 && error.response.data.existing) {
            remoteOptions.value = [error.response.data.existing, ...remoteOptions.value];
            errors.value = { general: error.response.data.message, existing: error.response.data.existing };
        } else errors.value = error.response?.data?.errors || { general: error.response?.data?.message || "Création impossible." };
    } finally { saving.value = false; }
};

const onKeydown = (event) => {
    if (event.key === "Escape") { open.value = false; modalOpen.value = false; }
    if (!open.value) return;
    if (event.key === "ArrowDown") { event.preventDefault(); activeIndex.value = Math.min(activeIndex.value + 1, filtered.value.length - 1); }
    if (event.key === "ArrowUp") { event.preventDefault(); activeIndex.value = Math.max(activeIndex.value - 1, 0); }
    if (event.key === "Enter" && activeIndex.value >= 0) { event.preventDefault(); select(filtered.value[activeIndex.value]); }
};
const outside = (event) => { if (!root.value?.contains(event.target) && !modalOpen.value) open.value = false; };
document.addEventListener("mousedown", outside);
onBeforeUnmount(() => { document.removeEventListener("mousedown", outside); clearTimeout(timer); controller?.abort(); });
</script>

<template>
    <div ref="root" class="creatable-select" @keydown="onKeydown">
        <input ref="searchInput" v-model="search" class="form-control table-input" :class="{ 'is-invalid': error }" :placeholder="placeholder" autocomplete="off" @focus="open = true; load()" />
        <div v-if="open" class="smart-menu creatable-menu">
            <div v-if="loading" class="creatable-state"><span class="spinner-border spinner-border-sm" /> Recherche…</div>
            <button v-for="(item, index) in filtered" :key="item.id" type="button" class="smart-item" :class="{ active: activeIndex === index }" @mousedown.prevent="select(item)">{{ item[labelKey] }}</button>
            <div v-if="!loading && !filtered.length" class="smart-empty">Aucun {{ createLabel }} trouvé</div>
            <button v-if="canCreate && search.trim() && !exactMatch" type="button" class="creatable-action" @mousedown.prevent="showCreate">+ Créer « {{ search.trim() }} »</button>
        </div>
        <small v-if="error" class="service-validation-error">{{ error }}</small>

        <Teleport to="body">
            <div v-if="modalOpen" class="quick-create-backdrop" @mousedown.self="modalOpen = false">
                <section class="quick-create-modal" role="dialog" aria-modal="true">
                    <header><div><small>Création rapide</small><h3>Nouveau {{ createLabel }}</h3></div><button type="button" aria-label="Fermer" @click="modalOpen = false">×</button></header>
                    <div class="quick-create-body">
                        <div v-if="errors.general" class="quick-create-alert">
                            {{ errors.general }}
                            <button v-if="errors.existing" type="button" @click="select(errors.existing); modalOpen = false">Utiliser l’existant</button>
                        </div>
                        <label v-for="field in fields" :key="field.key">
                            <span>{{ field.label }} <b v-if="field.required">*</b></span>
                            <select v-if="field.type === 'select'" v-model="form[field.key]" class="form-select">
                                <option value="">Choisir…</option><option v-for="option in field.options || []" :key="option.id" :value="option.id">{{ option.label || option.designation || option.name }}</option>
                            </select>
                            <textarea v-else-if="field.type === 'textarea'" v-model="form[field.key]" class="form-control" rows="3" />
                            <input v-else v-model="form[field.key]" :type="field.type || 'text'" class="form-control" />
                            <small v-if="errors[field.key]" class="text-danger">{{ errors[field.key][0] }}</small>
                        </label>
                    </div>
                    <footer><button type="button" class="btn btn-light" @click="modalOpen = false">Annuler</button><button type="button" class="btn btn-danger-red" :disabled="saving" @click="save"><span v-if="saving" class="spinner-border spinner-border-sm me-1" />Enregistrer</button></footer>
                </section>
            </div>
        </Teleport>
    </div>
</template>
