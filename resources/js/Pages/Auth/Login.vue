<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    canResetPassword: { type: Boolean, default: true },
    status: { type: String, default: "" },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="login-page">
        <!-- Background -->
        <div class="login-bg">
            <div class="bg-orb bg-orb-red"></div>
            <div class="bg-orb bg-orb-blue"></div>
            <div class="bg-grid"></div>
            <div class="bg-overlay"></div>
        </div>

        <div class="login-wrapper container-fluid px-4 px-lg-5 py-4 py-lg-5">
            <!-- Top small nav -->
            <div class="topbar-shell mb-4">
                <div class="login-topbar">
                    <Link href="/" class="brand-wrap text-decoration-none">
                        <div class="brand-logo">
                            <span>MD</span>
                        </div>

                        <div>
                            <div class="brand-name">MD TOURS</div>
                            <div class="brand-subtitle">
                                transport touristique
                            </div>
                        </div>
                    </Link>

                    <div class="topbar-actions">
                        <Link href="/" class="btn topbar-btn-outline">
                            <i class="bx bx-arrow-back me-1"></i>
                            Retour
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-stretch justify-content-center">
                <!-- LEFT SIDE -->
                <div class="col-12 col-xl-5 col-lg-6">
                    <div class="login-form-card h-100">
                        <div class="login-badge">
                            <i class="bx bx-shield-quarter"></i>
                            Accès sécurisé
                        </div>

                        <h1 class="login-title">
                            Connexion à votre
                            <span class="gradient-text"
                                >espace professionnel</span
                            >
                        </h1>

                        <p class="login-subtitle">
                            Connectez-vous pour accéder à la gestion des
                            plannings, chauffeurs, guides, clients et services
                            touristiques.
                        </p>

                        <div v-if="status" class="status-alert">
                            <i class="bx bx-check-circle"></i>
                            <span>{{ status }}</span>
                        </div>

                        <form class="login-form" @submit.prevent="submit">
                            <!-- Email -->
                            <div class="form-group-modern">
                                <label
                                    for="inputEmailAddress"
                                    class="form-label-modern"
                                >
                                    <i class="bx bx-envelope"></i>
                                    Email
                                </label>

                                <div class="input-icon-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-at"></i>
                                    </span>

                                    <input
                                        id="inputEmailAddress"
                                        type="email"
                                        class="form-control input-modern"
                                        placeholder="exemple@mdtours.com"
                                        v-model="form.email"
                                        required
                                        autocomplete="username"
                                        autofocus
                                    />
                                </div>

                                <div
                                    v-if="form.errors.email"
                                    class="error-text"
                                >
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group-modern">
                                <label
                                    for="inputChoosePassword"
                                    class="form-label-modern"
                                >
                                    <i class="bx bx-lock-alt"></i>
                                    Mot de passe
                                </label>

                                <div class="input-icon-wrap password-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-key"></i>
                                    </span>

                                    <input
                                        :type="
                                            showPassword ? 'text' : 'password'
                                        "
                                        id="inputChoosePassword"
                                        class="form-control input-modern input-password"
                                        placeholder="Entrez votre mot de passe"
                                        v-model="form.password"
                                        required
                                        autocomplete="current-password"
                                    />

                                    <button
                                        type="button"
                                        class="password-toggle-btn"
                                        @click="showPassword = !showPassword"
                                        :aria-label="
                                            showPassword
                                                ? 'Hide password'
                                                : 'Show password'
                                        "
                                    >
                                        <i
                                            :class="
                                                showPassword
                                                    ? 'bx bx-show'
                                                    : 'bx bx-hide'
                                            "
                                        ></i>
                                    </button>
                                </div>

                                <div
                                    v-if="form.errors.password"
                                    class="error-text"
                                >
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Options -->
                            <div class="login-options">
                                <label class="remember-box">
                                    <input
                                        type="checkbox"
                                        v-model="form.remember"
                                    />
                                    <span>Remember me</span>
                                </label>

                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="forgot-link"
                                >
                                    Mot de passe oublié ?
                                </Link>
                            </div>

                            <!-- Submit -->
                            <button
                                type="submit"
                                class="btn login-submit-btn w-100"
                                :disabled="form.processing"
                                :class="{ 'opacity-75': form.processing }"
                            >
                                <i
                                    v-if="!form.processing"
                                    class="bx bx-log-in-circle me-2"
                                ></i>
                                <i
                                    v-else
                                    class="bx bx-loader-alt bx-spin me-2"
                                ></i>

                                <span v-if="!form.processing"
                                    >Se connecter</span
                                >
                                <span v-else>Connexion en cours...</span>
                            </button>

                            <!-- Bottom -->
                            <div class="register-row">
                                <span>Vous n’avez pas encore de compte ?</span>
                                <Link
                                    :href="route('register')"
                                    class="register-link"
                                >
                                    Créer un compte
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT SIDE -->
                <div class="col-12 col-xl-5 col-lg-6 d-none d-lg-block">
                    <div class="login-showcase-card h-100">
                        <div class="showcase-badge">
                            <i class="bx bx-map-alt"></i>
                            Plateforme transport touristique
                        </div>

                        <h2 class="showcase-title">
                            Organisez vos opérations avec une interface
                            <span class="gradient-text">claire et premium</span>
                        </h2>

                        <!-- <p class="showcase-subtitle">
                            Une solution pensée pour centraliser vos activités,
                            améliorer la visibilité métier et piloter votre
                            activité touristique avec style.
                        </p> -->

                        <div class="mini-stats-grid">
                            <div class="mini-stat-card stat-red">
                                <div class="mini-stat-icon">
                                    <i class="bx bx-calendar-check"></i>
                                </div>
                                <div class="mini-stat-label">Plannings</div>
                                <div class="mini-stat-value">925</div>
                            </div>

                            <div class="mini-stat-card stat-blue">
                                <div class="mini-stat-icon">
                                    <i class="bx bx-group"></i>
                                </div>
                                <div class="mini-stat-label">Clients</div>
                                <div class="mini-stat-value">4174</div>
                            </div>

                            <div class="mini-stat-card stat-green">
                                <div class="mini-stat-icon">
                                    <i class="bx bx-car"></i>
                                </div>
                                <div class="mini-stat-label">Drivers</div>
                                <div class="mini-stat-value">30</div>
                            </div>

                            <div class="mini-stat-card stat-purple">
                                <div class="mini-stat-icon">
                                    <i class="bx bx-user-voice"></i>
                                </div>
                                <div class="mini-stat-label">Guides</div>
                                <div class="mini-stat-value">82</div>
                            </div>
                        </div>

                        <div class="showcase-features">
                            <div class="feature-row">
                                <div class="feature-icon red-soft">
                                    <i class="bx bx-route"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Suivi des trajets
                                    </div>
                                    <div class="feature-text">
                                        Visualisez les départs, destinations et
                                        affectations.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon blue-soft">
                                    <i class="bx bx-wallet-alt"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Gestion des budgets
                                    </div>
                                    <div class="feature-text">
                                        Contrôlez les montants, coûts
                                        fournisseurs et marges.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon green-soft">
                                    <i class="bx bx-bar-chart-alt-2"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Vision globale
                                    </div>
                                    <div class="feature-text">
                                        Accédez à une vue claire sur toute votre
                                        activité.
                                    </div>
                                </div>
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
.login-page {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 55%, #eef2f8 100%);
    color: #111827;
}

