<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

// logout
const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route("logout"));
};

const page = usePage();

const user = computed(() => page.props?.auth?.user || {});
const userName = computed(() => user.value?.name || "Utilisateur");
const userEmail = computed(() => user.value?.email || "compte@exemple.com");

const userInitials = computed(() => {
    const name = userName.value?.trim() || "U";
    const parts = name.split(" ").filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
});
</script>

<template>
    <header class="top-header">
        <nav class="navbar navbar-expand align-items-center gap-1 topbar-shell">
            <!-- LEFT -->
            <div class="topbar-left d-flex align-items-center gap-3">
                <div class="btn-toggle modern-toggle">
                    <a href="javascript:;">
                        <i class="material-icons-outlined">menu</i>
                    </a>
                </div>

                <!-- <div class="topbar-title-wrap d-none d-xl-flex">
                    <div class="topbar-kicker">Pilotage intelligent</div>
                    <div class="topbar-page-name">Espace Administration</div>
                </div> -->
            </div>

            <!-- SEARCH -->
            <div class="search-bar flex-grow-1">
                <div class="position-relative search-modern-wrap">
                    <input
                        class="form-control search-control-modern d-lg-block d-none"
                        type="text"
                        placeholder="Rechercher un planning, client, fournisseur..."
                    />

                    <span
                        class="material-icons-outlined position-absolute search-icon-start d-lg-block d-none"
                    >
                        search
                    </span>

                    <span
                        class="material-icons-outlined position-absolute search-icon-end d-lg-block d-none"
                    >
                        tune
                    </span>

                    <!-- MOBILE SEARCH POPUP -->
                    <div class="search-popup p-3">
                        <div class="card search-popup-card overflow-hidden">
                            <div
                                class="card-header d-lg-none border-0 bg-transparent pb-0"
                            >
                                <div class="position-relative">
                                    <input
                                        class="form-control mobile-search-control-modern"
                                        type="text"
                                        placeholder="Rechercher..."
                                    />
                                    <span
                                        class="material-icons-outlined position-absolute mobile-search-icon-start"
                                    >
                                        search
                                    </span>
                                    <span
                                        class="material-icons-outlined position-absolute mobile-search-icon-end mobile-search-close"
                                    >
                                        close
                                    </span>
                                </div>
                            </div>

                            <div class="card-body search-content pt-3">
                                <p class="search-title">Accès rapides</p>

                                <div
                                    class="d-flex align-items-start flex-wrap gap-2 keywords-wrapper"
                                >
                                    <a href="#" class="keywords-chip">
                                        <span>Dashboard</span>
                                        <i class="material-icons-outlined fs-6"
                                            >search</i
                                        >
                                    </a>

                                    <a href="#" class="keywords-chip">
                                        <span>Plannings</span>
                                        <i class="material-icons-outlined fs-6"
                                            >search</i
                                        >
                                    </a>

                                    <a href="#" class="keywords-chip">
                                        <span>Clients</span>
                                        <i class="material-icons-outlined fs-6"
                                            >search</i
                                        >
                                    </a>
                                </div>

                                <hr />

                                <p class="search-title">Suggestions</p>

                                <div
                                    class="search-list d-flex flex-column gap-2"
                                >
                                    <div class="search-list-item">
                                        <div
                                            class="list-icon gradient-red-soft"
                                        >
                                            <i
                                                class="material-icons-outlined fs-5"
                                                >event_note</i
                                            >
                                        </div>
                                        <div>
                                            <h5 class="mb-0 search-list-title">
                                                Gestion des plannings
                                            </h5>
                                            <p class="mb-0 search-list-sub">
                                                Suivi des trajets et
                                                affectations
                                            </p>
                                        </div>
                                    </div>

                                    <div class="search-list-item">
                                        <div
                                            class="list-icon gradient-blue-soft"
                                        >
                                            <i
                                                class="material-icons-outlined fs-5"
                                                >groups</i
                                            >
                                        </div>
                                        <div>
                                            <h5 class="mb-0 search-list-title">
                                                Base clients
                                            </h5>
                                            <p class="mb-0 search-list-sub">
                                                Recherche rapide par dossier
                                            </p>
                                        </div>
                                    </div>

                                    <div class="search-list-item">
                                        <div
                                            class="list-icon gradient-purple-soft"
                                        >
                                            <i
                                                class="material-icons-outlined fs-5"
                                                >analytics</i
                                            >
                                        </div>
                                        <div>
                                            <h5 class="mb-0 search-list-title">
                                                Rapports & statistiques
                                            </h5>
                                            <p class="mb-0 search-list-sub">
                                                Vue globale des performances
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="card-footer text-center bg-transparent border-0 pt-0"
                            >
                                <a href="#" class="btn search-footer-btn w-100">
                                    Voir tous les résultats
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT -->
            <ul class="navbar-nav gap-2 nav-right-links align-items-center">
                <li class="nav-item d-lg-none mobile-search-btn">
                    <a class="nav-link action-circle-btn" href="javascript:;">
                        <i class="material-icons-outlined">search</i>
                    </a>
                </li>

                <!-- Quick action -->
                <!-- <li class="nav-item d-none d-md-block">
                    <a href="javascript:;" class="top-action-pill">
                        <i class="material-icons-outlined">bolt</i>
                        Actions rapides
                    </a>
                </li> -->

                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative action-circle-btn"
                        data-bs-auto-close="outside"
                        data-bs-toggle="dropdown"
                        href="javascript:;"
                    >
                        <i class="material-icons-outlined">notifications</i>
                        <span class="badge-notify modern-badge">5</span>
                    </a>

                    <div
                        class="dropdown-menu dropdown-notify dropdown-menu-end shadow modern-dropdown"
                    >
                        <div class="dropdown-header-modern">
                            <div>
                                <h5 class="notify-title-main mb-0">
                                    Notifications
                                </h5>
                                <p class="notify-subtitle mb-0">
                                    Suivi en temps réel
                                </p>
                            </div>

                            <div class="dropdown">
                                <button
                                    class="btn option-btn"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                >
                                    <span class="material-icons-outlined"
                                        >more_horiz</span
                                    >
                                </button>

                                <div
                                    class="dropdown-menu dropdown-option dropdown-menu-end shadow modern-subdropdown"
                                >
                                    <a
                                        class="dropdown-item modern-item"
                                        href="javascript:;"
                                    >
                                        <i class="material-icons-outlined fs-6"
                                            >inventory_2</i
                                        >
                                        Archive All
                                    </a>

                                    <a
                                        class="dropdown-item modern-item"
                                        href="javascript:;"
                                    >
                                        <i class="material-icons-outlined fs-6"
                                            >done_all</i
                                        >
                                        Mark all as read
                                    </a>

                                    <a
                                        class="dropdown-item modern-item"
                                        href="javascript:;"
                                    >
                                        <i class="material-icons-outlined fs-6"
                                            >leaderboard</i
                                        >
                                        Reports
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="notify-list modern-notify-list">
                            <a
                                class="dropdown-item notify-item-modern"
                                href="javascript:;"
                            >
                                <div class="notify-avatar gradient-red">PL</div>
                                <div class="notify-content">
                                    <h6 class="notify-item-title">
                                        Nouveau planning validé
                                    </h6>
                                    <p class="notify-item-desc">
                                        Un planning a été ajouté avec succès
                                        aujourd’hui.
                                    </p>
                                    <span class="notify-item-time"
                                        >Il y a 5 min</span
                                    >
                                </div>
                            </a>

                            <a
                                class="dropdown-item notify-item-modern"
                                href="javascript:;"
                            >
                                <div class="notify-avatar gradient-blue">
                                    CL
                                </div>
                                <div class="notify-content">
                                    <h6 class="notify-item-title">
                                        Nouveau client affecté
                                    </h6>
                                    <p class="notify-item-desc">
                                        Un client vient d’être lié à un planning
                                        actif.
                                    </p>
                                    <span class="notify-item-time"
                                        >Aujourd’hui</span
                                    >
                                </div>
                            </a>

                            <a
                                class="dropdown-item notify-item-modern"
                                href="javascript:;"
                            >
                                <div class="notify-avatar gradient-purple">
                                    RP
                                </div>
                                <div class="notify-content">
                                    <h6 class="notify-item-title">
                                        Rapport disponible
                                    </h6>
                                    <p class="notify-item-desc">
                                        Les statistiques de la période ont été
                                        générées.
                                    </p>
                                    <span class="notify-item-time">Hier</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

                <!-- User -->
                <li class="nav-item dropdown">
                    <a
                        href="javascript:;"
                        class="dropdown-toggle dropdown-toggle-nocaret user-trigger"
                        data-bs-toggle="dropdown"
                    >
                        <div class="user-avatar-modern">
                            <span>{{ userInitials }}</span>
                        </div>

                        <div class="user-meta d-none d-xl-flex">
                            <div class="user-meta-name">{{ userName }}</div>
                            <div class="user-meta-role">Administrateur</div>
                        </div>

                        <i
                            class="material-icons-outlined user-arrow d-none d-xl-inline-flex"
                        >
                            expand_more
                        </i>
                    </a>

                    <div
                        class="dropdown-menu dropdown-user dropdown-menu-end shadow modern-dropdown user-dropdown-modern"
                    >
                        <div class="user-dropdown-head">
                            <div class="user-dropdown-avatar">
                                {{ userInitials }}
                            </div>

                            <div>
                                <h5 class="user-name mb-1 fw-bold">
                                    {{ userName }}
                                </h5>
                                <p class="user-email mb-0">
                                    {{ userEmail }}
                                </p>
                            </div>
                        </div>

                        <hr class="dropdown-divider" />

                        <Link
                            class="dropdown-item modern-item"
                            :href="route('profile.edit')"
                        >
                            <i class="material-icons-outlined"
                                >person_outline</i
                            >
                            Profile
                        </Link>

                        <a
                            class="dropdown-item modern-item"
                            href="javascript:;"
                        >
                            <i class="material-icons-outlined">settings</i>
                            Paramètres
                        </a>

                        <Link
                            class="dropdown-item modern-item"
                            :href="route('dashboard')"
                        >
                            <i class="material-icons-outlined">dashboard</i>
                            Dashboard
                        </Link>

                        <!-- Historique -->
                        <Link
                            class="dropdown-item modern-item"
                            :href="route('historique.index')"
                        >
                            <i class="material-icons-outlined">history</i>
                            Historique
                        </Link>

                        <hr class="dropdown-divider" />

                        <button
                            type="button"
                            class="dropdown-item modern-item logout-item"
                            @click="logout"
                            :disabled="logoutForm.processing"
                        >
                            <i class="material-icons-outlined"
                                >power_settings_new</i
                            >
                            Logout
                        </button>
                    </div>
                </li>
            </ul>
        </nav>
    </header>
