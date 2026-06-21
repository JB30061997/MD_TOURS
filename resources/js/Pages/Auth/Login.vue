<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import logo from "@/assets/images/logo_md_tours.png";

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
    <Head title="MD TOURS | Login">
        <link rel="icon" type="image/png" href="/assets/images/favicon-32x32.png" />
    </Head>

    <main class="auth-page">
        <div class="motion-bg" aria-hidden="true">
            <span class="route-line line-a"></span>
            <span class="route-line line-b"></span>
            <span class="route-line line-c"></span>
            <span class="pulse-dot dot-a"></span>
            <span class="pulse-dot dot-b"></span>
            <span class="pulse-dot dot-c"></span>
        </div>

        <header class="auth-head">
            <!-- <Link href="/" class="brand-link">
                <img :src="logo" alt="MD TOURS" />
            </Link> -->

            <Link href="/" class="ghost-link">
                <i class="bx bx-left-arrow-alt"></i>
                Retour
            </Link>
        </header>

        <section class="auth-shell">
            <div class="auth-copy">
                <div class="logo-stage">
                    <img :src="logo" alt="MD TOURS" />
                </div>

                <p class="kicker">MD TOURS PLATFORM</p>
                <h1>Welcome back.</h1>
                <p class="short-copy">Plannings, chauffeurs, clients. One clean cockpit.</p>

                <div class="motion-card">
                    <div class="mini-route">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div>
                        <strong>Live operations</strong>
                        <small>Ready for today's planning</small>
                    </div>
                </div>
            </div>

            <form class="auth-card" @submit.prevent="submit">
                <div>
                    <p class="form-kicker">Connexion</p>
                    <h2>Sign in</h2>
                </div>

                <div v-if="status" class="status-alert">
                    <i class="bx bx-check-circle"></i>
                    {{ status }}
                </div>

                <label class="field">
                    <span>Email</span>
                    <div class="input-wrap">
                        <i class="bx bx-envelope"></i>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="name@mdtours.com"
                            required
                            autocomplete="username"
                            autofocus
                        />
                    </div>
                    <small v-if="form.errors.email">{{ form.errors.email }}</small>
                </label>

                <label class="field">
                    <span>Password</span>
                    <div class="input-wrap">
                        <i class="bx bx-lock-alt"></i>
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            placeholder="Your password"
                            required
                            autocomplete="current-password"
                        />
                        <button type="button" @click="showPassword = !showPassword">
                            <i :class="showPassword ? 'bx bx-show' : 'bx bx-hide'"></i>
                        </button>
                    </div>
                    <small v-if="form.errors.password">{{ form.errors.password }}</small>
                </label>

                <div class="form-row">
                    <label class="check">
                        <input v-model="form.remember" type="checkbox" />
                        <span>Remember</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-link"
                    >
                        Forgot?
                    </Link>
                </div>

                <button class="submit-btn" type="submit" :disabled="form.processing">
                    <i :class="form.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-log-in-circle'"></i>
                    {{ form.processing ? "Signing in..." : "Enter" }}
                </button>

                <p class="switch-line">
                    New here?
                    <Link :href="route('register')">Create account</Link>
                </p>
            </form>
        </section>
    </main>
</template>

<style scoped>
.auth-page {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    padding: 28px;
    display: flex;
    flex-direction: column;
    color: #101827;
    background:
        linear-gradient(120deg, rgba(193, 18, 31, 0.1), transparent 32%),
        linear-gradient(135deg, #f8fafc 0%, #eef2ff 52%, #fff7ed 100%);
}

.motion-bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.9;
}

.motion-bg::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(15, 23, 42, 0.055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(15, 23, 42, 0.055) 1px, transparent 1px);
    background-size: 56px 56px;
    animation: gridMove 18s linear infinite;
}

.route-line {
    position: absolute;
    height: 2px;
    width: 42vw;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, rgba(193, 18, 31, 0.68), rgba(37, 99, 235, 0.45), transparent);
    transform-origin: left center;
    animation: routeSweep 7s ease-in-out infinite;
}

.line-a {
    left: -8vw;
    top: 25%;
    --r: -14deg;
    transform: rotate(var(--r));
}

.line-b {
    right: -14vw;
    top: 58%;
    animation-delay: 1.4s;
    --r: 12deg;
    transform: rotate(var(--r));
}

.line-c {
    left: 18vw;
    bottom: 16%;
    animation-delay: 2.3s;
    --r: -7deg;
    transform: rotate(var(--r));
}

.pulse-dot {
    position: absolute;
    width: 12px;
    height: 12px;
    border-radius: 999px;
    background: #c1121f;
    box-shadow: 0 0 0 10px rgba(193, 18, 31, 0.12);
    animation: pulse 2.2s ease-in-out infinite;
}

.dot-a { left: 18%; top: 27%; }
.dot-b { right: 22%; top: 56%; animation-delay: 0.8s; }
.dot-c { left: 52%; bottom: 18%; animation-delay: 1.4s; }

.auth-head {
    position: relative;
    z-index: 2;
    width: min(1180px, 100%);
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.brand-link {
    height: 62px;
    width: 154px;
    padding: 9px 14px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(16px);
}

.brand-link img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.ghost-link {
    height: 46px;
    padding: 0 16px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #111827;
    background: rgba(255, 255, 255, 0.78);
    text-decoration: none;
    font-weight: 850;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
}

.auth-shell {
    position: relative;
    z-index: 1;
    width: min(1180px, 100%);
    margin: auto;
    display: grid;
    grid-template-columns: 1fr 460px;
    gap: 44px;
    align-items: center;
}

.auth-copy {
    color: #101827;
    animation: riseIn 0.8s ease both;
}

.logo-stage {
    width: 180px;
    height: 90px;
    padding: 14px;
    border-radius: 24px;
    display: grid;
    place-items: center;
    background: #fff;
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.13);
    animation: floatLogo 4.5s ease-in-out infinite;
}

