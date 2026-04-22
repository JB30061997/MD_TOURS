<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = useForm({
    password: '',
})

const showPassword = ref(false)

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    })
}
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <div class="confirm-page">
            <!-- Background -->
            <div class="confirm-bg">
                <div class="bg-orb bg-orb-red"></div>
                <div class="bg-orb bg-orb-blue"></div>
                <div class="bg-grid"></div>
                <div class="bg-overlay"></div>
            </div>

            <div class="confirm-wrapper container-fluid px-4 px-lg-5 py-4 py-lg-5">
                <!-- Topbar -->
                <div class="confirm-topbar mb-4">
                    <Link href="/" class="brand-wrap text-decoration-none">
                        <div class="brand-logo">
                            <span>MD</span>
                        </div>

                        <div>
                            <div class="brand-name">MD TOURS</div>
                            <div class="brand-subtitle">transport touristique</div>
                        </div>
                    </Link>

                    <div class="topbar-actions">
                        <Link href="/" class="btn topbar-btn-outline">
                            <i class="bx bx-arrow-back me-1"></i>
                            Retour
                        </Link>
                    </div>
                </div>

                <div class="row g-4 align-items-stretch justify-content-center">
                    <!-- LEFT -->
                    <div class="col-12 col-xl-5 col-lg-6">
                        <div class="confirm-form-card h-100">
                            <div class="confirm-badge">
                                <i class="bx bx-shield-quarter"></i>
                                Vérification de sécurité
                            </div>

                            <h1 class="confirm-title">
                                Confirmez votre
                                <span class="gradient-text">mot de passe</span>
                            </h1>

                            <p class="confirm-subtitle">
                                Cette zone est sécurisée. Veuillez confirmer votre
                                mot de passe avant de continuer.
                            </p>

                            <div class="security-alert">
                                <i class="bx bx-lock-alt"></i>
                                <span>
                                    Validation requise pour accéder à cette action sensible.
                                </span>
                            </div>

                            <form class="confirm-form" @submit.prevent="submit">
                                <div class="form-group-modern">
                                    <InputLabel
                                        for="password"
                                        value="Mot de passe"
                                        class="form-label-modern"
                                    />

                                    <div class="input-icon-wrap password-wrap">
                                        <span class="input-left-icon">
                                            <i class="bx bx-key"></i>
                                        </span>

                                        <TextInput
                                            id="password"
                                            :type="showPassword ? 'text' : 'password'"
                                            class="input-modern input-password mt-0 block w-full"
                                            v-model="form.password"
                                            required
                                            autocomplete="current-password"
                                            autofocus
                                            placeholder="Entrez votre mot de passe"
                                        />

                                        <button
                                            type="button"
                                            class="password-toggle-btn"
                                            @click="showPassword = !showPassword"
                                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        >
                                            <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'"></i>
                                        </button>
                                    </div>

                                    <InputError class="mt-2 error-text" :message="form.errors.password" />
                                </div>

                                <div class="actions-row">
                                    <PrimaryButton
                                        class="confirm-submit-btn"
                                        :class="{ 'opacity-75': form.processing }"
                                        :disabled="form.processing"
                                    >
                                        <i v-if="!form.processing" class="bx bx-check-shield me-2"></i>
                                        <i v-else class="bx bx-loader-alt bx-spin me-2"></i>

                                        <span v-if="!form.processing">Confirmer</span>
                                        <span v-else>Vérification...</span>
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- RIGHT -->
                    <div class="col-12 col-xl-5 col-lg-6 d-none d-lg-block">
                        <div class="confirm-showcase-card h-100">
                            <div class="showcase-badge">
                                <i class="bx bx-check-shield"></i>
                                Protection du compte
                            </div>

                            <h2 class="showcase-title">
                                Une couche de sécurité
                                <span class="gradient-text">supplémentaire</span>
                            </h2>

                            <p class="showcase-subtitle">
                                La confirmation du mot de passe permet de protéger
                                les zones critiques de votre application et de
                                sécuriser les actions importantes.
                            </p>

                            <div class="feature-list">
                                <div class="feature-row">
                                    <div class="feature-icon red-soft">
                                        <i class="bx bx-lock"></i>
                                    </div>
                                    <div>
                                        <div class="feature-title">Accès protégé</div>
                                        <div class="feature-text">
                                            Les sections sensibles nécessitent une validation.
                                        </div>
                                    </div>
                                </div>

                                <div class="feature-row">
                                    <div class="feature-icon blue-soft">
                                        <i class="bx bx-user-check"></i>
                                    </div>
                                    <div>
                                        <div class="feature-title">Sécurité renforcée</div>
                                        <div class="feature-text">
                                            Vérifiez votre identité avant toute action importante.
                                        </div>
                                    </div>
                                </div>

                                <div class="feature-row">
                                    <div class="feature-icon green-soft">
                                        <i class="bx bx-shield-alt-2"></i>
                                    </div>
                                    <div>
                                        <div class="feature-title">Confiance & contrôle</div>
                                        <div class="feature-text">
                                            Une meilleure maîtrise des accès dans la plateforme.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mini-info-box">
                                <div class="mini-info-title">Zone sécurisée active</div>
                                <div class="mini-info-text">
                                    Merci de confirmer votre mot de passe pour continuer.
                                </div>
                            </div>

                            <div class="showcase-footer">
                                © {{ new Date().getFullYear() }} MD TOURS
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.confirm-page {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 55%, #eef2f8 100%);
    color: #111827;
}

