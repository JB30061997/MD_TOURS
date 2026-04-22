<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    status: { type: String, default: "" },
});

const form = useForm({});

const resend = () => form.post(route("verification.send"));

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent",
);
</script>

<template>
    <Head title="Email Verification" />

    <div class="verify-page">
        <!-- Background -->
        <div class="verify-bg">
            <div class="bg-orb bg-orb-red"></div>
            <div class="bg-orb bg-orb-blue"></div>
            <div class="bg-grid"></div>
            <div class="bg-overlay"></div>
        </div>

        <div class="verify-wrapper container-fluid px-4 px-lg-5 py-4 py-lg-5">
            <!-- Topbar -->
            <div class="verify-topbar mb-4">
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
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="btn topbar-btn-outline"
                    >
                        <i class="bx bx-log-out me-1"></i>
                        Log Out
                    </Link>
                </div>
            </div>

            <div class="row g-4 align-items-stretch justify-content-center">
                <!-- LEFT -->
                <div class="col-12 col-xl-5 col-lg-6">
                    <div class="verify-form-card h-100">
                        <div class="verify-badge">
                            <i class="bx bx-envelope-open"></i>
                            Vérification email
                        </div>

                        <h1 class="verify-title">
                            Confirmez votre
                            <span class="gradient-text">adresse email</span>
                        </h1>

                        <p class="verify-subtitle">
                            Merci pour votre inscription. Veuillez cliquer sur
                            le lien de vérification envoyé à votre adresse
                            email. Si vous ne l’avez pas reçu, vous pouvez
                            demander un nouvel envoi ci-dessous.
                        </p>

                        <div v-if="verificationLinkSent" class="status-alert">
                            <i class="bx bx-check-circle"></i>
                            <span>
                                A new verification link has been sent to your
                                email address.
                            </span>
                        </div>

                        <form class="verify-form" @submit.prevent="resend">
                            <div class="helper-box">
                                <div class="helper-icon">
                                    <i class="bx bx-mail-send"></i>
                                </div>

                                <div>
                                    <div class="helper-title">
                                        Boîte de réception
                                    </div>
                                    <div class="helper-text">
                                        Vérifiez aussi le dossier spam /
                                        courrier indésirable si le mail
                                        n’apparaît pas.
                                    </div>
                                </div>
                            </div>

                            <div class="action-stack">
                                <button
                                    type="submit"
                                    class="btn verify-submit-btn"
                                    :disabled="form.processing"
                                    :class="{ 'opacity-75': form.processing }"
                                >
                                    <i
                                        v-if="!form.processing"
                                        class="bx bx-send me-2"
                                    ></i>
                                    <i
                                        v-else
                                        class="bx bx-loader-alt bx-spin me-2"
                                    ></i>

                                    <span v-if="!form.processing"
                                        >Resend Verification Email</span
                                    >
                                    <span v-else>Processing...</span>
                                </button>

                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="btn logout-btn"
                                >
                                    <i class="bx bx-power-off me-2"></i>
                                    Log Out
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="col-12 col-xl-5 col-lg-6 d-none d-lg-block">
                    <div class="verify-showcase-card h-100">
                        <div class="showcase-badge">
                            <i class="bx bx-shield-quarter"></i>
                            Compte en attente de validation
                        </div>

                        <h2 class="showcase-title">
                            Activez votre accès à la
                            <span class="gradient-text">plateforme</span>
                        </h2>

                        <p class="showcase-subtitle">
                            La validation de votre email permet de sécuriser
                            votre compte et de confirmer votre identité avant
                            l’accès complet à l’application.
                        </p>

                        <div class="feature-list">
                            <div class="feature-row">
                                <div class="feature-icon red-soft">
                                    <i class="bx bx-envelope"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Email confirmé
                                    </div>
                                    <div class="feature-text">
                                        Vérifiez votre boîte mail pour activer
                                        votre compte.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon blue-soft">
                                    <i class="bx bx-check-shield"></i>
                                </div>
                                <div>
                                    <div class="feature-title">
                                        Sécurité renforcée
                                    </div>
                                    <div class="feature-text">
                                        Une validation essentielle pour protéger
                                        l’accès.
                                    </div>
                                </div>
                            </div>

                            <div class="feature-row">
                                <div class="feature-icon green-soft">
                                    <i class="bx bx-world"></i>
                                </div>
                                <div>
                                    <div class="feature-title">Accès prêt</div>
                                    <div class="feature-text">
                                        Une fois vérifié, vous pourrez utiliser
                                        toute la plateforme.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mini-info-box">
                            <div class="mini-info-title">
                                Étape finale d’activation
                            </div>
                            <div class="mini-info-text">
                                Cliquez sur le lien reçu par email pour
                                finaliser l’activation.
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
.verify-page {
    position: relative;
    min-height: 100vh;
    overflow: hidden;
    background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 55%, #eef2f8 100%);
    color: #111827;
}

.verify-bg {
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

.verify-wrapper {
    position: relative;
    z-index: 2;
}

.verify-topbar {
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

.verify-form-card,
.verify-showcase-card {
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

.verify-badge,
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

.verify-title {
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

.verify-subtitle,
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

.verify-form {
    margin-top: 28px;
}

.helper-box {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: linear-gradient(
        135deg,
        rgba(37, 99, 235, 0.07),
        rgba(255, 255, 255, 1)
    );
    border: 1px solid rgba(37, 99, 235, 0.12);
    border-radius: 20px;
    padding: 18px;
    margin-bottom: 22px;
}

.helper-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.helper-title {
    color: #111827;
    font-weight: 900;
    font-size: 0.95rem;
}

.helper-text {
    color: #6b7280;
    font-size: 0.88rem;
    margin-top: 4px;
    line-height: 1.6;
}

.action-stack {
    display: grid;
    gap: 12px;
}

.verify-submit-btn {
    min-height: 54px;
    border-radius: 16px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
    box-shadow: 0 18px 35px rgba(143, 18, 48, 0.2);
}

.verify-submit-btn:hover {
    color: #fff;
    transform: translateY(-2px);
}

.logout-btn {
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

.logout-btn:hover {
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
    background: linear-gradient(
        135deg,
        rgba(22, 163, 74, 0.08),
        rgba(255, 255, 255, 1)
    );
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
    .verify-title {
        font-size: 2.05rem;
    }

    .showcase-title {
        font-size: 1.7rem;
    }
}

@media (max-width: 991.98px) {
    .verify-topbar {
        padding: 14px;
        border-radius: 20px;
    }

    .verify-form-card,
    .verify-showcase-card {
        padding: 22px;
        border-radius: 24px;
    }

    .verify-title {
        font-size: 1.85rem;
    }
}

@media (max-width: 767.98px) {
    .verify-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .brand-logo {
        width: 48px;
        height: 48px;
        border-radius: 16px;
    }

    .verify-title {
        font-size: 1.65rem;
    }

    .verify-form-card {
        padding: 18px;
    }
}
</style>
