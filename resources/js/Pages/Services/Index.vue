<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";
import SearchSelect from "@/Components/SearchSelect.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    services: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: "",
        }),
    },
    allServices: {
        type: Array,
        default: () => [],
    },
    statsDestinations: { type: Array, default: () => [] },
    serviceDestinationStats: { type: Array, default: () => [] },
    statsSummary: { type: Object, default: () => ({}) },
});

const page = usePage();

const can = (permission) =>
    page.props?.auth?.isSuperAdmin || !!page.props?.auth?.can?.[permission];

const showPermissionDenied = () => {
    Swal.fire({
        icon: "warning",
        title: "Accès refusé",
        text: "Vous n’avez pas la permission d’effectuer cette action.",
        confirmButtonColor: "#c1121f",
    });
};

const form = reactive({
    search: props.filters?.search || "",
});

const statsForm = reactive({
    stats_date_from: props.filters?.stats_date_from || "",
    stats_date_to: props.filters?.stats_date_to || "",
    stats_service_id: props.filters?.stats_service_id || "",
    stats_destination_id: props.filters?.stats_destination_id || "",
});

const serviceStatsOptions = computed(() => [
    { id: "", designation: "Tous les services" },
    ...props.allServices,
]);

const destinationStatsOptions = computed(() => [
    { id: "", stats_label: "Toutes les destinations", city: "" },
    ...props.statsDestinations.map((destination) => ({
        ...destination,
        stats_label: `${destination.name}${destination.city ? ` · ${destination.city}` : ""}`,
    })),
]);

const selectedStatsService = props.allServices.find(
    (service) => String(service.id) === String(statsForm.stats_service_id),
);
const selectedStatsDestination = destinationStatsOptions.value.find(
    (destination) => String(destination.id) === String(statsForm.stats_destination_id),
);
const statsServiceSearch = ref(selectedStatsService?.designation || "Tous les services");
const statsDestinationSearch = ref(selectedStatsDestination?.stats_label || "Toutes les destinations");
const showStatsModal = ref(false);
const statsTableSearch = ref("");

const filteredServiceDestinationStats = computed(() => {
    const query = statsTableSearch.value.trim().toLocaleLowerCase("fr");
    if (!query) return props.serviceDestinationStats;

    return props.serviceDestinationStats.filter((row) =>
        [row.service_name, row.destination_name, row.destination_city]
            .filter(Boolean)
            .some((value) => String(value).toLocaleLowerCase("fr").includes(query)),
    );
});

const filteredStatsTotals = computed(() =>
    filteredServiceDestinationStats.value.reduce(
        (totals, row) => ({
            trips: totals.trips + Number(row.total_trips || 0),
            dossiers: totals.dossiers + Number(row.total_dossiers || 0),
            budget: totals.budget + Number(row.total_budget || 0),
            supplierPrice:
                totals.supplierPrice + Number(row.total_supplier_price || 0),
            margin: totals.margin + Number(row.gross_margin || 0),
        }),
        { trips: 0, dossiers: 0, budget: 0, supplierPrice: 0, margin: 0 },
    ),
);

const selectedServices = ref([]);
const showReplaceModal = ref(false);
const replacementServiceId = ref("");

const pageServiceIds = computed(() =>
    (props.services?.data || []).map((service) => service.id)
);

const allPageSelected = computed(
    () =>
        pageServiceIds.value.length > 0 &&
        pageServiceIds.value.every((id) => selectedServices.value.includes(id))
);

const selectedRows = computed(() =>
    (props.services?.data || []).filter((service) =>
        selectedServices.value.includes(service.id)
    )
);

const selectedReplacementService = computed(() =>
    props.allServices.find((service) => service.id == replacementServiceId.value)
);

let searchTimeout = null;

watch(
    () => form.search,
    (value) => {
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            router.get(
                "/services",
                { search: value, ...statsForm },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                }
            );
        }, 400);
    }
);

const applyStatsFilters = () => {
    router.get(
        route("services.index"),
        { search: form.search, ...statsForm },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetStatsFilters = () => {
    statsForm.stats_date_from = props.filters?.stats_date_from || "";
    statsForm.stats_date_to = props.filters?.stats_date_to || "";
    statsForm.stats_service_id = "";
    statsForm.stats_destination_id = "";
    statsServiceSearch.value = "Tous les services";
    statsDestinationSearch.value = "Toutes les destinations";
    applyStatsFilters();
};

const formatMoney = (value) =>
    Number(value || 0).toLocaleString("fr-FR", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

// ✅ SweetAlert delete
const destroyService = (id) => {
    if (!can("services.delete")) {
        showPermissionDenied();
        return;
    }

    Swal.fire({
        title: "Delete this service?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, delete",
        cancelButtonText: "Cancel",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/services/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    if (page.props.flash?.error) {
                        Swal.fire({
                            icon: "error",
                            title: "Suppression impossible",
                            text: page.props.flash.error,
                            confirmButtonColor: "#c1121f",
                        });
                        return;
                    }

                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Service deleted successfully",
                        showConfirmButton: false,
                        timer: 2500,
                    });
                },
                onError: () => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Something went wrong",
                    });
                },
            });
        }
    });
};

