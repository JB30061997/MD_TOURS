<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
    status: { type: String, default: '' },
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <Head title="Forgot Password" />

    <div class="forgot-page">
        <!-- Background -->
        <div class="forgot-bg">
            <div class="bg-orb bg-orb-red"></div>
            <div class="bg-orb bg-orb-blue"></div>
            <div class="bg-grid"></div>
            <div class="bg-overlay"></div>
        </div>

        <div class="forgot-wrapper container-fluid px-4 px-lg-5 py-4 py-lg-5">
            <!-- Topbar -->
            <div class="forgot-topbar mb-4">
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
                    <Link :href="route('login')" class="btn topbar-btn-outline">
                        <i class="bx bx-arrow-back me-1"></i>
                        Retour au login
                    </Link>
                </div>
            </div>

            <div class="row g-4 align-items-stretch justify-content-center">
                <!-- LEFT -->
                <div class="col-12 col-xl-5 col-lg-6">
                    <div class="forgot-form-card h-100">
                        <div class="forgot-badge">
                            <i class="bx bx-mail-send"></i>
                            Récupération de mot de passe
                        </div>

                        <h1 class="forgot-title">
                            Mot de passe
                            <span class="gradient-text">oublié ?</span>
                        </h1>

                        <p class="forgot-subtitle">
                            Entrez votre adresse email enregistrée pour recevoir
                            le lien de réinitialisation et récupérer l’accès à
                            votre compte.
                        </p>

                        <div v-if="status" class="status-alert">
                            <i class="bx bx-check-circle"></i>
                            <span>{{ status }}</span>
                        </div>

                        <form class="forgot-form" @submit.prevent="submit">
                            <div class="form-group-modern">
                                <label for="email" class="form-label-modern">
                                    <i class="bx bx-envelope"></i>
                                    Adresse email
                                </label>

                                <div class="input-icon-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-at"></i>
                                    </span>

                                    <input
                                        id="email"
                                        type="email"
                                        class="form-control input-modern"
                                        placeholder="exemple@mdtours.com"
                                        v-model="form.email"
                                        required
                                        autocomplete="username"
                                        autofocus
                                    />
                                </div>

                                <div v-if="form.errors.email" class="error-text">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="action-stack">
                                <button
                                    type="submit"
                                    class="btn forgot-submit-btn"
                                    :disabled="form.processing"
                                    :class="{ 'opacity-75': form.processing }"
                                >
                                    <i v-if="!form.processing" class="bx bx-send me-2"></i>
                                    <i v-else class="bx bx-loader-alt bx-spin me-2"></i>

                                    <span v-if="!form.processing">Envoyer le lien</span>
                                    <span v-else">Envoi en cours...</span>
                                </button>

                                <Link :href="route('login')" class="btn back-login-btn">
                                    <i class="bx bx-left-arrow-alt me-2"></i>
                                    Back to Login
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="col-12 col-xl-5 col-lg-6 d-none d-lg-block">
                    <div class="forgot-showcase-card h-100">
                        <div class="showcase-badge">
                            <i class="bx bx-shield-quarter"></i>
                            Assistance d’accès
                        </div>

                        <h2 class="showcase-title">
                            Récupérez l’accès à votre
                            <span class="gradient-text">plateforme</span>
                        </h2>

                        <p class="showcase-subtitle">
                            Nous vous envoyons un lien sécurisé pour réinitialiser
                            votre mot de passe et reprendre rapidement la gestion
                            de vos opérations touristiques.
                        </p>

                        <div class="feature-list">
                            <div class="feature-row">
                                <div class="feature-icon red-soft">
                                    <i class="bx bx-envelope-open"></i>
                                </div>
                                <div>
                                    <div class="feature-title">Lien par email</div>
                                    <div class="feature-text">
                                        Recevez une instruction sécurisée directement
                                        dans votre boîte mail.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon blue-soft">
                                    <i class="bx bx-lock-open"></i>
                                </div>
                                <div>
                                    <div class="feature-title">Réinitialisation rapide</div>
                                    <div class="feature-text">
                                        Changez votre mot de passe en quelques
                                        étapes simples.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon green-soft">
                                    <i class="bx bx-check-shield"></i>
                                </div>
                                <div>
                                    <div class="feature-title">Accès sécurisé</div>
                                    <div class="feature-text">
                                        Le processus est protégé pour garantir la
                                        sécurité de votre compte.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mini-info-box">
                            <div class="mini-info-title">Support d’authentification</div>
                            <div class="mini-info-text">
                                Vérifiez bien votre adresse email avant l’envoi du lien.
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
</template>

