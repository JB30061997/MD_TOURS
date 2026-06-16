<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: "",
    },
    search: {
        type: String,
        default: "",
    },
    options: {
        type: Array,
        default: () => [],
    },
    labelKey: {
        type: String,
        default: "name",
    },
    valueKey: {
        type: String,
        default: "id",
    },
    placeholder: {
        type: String,
        default: "Search...",
    },
    allowCustom: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["update:modelValue", "update:search", "select"]);

const open = ref(false);

const normalizedSearch = computed(() =>
    (props.search || "").toString().trim().toLowerCase(),
);

const visibleOptions = computed(() =>
    props.options
        .filter((item) => {
            const label = (item?.[props.labelKey] || "").toString();
            return !normalizedSearch.value || label.toLowerCase().includes(normalizedSearch.value);
        })
        .slice(0, 80),
);

const selectOption = (item) => {
    emit("update:modelValue", item?.[props.valueKey] || "");
    emit("update:search", item?.[props.labelKey] || "");
    emit("select", item);
    open.value = false;
};

const updateSearch = (value) => {
    emit("update:search", value);

    if (props.allowCustom) {
        emit("update:modelValue", "");
    }
};

const clearValue = () => {
    emit("update:modelValue", "");
    emit("update:search", "");
    open.value = false;
};
</script>

<template>
    <div class="search-select">
        <input
            :value="search"
            type="text"
            class="search-select-input"
            :placeholder="placeholder"
            @input="updateSearch($event.target.value)"
            @focus="open = true"
            @keydown.esc="open = false"
        />

        <button
            v-if="search"
            type="button"
            class="search-clear"
            title="Clear"
            @click="clearValue"
        >
            <i class="bx bx-x"></i>
        </button>

        <div v-if="open" class="search-select-menu">
            <button
                v-for="item in visibleOptions"
                :key="item[valueKey]"
                type="button"
                class="search-select-item"
                @mousedown.prevent="selectOption(item)"
            >
                <span>{{ item[labelKey] }}</span>
                <small v-if="item.email || item.phone || item.city || item.matricule">
                    {{ item.email || item.phone || item.city || item.matricule }}
                </small>
            </button>

            <div v-if="!visibleOptions.length" class="search-select-empty">
                Aucun élément trouvé
                <strong v-if="allowCustom && search">, le texte sera gardé.</strong>
            </div>
        </div>
    </div>
</template>

<style scoped>
.search-select {
    position: relative;
    width: 100%;
}

.search-select-input {
    width: 100%;
    border: 1px solid #dbe2ea;
    border-radius: 14px;
    padding: 12px 42px 12px 14px;
    color: #111827;
    font-weight: 750;
    outline: none;
    transition: 0.18s ease;
}

.search-select-input:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 4px rgba(193, 18, 31, 0.08);
}

.search-clear {
    position: absolute;
    right: 8px;
    top: 8px;
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 10px;
    background: #f8fafc;
    color: #64748b;
}

.search-select-menu {
    position: absolute;
    z-index: 50;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    max-height: 260px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    background: #fff;
    box-shadow: 0 22px 50px rgba(15, 23, 42, 0.16);
    padding: 8px;
}

.search-select-item {
    width: 100%;
    border: 0;
    border-radius: 12px;
    padding: 10px 12px;
    background: transparent;
    color: #334155;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    text-align: left;
    font-weight: 850;
}

.search-select-item:hover {
    background: #fff1f2;
    color: #be123c;
}

.search-select-item small,
.search-select-empty {
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 750;
}

.search-select-empty {
    padding: 12px;
}
</style>
