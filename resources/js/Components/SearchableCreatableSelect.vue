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
    multiple: { type: Boolean, default: false },
    queryParams: { type: Object, default: () => ({}) },
    createDefaults: { type: Object, default: () => ({}) },
    valueKey: { type: String, default: "id" },
    optionLabel: { type: Function, default: null },
    searchKeys: { type: Array, default: () => [] },
    alwaysShowCreate: { type: Boolean, default: true },
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
const displayLabel = (item) => props.optionLabel ? props.optionLabel(item) : String(item?.[props.labelKey] || "");
const searchText = (item) => props.searchKeys.length
    ? props.searchKeys.map((key) => item?.[key]).filter((value) => value !== null && value !== undefined).join(" ")
    : displayLabel(item);
const filtered = computed(() => {
    const needle = search.value.trim().toLocaleLowerCase();
    if (!needle) return allOptions.value.slice(0, 30);
    return allOptions.value.filter((item) => searchText(item).toLocaleLowerCase().includes(needle)).slice(0, 30);
});
const exactMatch = computed(() => filtered.value.some((item) => displayLabel(item).trim().toLocaleLowerCase() === search.value.trim().toLocaleLowerCase()));
const selectedItems = computed(() => {
    if (!props.multiple || !Array.isArray(props.modelValue)) return [];
    return allOptions.value.filter((item) => props.modelValue.some((id) => String(id) === String(item[props.valueKey])));
});

watch(() => props.modelValue, (id) => {
    if (Array.isArray(id)) return;
    const selected = allOptions.value.find((item) => String(item[props.valueKey]) === String(id));
    if (selected) search.value = displayLabel(selected);
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
        const response = await axios.get(props.endpoint, { params: { ...props.queryParams, q: search.value }, signal: controller.signal, globalLoader: false });
        remoteOptions.value = response.data.data || [];
    } catch (error) {
        if (error.code !== "ERR_CANCELED") errors.value = { general: "Recherche indisponible." };
    } finally { loading.value = false; }
};

const select = (item) => {
    if (props.multiple) {
        const current = Array.isArray(props.modelValue) ? props.modelValue : [];
        if (!current.some((id) => String(id) === String(item[props.valueKey]))) emit("update:modelValue", [...current, item[props.valueKey]]);
        search.value = "";
    } else {
        emit("update:modelValue", item[props.valueKey]);
        search.value = displayLabel(item);
    }
    open.value = false;
};
const remove = (item) => {
    if (!props.multiple || !Array.isArray(props.modelValue)) return;
    emit("update:modelValue", props.modelValue.filter((id) => String(id) !== String(item[props.valueKey])));
};

const showCreate = () => {
    form.value = Object.fromEntries(props.fields.map((field) => [field.key, props.createDefaults[field.key] ?? (field.key === props.labelKey ? search.value.trim() : (field.default ?? ""))]));
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
    <div ref="root" class="creatable-select smart-select" @keydown="onKeydown">
        <div v-if="multiple && selectedItems.length" class="creatable-selected-items">
            <span v-for="item in selectedItems" :key="item.id">{{ displayLabel(item) }}<button type="button" :aria-label="`Retirer ${displayLabel(item)}`" @click="remove(item)">×</button></span>
        </div>
        <div class="creatable-input-wrap">
            <input ref="searchInput" v-model="search" class="form-control table-input" :class="{ 'is-invalid': error }" :placeholder="placeholder" autocomplete="off" @focus="open = true; load()" />
            <div v-if="open" class="smart-menu creatable-menu">
                <div v-if="loading" class="creatable-state"><span class="spinner-border spinner-border-sm" /> Recherche…</div>
                <button v-for="(item, index) in filtered" :key="item.id" type="button" class="smart-item" :class="{ active: activeIndex === index }" @mousedown.prevent="select(item)">{{ displayLabel(item) }}</button>
                <div v-if="!loading && !filtered.length" class="smart-empty">Aucun {{ createLabel }} trouvé</div>
                <button v-if="canCreate && search.trim() && !exactMatch" type="button" class="creatable-action" @mousedown.prevent="showCreate">+ Créer « {{ search.trim() }} »</button>
            </div>
            <button v-if="canCreate && alwaysShowCreate" type="button" class="creatable-inline-add" :title="`Ajouter un ${createLabel}`" :aria-label="`Ajouter un ${createLabel}`" @click="showCreate">+</button>
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