const serviceTypeName = (service) =>
    service.type_service_name ||
    service.type_service?.designation ||
    service.typeService?.designation ||
    "-";

const serviceOptionLabel = (service) => {
    const type = serviceTypeName(service);
    return `#${service.id} - ${service.designation}${type !== "-" ? ` · ${type}` : ""}`;
};

const togglePageSelection = () => {
    if (allPageSelected.value) {
        selectedServices.value = selectedServices.value.filter(
            (id) => !pageServiceIds.value.includes(id)
        );
        return;
    }

    selectedServices.value = Array.from(
        new Set([...selectedServices.value, ...pageServiceIds.value])
    );
};

const openReplaceModal = () => {
    if (!can("services.manage")) {
        showPermissionDenied();
        return;
    }

    if (!selectedServices.value.length) {
        Swal.fire({
            icon: "warning",
            title: "Aucun service sélectionné",
            text: "Sélectionnez d'abord les services à remplacer.",
            confirmButtonColor: "#c1121f",
        });
        return;
    }

    showReplaceModal.value = true;
};

const submitReplace = () => {
    if (!replacementServiceId.value) {
        Swal.fire({
            icon: "warning",
            title: "Service manquant",
            text: "Choisissez le service correct qui va remplacer les services sélectionnés.",
            confirmButtonColor: "#c1121f",
        });
        return;
    }

    const selectedWithoutReplacement = selectedServices.value.filter(
        (id) => Number(id) !== Number(replacementServiceId.value)
    );

    if (!selectedWithoutReplacement.length) {
        Swal.fire({
            icon: "warning",
            title: "Sélection invalide",
            text: "Le service de remplacement ne peut pas être le seul service sélectionné.",
            confirmButtonColor: "#c1121f",
        });
        return;
    }

    showReplaceModal.value = false;

    setTimeout(() => {
        Swal.fire({
            title: "Remplacer les services sélectionnés ?",
            html: `
                <div style="text-align:left; line-height:1.7">
                    <p><strong>${selectedWithoutReplacement.length}</strong> service(s) sélectionné(s) seront fusionnés.</p>
                    <p>Les plannings, bons de commande et tarifs liés seront rattachés au service correct.</p>
                    <p style="margin-bottom:0;color:#991b1f"><strong>Cette action supprime les doublons sélectionnés.</strong></p>
                </div>
            `,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#c1121f",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Oui, remplacer",
            cancelButtonText: "Annuler",
        }).then((result) => {
            if (!result.isConfirmed) {
                showReplaceModal.value = true;
                return;
            }

            router.post(
                route("services.bulk-replace"),
                {
                    service_ids: selectedWithoutReplacement,
                    replacement_service_id: replacementServiceId.value,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (page.props.flash?.error) {
                            showReplaceModal.value = true;
                            Swal.fire({
                                icon: "error",
                                title: "Remplacement impossible",
                                text: page.props.flash.error,
                                confirmButtonColor: "#c1121f",
                            });
                            return;
                        }

                        selectedServices.value = [];
                        replacementServiceId.value = "";
                        showReplaceModal.value = false;

                        Swal.fire({
                            toast: true,
                            position: "top-end",
                            icon: "success",
                            title:
                                page.props.flash?.success ||
                                "Services remplacés avec succès",
                            showConfirmButton: false,
                            timer: 3200,
                        });
                    },
                    onError: () => {
                        showReplaceModal.value = true;
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: "Vérifiez la sélection et réessayez.",
                            confirmButtonColor: "#c1121f",
                        });
                    },
                }
            );
        });
    }, 180);
};

const getInitials = (designation) => {
    if (!designation) return "SV";

    return designation
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
};
</script>