.confirm-bg {
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
    width: 420px;
    height: 420px;
    top: -110px;
    left: -110px;
    background: #e11d48;
}

.bg-orb-blue {
    width: 500px;
    height: 500px;
    bottom: -180px;
    right: -120px;
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
    background:
        radial-gradient(circle at top center, rgba(255, 255, 255, 0.76), transparent 42%);
}

.confirm-wrapper {
    position: relative;
    z-index: 2;
}

.confirm-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    padding: 14px 18px;
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
    letter-spacing: 0.4px;
}

.brand-subtitle {
    font-size: 0.87rem;
    color: #6b7280;
    font-weight: 600;
}

.topbar-btn-outline {
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 800;
    border: 1px solid #dbe2ea;
    background: rgba(255, 255, 255, 0.8);
    color: #374151;
}

.topbar-btn-outline:hover {
    background: #fff;
    color: #be123c;
}

.confirm-form-card,
.confirm-showcase-card {
    height: 100%;
    border-radius: 30px;
    padding: 30px;
    background: rgba(255, 255, 255, 0.84);
    border: 1px solid rgba(226, 232, 240, 0.9);
    backdrop-filter: blur(16px);
    box-shadow:
        0 24px 48px rgba(15, 23, 42, 0.08),
        0 8px 20px rgba(15, 23, 42, 0.04);
}

.confirm-badge,
.showcase-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(225, 29, 72, 0.08);
    color: #be123c;
    border: 1px solid rgba(225, 29, 72, 0.12);
    border-radius: 999px;
    padding: 10px 15px;
    font-weight: 800;
    font-size: 0.9rem;
}

.confirm-title {
    margin-top: 22px;
    margin-bottom: 0;
    font-size: 2.3rem;
    line-height: 1.1;
    font-weight: 900;
    color: #111827;
}

.gradient-text {
    background: linear-gradient(135deg, #d51024 0%, #8f1230 45%, #2a56d9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.confirm-subtitle,
.showcase-subtitle {
    margin-top: 16px;
    color: #4b5563;
    font-size: 1rem;
    line-height: 1.8;
}

.security-alert {
    margin-top: 22px;
    border-radius: 18px;
    padding: 15px 16px;
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.07), rgba(255,255,255,1));
    border: 1px solid rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.confirm-form {
    margin-top: 28px;
}

.form-group-modern {
    margin-bottom: 22px;
}

:deep(.form-label-modern) {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    color: #374151 !important;
    font-weight: 800 !important;
    font-size: 0.92rem !important;
}

.input-icon-wrap {
    position: relative;
}

.input-left-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 1.1rem;
    z-index: 2;
}

:deep(.input-modern) {
    min-height: 54px;
    border-radius: 16px !important;
    border: 1px solid #dbe2ea !important;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%) !important;
    box-shadow: none !important;
    padding-left: 48px !important;
    color: #111827 !important;
    font-weight: 600 !important;
}

:deep(.input-modern:focus) {
    border-color: rgba(29, 78, 216, 0.35) !important;
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08) !important;
}

.password-wrap :deep(.input-password) {
    padding-right: 54px !important;
}

.password-toggle-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 40px;
    height: 40px;
    border-radius: 12px;
    border: 0;
    background: transparent;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.password-toggle-btn:hover {
    background: rgba(15, 23, 42, 0.04);
    color: #111827;
}

:deep(.error-text) {
    color: #dc2626 !important;
    font-size: 0.84rem !important;
    font-weight: 600 !important;
}

.actions-row {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

:deep(.confirm-submit-btn) {
    min-height: 54px !important;
    border-radius: 16px !important;
    padding: 0 24px !important;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%) !important;
    color: #fff !important;
    font-weight: 800 !important;
    border: none !important;
    box-shadow: 0 18px 35px rgba(143, 18, 48, 0.2) !important;
}

.feature-list {
    display: grid;
    gap: 14px;
    margin-top: 24px;
}

.feature-row {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #edf0f6;
    border-radius: 20px;
    padding: 15px 16px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
}

.feature-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
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

.mini-info-box {
    margin-top: 24px;
    border-radius: 20px;
    padding: 18px;
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.08), rgba(255,255,255,1));
    border: 1px solid rgba(22, 163, 74, 0.12);
}

.mini-info-title {
    color: #166534;
    font-weight: 900;
    font-size: 0.95rem;
}

.mini-info-text {
    color: #4b5563;
    font-size: 0.88rem;
    margin-top: 4px;
    line-height: 1.6;
}

.showcase-footer {
    margin-top: 24px;
    color: #6b7280;
    font-size: 0.84rem;
    font-weight: 700;
    text-align: center;
}

@media (max-width: 1199.98px) {
    .confirm-title {
        font-size: 2rem;
    }
}

@media (max-width: 991.98px) {
    .confirm-topbar {
        padding: 14px;
        border-radius: 20px;
    }

    .confirm-form-card,
    .confirm-showcase-card {
        padding: 22px;
        border-radius: 24px;
    }

    .confirm-title {
        font-size: 1.8rem;
    }
}

@media (max-width: 767.98px) {
    .confirm-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .brand-logo {
        width: 48px;
        height: 48px;
        border-radius: 16px;
    }

    .confirm-title {
        font-size: 1.6rem;
    }

    .confirm-form-card {
        padding: 18px;
    }

    .actions-row {
        justify-content: stretch;
    }

    :deep(.confirm-submit-btn) {
        width: 100% !important;
        justify-content: center !important;
    }
}
</style>