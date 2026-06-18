<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { reactive, watch } from "vue";
import Swal from "sweetalert2";

const page = usePage();
const form = reactive({ reference: "" });

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.error) {
            Swal.fire({
                icon: "error",
                title: "Référence invalide",
                text: flash.error,
                confirmButtonColor: "#4051dc",
            });
        }
    },
    { immediate: true },
);

const submit = () => {
    router.post(route("reservateur.authenticate"), form, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Portail Réservateur" />

    <main class="portal-login">
        <div class="noise"></div>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <section class="login-shell">
            <div class="left-panel">
                <div class="brand">
                    <div class="logo">MD</div>
                    <div>
                        <span>MD TOURS</span>
                        <strong>Travel Management</strong>
                    </div>
                </div>

                <div class="premium-box">
                    <span>Portail Premium</span>
                    <h1>Vos voyages, simplement.</h1>
                    <p>Un accès rapide et sécurisé à vos réservations.</p>

                    <div class="stats">
                        <div>
                            <b>24/7</b>
                            <small>Accès</small>
                        </div>
                        <div>
                            <b>100%</b>
                            <small>Sécurisé</small>
                        </div>
                    </div>
                </div>

                <div class="ring"></div>
            </div>

            <div class="right-panel">
                <div class="login-card">
                    <div class="mobile-brand">
                        <div class="logo">MD</div>
                        <span>MD TOURS</span>
                    </div>

                    <span class="eyebrow">Portail réservé</span>
                    <h2>Connexion Réservateur</h2>
                    <p>Saisissez votre référence pour accéder à votre espace.</p>

                    <form @submit.prevent="submit">
                        <label>
                            <span>Référence Réservateur</span>
                            <div class="input-box">
                                <input
                                    v-model="form.reference"
                                    type="text"
                                    placeholder="RS0928618266"
                                    autocomplete="off"
                                />
                            </div>
                        </label>

                        <button type="submit">
                            <span>Authentification</span>
                            <b>→</b>
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</template>

<style scoped>
.portal-login {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    display: grid;
    place-items: center;
    padding: 34px;
    background:
        radial-gradient(circle at 10% 10%, rgba(64, 81, 220, 0.18), transparent 28%),
        radial-gradient(circle at 90% 20%, rgba(15, 23, 42, 0.18), transparent 32%),
        linear-gradient(135deg, #eef2ff 0%, #f8fafc 48%, #e0f2fe 100%);
}

.noise {
    position: absolute;
    inset: 0;
    opacity: 0.35;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.25) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.25) 1px, transparent 1px);
    background-size: 42px 42px;
    mask-image: radial-gradient(circle, #000, transparent 78%);
}

.blob {
    position: absolute;
    border-radius: 999px;
    filter: blur(30px);
    animation: floatBlob 8s ease-in-out infinite;
}

.blob-1 {
    width: 300px;
    height: 300px;
    left: -90px;
    top: 20%;
    background: rgba(64, 81, 220, 0.24);
}

.blob-2 {
    width: 380px;
    height: 380px;
    right: -130px;
    top: 10%;
    background: rgba(14, 165, 233, 0.2);
    animation-delay: 1.5s;
}

.blob-3 {
    width: 260px;
    height: 260px;
    bottom: -80px;
    left: 45%;
    background: rgba(99, 102, 241, 0.2);
    animation-delay: 3s;
}

.login-shell {
    position: relative;
    z-index: 2;
    width: min(1180px, 100%);
    min-height: 650px;
    display: grid;
    grid-template-columns: 1.12fr 0.88fr;
    overflow: hidden;
    border-radius: 42px;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(255, 255, 255, 0.7);
    box-shadow:
        0 45px 120px rgba(15, 23, 42, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(24px);
    animation: enter 0.8s ease both;
}

.left-panel {
    position: relative;
    overflow: hidden;
    padding: 56px;
    color: white;
    background:
        radial-gradient(circle at 75% 22%, rgba(255, 255, 255, 0.16), transparent 28%),
        linear-gradient(135deg, #17245b 0%, #243c8f 48%, #0f1c48 100%);
}

.left-panel::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(0, 0, 0, 0.12), transparent);
}

.brand {
    position: relative;
    z-index: 4;
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo {
    width: 72px;
    height: 72px;
    border-radius: 24px;
    display: grid;
    place-items: center;
    color: white;
    font-weight: 950;
    background: linear-gradient(135deg, #4051dc, #6d5dfc);
    box-shadow: 0 20px 42px rgba(0, 0, 0, 0.25);
}

.brand span,
.eyebrow,
label span {
    display: block;
    color: #4051dc;
    font-size: 0.74rem;
    font-weight: 950;
    letter-spacing: 0.18em;
    text-transform: uppercase;
}

.brand span {
    color: rgba(255, 255, 255, 0.88);
}

.brand strong {
    display: block;
    margin-top: 5px;
    color: rgba(255, 255, 255, 0.72);
    font-size: 1rem;
}

.premium-box {
    position: relative;
    z-index: 3;
    width: min(540px, 100%);
    margin-top: 92px;
    padding: 40px;
    border-radius: 34px;
    background: rgba(255, 255, 255, 0.13);
    border: 1px solid rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(22px);
    box-shadow: 0 28px 70px rgba(0, 0, 0, 0.22);
    animation: floatCard 5s ease-in-out infinite;
}

.premium-box span {
    display: inline-flex;
    padding: 10px 16px;
    border-radius: 999px;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 950;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.premium-box h1 {
    margin: 30px 0 18px;
    color: #fff;
    font-size: clamp(3rem, 4.8vw, 5.4rem);
    line-height: 0.9;
    letter-spacing: -0.08em;
    font-weight: 950;
    text-shadow: 0 20px 36px rgba(0, 0, 0, 0.22);
}

.premium-box p {
    margin: 0;
    max-width: 390px;
    color: rgba(255, 255, 255, 0.82);
    font-size: 1.05rem;
    line-height: 1.7;
    font-weight: 750;
}

.stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    margin-top: 34px;
}

.stats div {
    padding: 18px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.stats b {
    display: block;
    color: #fff;
    font-size: 1.55rem;
    font-weight: 950;
}

.stats small {
    color: rgba(255, 255, 255, 0.72);
    font-weight: 850;
}

.ring {
    position: absolute;
    right: -115px;
    bottom: -130px;
    width: 400px;
    height: 400px;
    border-radius: 999px;
    border: 48px solid rgba(255, 255, 255, 0.09);
    animation: rotateRing 14s linear infinite;
}

.right-panel {
    display: grid;
    place-items: center;
    padding: 60px 54px;
    background:
        radial-gradient(circle at 100% 0%, rgba(64, 81, 220, 0.07), transparent 30%),
        #fff;
}

.login-card {
    width: 100%;
    max-width: 390px;
    animation: slideUp 0.9s ease both;
}

.mobile-brand {
    display: none;
    align-items: center;
    gap: 14px;
    margin-bottom: 32px;
}

.login-card h2 {
    margin: 14px 0 14px;
    color: #0f172a;
    font-size: clamp(2.35rem, 4vw, 3.45rem);
    line-height: 0.92;
    font-weight: 950;
    letter-spacing: -0.075em;
}

.login-card p {
    margin: 0 0 34px;
    color: #64748b;
    line-height: 1.7;
    font-weight: 750;
}

form {
    display: grid;
    gap: 20px;
}

label span {
    margin-bottom: 11px;
}

.input-box {
    position: relative;
}

.input-box::before {
    content: "⌞";
    position: absolute;
    left: 21px;
    top: 50%;
    transform: translateY(-50%);
    color: #4051dc;
    font-size: 1.15rem;
    font-weight: 950;
}

input {
    width: 100%;
    height: 68px;
    border: 1px solid #dbe2ea;
    border-radius: 24px;
    padding: 0 22px 0 54px;
    color: #0f172a;
    background: #f8fafc;
    font-size: 1rem;
    font-weight: 950;
    outline: 0;
    text-transform: uppercase;
    transition: 0.25s ease;
}

input::placeholder {
    color: #a1aab8;
}

input:focus {
    background: #fff;
    border-color: #4051dc;
    box-shadow:
        0 0 0 6px rgba(64, 81, 220, 0.1),
        0 20px 38px rgba(15, 23, 42, 0.08);
}

button {
    position: relative;
    height: 68px;
    border: 0;
    border-radius: 24px;
    overflow: hidden;
    cursor: pointer;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    background: linear-gradient(135deg, #4051dc, #5360ec 48%, #0ea5e9);
    font-weight: 950;
    box-shadow: 0 24px 48px rgba(64, 81, 220, 0.32);
    transition: 0.25s ease;
}

button::before {
    content: "";
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.28), transparent);
    transition: 0.6s ease;
}

button:hover {
    transform: translateY(-3px);
    box-shadow: 0 32px 60px rgba(64, 81, 220, 0.38);
}

button:hover::before {
    transform: translateX(100%);
}

button span,
button b {
    position: relative;
    z-index: 1;
}

button b {
    font-size: 1.5rem;
}

@keyframes enter {
    from {
        opacity: 0;
        transform: translateY(28px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(22px);
    }
    to {
        opacity: 1;
        transform: none;
    }
}

@keyframes floatBlob {
    0%,
    100% {
        transform: translateY(0) translateX(0);
    }
    50% {
        transform: translateY(-28px) translateX(18px);
    }
}

@keyframes floatCard {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-12px);
    }
}

@keyframes rotateRing {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 980px) {
    .portal-login {
        padding: 16px;
    }

    .login-shell {
        grid-template-columns: 1fr;
        min-height: auto;
        border-radius: 32px;
    }

    .left-panel {
        display: none;
    }

    .right-panel {
        padding: 40px 26px;
    }

    .mobile-brand {
        display: flex;
    }
}

@media (max-width: 520px) {
    .right-panel {
        padding: 34px 22px;
    }

    .login-card h2 {
        font-size: 2.45rem;
    }

    input,
    button {
        height: 62px;
        border-radius: 20px;
    }
}
</style>