<template>
    <Head title="Services" />

    <div class="services-page">
        <div class="container-fluid py-4">

            <!-- HERO -->
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-briefcase-alt-2"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Service Management</h1>
                            <p class="hero-subtitle mb-0">
                                Manage, search and organize all your services.
                            </p>
                        </div>
                    </div>

                    <div class="hero-right">
                        <button
                            v-if="can('services.manage')"
                            type="button"
                            class="btn btn-replace-service"
                            @click="openReplaceModal"
                        >
                            <i class="bx bx-transfer-alt me-2"></i>
                            Replace
                            <span
                                v-if="selectedServices.length"
                                class="selected-count"
                            >
                                {{ selectedServices.length }}
                            </span>
                        </button>

                        <Link
                            v-if="can('services.create')"
                            href="/services/create"
                            class="btn btn-add-service"
                        >
                            <i class="bx bx-plus-circle me-2"></i>
                            New Service
                        </Link>
                    </div>
                </div>
            </div>

            <!-- STATS + SEARCH -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-xl-4">
                    <div class="mini-stat-card">
                        <div class="stat-icon">
                            <i class="bx bx-layer"></i>
                        </div>
                        <div>
                            <div class="stat-label">Total Services</div>
                            <div class="stat-value">
                                {{ services.total || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="toolbar-card">
                        <div class="search-wrapper">
                            <i class="bx bx-search search-leading-icon"></i>
                            <input
                                v-model="form.search"
                                type="text"
                                class="form-control search-input"
                                placeholder="Search by name, type or description..."
                            />
                        </div>
                    </div>
                </div>
            </div>

            <section class="stats-launch-card mb-4">
                <div class="analytics-details-toolbar">
                    <label>
                        <i class="bx bx-search"></i>
                        <input v-model="statsTableSearch" type="search" placeholder="Rechercher dans les statistiques…" />
                    </label>
                    <button type="button" @click="showStatsModal = true">
                        <i class="bx bx-bar-chart-alt-2"></i>
                        Afficher les statistiques
                        <span>{{ filteredServiceDestinationStats.length }}</span>
                    </button>
                </div>
            </section>

            <!-- TABLE -->
            <div class="main-card">
                <div class="table-header">
                    <div>
                        <h5 class="table-title mb-1">Services List</h5>
                        <p class="table-subtitle mb-0">
                            Showing {{ services.from || 0 }} to
                            {{ services.to || 0 }} of
                            {{ services.total || 0 }} services
                        </p>
                    </div>
                </div>

                <div class="table-responsive custom-table-wrapper">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="select-col">
                                    <input
                                        type="checkbox"
                                        class="service-check"
                                        :checked="allPageSelected"
                                        @change="togglePageSelection"
                                    />
                                </th>
                                <th>Service</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody v-if="services.data && services.data.length">
                            <tr
                                v-for="service in services.data"
                                :key="service.id"
                                :class="{
                                    'row-selected': selectedServices.includes(
                                        service.id
                                    ),
                                }"
                            >
                                <td class="select-col">
                                    <input
                                        v-model="selectedServices"
                                        type="checkbox"
                                        class="service-check"
                                        :value="service.id"
                                    />
                                </td>
                                <td>
                                    <div class="service-cell">
                                        <div class="service-avatar">
                                            {{ getInitials(service.designation) }}
                                        </div>

                                        <div>
                                            <div class="service-name">
                                                {{ service.designation }}
                                            </div>
                                            <div class="service-id">
                                                ID #{{ service.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="info-badge type-badge">
                                        <i class="bx bx-category-alt me-1"></i>
                                        {{ serviceTypeName(service) }}
                                    </span>
                                </td>

                                <td>
                                    <div class="description-box">
                                        {{ service.description || "-" }}
                                    </div>
                                </td>

                                <td>
                                    <div class="actions-wrapper">
                                        <Link
                                            v-if="can('services.edit')"
                                            :href="`/services/${service.id}/edit`"
                                            class="btn btn-action-edit"
                                        >
                                            <i class="bx bx-edit-alt"></i>
                                            <span>Edit</span>
                                        </Link>

                                        <button
                                            v-if="can('services.delete')"
                                            class="btn btn-action-delete"
                                            @click="destroyService(service.id)"
                                        >
                                            <i class="bx bx-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bx bx-search-alt"></i>
                                        </div>
                                        <h5 class="mb-2">No services found</h5>
                                        <p class="text-muted mb-0">
                                            Try adjusting your search or add a new service.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="services.links && services.links.length > 3" class="pagination-area">
                    <div class="pagination-list">
                        <template v-for="(link, index) in services.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="page-btn"
                                :class="{ active: link.active }"
                                v-html="link.label"
                                preserve-scroll
                                preserve-state
                            />
                            <span v-else class="page-btn disabled" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <Teleport to="body">
            <div v-if="showStatsModal" class="stats-modal-backdrop" @click.self="showStatsModal = false">
                <section class="stats-details-modal" role="dialog" aria-modal="true" aria-labelledby="stats-details-title">
                    <header>
                        <div>
                            <span>Analyse opérationnelle</span>
                            <h2 id="stats-details-title">Statistiques services & destinations</h2>
                            <p>Mesurez les prestations réalisées par service et destination sur une période donnée.</p>
                        </div>
                        <button type="button" @click="showStatsModal = false"><i class="bx bx-x"></i> Fermer</button>
                    </header>

                    <div class="stats-modal-body">
                        <div class="analytics-period-row">
                            <div class="analytics-period-chip">
                                <i class="bx bx-calendar"></i>
                                {{ statsForm.stats_date_from }} → {{ statsForm.stats_date_to }}
                            </div>
                            <span>{{ filteredServiceDestinationStats.length }} résultat(s)</span>
                        </div>

                        <div class="analytics-filters">
                            <label><span>Date début</span><input v-model="statsForm.stats_date_from" type="date" /></label>
                            <label><span>Date fin</span><input v-model="statsForm.stats_date_to" type="date" /></label>
                            <label>
                                <span>Service</span>
                                <SearchSelect
                                    v-model="statsForm.stats_service_id"
                                    v-model:search="statsServiceSearch"
                                    :options="serviceStatsOptions"
                                    label-key="designation"
                                    placeholder="Tous les services"
                                    :allow-custom="false"
                                />
                            </label>
                            <label>
                                <span>Destination</span>
                                <SearchSelect
                                    v-model="statsForm.stats_destination_id"
                                    v-model:search="statsDestinationSearch"
                                    :options="destinationStatsOptions"
                                    label-key="stats_label"
                                    placeholder="Toutes les destinations"
                                    :allow-custom="false"
                                />
                            </label>
                            <button type="button" class="analytics-apply" @click="applyStatsFilters"><i class="bx bx-filter-alt"></i> Appliquer</button>
                            <button type="button" class="analytics-reset" @click="resetStatsFilters">Réinitialiser</button>
                        </div>

                        <div class="analytics-kpis">
                            <div class="analytics-kpi blue"><i class="bx bx-trip"></i><span>Prestations</span><strong>{{ statsSummary.total_trips || 0 }}</strong></div>
                            <div class="analytics-kpi purple"><i class="bx bx-layer"></i><span>Services</span><strong>{{ statsSummary.services_count || 0 }}</strong></div>
                            <div class="analytics-kpi orange"><i class="bx bx-map"></i><span>Destinations</span><strong>{{ statsSummary.destinations_count || 0 }}</strong></div>
                            <div class="analytics-kpi green"><i class="bx bx-wallet"></i><span>Budget total</span><strong>{{ formatMoney(statsSummary.total_budget) }} MAD</strong></div>
                        </div>

                        <div class="stats-modal-search">
                            <i class="bx bx-search"></i>
                            <input v-model="statsTableSearch" type="search" placeholder="Service, destination ou ville…" />
                        </div>

                        <div class="analytics-table-wrap modal-table-wrap">
                        <table class="analytics-table">
                            <thead><tr><th>Service</th><th>Destination</th><th>Ville</th><th>Prestations</th><th>Dossiers</th><th>Budget</th><th>Prix fournisseur</th><th>Marge</th></tr></thead>
                            <tbody>
                                <tr v-for="row in filteredServiceDestinationStats" :key="`${row.service_id || 'none'}-${row.destination_id || 'none'}`">
                                    <td><strong><i class="bx bx-briefcase-alt-2"></i>{{ row.service_name }}</strong></td>
                                    <td>{{ row.destination_name }}</td>
                                    <td><span class="city-badge"><i class="bx bx-map-pin"></i>{{ row.destination_city }}</span></td>
                                    <td><span class="trip-count">{{ row.total_trips }}</span></td>
                                    <td>{{ row.total_dossiers }}</td>
                                    <td class="analytics-money budget">{{ formatMoney(row.total_budget) }} MAD</td>
                                    <td class="analytics-money price">{{ formatMoney(row.total_supplier_price) }} MAD</td>
                                    <td class="analytics-money margin">{{ formatMoney(row.gross_margin) }} MAD</td>
                                </tr>
                                <tr v-if="!filteredServiceDestinationStats.length"><td colspan="8" class="analytics-empty">Aucune prestation trouvée pour cette recherche.</td></tr>
                            </tbody>
                            <tfoot v-if="filteredServiceDestinationStats.length">
                                <tr>
                                    <td colspan="3"><strong>Total</strong></td>
                                    <td><span class="trip-count">{{ filteredStatsTotals.trips }}</span></td>
                                    <td>{{ filteredStatsTotals.dossiers }}</td>
                                    <td class="analytics-money budget">{{ formatMoney(filteredStatsTotals.budget) }} MAD</td>
                                    <td class="analytics-money price">{{ formatMoney(filteredStatsTotals.supplierPrice) }} MAD</td>
                                    <td class="analytics-money margin">{{ formatMoney(filteredStatsTotals.margin) }} MAD</td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>
                </section>
            </div>
        </Teleport>

        <div v-if="showReplaceModal" class="replace-modal-backdrop">
            <div class="replace-modal">
                <div class="replace-modal-header">
                    <div class="modal-icon-title">
                        <div class="modal-icon">
                            <i class="bx bx-transfer-alt"></i>
                        </div>

                        <div>
                            <h4 class="mb-1">Replace selected services</h4>
                            <p class="mb-0">
                                Merge duplicate services into one correct service.
                            </p>
                        </div>
                    </div>

                    <button
                        class="btn-close-custom"
                        @click="showReplaceModal = false"
                    >
                        <i class="bx bx-x"></i>
                    </button>
                </div>

                <div class="warning-box mb-4">
                    <div class="warning-icon">
                        <i class="bx bx-error-circle"></i>
                    </div>

                    <div>
                        <h6 class="mb-1">What will happen?</h6>
                        <p class="mb-0">
                            The selected duplicate services will be removed.
                            Plannings, commandes and supplier tariffs will be
                            attached to the correct service selected below.
                        </p>
                    </div>
                </div>

                <div class="summary-grid mb-4">
                    <div class="summary-card">
                        <span class="summary-label">Selected services</span>
                        <strong>{{ selectedRows.length }}</strong>
                    </div>

                    <div class="summary-card">
                        <span class="summary-label">Correct service</span>
                        <strong>
                            {{
                                selectedReplacementService?.designation ||
                                "Not selected"
                            }}
                        </strong>
                    </div>
                </div>

                <div class="replace-section">
                    <div class="section-title">
                        <i class="bx bx-list-check"></i>
                        Selected duplicate services
                    </div>

                    <div class="selected-list">
                        <div
                            v-for="row in selectedRows"
                            :key="row.id"
                            class="selected-item"
                        >
                            <div class="selected-left">
                                <div class="selected-avatar">
                                    {{ getInitials(row.designation) }}
                                </div>

                                <div>
                                    <strong>
                                        #{{ row.id }} - {{ row.designation }}
                                    </strong>
                                    <span> Type: {{ serviceTypeName(row) }} </span>
                                </div>
                            </div>

                            <div class="selected-badge">Will be merged</div>
                        </div>
                    </div>
                </div>

                <div class="replace-section mt-4">
                    <label class="section-title mb-2">
                        <i class="bx bx-layer"></i>
                        Replace in plannings with this correct Service
                    </label>

                    <select
                        v-model="replacementServiceId"
                        class="form-select service-select"
                    >
                        <option value="">-- Select Service --</option>

                        <option
                            v-for="service in allServices"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ serviceOptionLabel(service) }}
                        </option>
                    </select>
                </div>

                <div v-if="selectedReplacementService" class="final-preview mt-4">
                    <strong>Final preview:</strong>
                    selected duplicate services will be merged into
                    <span>{{ selectedReplacementService.designation }}</span>.
                </div>

                <div class="replace-modal-actions">
                    <button
                        class="btn btn-light btn-cancel-replace"
                        @click="showReplaceModal = false"
                    >
                        Cancel
                    </button>

                    <button
                        class="btn btn-confirm-replace"
                        @click="submitReplace"
                    >
                        <i class="bx bx-check-circle me-2"></i>
                        Continue to confirmation
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.services-page {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.1),
            transparent 24%
        ),
        radial-gradient(
            circle at top right,
            rgba(249, 115, 22, 0.08),
            transparent 22%
        ),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.hero-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
    box-shadow: 0 20px 40px rgba(190, 24, 93, 0.18);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(255, 255, 255, 0.18),
            transparent 25%
        ),
        radial-gradient(
            circle at 80% 30%,
            rgba(255, 255, 255, 0.12),
            transparent 25%
        );
    pointer-events: none;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 18px;
}

