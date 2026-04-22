<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AppShell>
        <!-- <template #header>
            <div class="profile-page-header">
                <div>
                    <div class="profile-header-badge">
                        <i class="bx bx-user-circle"></i>
                        Espace personnel
                    </div>
                    <h1 class="profile-page-title">Mon Profil</h1>
                    <p class="profile-page-subtitle">
                        Gérez vos informations personnelles, votre mot de passe
                        et la sécurité de votre compte dans une interface
                        premium.
                    </p>
                </div>

                <div class="profile-header-side">
                    <div class="profile-side-pill">
                        <i class="bx bx-shield-quarter"></i>
                        Paramètres sécurisés
                    </div>
                </div>
            </div>
        </template> -->

        <div class="profile-wrapper">
            <div
                class="profile-hero card border-0 shadow-lg overflow-hidden mb-4"
            >
                <div class="hero-overlay"></div>

                <div class="card-body p-4 p-lg-5 position-relative">
                    <div class="row g-4 align-items-center">
                        <div class="col-12 col-xl-8">
                            <div class="hero-badge mb-3">
                                <i class="bx bx-cog"></i>
                                Gestion du compte
                            </div>

                            <h2 class="hero-title mb-2">Centre de Profil</h2>

                            <p class="hero-subtitle mb-4">
                                Modifiez votre identité, renforcez la sécurité
                                de votre accès et gardez le contrôle total sur
                                votre compte.
                            </p>

                            <div class="hero-pills">
                                <div class="hero-pill">
                                    <i class="bx bx-id-card"></i>
                                    Informations personnelles
                                </div>
                                <div class="hero-pill">
                                    <i class="bx bx-lock-alt"></i>
                                    Mot de passe
                                </div>
                                <div class="hero-pill">
                                    <i class="bx bx-trash-alt"></i>
                                    Suppression du compte
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="hero-mini-panel">
                                <div class="hero-mini-title">
                                    <i class="bx bx-check-shield"></i>
                                    Sécurité & accès
                                </div>

                                <div class="hero-mini-item">
                                    <span>Profil utilisateur</span>
                                    <strong>Actif</strong>
                                </div>

                                <div class="hero-mini-item">
                                    <span>Données personnelles</span>
                                    <strong>Modifiables</strong>
                                </div>

                                <div class="hero-mini-item">
                                    <span>Protection du compte</span>
                                    <strong>Disponible</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div
                        class="profile-card profile-card-primary card border-0 shadow-sm"
                    >
                        <div class="card-body p-4 p-lg-5">
                            <div class="section-head">
                                <div>
                                    <div class="section-kicker">
                                        Informations
                                    </div>
                                    <div class="section-title">
                                        Informations du profil
                                    </div>
                                    <div class="section-subtitle">
                                        Mettez à jour votre nom et votre adresse
                                        email.
                                    </div>
                                </div>
                                <div class="section-icon red-soft">
                                    <i class="bx bx-user"></i>
                                </div>
                            </div>

                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                class="max-w-none"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="profile-card card border-0 shadow-sm h-100">
                        <div class="card-body p-4 p-lg-5">
                            <div class="section-head">
                                <div>
                                    <div class="section-kicker">Sécurité</div>
                                    <div class="section-title">
                                        Mot de passe
                                    </div>
                                    <div class="section-subtitle">
                                        Renforcez la sécurité de votre compte.
                                    </div>
                                </div>
                                <div class="section-icon blue-soft">
                                    <i class="bx bx-lock-alt"></i>
                                </div>
                            </div>

                            <UpdatePasswordForm class="max-w-none" />
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div
                        class="profile-card danger-card card border-0 shadow-sm h-100"
                    >
                        <div class="card-body p-4 p-lg-5">
                            <div class="section-head">
                                <div>
                                    <div class="section-kicker danger-text">
                                        Zone critique
                                    </div>
                                    <div class="section-title">
                                        Suppression du compte
                                    </div>
                                    <div class="section-subtitle">
                                        Cette action est irréversible. Supprimez
                                        définitivement votre compte si
                                        nécessaire.
                                    </div>
                                </div>
                                <div class="section-icon danger-soft">
                                    <i class="bx bx-error-circle"></i>
                                </div>
                            </div>

                            <DeleteUserForm class="max-w-none" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppShell>
</template>

<style scoped>
.profile-wrapper {
    padding: 24px;
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.04),
            transparent 22%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(37, 99, 235, 0.05),
            transparent 24%
        ),
        #f4f6fb;
    min-height: calc(100vh - 120px);
}

.profile-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}

.profile-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(225, 29, 72, 0.08);
    color: #be123c;
    border: 1px solid rgba(225, 29, 72, 0.12);
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 800;
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.profile-page-title {
    margin: 0;
    color: #111827;
    font-size: 2rem;
    font-weight: 900;
    line-height: 1.1;
}

.profile-page-subtitle {
    margin: 8px 0 0;
    color: #6b7280;
    font-size: 0.98rem;
    max-width: 820px;
}

.profile-side-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #e5e7eb;
    color: #374151;
    border-radius: 999px;
    padding: 10px 14px;
    font-weight: 800;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
}

.profile-hero {
    position: relative;
    border-radius: 28px;
    background:
        radial-gradient(
            circle at 85% 15%,
            rgba(255, 255, 255, 0.22),
            transparent 18%
        ),
        radial-gradient(
            circle at 20% 120%,
            rgba(255, 255, 255, 0.12),
            transparent 30%
        ),
        linear-gradient(135deg, #d51024 0%, #8f1230 46%, #2a56d9 100%);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.04),
        rgba(255, 255, 255, 0.01)
    );
    pointer-events: none;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 800;
    font-size: 0.92rem;
}

.hero-title {
    color: #fff;
    font-size: 2.35rem;
    font-weight: 900;
    letter-spacing: 0.2px;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1rem;
    max-width: 700px;
}

.hero-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.16);
    color: #fff;
    font-weight: 700;
    font-size: 0.92rem;
}

.hero-mini-panel {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 22px;
    padding: 20px;
    backdrop-filter: blur(10px);
}

.hero-mini-title {
    color: #fff;
    font-weight: 900;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.hero-mini-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    color: rgba(255, 255, 255, 0.92);
    background: rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 12px 14px;
    margin-bottom: 10px;
}

.hero-mini-item:last-child {
    margin-bottom: 0;
}

.hero-mini-item strong {
    color: #fff;
    font-size: 0.92rem;
}

.profile-card {
    border-radius: 24px;
    background: #fff;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.profile-card:hover {
    transform: translateY(-3px);
}

.profile-card-primary {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 1) 0%,
        rgba(255, 247, 248, 1) 100%
    );
}

.danger-card {
    background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 1) 0%,
        rgba(255, 245, 245, 1) 100%
    );
    border: 1px solid rgba(239, 68, 68, 0.08);
}

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 26px;
}

.section-kicker {
    color: #6b7280;
    font-weight: 800;
    font-size: 0.88rem;
    margin-bottom: 4px;
}

.section-title {
    color: #111827;
    font-weight: 900;
    font-size: 1.3rem;
    line-height: 1.2;
}

.section-subtitle {
    color: #6b7280;
    font-size: 0.95rem;
    margin-top: 6px;
}

.section-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.55rem;
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

.danger-soft {
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
}

.danger-text {
    color: #dc2626;
}

@media (max-width: 991.98px) {
    .profile-wrapper {
        padding: 16px;
    }

    .hero-title {
        font-size: 1.85rem;
    }

    .section-head {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
