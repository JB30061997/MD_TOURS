<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});
</script>

<template>
    <Head title="Welcome" />

    <div class="welcome-page">
        <!-- Background -->
        <div class="welcome-bg">
            <div class="bg-orb bg-orb-red"></div>
            <div class="bg-orb bg-orb-blue"></div>
            <div class="bg-grid"></div>
            <div class="bg-overlay"></div>
        </div>

        <!-- Top Nav -->
        <header class="landing-topbar">
            <div class="container-fluid px-4 px-lg-5">
                <div class="landing-nav">
                    <div class="brand-wrap">
                        <div class="brand-logo">
                            <span>MD</span>
                        </div>

                        <div>
                            <div class="brand-name">MD TOURS Jaouad</div>
                            <div class="brand-subtitle">
                                transport touristique
                            </div>
                        </div>
                    </div>

                    <div class="nav-actions" v-if="canLogin">
                        <Link
                            :href="route('login')"
                            class="btn btn-light nav-btn-primary"
                        >
                            Se connecter
                        </Link>

                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="btn nav-btn-outline"
                        >
                            S’inscrire
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main -->
        <main class="landing-main">
            <div class="container-fluid px-4 px-lg-5">
                <div class="row align-items-center g-5 min-vh-100 py-5">
                    <!-- Left content -->
                    <div class="col-12 col-xl-7">
                        <div class="hero-badge">
                            <i class="bx bx-shield-quarter"></i>
                            Plateforme intelligente de gestion touristique
                        </div>

                        <h1 class="hero-title">
                            Pilotez votre activité de
                            <span class="hero-gradient-text">
                                transport touristique
                            </span>
                            avec élégance et efficacité
                        </h1>

                        <p class="hero-description">
                            Centralisez vos plannings, chauffeurs, guides,
                            fournisseurs, services et clients dans une interface
                            moderne, rapide et pensée pour les besoins réels du
                            terrain.
                        </p>

                        <div class="hero-pills">
                            <div class="hero-pill">
                                <i class="bx bx-calendar-check"></i>
                                Gestion des plannings
                            </div>

                            <div class="hero-pill">
                                <i class="bx bx-group"></i>
                                Suivi des clients
                            </div>

                            <div class="hero-pill">
                                <i class="bx bx-car"></i>
                                Chauffeurs & véhicules
                            </div>
                        </div>

                        <div v-if="canLogin" class="hero-actions">
                            <Link
                                :href="route('login')"
                                class="btn hero-btn-primary"
                            >
                                <i class="bx bx-log-in-circle me-2"></i>
                                Accéder à la plateforme
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="btn hero-btn-secondary"
                            >
                                <i class="bx bx-user-plus me-2"></i>
                                Créer un compte
                            </Link>
                        </div>

                        <div class="hero-meta">
                            <div class="meta-item">
                                <span class="meta-label">Technologie</span>
                                <strong>Laravel v{{ laravelVersion }}</strong>
                            </div>

                            <div class="meta-item">
                                <span class="meta-label">Environnement</span>
                                <strong>PHP v{{ phpVersion }}</strong>
                            </div>

                            <div class="meta-item">
                                <span class="meta-label">Expérience</span>
                                <strong>Inertia + Vue</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Right content -->
                    <div class="col-12 col-xl-5">
                        <div class="hero-dashboard-card">
                            <div class="dashboard-card-header">
                                <div>
                                    <div class="mini-kicker">
                                        Tableau d’ensemble
                                    </div>
                                    <h3 class="dashboard-card-title">
                                        Vue rapide de l’activité
                                    </h3>
                                </div>

                                <div class="dashboard-icon-box">
                                    <i class="bx bx-line-chart"></i>
                                </div>
                            </div>

                            <div class="stats-grid">
                                <div class="stat-box stat-red">
                                    <div class="stat-icon">
                                        <i class="bx bx-calendar-star"></i>
                                    </div>
                                    <div class="stat-label">Plannings</div>
                                    <div class="stat-value">925</div>
                                    <div class="stat-sub">Suivi centralisé</div>
                                </div>

                                <div class="stat-box stat-blue">
                                    <div class="stat-icon">
                                        <i class="bx bx-group"></i>
                                    </div>
                                    <div class="stat-label">Clients</div>
                                    <div class="stat-value">4174</div>
                                    <div class="stat-sub">Base touristique</div>
                                </div>

                                <div class="stat-box stat-green">
                                    <div class="stat-icon">
                                        <i class="bx bx-wallet"></i>
                                    </div>
                                    <div class="stat-label">Budget</div>
                                    <div class="stat-value">2.5M</div>
                                    <div class="stat-sub">MAD estimé</div>
                                </div>

                                <div class="stat-box stat-purple">
                                    <div class="stat-icon">
                                        <i class="bx bx-user-voice"></i>
                                    </div>
                                    <div class="stat-label">Guides</div>
                                    <div class="stat-value">82</div>
                                    <div class="stat-sub">
                                        Ressources actives
                                    </div>
                                </div>
                            </div>

                            <div class="feature-list">
                                <div class="feature-item">
                                    <div class="feature-item-icon red-soft">
                                        <i class="bx bx-map"></i>
                                    </div>
                                    <div>
                                        <div class="feature-title">
                                            Organisation des trajets
                                        </div>
                                        <div class="feature-text">
                                            Départs, destinations et
                                            affectations en un seul espace.
                                        </div>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-item-icon blue-soft">
                                        <i class="bx bx-buildings"></i>
                                    </div>
                                    <div>
                                        <div class="feature-title">
                                            Gestion des fournisseurs
                                        </div>
                                        <div class="feature-text">
                                            Suivez les coûts, prestations et
                                            collaborations.
                                        </div>
                                    </div>
                                </div>

                                <div class="feature-item">
                                    <div class="feature-item-icon green-soft">
                                        <i class="bx bx-bar-chart-alt-2"></i>
                                    </div>
                                    <div>
                                        <div class="feature-title">
                                            Analyse & pilotage
                                        </div>
                                        <div class="feature-text">
                                            Gardez une vision claire sur votre
                                            activité et vos marges.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="canLogin && $page.props.auth.user"
                                class="direct-access-box"
                            >
                                <div>
                                    <div class="direct-access-title">
                                        Vous êtes déjà connecté
                                    </div>
                                    <div class="direct-access-text">
                                        Accédez directement à votre dashboard.
                                    </div>
                                </div>

                                <Link
                                    :href="route('dashboard')"
                                    class="btn direct-access-btn"
                                >
                                    Dashboard
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom cards -->
                <!-- <div class="row g-4 landing-bottom-section">
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="landing-info-card">
                            <div class="info-icon red-soft">
                                <i class="bx bx-check-shield"></i>
                            </div>
                            <h4 class="info-title">Sécurisé</h4>
                            <p class="info-text">
                                Authentification fiable et gestion sécurisée des
                                accès utilisateurs.
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="landing-info-card">
                            <div class="info-icon blue-soft">
                                <i class="bx bx-tachometer"></i>
                            </div>
                            <h4 class="info-title">Rapide</h4>
                            <p class="info-text">
                                Interface fluide et moderne pour naviguer
                                rapidement entre vos opérations.
                            </p>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="landing-info-card">
                            <div class="info-icon green-soft">
                                <i class="bx bx-customize"></i>
                            </div>
                            <h4 class="info-title">Évolutif</h4>
                            <p class="info-text">
                                Une base solide pour ajouter de nouvelles
                                fonctionnalités selon vos besoins métier.
                            </p>
                        </div>
                    </div>
                </div> -->

                <footer class="landing-footer">
                    © {{ new Date().getFullYear() }} MD TOURS • Plateforme de
                    gestion transport touristique
                </footer>
            </div>
        </main>
    </div>
