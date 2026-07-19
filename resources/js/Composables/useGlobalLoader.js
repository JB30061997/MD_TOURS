import { computed, reactive, readonly } from "vue";

const MINIMUM_VISIBLE_MS = 380;
const DEFAULT_TIMEOUT_MS = 30000;

const state = reactive({
    requests: new Map(),
    message: "Chargement...",
    visibleSince: 0,
});

let sequence = 0;
let pendingHideTimer = null;

const visible = computed(() => state.requests.size > 0);
const count = computed(() => state.requests.size);

const newestMessage = () => {
    const entries = [...state.requests.values()];
    return entries.at(-1)?.message || "Chargement...";
};

const showLoader = (message = "Chargement...", options = {}) => {
    const token = `loader-${Date.now()}-${++sequence}`;
    if (state.requests.size === 0) state.visibleSince = performance.now();

    const timeoutMs = Number(options.timeout ?? DEFAULT_TIMEOUT_MS);
    const timeoutId = window.setTimeout(() => {
        if (!state.requests.has(token)) return;
        state.requests.delete(token);
        state.message = newestMessage();
        window.dispatchEvent(new CustomEvent("md-tours:loader-timeout", {
            detail: { message: "La requête a pris trop de temps. Veuillez réessayer." },
        }));
    }, timeoutMs);

    state.requests.set(token, { message, timeoutId });
    state.message = message;
    return token;
};

const hideLoader = (token) => {
    if (!token || !state.requests.has(token)) return;
    const request = state.requests.get(token);
    window.clearTimeout(request.timeoutId);

    const remove = () => {
        state.requests.delete(token);
        state.message = newestMessage();
    };

    if (state.requests.size === 1) {
        const remaining = Math.max(0, MINIMUM_VISIBLE_MS - (performance.now() - state.visibleSince));
        pendingHideTimer = window.setTimeout(remove, remaining);
        return;
    }

    remove();
};

const updateMessage = (message, token = null) => {
    if (token && state.requests.has(token)) {
        state.requests.get(token).message = message;
    }
    state.message = message || newestMessage();
};

const forceHideLoader = () => {
    window.clearTimeout(pendingHideTimer);
    pendingHideTimer = null;
    state.requests.forEach((request) => window.clearTimeout(request.timeoutId));
    state.requests.clear();
    state.message = "Chargement...";
};

const withLoader = async (message, callback, options = {}) => {
    const token = showLoader(message, options);
    try {
        return await callback();
    } finally {
        hideLoader(token);
    }
};

export const loaderState = readonly(state);

export function useGlobalLoader() {
    return { visible, count, showLoader, hideLoader, updateMessage, forceHideLoader, withLoader };
}

export { showLoader, hideLoader, updateMessage, forceHideLoader, withLoader };