<style scoped>
.forgot-page {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 55%, #eef2f8 100%);
    color: #111827;
}

.forgot-bg {
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

.forgot-wrapper {
    position: relative;
    z-index: 2;
}

.forgot-topbar {
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

.forgot-form-card,
.forgot-showcase-card {
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

.forgot-badge,
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

.forgot-title {
    margin-top: 22px;
    margin-bottom: 0;
    font-size: 2.35rem;
    line-height: 1.1;
    font-weight: 900;
    color: #111827;
}

.gradient-text {
    background: linear-gradient(135deg, #d51024 0%, #8f1230 45%, #2a56d9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.forgot-subtitle,
.showcase-subtitle {
    margin-top: 16px;
    color: #4b5563;
    font-size: 1rem;
    line-height: 1.8;
}

.status-alert {
    margin-top: 20px;
    border-radius: 16px;
    padding: 14px 16px;
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.08), rgba(255,255,255,1));
    border: 1px solid rgba(22, 163, 74, 0.14);
    color: #166534;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.forgot-form {
    margin-top: 28px;
}

.form-group-modern {
    margin-bottom: 22px;
}

.form-label-modern {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    color: #374151;
    font-weight: 800;
    font-size: 0.92rem;
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

.input-modern {
    min-height: 54px;
    border-radius: 16px;
    border: 1px solid #dbe2ea;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    box-shadow: none;
    padding-left: 48px;
    color: #111827;
    font-weight: 600;
}

.input-modern:focus {
    border-color: rgba(29, 78, 216, 0.35);
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08);
}

.error-text {
    margin-top: 8px;
    color: #dc2626;
    font-size: 0.84rem;
    font-weight: 600;
}

.action-stack {
    display: grid;
    gap: 12px;
}

.forgot-submit-btn {
    min-height: 54px;
    border-radius: 16px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
    box-shadow: 0 18px 35px rgba(143, 18, 48, 0.2);
}

.forgot-submit-btn:hover {
    color: #fff;
    transform: translateY(-2px);
}

.back-login-btn {
    min-height: 52px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid #dbe2ea;
    color: #374151;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.back-login-btn:hover {
    background: #fff;
    color: #be123c;
}

.showcase-title {
    margin-top: 20px;
    font-size: 1.95rem;
    line-height: 1.2;
    font-weight: 900;
    color: #111827;
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
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(255,255,255,1));
    border: 1px solid rgba(37, 99, 235, 0.12);
}

.mini-info-title {
    color: #1d4ed8;
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
    .forgot-title {
        font-size: 2.05rem;
    }

    .showcase-title {
        font-size: 1.7rem;
    }
}

@media (max-width: 991.98px) {
    .forgot-topbar {
        padding: 14px;
        border-radius: 20px;
    }

    .forgot-form-card,
    .forgot-showcase-card {
        padding: 22px;
        border-radius: 24px;
    }

    .forgot-title {
        font-size: 1.85rem;
    }
}

@media (max-width: 767.98px) {
    .forgot-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .brand-logo {
        width: 48px;
        height: 48px;
        border-radius: 16px;
    }

    .forgot-title {
        font-size: 1.65rem;
    }

    .forgot-form-card {
        padding: 18px;
    }
}
</style>