.login-bg {
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
    background: radial-gradient(
        circle at top center,
        rgba(255, 255, 255, 0.76),
        transparent 42%
    );
}

.login-wrapper {
    position: relative;
    z-index: 2;
}

.login-topbar {
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

.login-form-card,
.login-showcase-card {
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

.login-badge,
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

.login-title {
    margin-top: 22px;
    margin-bottom: 0;
    font-size: 2.45rem;
    line-height: 1.1;
    font-weight: 900;
    color: #111827;
}

.gradient-text {
    background: linear-gradient(135deg, #d51024 0%, #8f1230 45%, #2a56d9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.login-subtitle,
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
    background: linear-gradient(
        135deg,
        rgba(22, 163, 74, 0.08),
        rgba(255, 255, 255, 1)
    );
    border: 1px solid rgba(22, 163, 74, 0.14);
    color: #166534;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

.login-form {
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

.password-wrap .input-password {
    padding-right: 54px;
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

.error-text {
    margin-top: 8px;
    color: #dc2626;
    font-size: 0.84rem;
    font-weight: 600;
}

.login-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.remember-box {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: #374151;
    font-weight: 700;
    cursor: pointer;
}

.remember-box input {
    width: 18px;
    height: 18px;
    accent-color: #be123c;
}

.forgot-link {
    color: #1d4ed8;
    font-weight: 700;
    text-decoration: none;
}

.forgot-link:hover {
    color: #be123c;
}

.login-submit-btn {
    min-height: 54px;
    border-radius: 16px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
    box-shadow: 0 18px 35px rgba(143, 18, 48, 0.2);
}

.login-submit-btn:hover {
    color: #fff;
    transform: translateY(-2px);
}

.register-row {
    margin-top: 22px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    color: #6b7280;
    font-weight: 600;
}

.register-link {
    color: #be123c;
    font-weight: 800;
    text-decoration: none;
}

.register-link:hover {
    color: #1d4ed8;
}

.showcase-title {
    margin-top: 20px;
    font-size: 2rem;
    line-height: 1.2;
    font-weight: 900;
    color: #111827;
}

.mini-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    margin-top: 24px;
}

.mini-stat-card {
    border-radius: 22px;
    padding: 18px;
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

.mini-stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #111827;
    color: #fff;
    margin-bottom: 12px;
    font-size: 1.2rem;
    box-shadow: 0 12px 20px rgba(17, 24, 39, 0.16);
}

.mini-stat-label {
    color: #6b7280;
    font-weight: 700;
    font-size: 0.88rem;
}

.mini-stat-value {
    margin-top: 6px;
    font-size: 1.8rem;
    line-height: 1.05;
    font-weight: 900;
    color: #111827;
}

.showcase-features {
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

.showcase-footer {
    margin-top: 24px;
    color: #6b7280;
    font-size: 0.84rem;
    font-weight: 700;
    text-align: center;
}

@media (max-width: 1199.98px) {
    .login-title {
        font-size: 2.1rem;
    }

    .showcase-title {
        font-size: 1.7rem;
    }
}

@media (max-width: 991.98px) {
    .login-topbar {
        padding: 14px;
        border-radius: 20px;
    }

    .login-form-card,
    .login-showcase-card {
        padding: 22px;
        border-radius: 24px;
    }

    .login-title {
        font-size: 1.9rem;
    }

    .mini-stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .login-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .brand-logo {
        width: 48px;
        height: 48px;
        border-radius: 16px;
    }

    .login-title {
        font-size: 1.7rem;
    }

    .login-options {
        flex-direction: column;
        align-items: flex-start;
    }

    .login-form-card {
        padding: 18px;
    }
}

.topbar-shell {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}

.login-topbar {
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
</style>