</template>

<style scoped>
.top-header {
    position: sticky;
    top: 0;
    z-index: 1030;
    padding: 14px 18px 0 18px;
    background: transparent;
}

.topbar-shell {
    min-height: 78px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 0px;
    padding: 14px 18px;
    backdrop-filter: blur(16px);
    box-shadow:
        0 12px 30px rgba(15, 23, 42, 0.06),
        0 3px 10px rgba(15, 23, 42, 0.03);
}

.topbar-left {
    min-width: fit-content;
}

.modern-toggle a {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e5e7eb;
    color: #111827;
    transition: all 0.2s ease;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.modern-toggle a:hover {
    transform: translateY(-2px);
    color: #be123c;
    border-color: rgba(225, 29, 72, 0.18);
}

.topbar-title-wrap {
    flex-direction: column;
    line-height: 1.1;
}

.topbar-kicker {
    color: #be123c;
    font-weight: 800;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.topbar-page-name {
    color: #111827;
    font-weight: 900;
    font-size: 1rem;
}

.search-modern-wrap {
    position: relative;
}

.search-control-modern {
    height: 52px;
    border-radius: 18px;
    border: 1px solid #dbe2ea;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    box-shadow: none;
    padding-left: 52px;
    padding-right: 48px;
    color: #111827;
    font-weight: 600;
}

.search-control-modern:focus {
    border-color: rgba(29, 78, 216, 0.35);
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08);
}

.search-icon-start {
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 22px;
}

.search-icon-end {
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 21px;
}

.mobile-search-control-modern {
    height: 50px;
    border-radius: 16px;
    border: 1px solid #dbe2ea;
    padding-left: 48px;
    padding-right: 44px;
    box-shadow: none;
}

.mobile-search-icon-start {
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.mobile-search-icon-end {
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}

.action-circle-btn {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e5e7eb;
    color: #111827 !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
    transition: all 0.2s ease;
}

.action-circle-btn:hover {
    transform: translateY(-2px);
    color: #be123c !important;
}

.top-action-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 48px;
    padding: 0 16px;
    border-radius: 16px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    text-decoration: none;
    font-weight: 800;
    box-shadow: 0 14px 28px rgba(143, 18, 48, 0.18);
    transition: transform 0.2s ease;
}

.top-action-pill:hover {
    transform: translateY(-2px);
    color: #fff;
}

.modern-badge {
    position: absolute;
    top: 2px;
    right: 1px;
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    padding: 0 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: 2px solid #fff;
    box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
}

.user-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px;
    padding-right: 10px;
    border-radius: 18px;
    text-decoration: none;
    background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.user-avatar-modern {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    font-weight: 900;
    font-size: 0.95rem;
    box-shadow: 0 12px 24px rgba(42, 86, 217, 0.18);
}

.user-meta {
    flex-direction: column;
    line-height: 1.15;
}

.user-meta-name {
    color: #111827;
    font-weight: 900;
    font-size: 0.92rem;
}

.user-meta-role {
    color: #6b7280;
    font-size: 0.8rem;
    font-weight: 700;
}

.user-arrow {
    color: #6b7280;
    font-size: 18px;
}

.modern-dropdown {
    border: 1px solid #edf0f6;
    border-radius: 20px;
    padding: 10px;
    min-width: 320px;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(12px);
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.12);
}

