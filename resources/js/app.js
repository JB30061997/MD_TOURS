import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, Fragment, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ApexCharts from 'apexcharts';
import Swal from 'sweetalert2';
import GlobalLoader from './Components/GlobalLoader.vue';
import { hideLoader, showLoader } from './Composables/useGlobalLoader';
import { installEnglishUi } from './i18n/installEnglishUi';


const viteAppName = import.meta.env.VITE_APP_NAME;
const appName = viteAppName && viteAppName !== 'Laravel' ? viteAppName : 'MD Tours';
const inertiaVisits = new Map();

const inertiaMessage = (visit = {}) => {
    const method = String(visit.method || 'get').toUpperCase();
    const url = String(visit.url || '');
    if (method === 'POST' && /planning-quick\/services|\/services(?:\?|$)/i.test(url)) return 'Creating service...';
    if (method === 'POST' && /planning-quick\/clients|\/clients(?:\?|$)/i.test(url)) return 'Creating client...';
    if (method === 'POST' && /supplier/i.test(url)) return 'Creating supplier...';
    if (method === 'POST' && /\/plannings(?:\?|$)/i.test(url)) return 'Creating planning...';
    if (['PUT', 'PATCH'].includes(method) && /\/plannings\//i.test(url)) return 'Updating planning...';
    if (/pdf|print/i.test(url)) return 'Generating PDF...';
    if (/export/i.test(url)) return 'Exporting data...';
    if (/import/i.test(url)) return 'Importing data...';
    if (/mail|send/i.test(url)) return 'Sending email...';
    if (method === 'POST') return 'Saving...';
    if (['PUT', 'PATCH'].includes(method)) return 'Updating...';
    if (method === 'DELETE') return 'Deleting...';
    return 'Loading...';
};

router.on('start', (event) => {
    const visit = event.detail.visit;
    inertiaVisits.set(visit, showLoader(inertiaMessage(visit)));
});

router.on('finish', (event) => {
    const visit = event.detail.visit;
    hideLoader(inertiaVisits.get(visit));
    inertiaVisits.delete(visit);
});

window.addEventListener('md-tours:loader-timeout', (event) => {
    Swal.fire({
        icon: 'error',
        title: 'Request timed out',
        text: event.detail?.message || 'The request took too long.',
        confirmButtonColor: '#c1121f',
    });
});

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');
    if (!link || link.dataset.noGlobalLoader !== undefined) return;
    const url = String(link.getAttribute('href') || '');
    const isDocument = link.hasAttribute('download') || /(?:pdf|export|download|print)/i.test(url);
    if (!isDocument || url.startsWith('#')) return;

    const token = showLoader(/pdf|print/i.test(url) ? 'Generating PDF...' : 'Preparing download...');
    window.setTimeout(() => hideLoader(token), 900);
}, true);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(Fragment, null, [h(App, props), h(GlobalLoader)]) })
            .use(plugin)
            .use(ZiggyVue);
        const instance = vueApp.mount(el);
        installEnglishUi(el);
        return instance;
    },
    progress: false,
});