</template>

<style scoped>
.welcome-page {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 55%, #eef2f8 100%);
    color: #111827;
}

.welcome-bg {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.bg-orb {
    position: absolute;
    border-radius: 999px;
    filter: blur(80px);
    opacity: 0.22;
}

.bg-orb-red {
    width: 460px;
    height: 460px;
    top: -120px;
    left: -120px;
    background: #e11d48;
}

.bg-orb-blue {
    width: 520px;
    height: 520px;
    bottom: -180px;
    right: -140px;
    background: #2563eb;
}

.bg-grid {
    position: absolute;
    inset: 0;
    opacity: 0.05;
    background-image:
        linear-gradient(to right, #0f172a 1px, transparent 1px),
        linear-gradient(to bottom, #0f172a 1px, transparent 1px);
    background-size: 56px 56px;
}

.bg-overlay {
    position: absolute;
    inset: 0;
    background: radial-gradient(
        circle at top center,
        rgba(255, 255, 255, 0.75),
        transparent 42%
    );
}

.landing-topbar {
    position: relative;
    z-index: 2;
    padding-top: 20px;
}

.landing-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 20px;
    border-radius: 24px;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(226, 232, 240, 0.9);
    backdrop-filter: blur(16px);
    box-shadow:
        0 14px 34px rgba(15, 23, 42, 0.06),
        0 4px 12px rgba(15, 23, 42, 0.04);
}

.brand-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
}

.brand-logo {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-weight: 900;
    font-size: 1rem;
    box-shadow: 0 14px 28px rgba(143, 18, 48, 0.18);
}

.brand-name {
    font-size: 1.2rem;
    font-weight: 900;
    color: #111827;
    letter-spacing: 0.5px;
}

.brand-subtitle {
    font-size: 0.87rem;
    color: #6b7280;
    font-weight: 600;
}

.nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.nav-btn-primary {
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 800;
    color: #111827;
    border: none;
    box-shadow: 0 10px 20px rgba(255, 255, 255, 0.25);
}

.nav-btn-outline {
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 800;
    border: 1px solid #dbe2ea;
    background: transparent;
    color: #374151;
}

.nav-btn-outline:hover {
    background: #fff;
    color: #be123c;
}

.landing-main {
    position: relative;
    z-index: 2;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(225, 29, 72, 0.08);
    color: #be123c;
    border: 1px solid rgba(225, 29, 72, 0.12);
    border-radius: 999px;
    padding: 10px 16px;
    font-weight: 800;
    font-size: 0.92rem;
    box-shadow: 0 8px 18px rgba(225, 29, 72, 0.06);
}

.hero-title {
    margin-top: 22px;
    font-size: 3.6rem;
    line-height: 1.08;
    font-weight: 900;
    color: #111827;
    max-width: 820px;
}

.hero-gradient-text {
    background: linear-gradient(135deg, #d51024 0%, #8f1230 42%, #2a56d9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.hero-description {
    margin-top: 22px;
    max-width: 760px;
    color: #4b5563;
    font-size: 1.08rem;
    line-height: 1.9;
}

.hero-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 28px;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid #e5e7eb;
    color: #374151;
    font-weight: 800;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.05);
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 32px;
}