.hero-icon {
    width: 72px;
    height: 72px;
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    font-size: 34px;
    backdrop-filter: blur(8px);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
}

.hero-title {
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 6px;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.82);
    font-size: 0.98rem;
}

.btn-add-service,
.btn-replace-service {
    border: 0;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: 0.25s ease;
}

.btn-add-service {
    color: #991b1b;
    background: #fff;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.btn-add-service:hover {
    transform: translateY(-2px);
    color: #7f1d1d;
    background: #fff;
}

.btn-replace-service {
    color: #fff;
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(8px);
}

.btn-replace-service:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.24);
    transform: translateY(-2px);
}

.selected-count {
    min-width: 24px;
    height: 24px;
    padding: 0 7px;
    border-radius: 999px;
    background: #fff;
    color: #be123c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    font-weight: 900;
}

.mini-stat-card,
.toolbar-card,
.main-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.7);
    border-radius: 24px;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
}

.mini-stat-card {
    padding: 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    height: 100%;
}

.stat-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    background: linear-gradient(135deg, #e11d48 0%, #fb923c 100%);
    box-shadow: 0 12px 22px rgba(225, 29, 72, 0.22);
}

.stat-label {
    font-size: 0.92rem;
    color: #64748b;
    margin-bottom: 4px;
}

