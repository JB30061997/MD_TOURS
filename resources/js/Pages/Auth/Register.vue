<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const showPwd = ref(false);
const showPwd2 = ref(false);
const submitting = computed(() => form.processing);

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="register-page">
        <!-- Background -->
        <div class="register-bg">
            <div class="bg-orb bg-orb-red"></div>
            <div class="bg-orb bg-orb-blue"></div>
            <div class="bg-grid"></div>
            <div class="bg-overlay"></div>
        </div>

        <div class="register-wrapper container-fluid px-4 px-lg-5 py-4 py-lg-5">
            <!-- Topbar -->
            <div class="topbar-shell mb-4">
                <div class="register-topbar">
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
                    <div class="register-form-card h-100">
                        <div class="register-badge">
                            <i class="bx bx-user-plus"></i>
                            Création de compte
                        </div>

                        <h1 class="register-title">
                            Rejoignez votre
                            <span class="gradient-text"
                                >plateforme touristique</span
                            >
                        </h1>

                        <p class="register-subtitle">
                            Créez votre compte pour accéder à une interface
                            moderne de gestion des plannings, clients,
                            chauffeurs, guides et services.
                        </p>

                        <form class="register-form" @submit.prevent="submit">
                            <!-- Name -->
                            <div class="form-group-modern">
                                <label
                                    for="inputName"
                                    class="form-label-modern"
                                >
                                    <i class="bx bx-user"></i>
                                    Nom complet
                                </label>

                                <div class="input-icon-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-id-card"></i>
                                    </span>

                                    <input
                                        id="inputName"
                                        type="text"
                                        class="form-control input-modern"
                                        placeholder="Votre nom complet"
                                        v-model="form.name"
                                        required
                                        autocomplete="name"
                                    />
                                </div>

                                <div v-if="form.errors.name" class="error-text">
                                    {{ form.errors.name }}
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group-modern">
                                <label
                                    for="inputEmailAddress"
                                    class="form-label-modern"
                                >
                                    <i class="bx bx-envelope"></i>
                                    Adresse email
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
                                        :type="showPwd ? 'text' : 'password'"
                                        id="inputChoosePassword"
                                        class="form-control input-modern input-password"
                                        placeholder="Choisissez un mot de passe"
                                        v-model="form.password"
                                        required
                                        autocomplete="new-password"
                                    />

                                    <button
                                        type="button"
                                        class="password-toggle-btn"
                                        @click="showPwd = !showPwd"
                                        :aria-label="
                                            showPwd
                                                ? 'Hide password'
                                                : 'Show password'
                                        "
                                    >
                                        <i
                                            :class="
                                                showPwd
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

                            <!-- Confirm Password -->
                            <div class="form-group-modern">
                                <label
                                    for="inputConfirmPassword"
                                    class="form-label-modern"
                                >
                                    <i class="bx bx-check-shield"></i>
                                    Confirmation du mot de passe
                                </label>

                                <div class="input-icon-wrap password-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-lock-open-alt"></i>
                                    </span>

                                    <input
                                        :type="showPwd2 ? 'text' : 'password'"
                                        id="inputConfirmPassword"
                                        class="form-control input-modern input-password"
                                        placeholder="Confirmez votre mot de passe"
                                        v-model="form.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                    />

                                    <button
                                        type="button"
                                        class="password-toggle-btn"
                                        @click="showPwd2 = !showPwd2"
                                        :aria-label="
                                            showPwd2
                                                ? 'Hide password'
                                                : 'Show password'
                                        "
                                    >
                                        <i
                                            :class="
                                                showPwd2
                                                    ? 'bx bx-show'
                                                    : 'bx bx-hide'
                                            "
                                        ></i>
                                    </button>
                                </div>

                                <div
                                    v-if="form.errors.password_confirmation"
                                    class="error-text"
                                >
                                    {{ form.errors.password_confirmation }}
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="terms-box">
                                <label class="terms-label">
                                    <input
                                        type="checkbox"
                                        v-model="form.terms"
                                    />
                                    <span>
                                        J’ai lu et j’accepte les
                                        <strong>conditions générales</strong>
                                    </span>
                                </label>

                                <div
                                    v-if="form.errors.terms"
                                    class="error-text mt-2"
                                >
                                    {{ form.errors.terms }}
                                </div>
                            </div>

                            <!-- Submit -->
                            <button
                                type="submit"
                                class="btn register-submit-btn w-100"
                                :disabled="submitting"
                                :class="{ 'opacity-75': submitting }"
                            >
                                <i
                                    v-if="!submitting"
                                    class="bx bx-user-check me-2"
                                ></i>
                                <i
                                    v-else
                                    class="bx bx-loader-alt bx-spin me-2"
                                ></i>

                                <span v-if="!submitting">Créer mon compte</span>
                                <span v-else>Création en cours...</span>
                            </button>

                            <!-- Bottom -->
                            <div class="signin-row">
                                <span>Vous avez déjà un compte ?</span>
                                <Link
                                    :href="route('login')"
                                    class="signin-link"
                                >
                                    Se connecter
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT SIDE -->
                <div class="col-12 col-xl-5 col-lg-6 d-none d-lg-block">
                    <div class="register-showcase-card h-100">
                        <div class="showcase-badge">
                            <i class="bx bx-map-alt"></i>
                            Solution transport touristique
                        </div>

                        <h2 class="showcase-title">
                            Lancez-vous avec une interface
                            <span class="gradient-text"
                                >élégante et métier</span
                            >
                        </h2>

                        <!-- <p class="showcase-subtitle">
                            Une plateforme conçue pour structurer votre activité
                            touristique, fluidifier la gestion opérationnelle et
                            améliorer votre visibilité globale.
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
                                    <i class="bx bx-buildings"></i>
                                </div>
                                <div class="mini-stat-label">Suppliers</div>
                                <div class="mini-stat-value">54</div>
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
                                        Pilotage des trajets
                                    </div>
                                    <div class="feature-text">
                                        Gérez facilement les départs,
                                        destinations et missions touristiques.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon blue-soft">
                                    <i class="bx bx-car"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Gestion des ressources
                                    </div>
                                    <div class="feature-text">
                                        Organisez vos chauffeurs, guides et
                                        services dans une seule interface.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon green-soft">
                                    <i class="bx bx-bar-chart-alt-2"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Suivi intelligent
                                    </div>
                                    <div class="feature-text">
                                        Analysez les données clés pour mieux
                                        piloter votre activité.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="social-separator">
                            <div class="line"></div>
                            <p class="mb-0 fw-bold">OU INSCRIPTION AVEC</p>
                            <div class="line"></div>
                        </div>

                        <div class="social-buttons">
                            <button
                                type="button"
                                class="btn social-btn"
                                disabled
                            >
                                <img
                                    src="/assets/images/apps/05.png"
                                    width="20"
                                    alt="Google"
                                />
                                <span>Google</span>
                            </button>

                            <button
                                type="button"
                                class="btn social-btn"
                                disabled
                            >
                                <img
                                    src="/assets/images/apps/17.png"
                                    width="20"
                                    alt="Facebook"
                                />
                                <span>Facebook</span>
                            </button>

                            <button
                                type="button"
                                class="btn social-btn"
                                disabled
                            >
                                <img
                                    src="/assets/images/apps/18.png"
                                    width="20"
                                    alt="LinkedIn"
                                />
                                <span>LinkedIn</span>
                            </button>
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
.register-page {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 55%, #eef2f8 100%);
    color: #111827;
}