.hero-btn-primary {
    min-height: 52px;
    border-radius: 16px;
    padding: 0 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
    box-shadow: 0 18px 35px rgba(143, 18, 48, 0.2);
}

.hero-btn-primary:hover {
    color: #fff;
    transform: translateY(-2px);
}

.hero-btn-secondary {
    min-height: 52px;
    border-radius: 16px;
    padding: 0 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid #dbe2ea;
    color: #374151;
    font-weight: 800;
}

.hero-btn-secondary:hover {
    background: #fff;
    color: #be123c;
}

.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    margin-top: 32px;
}

.meta-item {
    min-width: 160px;
    background: rgba(255, 255, 255, 0.74);
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 14px 16px;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
}

.meta-label {
    display: block;
    color: #6b7280;
    font-size: 0.82rem;
    margin-bottom: 4px;
    font-weight: 700;
}

.meta-item strong {
    color: #111827;
    font-size: 0.96rem;
    font-weight: 900;
}

.hero-dashboard-card {
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 30px;
    padding: 24px;
    backdrop-filter: blur(16px);
    box-shadow:
        0 24px 48px rgba(15, 23, 42, 0.08),
        0 8px 20px rgba(15, 23, 42, 0.04);
}

.dashboard-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 24px;
}

.mini-kicker {
    color: #be123c;
    font-size: 0.84rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.dashboard-card-title {
    margin: 6px 0 0;
    color: #111827;
    font-weight: 900;
    font-size: 1.4rem;
}

.dashboard-icon-box {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-size: 1.5rem;
    box-shadow: 0 16px 28px rgba(42, 86, 217, 0.18);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 22px;
}

.stat-box {
    border-radius: 22px;
    padding: 18px;
    color: #111827;
    border: 1px solid #edf0f6;
    background: #fff;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
}

.stat-red {
    background: linear-gradient(
        180deg,
        rgba(255, 245, 247, 1),
        rgba(255, 255, 255, 1)
    );
}

.stat-blue {
    background: linear-gradient(
        180deg,
        rgba(243, 247, 255, 1),
        rgba(255, 255, 255, 1)
    );
}

.stat-green {
    background: linear-gradient(
        180deg,
        rgba(240, 253, 247, 1),
        rgba(255, 255, 255, 1)
    );
}

.stat-purple {
    background: linear-gradient(
        180deg,
        rgba(248, 245, 255, 1),
        rgba(255, 255, 255, 1)
    );
}

.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #111827;
    color: #fff;
    margin-bottom: 12px;
    font-size: 1.25rem;
    box-shadow: 0 12px 20px rgba(17, 24, 39, 0.16);
}

