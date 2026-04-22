<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    audits: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
        }),
    },
});

const detailsModalTitle = ref("");
const detailsModalType = ref("old");
const detailsModalValues = ref({});

const eventClass = (event) => {
    if (event === "created") return "badge-created";
    if (event === "updated") return "badge-updated";
    if (event === "deleted") return "badge-deleted";
    return "badge-default";
};

const eventLabel = (event) => {
    if (event === "created") return "Création";
    if (event === "updated") return "Modification";
    if (event === "deleted") return "Suppression";
    return event || "-";
};

const formatModuleName = (value) => {
    if (!value) return "-";

    const map = {
        Planning: "Planning",
        Client: "Client",
        Supplier: "Fournisseur",
        Driver: "Chauffeur",
        Guide: "Guide",
        Service: "Service",
        PlanningClient: "Client Planning",
        User: "Utilisateur",
    };

    return map[value] || value;
};

const formatFieldLabel = (key) => {
    const map = {
        date_du: "Date du",
        date_au: "Date au",
        ref_dossier: "Référence dossier",
        destination: "Destination",
        point_depart: "Point de départ",
        bus: "Bus",
        nbr_personnes: "Nombre de personnes",
        flight: "Vol",
        heure: "Heure",
        supplier_id: "Fournisseur",
        driver_id: "Chauffeur",
        guide_id: "Guide",
        service_id: "Service",
        budget: "Budget",
        supplier_price: "Prix fournisseur",
        notes: "Notes",
        name: "Nom",
        full_name: "Nom complet",
        phone: "Téléphone",
        email: "Email",
        address: "Adresse",
        status: "Statut",
        designation: "Désignation",
        type_service: "Type service",
        type_supplier_id: "Type fournisseur",
        created_at: "Date création",
        updated_at: "Date modification",
        deleted_at: "Date suppression",
        id: "Identifiant",
    };

    return map[key] || key.replaceAll("_", " ");
};

const normalizeValue = (value) => {
    if (value === null || value === undefined || value === "") return "-";
    if (typeof value === "boolean") return value ? "Oui" : "Non";
    return String(value);
};

const hasValues = (values) => {
    return (
        values && typeof values === "object" && Object.keys(values).length > 0
    );
};

const openDetailsModal = (title, type, values) => {
    detailsModalTitle.value = title;
    detailsModalType.value = type;
    detailsModalValues.value = values || {};

    const el = document.getElementById("auditDetailsModal");
    if (!el) return;

    if (window.bootstrap) {
        const modal = new window.bootstrap.Modal(el);
        modal.show();
    }
};

const detailModalBadgeText = () => {
    return detailsModalType.value === "old"
        ? "Anciennes valeurs"
        : "Nouvelles valeurs";
};

const detailModalBadgeClass = () => {
    return detailsModalType.value === "old" ? "badge-old" : "badge-new";
};

const summaryText = (values, type = "old") => {
    if (!hasValues(values)) {
        return type === "old"
            ? "Aucune ancienne valeur"
            : "Aucune nouvelle valeur";
    }

    const count = Object.keys(values).length;
    return `${count} champ(s) disponible(s)`;
};
</script>

