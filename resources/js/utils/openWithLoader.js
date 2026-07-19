import { hideLoader, showLoader } from "@/Composables/useGlobalLoader";

export function openWithLoader(url, message = "Préparation du document...", features = "noopener") {
    const token = showLoader(message);
    const openedWindow = window.open(url, "_blank", features);
    window.setTimeout(() => hideLoader(token), 900);
    return openedWindow;
}