.stat-label {
    color: #6b7280;
    font-weight: 700;
    font-size: 0.92rem;
}

.stat-value {
    font-size: 2rem;
    line-height: 1.05;
    font-weight: 900;
    color: #111827;
    margin-top: 6px;
}

.stat-sub {
    margin-top: 6px;
    color: #9ca3af;
    font-size: 0.85rem;
    font-weight: 600;
}

.feature-list {
    display: grid;
    gap: 14px;
}

.feature-item {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #edf0f6;
    border-radius: 20px;
    padding: 15px 16px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
}

.feature-item-icon,
.info-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.red-soft {
    background: rgba(225, 29, 72, 0.12);
    color: #be123c;
}

.blue-soft {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.green-soft {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.feature-title {
    color: #111827;
    font-weight: 900;
    font-size: 0.95rem;
}

.feature-text {
    color: #6b7280;
    font-size: 0.88rem;
    margin-top: 4px;
    line-height: 1.6;
}

.direct-access-box {
    margin-top: 20px;
    padding: 18px;
    border-radius: 20px;
    background: linear-gradient(
        135deg,
        rgba(37, 99, 235, 0.08),
        rgba(255, 255, 255, 1)
    );
    border: 1px solid rgba(37, 99, 235, 0.12);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
}

.direct-access-title {
    color: #111827;
    font-weight: 900;
    font-size: 0.95rem;
}

.direct-access-text {
    color: #6b7280;
    font-size: 0.85rem;
    margin-top: 3px;
}

.direct-access-btn {
    border-radius: 14px;
    min-height: 46px;
    padding: 0 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #16a34a 0%, #10b981 100%);
    color: #fff;
    font-weight: 800;
    border: none;
    white-space: nowrap;
}

.direct-access-btn:hover {
    color: #fff;
}

.landing-bottom-section {
    margin-top: -20px;
    padding-bottom: 18px;
}

.landing-info-card {
    height: 100%;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 24px;
    padding: 22px;
    backdrop-filter: blur(12px);
    box-shadow:
        0 16px 34px rgba(15, 23, 42, 0.05),
        0 5px 14px rgba(15, 23, 42, 0.03);
}

.info-title {
    margin-top: 16px;
    margin-bottom: 8px;
    color: #111827;
    font-weight: 900;
    font-size: 1.1rem;
}

.info-text {
    color: #6b7280;
    font-size: 0.94rem;
    line-height: 1.75;
    margin: 0;
}

.landing-footer {
    text-align: center;
    color: #6b7280;
    font-size: 0.88rem;
    padding: 22px 0 34px;
    font-weight: 600;
}

@media (max-width: 1199.98px) {
    .hero-title {
        font-size: 3rem;
    }
}

@media (max-width: 991.98px) {
    .landing-nav {
        flex-direction: column;
        align-items: flex-start;
    }

    .nav-actions {
        width: 100%;
    }

    .hero-title {
        font-size: 2.45rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .direct-access-box {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 767.98px) {
    .landing-topbar {
        padding-top: 14px;
    }

    .landing-nav {
        padding: 14px;
        border-radius: 20px;
    }

    .brand-logo {
        width: 48px;
        height: 48px;
        border-radius: 16px;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-description {
        font-size: 1rem;
    }

    .hero-actions,
    .hero-meta,
    .hero-pills {
        flex-direction: column;
        align-items: stretch;
    }

    .hero-dashboard-card {
        padding: 18px;
        border-radius: 24px;
    }

    .feature-item {
        padding: 14px;
    }
}
</style>