<template>
    <Head title="Historique" />

    <div class="page-content">
        <div class="container-fluid">
            <div
                class="history-hero card border-0 shadow-lg mb-4 overflow-hidden"
            >
                <div class="card-body p-4 p-lg-5">
                    <h1 class="history-title mb-2">
                        Historique de mes actions
                    </h1>
                    <p class="history-subtitle mb-0">
                        Consultez les opérations que vous avez effectuées sur la
                        plateforme.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 history-main-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table align-middle table-hover mb-0 custom-history-table"
                        >
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>ID</th>
                                    <th>Ancien</th>
                                    <th>Nouveau</th>
                                    <th>URL</th>
                                    <th>IP</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="audit in audits.data"
                                    :key="audit.id"
                                >
                                    <td>
                                        <div class="date-cell">
                                            <div class="date-main">
                                                {{ audit.created_at || "-" }}
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span
                                            class="event-badge"
                                            :class="eventClass(audit.event)"
                                        >
                                            {{ eventLabel(audit.event) }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="module-badge">
                                            {{
                                                formatModuleName(
                                                    audit.auditable_type,
                                                )
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="id-badge">
                                            #{{ audit.auditable_id }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="value-action-box">
                                            <div
                                                class="value-summary old-summary"
                                            >
                                                {{
                                                    summaryText(
                                                        audit.old_values,
                                                        "old",
                                                    )
                                                }}
                                            </div>

                                            <button
                                                type="button"
                                                class="btn value-btn value-btn-old"
                                                @click="
                                                    openDetailsModal(
                                                        `Historique ${formatModuleName(audit.auditable_type)} #${audit.auditable_id}`,
                                                        'old',
                                                        audit.old_values,
                                                    )
                                                "
                                            >
                                                <i
                                                    class="bx bx-show-alt me-1"
                                                ></i>
                                                Voir
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="value-action-box">
                                            <div
                                                class="value-summary new-summary"
                                            >
                                                {{
                                                    summaryText(
                                                        audit.new_values,
                                                        "new",
                                                    )
                                                }}
                                            </div>

                                            <button
                                                type="button"
                                                class="btn value-btn value-btn-new"
                                                @click="
                                                    openDetailsModal(
                                                        `Historique ${formatModuleName(audit.auditable_type)} #${audit.auditable_id}`,
                                                        'new',
                                                        audit.new_values,
                                                    )
                                                "
                                            >
                                                <i
                                                    class="bx bx-show-alt me-1"
                                                ></i>
                                                Voir
                                            </button>
                                        </div>
                                    </td>

                                    <td class="url-cell">
                                        <span class="url-badge">{{
                                            audit.url || "-"
                                        }}</span>
                                    </td>

                                    <td>
                                        <span class="ip-badge">{{
                                            audit.ip_address || "-"
                                        }}</span>
                                    </td>
                                </tr>

                                <tr v-if="audits.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="text-center py-5 text-muted"
                                    >
                                        Aucun historique disponible.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="audits.links?.length"
                        class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top"
                    >
                        <div class="text-muted small">
                            Page {{ audits.current_page }} /
                            {{ audits.last_page }}
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <Link
                                v-for="(link, index) in audits.links"
                                :key="index"
                                :href="link.url || ''"
                                v-html="link.label"
                                class="btn btn-sm"
                                :class="
                                    link.active
                                        ? 'btn-danger-red'
                                        : 'btn-outline-secondary'
                                "
                                :disabled="!link.url"
                                preserve-scroll
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details modal -->
            <div
                class="modal fade"
                id="auditDetailsModal"
                tabindex="-1"
                aria-hidden="true"
            >
                <div
                    class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable"
                >
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold mb-2">
                                    {{ detailsModalTitle }}
                                </h5>
                                <span
                                    class="details-badge"
                                    :class="detailModalBadgeClass()"
                                >
                                    {{ detailModalBadgeText() }}
                                </span>
                            </div>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                            ></button>
                        </div>

                        <div class="modal-body pt-3">
                            <div
                                v-if="hasValues(detailsModalValues)"
                                class="details-grid"
                            >
                                <div
                                    v-for="(value, key) in detailsModalValues"
                                    :key="key"
                                    class="detail-card"
                                >
                                    <div class="detail-label">
                                        {{ formatFieldLabel(key) }}
                                    </div>
                                    <div class="detail-value">
                                        {{ normalizeValue(value) }}
                                    </div>
                                </div>
                            </div>

                            <div v-else class="empty-details-box">
                                <div class="empty-icon">
                                    <i class="bx bx-folder-open"></i>
                                </div>
                                <div class="empty-title">
                                    Aucune donnée à afficher
                                </div>
                                <div class="empty-subtitle">
                                    Il n'y a pas de valeurs disponibles pour
                                    cette opération.
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-0">
                            <button
                                type="button"
                                class="btn btn-light rounded-3 px-4"
                                data-bs-dismiss="modal"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background:
        radial-gradient(
            circle at top left,
            rgba(193, 18, 31, 0.06),
            transparent 18%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(29, 78, 216, 0.06),
            transparent 18%
        ),
        #f4f6fb;
    min-height: 100vh;
}

.history-hero {
    border-radius: 28px;
    background: linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 20px 45px rgba(127, 16, 36, 0.18);
}

.history-title {
    font-size: 2.1rem;
    font-weight: 900;
    margin: 0;
    color: #fff;
    letter-spacing: -0.03em;
}

.history-subtitle {
    color: rgba(255, 255, 255, 0.86);
    font-size: 1rem;
}

.history-main-card {
    overflow: hidden;
    border-radius: 28px !important;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
}

.custom-history-table thead th {
    background: #f8fafc;
    font-size: 0.86rem;
    font-weight: 800;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    padding: 16px 14px;
}

.custom-history-table tbody td {
    vertical-align: middle;
    padding: 16px 14px;
    font-size: 0.92rem;
    border-color: #eef2f7;
}

.custom-history-table tbody tr {
    transition: background 0.22s ease;
}

.custom-history-table tbody tr:hover {
    background: rgba(248, 250, 252, 0.7);
}

.date-main {
    font-weight: 800;
    color: #0f172a;
    white-space: nowrap;
}

.event-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 118px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
}

.badge-created {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.badge-updated {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.badge-deleted {
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
}

.badge-default {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
}

.module-badge,
.id-badge,
.ip-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 14px;
    font-weight: 700;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
}

.url-badge {
    display: inline-block;
    max-width: 250px;
    word-break: break-word;
    color: #475569;
    font-weight: 600;
    line-height: 1.5;
}

.value-action-box {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 170px;
}

.value-summary {
    font-size: 0.84rem;
    font-weight: 700;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.old-summary {
    background: #fff7ed;
    color: #9a3412;
    border-color: #fdba74;
}

.new-summary {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #93c5fd;
}

.value-btn {
    min-height: 42px;
    border-radius: 14px;
    font-weight: 800;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        opacity 0.22s ease;
}

.value-btn:hover {
    transform: translateY(-2px);
}

.value-btn-old {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    color: #fff;
    box-shadow: 0 12px 24px rgba(234, 88, 12, 0.22);
}

.value-btn-old:hover {
    color: #fff;
    opacity: 0.96;
}

.value-btn-new {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 12px 24px rgba(29, 78, 216, 0.22);
}

.value-btn-new:hover {
    color: #fff;
    opacity: 0.96;
}

.btn-danger-red {
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    border: none;
    box-shadow: 0 14px 26px rgba(143, 18, 48, 0.18);
}

.btn-danger-red:hover {
    color: #fff;
}

/* =========================
   PREMIUM AUDIT MODAL
========================= */

#auditDetailsModal .modal-dialog {
    max-width: 1180px;
}

#auditDetailsModal .modal-content {
    border: none;
    border-radius: 34px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(18px);
    box-shadow:
        0 35px 90px rgba(15, 23, 42, 0.18),
        0 12px 35px rgba(15, 23, 42, 0.08);
    animation: auditModalPop 0.35s ease;
}

#auditDetailsModal .modal-header {
    padding: 28px 30px 18px;
    background: linear-gradient(
        135deg,
        rgba(213, 16, 36, 0.08) 0%,
        rgba(143, 18, 48, 0.05) 40%,
        rgba(42, 86, 217, 0.06) 100%
    );
    border-bottom: 1px solid #edf2f7;
    position: relative;
}

