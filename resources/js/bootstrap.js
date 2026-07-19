import axios from 'axios';
import { hideLoader, showLoader } from './Composables/useGlobalLoader';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const ajaxMessage = (config) => {
    if (config.loaderMessage) return config.loaderMessage;
    const method = String(config.method || 'get').toUpperCase();
    const url = String(config.url || '');
    if (method === 'POST' && /planning-quick\/services|\/services(?:\?|$)/i.test(url)) return 'Création du service...';
    if (method === 'POST' && /planning-quick\/clients|\/clients(?:\?|$)/i.test(url)) return 'Création du client...';
    if (method === 'POST' && /supplier/i.test(url)) return 'Création du fournisseur...';
    if (method === 'POST' && /\/plannings(?:\?|$)/i.test(url)) return 'Création du planning...';
    if (['PUT', 'PATCH'].includes(method) && /\/plannings\//i.test(url)) return 'Modification du planning...';
    if (/pdf|print/i.test(url)) return 'Génération du PDF...';
    if (/export|download/i.test(url)) return 'Préparation du téléchargement...';
    if (/import|upload/i.test(url)) return 'Import des données...';
    if (/mail|send/i.test(url)) return 'Envoi du mail...';
    if (/sync/i.test(url)) return 'Synchronisation...';
    if (method === 'POST') return 'Enregistrement...';
    if (['PUT', 'PATCH'].includes(method)) return 'Modification en cours...';
    if (method === 'DELETE') return 'Suppression...';
    return 'Chargement des données...';
};

window.axios.interceptors.request.use((config) => {
    if (config.globalLoader === false) return config;
    config.__globalLoaderToken = showLoader(ajaxMessage(config), { timeout: config.loaderTimeout });
    return config;
});

const finishAjaxLoader = (result) => {
    hideLoader(result?.config?.__globalLoaderToken);
    return result;
};

window.axios.interceptors.response.use(
    finishAjaxLoader,
    (error) => {
        hideLoader(error?.config?.__globalLoaderToken);
        return Promise.reject(error);
    },
);