.dropdown-header-modern {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 8px 8px 12px 8px;
    border-bottom: 1px solid #edf0f6;
}

.notify-title-main {
    color: #111827;
    font-weight: 900;
    font-size: 1rem;
}

.notify-subtitle {
    color: #6b7280;
    font-size: 0.84rem;
}

.option-btn {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: none;
}

.modern-subdropdown {
    border: 1px solid #edf0f6;
    border-radius: 16px;
    padding: 8px;
    min-width: 220px;
}

.modern-item {
    border-radius: 14px;
    min-height: 46px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #374151;
    font-weight: 700;
    transition: all 0.2s ease;
}

.modern-item:hover {
    background: #f8fafc;
    color: #be123c;
}

.modern-notify-list {
    padding-top: 8px;
}

.notify-item-modern {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-radius: 16px;
    padding: 12px;
    transition: all 0.2s ease;
}

.notify-item-modern:hover {
    background: #f8fafc;
}

.notify-avatar {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 900;
    flex-shrink: 0;
}

.gradient-red {
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
}

.gradient-blue {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.gradient-purple {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
}

.gradient-red-soft {
    background: rgba(225, 29, 72, 0.12);
    color: #be123c;
}

.gradient-blue-soft {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.gradient-purple-soft {
    background: rgba(124, 58, 237, 0.12);
    color: #6d28d9;
}

.notify-content {
    min-width: 0;
}

.notify-item-title {
    margin: 0 0 4px;
    color: #111827;
    font-weight: 900;
    font-size: 0.92rem;
}

.notify-item-desc {
    margin: 0;
    color: #6b7280;
    font-size: 0.84rem;
    line-height: 1.45;
}

.notify-item-time {
    display: inline-block;
    margin-top: 6px;
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 700;
}

.user-dropdown-modern {
    min-width: 290px;
}

.user-dropdown-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 8px;
}

.user-dropdown-avatar {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    font-weight: 900;
    font-size: 1.05rem;
    box-shadow: 0 12px 24px rgba(42, 86, 217, 0.18);
}

.user-email {
    color: #6b7280;
    font-size: 0.84rem;
    word-break: break-word;
}

.logout-item {
    width: 100%;
    background: transparent;
    border: 0;
    text-align: left;
}

.search-popup-card {
    border-radius: 22px;
    border: 1px solid #edf0f6;
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.12);
}