.register-bg {
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
    width: 430px;
    height: 430px;
    top: -110px;
    left: -110px;
    background: #e11d48;
}

.bg-orb-blue {
    width: 520px;
    height: 520px;
    bottom: -170px;
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

.register-wrapper {
    position: relative;
    z-index: 2;
}

.register-topbar {
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

.register-form-card,
.register-showcase-card {
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

.register-badge,
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

.register-title {
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

.register-subtitle,
.showcase-subtitle {
    margin-top: 16px;
    color: #4b5563;
    font-size: 1rem;
    line-height: 1.8;
}

.register-form {
    margin-top: 28px;
}

.form-group-modern {
    margin-bottom: 20px;
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

.terms-box {
    margin-bottom: 22px;
    background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
    border: 1px solid #edf0f6;
    border-radius: 18px;
    padding: 16px 18px;
}

.terms-label {
    display: inline-flex;
    align-items: flex-start;
    gap: 12px;
    color: #374151;
    font-weight: 700;
    cursor: pointer;
    line-height: 1.6;
}

.terms-label input {
    width: 18px;
    height: 18px;
    margin-top: 3px;
    accent-color: #be123c;
    flex-shrink: 0;
}

.register-submit-btn {
    min-height: 54px;
    border-radius: 16px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
    box-shadow: 0 18px 35px rgba(143, 18, 48, 0.2);
}

.register-submit-btn:hover {
    color: #fff;
    transform: translateY(-2px);
}

.signin-row {
    margin-top: 22px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    color: #6b7280;
    font-weight: 600;
}

.signin-link {
    color: #be123c;
    font-weight: 800;
    text-decoration: none;
}

.signin-link:hover {
    color: #1d4ed8;
}

.showcase-title {
    margin-top: 20px;
    font-size: 1.95rem;
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

.social-separator {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 24px;
}

.social-separator .line {
    height: 1px;
    flex: 1;
    background: var(--bs-border-color, #e5e7eb);
}

.social-separator p {
    color: #6b7280;
    font-size: 0.8rem;
    white-space: nowrap;
}

.social-buttons {
    display: grid;
    gap: 12px;
    margin-top: 18px;
}

.social-btn {
    min-height: 48px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.78);
    border: 1px solid #dbe2ea;
    color: #374151;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.showcase-footer {
    margin-top: 24px;
    color: #6b7280;
    font-size: 0.84rem;
    font-weight: 700;
    text-align: center;
}

@media (max-width: 1199.98px) {
    .register-title {
        font-size: 2.05rem;
    }

    .showcase-title {
        font-size: 1.7rem;
    }
}

@media (max-width: 991.98px) {
    .register-topbar {
        padding: 14px;
        border-radius: 20px;
    }

    .register-form-card,
    .register-showcase-card {
        padding: 22px;
        border-radius: 24px;
    }

    .register-title {
        font-size: 1.85rem;
    }

    .mini-stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .register-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .brand-logo {
        width: 48px;
        height: 48px;
        border-radius: 16px;
    }

    .register-title {
        font-size: 1.65rem;
    }

    .register-form-card {
        padding: 18px;
    }
}

.topbar-shell {
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
}
</style>