#auditDetailsModal .modal-header::after {
    content: "";
    position: absolute;
    inset: auto 0 0 0;
    height: 1px;
    background: linear-gradient(
        90deg,
        rgba(213, 16, 36, 0.18),
        rgba(42, 86, 217, 0.18)
    );
}

#auditDetailsModal .modal-title {
    font-size: 1.45rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.02em;
}

.details-badge {
    padding: 10px 18px;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 10px 18px rgba(15, 23, 42, 0.05);
}

.badge-old {
    background: rgba(249, 115, 22, 0.12);
    color: #c2410c;
    border: 1px solid rgba(249, 115, 22, 0.18);
}

.badge-new {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    border: 1px solid rgba(37, 99, 235, 0.18);
}

#auditDetailsModal .btn-close {
    width: 46px;
    height: 46px;
    border-radius: 16px;
    background-color: #ffffff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    opacity: 1;
    transition: all 0.28s ease;
}

#auditDetailsModal .btn-close:hover {
    transform: rotate(90deg) scale(1.05);
    box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
}

#auditDetailsModal .modal-body {
    padding: 28px 30px;
    background:
        radial-gradient(
            circle at top left,
            rgba(193, 18, 31, 0.03),
            transparent 20%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(29, 78, 216, 0.03),
            transparent 20%
        ),
        #ffffff;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

.detail-card {
    position: relative;
    border-radius: 24px;
    padding: 22px;
    border: 1px solid #e8eef6;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    transition:
        transform 0.35s ease,
        box-shadow 0.35s ease,
        border-color 0.35s ease;
    overflow: hidden;
    animation: auditCardIn 0.5s ease both;
}

.detail-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        120deg,
        transparent 0%,
        rgba(255, 255, 255, 0.45) 50%,
        transparent 100%
    );
    transform: translateX(-120%);
    transition: transform 0.75s ease;
    pointer-events: none;
}

.detail-card::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    border-radius: 24px 24px 0 0;
    opacity: 0.96;
}

.detail-card:hover {
    transform: translateY(-6px) scale(1.01);
    box-shadow: 0 22px 42px rgba(15, 23, 42, 0.1);
}

.detail-card:hover::before {
    transform: translateX(120%);
}

.detail-label {
    font-size: 0.83rem;
    font-weight: 900;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-size: 1.08rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.6;
    word-break: break-word;
}