.stat-value {
    font-size: 1.8rem;
    line-height: 1;
    font-weight: 800;
    color: #0f172a;
}

.toolbar-card {
    padding: 20px;
    height: 100%;
    display: flex;
    align-items: center;
}

.search-wrapper {
    width: 100%;
    position: relative;
}

.search-leading-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #94a3b8;
}

.search-input {
    min-height: 56px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    padding-left: 46px;
    font-size: 0.98rem;
    background: #fff;
}

.search-input:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.main-card {
    overflow: hidden;
}

.service-analytics-card {
    padding: 24px;
    border: 1px solid rgba(255, 255, 255, 0.75);
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07);
}

.stats-launch-card {
    padding: 18px;
    border: 1px solid rgba(255, 255, 255, 0.75);
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.07);
}

.analytics-title-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 18px;
}

.analytics-kicker { color: #be123c; font-size: .72rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
.analytics-title-row h2 { margin: 4px 0; color: #0f172a; font-size: 1.35rem; font-weight: 900; }
.analytics-title-row p { margin: 0; color: #64748b; }
.analytics-period-chip { display: inline-flex; align-items: center; gap: 7px; padding: 9px 12px; border-radius: 999px; color: #6d28d9; background: #f5f3ff; font-size: .78rem; font-weight: 850; white-space: nowrap; }

.analytics-filters { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)) auto auto; gap: 10px; align-items: end; padding: 14px; border: 1px solid #e2e8f0; border-radius: 16px; background: #f8fafc; }
.analytics-filters label { display: grid; gap: 5px; }
.analytics-filters label span { color: #64748b; font-size: .7rem; font-weight: 900; text-transform: uppercase; }
.analytics-filters input, .analytics-filters select { width: 100%; min-height: 42px; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; }
.analytics-apply, .analytics-reset { min-height: 42px; padding: 8px 13px; border-radius: 10px; font-weight: 850; white-space: nowrap; }
.analytics-apply { border: 0; color: #fff; background: linear-gradient(135deg, #be123c, #e11d48); }
.analytics-reset { border: 1px solid #cbd5e1; color: #475569; background: #fff; }

.analytics-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 16px 0; }
.analytics-kpi { display: grid; grid-template-columns: 42px 1fr; column-gap: 10px; align-items: center; padding: 13px; border: 1px solid; border-radius: 14px; }
.analytics-kpi i { grid-row: span 2; display: grid; place-items: center; width: 42px; height: 42px; border-radius: 12px; font-size: 1.25rem; }
.analytics-kpi span { color: #64748b; font-size: .7rem; font-weight: 850; text-transform: uppercase; }
.analytics-kpi strong { color: #0f172a; font-size: 1rem; font-weight: 900; }
.analytics-kpi.blue { border-color: #bfdbfe; background: #eff6ff; }.analytics-kpi.blue i { color: #2563eb; background: #dbeafe; }
.analytics-kpi.purple { border-color: #ddd6fe; background: #f5f3ff; }.analytics-kpi.purple i { color: #7c3aed; background: #ede9fe; }
.analytics-kpi.orange { border-color: #fed7aa; background: #fff7ed; }.analytics-kpi.orange i { color: #ea580c; background: #ffedd5; }
.analytics-kpi.green { border-color: #a7f3d0; background: #ecfdf5; }.analytics-kpi.green i { color: #059669; background: #d1fae5; }

.analytics-table-wrap { overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 16px; }
.analytics-table { width: 100%; border-collapse: collapse; white-space: nowrap; }
.analytics-table th { padding: 12px 14px; color: #475569; background: #f1f5f9; font-size: .7rem; font-weight: 900; text-align: left; text-transform: uppercase; }
.analytics-table td { padding: 13px 14px; border-top: 1px solid #edf2f7; color: #334155; font-size: .82rem; }
.analytics-table tfoot td { position: sticky; bottom: 0; z-index: 2; border-top: 2px solid #cbd5e1; background: #f8fafc; font-weight: 900; box-shadow: 0 -5px 14px rgba(15,23,42,.06); }
.analytics-table tbody tr:hover { background: #fff7f7; }
.analytics-table td strong { display: inline-flex; align-items: center; gap: 7px; color: #172554; }.analytics-table td strong i { color: #be123c; }
.city-badge, .trip-count { display: inline-flex; align-items: center; gap: 5px; padding: 5px 8px; border-radius: 999px; font-weight: 850; }
.city-badge { color: #6d28d9; background: #f5f3ff; }.trip-count { color: #1d4ed8; background: #dbeafe; }
.analytics-money { font-weight: 850; }.analytics-money.budget { color: #be123c; }.analytics-money.price { color: #c2410c; }.analytics-money.margin { color: #047857; }
.analytics-empty { padding: 28px !important; color: #94a3b8 !important; text-align: center; }
.analytics-details-toolbar { display: flex; align-items: center; gap: 12px; }
.analytics-details-toolbar label { position: relative; flex: 1; }
.analytics-details-toolbar label i, .stats-modal-search i { position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem; }
.analytics-details-toolbar input, .stats-modal-search input { width: 100%; min-height: 44px; padding: 9px 12px 9px 42px; border: 1px solid #cbd5e1; border-radius: 12px; background: #fff; outline: none; }
.analytics-details-toolbar input:focus, .stats-modal-search input:focus { border-color: #be123c; box-shadow: 0 0 0 4px rgba(190,18,60,.08); }
.analytics-details-toolbar button { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 9px 15px; border: 0; border-radius: 12px; color: #fff; background: linear-gradient(135deg, #172554, #4338ca); font-weight: 900; white-space: nowrap; box-shadow: 0 9px 18px rgba(67,56,202,.18); }
.analytics-details-toolbar button span { display: grid; place-items: center; min-width: 24px; height: 24px; padding: 0 6px; border-radius: 999px; background: rgba(255,255,255,.18); font-size: .72rem; }

.stats-modal-backdrop { position: fixed; z-index: 1500; inset: 0; display: grid; place-items: center; padding: 2.5vh; background: rgba(15,23,42,.72); backdrop-filter: blur(8px); }
.stats-details-modal { display: flex; flex-direction: column; width: 95vw; max-width: 1800px; height: 90vh; overflow: hidden; border: 1px solid rgba(255,255,255,.7); border-radius: 24px; background: #fff; box-shadow: 0 30px 80px rgba(15,23,42,.3); }
.stats-details-modal > header { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; padding: 20px 24px; color: #fff; background: linear-gradient(135deg, #172554, #312e81 58%, #6d28d9); }
.stats-details-modal > header span { color: #c4b5fd; font-size: .7rem; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
.stats-details-modal > header h2 { margin: 4px 0; color: #fff; font-size: 1.35rem; font-weight: 900; }
.stats-details-modal > header p { margin: 0; color: rgba(255,255,255,.72); }
.stats-details-modal > header button { display: inline-flex; align-items: center; gap: 6px; padding: 9px 12px; border: 1px solid rgba(255,255,255,.22); border-radius: 11px; color: #fff; background: rgba(255,255,255,.12); font-weight: 850; }
.stats-modal-body { flex: 1; min-height: 0; padding: 18px; overflow-y: auto; }
.analytics-period-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.analytics-period-row > span { color: #64748b; font-size: .8rem; font-weight: 850; }
.stats-modal-search { position: relative; margin: 0 0 14px; }
.modal-table-wrap { min-height: 240px; max-height: 52vh; margin: 0; overflow: auto; }
.modal-table-wrap .analytics-table thead { position: sticky; z-index: 2; top: 0; }

@media (max-width: 1199px) {
    .analytics-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .analytics-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 767px) {
    .service-analytics-card { padding: 16px; }
    .analytics-title-row { flex-direction: column; }
    .analytics-filters, .analytics-kpis { grid-template-columns: 1fr; }
    .analytics-apply, .analytics-reset { width: 100%; }
    .analytics-details-toolbar { align-items: stretch; flex-direction: column; }
    .analytics-details-toolbar button { justify-content: center; width: 100%; }
    .stats-modal-backdrop { padding: 0; }
    .stats-details-modal { width: 100vw; height: 100vh; border-radius: 0; }
    .stats-details-modal > header { padding: 16px; }
    .stats-details-modal > header h2 { font-size: 1rem; }
    .stats-details-modal > header button { padding: 8px; }
    .stats-details-modal > header button { font-size: 0; }
    .stats-details-modal > header button i { font-size: 1.25rem; }
    .stats-modal-body { padding: 14px; }
    .analytics-period-row { align-items: flex-start; flex-direction: column; }
    .modal-table-wrap { max-height: none; }
}

.table-header {
    padding: 22px 24px 16px;
    border-bottom: 1px solid #eef2f7;
}

.table-title {
    font-weight: 800;
    color: #0f172a;
}

.table-subtitle {
    color: #64748b;
    font-size: 0.92rem;
}

.custom-table thead th {
    padding: 16px 18px;
    background: linear-gradient(180deg, #fff1f2 0%, #fff7ed 100%);
    color: #9f1239;
    font-size: 0.85rem;
    font-weight: 800;
    border-bottom: 1px solid #ffe4e6;
    white-space: nowrap;
}

.select-col {
    width: 62px;
    text-align: center;
}

.service-check {
    width: 19px;
    height: 19px;
    cursor: pointer;
    accent-color: #c1121f;
}

.custom-table tbody td {
    padding: 18px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.custom-table tbody tr {
    transition: 0.2s ease;
}

.custom-table tbody tr:hover {
    background: rgba(248, 250, 252, 0.95);
}

.custom-table tbody tr.row-selected {
    background: rgba(225, 29, 72, 0.06);
}

.service-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.service-avatar {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    box-shadow: 0 10px 18px rgba(190, 24, 93, 0.18);
}

.service-name {
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 2px;
}

.service-id {
    font-size: 0.82rem;
    color: #94a3b8;
}

.info-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 0.88rem;
    font-weight: 600;
    white-space: nowrap;
}

.type-badge {
    background: #fff7ed;
    color: #c2410c;
}

.description-box {
    max-width: 360px;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 10px 12px;
    border-radius: 12px;
}

.actions-wrapper {
    display: flex;
    justify-content: center;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-action-edit,
.btn-action-delete {
    border: 0;
    border-radius: 12px;
    padding: 9px 14px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    transition: 0.2s ease;
}

.btn-action-edit {
    color: #fff;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    box-shadow: 0 10px 18px rgba(245, 158, 11, 0.2);
}

.btn-action-delete {
    color: #fff;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    box-shadow: 0 10px 18px rgba(239, 68, 68, 0.2);
}

.btn-action-edit:hover,
.btn-action-delete:hover {
    color: #fff;
    transform: translateY(-2px);
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-icon {
    width: 78px;
    height: 78px;
    margin: 0 auto 16px;
    border-radius: 24px;
    background: linear-gradient(135deg, #fda4af 0%, #fdba74 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 34px;
    box-shadow: 0 16px 28px rgba(244, 114, 182, 0.18);
}

.pagination-area {
    padding: 18px 24px 24px;
    display: flex;
    justify-content: center;
    border-top: 1px solid #eef2f7;
}

.pagination-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
}

.page-btn {
    min-width: 42px;
    height: 42px;
    padding: 0 14px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-weight: 700;
    transition: 0.2s ease;
}

.page-btn:hover {
    background: #fff1f2;
    color: #be123c;
    border-color: #fecdd3;
}

.page-btn.active {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
    box-shadow: 0 12px 22px rgba(190, 24, 93, 0.22);
}

.page-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
    background: #f8fafc;
}

.replace-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.replace-modal {
    width: min(880px, 100%);
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
    padding: 24px;
}

.replace-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    padding-bottom: 18px;
    border-bottom: 1px solid #eef2f7;
    margin-bottom: 18px;
}

.modal-icon-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.modal-icon,
.warning-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    font-size: 24px;
    flex: 0 0 auto;
}

.replace-modal-header h4 {
    font-weight: 900;
    color: #0f172a;
}

.replace-modal-header p,
.warning-box p {
    color: #64748b;
}

.btn-close-custom {
    border: 0;
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #f8fafc;
    color: #64748b;
    font-size: 22px;
}

.warning-box {
    display: flex;
    gap: 14px;
    padding: 16px;
    border-radius: 18px;
    border: 1px solid #fed7aa;
    background: #fff7ed;
}

.warning-box h6 {
    font-weight: 900;
    color: #9a3412;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.summary-card,
.replace-section,
.final-preview {
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    background: #f8fafc;
}

.summary-card {
    padding: 16px;
}

.summary-label {
    display: block;
    color: #64748b;
    font-size: 0.84rem;
    margin-bottom: 6px;
}

.summary-card strong {
    color: #0f172a;
    font-size: 1.05rem;
}

.replace-section {
    padding: 16px;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #0f172a;
    font-weight: 900;
}

.selected-list {
    margin-top: 12px;
    display: grid;
    gap: 10px;
}

.selected-item {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
    padding: 12px;
    border-radius: 14px;
    background: #fff;
    border: 1px solid #eef2f7;
}

.selected-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.selected-avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
}

.selected-left span {
    display: block;
    color: #64748b;
    font-size: 0.85rem;
}

.selected-badge {
    white-space: nowrap;
    padding: 7px 10px;
    border-radius: 999px;
    color: #be123c;
    background: #fff1f2;
    font-weight: 800;
    font-size: 0.78rem;
}

.service-select {
    min-height: 52px;
    border-radius: 14px;
    border-color: #e2e8f0;
    margin-top: 8px;
}

.service-select:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.final-preview {
    padding: 14px 16px;
    color: #475569;
}

.final-preview span {
    color: #be123c;
    font-weight: 900;
}

.replace-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 22px;
}

.btn-cancel-replace,
.btn-confirm-replace {
    border: 0;
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 900;
}

.btn-confirm-replace {
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.btn-confirm-replace:hover {
    color: #fff;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .hero-card {
        padding: 20px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .description-box {
        max-width: 180px;
    }

    .btn-action-edit span,
    .btn-action-delete span {
        display: none;
    }

    .summary-grid {
        grid-template-columns: 1fr;
    }

    .selected-item,
    .replace-modal-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-cancel-replace,
    .btn-confirm-replace {
        width: 100%;
    }
}
</style>