.logo-stage img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.kicker,
.form-kicker {
    margin: 34px 0 10px;
    color: #c1121f;
    font-size: 0.76rem;
    font-weight: 950;
    letter-spacing: 0.16em;
}

.auth-copy h1 {
    margin: 0;
    color: #111827;
    font-size: clamp(4rem, 9vw, 7.1rem);
    line-height: 0.86;
    font-weight: 950;
}

.short-copy {
    max-width: 520px;
    margin: 26px 0 0;
    color: #475569;
    font-size: 1.14rem;
    font-weight: 750;
}

.motion-card {
    width: min(390px, 100%);
    margin-top: 34px;
    padding: 18px;
    border-radius: 22px;
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(255, 255, 255, 0.86);
    border: 1px solid rgba(226, 232, 240, 0.86);
    box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
}

.motion-card small {
    display: block;
    margin-top: 3px;
    color: #64748b;
    font-weight: 700;
}

.mini-route {
    width: 78px;
    height: 48px;
    position: relative;
}

.mini-route span {
    position: absolute;
    height: 7px;
    border-radius: 999px;
    background: #c1121f;
    animation: lane 1.8s ease-in-out infinite;
}

.mini-route span:nth-child(1) { width: 66px; top: 6px; }
.mini-route span:nth-child(2) { width: 48px; top: 21px; animation-delay: 0.16s; }
.mini-route span:nth-child(3) { width: 58px; top: 36px; animation-delay: 0.32s; }

.auth-card {
    padding: 34px;
    border-radius: 28px;
    display: grid;
    gap: 20px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.82);
    box-shadow: 0 32px 90px rgba(15, 23, 42, 0.16);
    backdrop-filter: blur(20px);
    animation: cardIn 0.82s ease 0.08s both;
}

.auth-card h2 {
    margin: 0;
    color: #111827;
    font-size: 2.4rem;
    font-weight: 950;
}

.form-kicker {
    margin: 0 0 4px;
    color: #c1121f;
}

.field {
    display: grid;
    gap: 8px;
}

.field span,
.check span {
    color: #475569;
    font-size: 0.82rem;
    font-weight: 900;
}

.input-wrap {
    height: 58px;
    border: 1px solid #dde4ef;
    border-radius: 18px;
    padding: 0 12px 0 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    transition: 0.2s ease;
}

.input-wrap:focus-within {
    border-color: #c1121f;
    box-shadow: 0 0 0 5px rgba(193, 18, 31, 0.08);
}

.input-wrap > i {
    color: #c1121f;
    font-size: 1.25rem;
}

.input-wrap input {
    min-width: 0;
    flex: 1;
    border: 0;
    outline: 0;
    color: #111827;
    background: transparent;
    font-weight: 800;
}

.input-wrap button {
    width: 38px;
    height: 38px;
    border: 0;
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: #475569;
    background: #f1f5f9;
}

.field small {
    color: #c1121f;
    font-weight: 800;
}

.form-row,
.check {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.check {
    justify-content: flex-start;
}

.text-link,
.switch-line a {
    color: #c1121f;
    font-weight: 950;
    text-decoration: none;
}

.submit-btn {
    height: 58px;
    border: 0;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: #fff;
    background: linear-gradient(135deg, #c1121f, #ef4444);
    font-weight: 950;
    box-shadow: 0 18px 36px rgba(193, 18, 31, 0.28);
}

.submit-btn:disabled {
    opacity: 0.72;
}

.status-alert {
    padding: 12px 14px;
    border-radius: 16px;
    color: #047857;
    background: #ecfdf5;
    font-weight: 850;
}

.switch-line {
    margin: 0;
    color: #64748b;
    text-align: center;
    font-weight: 750;
}

@keyframes gridMove {
    to { background-position: 56px 56px; }
}

@keyframes routeSweep {
    0%, 100% { opacity: 0.34; transform: translateX(0) rotate(var(--r, 0deg)); }
    50% { opacity: 1; transform: translateX(8vw) rotate(var(--r, 0deg)); }
}

@keyframes pulse {
    50% { transform: scale(1.55); opacity: 0.55; }
}

@keyframes riseIn {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: none; }
}

@keyframes cardIn {
    from { opacity: 0; transform: translateX(24px) scale(0.98); }
    to { opacity: 1; transform: none; }
}

@keyframes floatLogo {
    50% { transform: translateY(-10px); }
}

@keyframes lane {
    50% { transform: translateX(12px); opacity: 0.55; }
}

@media (max-width: 980px) {
    .auth-page {
        padding: 18px;
    }

    .auth-shell {
        grid-template-columns: 1fr;
        padding: 34px 0;
    }

    .auth-copy {
        min-height: auto;
        padding: 30px;
    }
}

@media (max-width: 560px) {
    .auth-head {
        align-items: flex-start;
    }

    .brand-link {
        width: 132px;
    }

    .auth-card {
        padding: 22px;
    }

    .auth-copy h1,
    .auth-card h2 {
        font-size: 2.15rem;
    }

    .form-row {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