/* Color variants */
.detail-card:nth-child(6n + 1) {
    background: linear-gradient(180deg, #fff5f7 0%, #ffffff 100%);
    border-color: #ffd4de;
}
.detail-card:nth-child(6n + 1)::after {
    background: linear-gradient(90deg, #e11d48, #fb7185);
}
.detail-card:nth-child(6n + 1) .detail-label {
    color: #be123c;
}

.detail-card:nth-child(6n + 2) {
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
    border-color: #cfe0ff;
}
.detail-card:nth-child(6n + 2)::after {
    background: linear-gradient(90deg, #2563eb, #60a5fa);
}
.detail-card:nth-child(6n + 2) .detail-label {
    color: #1d4ed8;
}

.detail-card:nth-child(6n + 3) {
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
    border-color: #cdeed8;
}
.detail-card:nth-child(6n + 3)::after {
    background: linear-gradient(90deg, #16a34a, #4ade80);
}
.detail-card:nth-child(6n + 3) .detail-label {
    color: #15803d;
}

.detail-card:nth-child(6n + 4) {
    background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
    border-color: #e8d9ff;
}
.detail-card:nth-child(6n + 4)::after {
    background: linear-gradient(90deg, #7c3aed, #a78bfa);
}
.detail-card:nth-child(6n + 4) .detail-label {
    color: #6d28d9;
}

.detail-card:nth-child(6n + 5) {
    background: linear-gradient(180deg, #fff7ed 0%, #ffffff 100%);
    border-color: #ffd9b3;
}
.detail-card:nth-child(6n + 5)::after {
    background: linear-gradient(90deg, #f97316, #fb923c);
}
.detail-card:nth-child(6n + 5) .detail-label {
    color: #c2410c;
}

.detail-card:nth-child(6n + 6) {
    background: linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%);
    border-color: #c9f3ea;
}
.detail-card:nth-child(6n + 6)::after {
    background: linear-gradient(90deg, #0f766e, #2dd4bf);
}
.detail-card:nth-child(6n + 6) .detail-label {
    color: #0f766e;
}

/* stagger animation */
.detail-card:nth-child(1) {
    animation-delay: 0.03s;
}
.detail-card:nth-child(2) {
    animation-delay: 0.06s;
}
.detail-card:nth-child(3) {
    animation-delay: 0.09s;
}
.detail-card:nth-child(4) {
    animation-delay: 0.12s;
}
.detail-card:nth-child(5) {
    animation-delay: 0.15s;
}
.detail-card:nth-child(6) {
    animation-delay: 0.18s;
}
.detail-card:nth-child(7) {
    animation-delay: 0.21s;
}
.detail-card:nth-child(8) {
    animation-delay: 0.24s;
}
.detail-card:nth-child(9) {
    animation-delay: 0.27s;
}
.detail-card:nth-child(10) {
    animation-delay: 0.3s;
}
.detail-card:nth-child(11) {
    animation-delay: 0.33s;
}
.detail-card:nth-child(12) {
    animation-delay: 0.36s;
}
.detail-card:nth-child(13) {
    animation-delay: 0.39s;
}
.detail-card:nth-child(14) {
    animation-delay: 0.42s;
}
.detail-card:nth-child(15) {
    animation-delay: 0.45s;
}

.empty-details-box {
    text-align: center;
    padding: 50px 20px;
}

.empty-icon {
    width: 82px;
    height: 82px;
    border-radius: 24px;
    margin: 0 auto 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        rgba(213, 16, 36, 0.12),
        rgba(42, 86, 217, 0.12)
    );
    color: #8f1230;
    font-size: 2.2rem;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.empty-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #0f172a;
}

.empty-subtitle {
    color: #64748b;
    margin-top: 8px;
    line-height: 1.7;
    font-size: 0.95rem;
}

#auditDetailsModal .modal-footer {
    padding: 18px 30px 28px;
    border-top: 1px solid #edf2f7;
    background: #fcfdff;
}

#auditDetailsModal .modal-footer .btn-light {
    min-height: 48px;
    border-radius: 16px;
    font-weight: 800;
    padding: 0 28px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: all 0.25s ease;
}

#auditDetailsModal .modal-footer .btn-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
}

@keyframes auditModalPop {
    0% {
        opacity: 0;
        transform: translateY(20px) scale(0.97);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes auditCardIn {
    0% {
        opacity: 0;
        transform: translateY(18px) scale(0.97);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (max-width: 991.98px) {
    .details-grid {
        grid-template-columns: 1fr;
    }

    #auditDetailsModal .modal-dialog {
        max-width: 95%;
    }
}

@media (max-width: 768px) {
    .history-title {
        font-size: 1.6rem;
    }

    .value-action-box {
        min-width: 145px;
    }

    .event-badge {
        min-width: 100px;
    }

    #auditDetailsModal .modal-header,
    #auditDetailsModal .modal-body,
    #auditDetailsModal .modal-footer {
        padding-left: 18px;
        padding-right: 18px;
    }

    .detail-card {
        padding: 18px;
        border-radius: 20px;
    }

    .detail-value {
        font-size: 1rem;
    }
}
</style>