.search-title {
    color: #111827;
    font-weight: 900;
    font-size: 0.95rem;
    margin-bottom: 12px;
}

.keywords-wrapper {
    margin-bottom: 6px;
}

.keywords-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    color: #374151;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.85rem;
}

.search-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 16px;
    padding: 10px 12px;
    transition: all 0.2s ease;
}

.search-list-item:hover {
    background: #f8fafc;
}

.list-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.search-list-title {
    color: #111827;
    font-size: 0.92rem;
    font-weight: 900;
}

.search-list-sub {
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: 2px;
}

.search-footer-btn {
    min-height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
}

.search-footer-btn:hover {
    color: #fff;
    opacity: 0.95;
}

@media (max-width: 1199.98px) {
    .top-header {
        padding: 12px 12px 0 12px;
    }

    .topbar-shell {
        padding: 0px 0px;
        border-radius: 10px;
    }
}

@media (max-width: 991.98px) {
    .modern-dropdown {
        min-width: 290px;
    }

    .search-control-modern {
        display: none !important;
    }
}

@media (max-width: 767.98px) {
    .topbar-shell {
        min-height: 70px;
    }

    .modern-toggle a,
    .action-circle-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
    }

    .user-trigger {
        padding-right: 6px;
    }

    .user-avatar-modern {
        width: 42px;
        height: 42px;
        border-radius: 14px;
    }
}
</style>
