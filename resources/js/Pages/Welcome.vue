<script setup>
import { Head, Link } from "@inertiajs/vue3";
import logo from "@/assets/images/logo_md_tours.png";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: { type: String, required: true },
    phpVersion: { type: String, required: true },
});
</script>

<template>
    <Head title="MD TOURS">
        <link rel="icon" type="image/png" href="/assets/images/favicon-32x32.png" />
    </Head>

    <main class="welcome-page">
        <div class="motion-bg" aria-hidden="true">
            <span class="route-line line-a"></span>
            <span class="route-line line-b"></span>
            <span class="route-line line-c"></span>
            <span class="pulse-dot dot-a"></span>
            <span class="pulse-dot dot-b"></span>
            <span class="pulse-dot dot-c"></span>
        </div>

        <header class="topbar">
            <!-- <Link href="/" class="brand-link">
                <img :src="logo" alt="MD TOURS" />
            </Link>

            <nav v-if="canLogin" class="nav-actions">
                <Link :href="route('login')" class="nav-login">Login</Link>
                <Link v-if="canRegister" :href="route('register')" class="nav-register">
                    Register
                </Link>
            </nav> -->
        </header>

        <section class="hero">
            <div class="hero-copy">
                <div class="logo-stage">
                    <img :src="logo" alt="MD TOURS" />
                </div>

                <p class="kicker">TOURISM OPERATIONS</p>
                <h1>Move smarter.</h1>
                <p class="short-copy">A clean cockpit for plannings, vehicles, drivers and clients.</p>

                <div v-if="canLogin" class="hero-actions">
                    <Link :href="route('login')" class="primary-action">
                        <i class="bx bx-log-in-circle"></i>
                        Open platform
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="secondary-action">
                        Create account
                    </Link>
                </div>
            </div>

            <div class="visual-panel" aria-label="MD TOURS dashboard preview">
                <div class="panel-head">
                    <div>
                        <span>Today</span>
                        <strong>Live planning</strong>
                    </div>
                    <i class="bx bx-trip"></i>
                </div>

                <div class="route-board">
                    <div class="stop active">
                        <span></span>
                        Marrakech
                    </div>
                    <div class="dash"></div>
                    <div class="stop">
                        <span></span>
                        Airport
                    </div>
                    <div class="dash"></div>
                    <div class="stop">
                        <span></span>
                        Hotel
                    </div>
                </div>

                <div class="cards-grid">
                    <div class="metric-card red">
                        <i class="bx bx-calendar-check"></i>
                        <strong>Plannings</strong>
                        <span>Live</span>
                    </div>
                    <div class="metric-card blue">
                        <i class="bx bx-bus"></i>
                        <strong>Vehicles</strong>
                        <span>Ready</span>
                    </div>
                    <div class="metric-card dark">
                        <i class="bx bx-user-voice"></i>
                        <strong>Drivers</strong>
                        <span>Assigned</span>
                    </div>
                </div>

                <div class="dispatch-row">
                    <div class="vehicle-pill">
                        <i class="bx bx-map-pin"></i>
                        15327 B 33
                    </div>
                    <div class="progress-track">
                        <span></span>
                    </div>
                </div>
            </div>
        </section>
    </main>
</template>

<style scoped>
.welcome-page {
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
    width: 44vw;
    border-radius: 999px;
    background: linear-gradient(90deg, transparent, rgba(193, 18, 31, 0.68), rgba(37, 99, 235, 0.45), transparent);
    animation: routeSweep 7s ease-in-out infinite;
}

.line-a { left: -8vw; top: 25%; --r: -14deg; transform: rotate(var(--r)); }
.line-b { right: -14vw; top: 58%; --r: 12deg; transform: rotate(var(--r)); animation-delay: 1.4s; }
.line-c { left: 18vw; bottom: 16%; --r: -7deg; transform: rotate(var(--r)); animation-delay: 2.3s; }

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

.topbar {
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

.nav-actions {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.nav-login,
.nav-register {
    height: 46px;
    padding: 0 18px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    color: #111827;
    background: rgba(255, 255, 255, 0.78);
    text-decoration: none;
    font-weight: 900;
    box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
}

.nav-register {
    color: #fff;
    background: #c1121f;
}

.hero {
    position: relative;
    z-index: 1;
    width: min(1180px, 100%);
    margin: auto;
    padding: 54px 0;
    display: grid;
    grid-template-columns: 1fr 500px;
    gap: 44px;
    align-items: center;
}

.hero-copy {
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

.kicker {
    margin: 34px 0 10px;
    color: #c1121f;
    font-size: 0.76rem;
    font-weight: 950;
    letter-spacing: 0.16em;
}

h1 {
    margin: 0;
    color: #111827;
    font-size: clamp(4rem, 9vw, 7.6rem);
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

.hero-actions {
    margin-top: 34px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.primary-action,
.secondary-action {
    height: 58px;
    padding: 0 22px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-weight: 950;
}

.primary-action {
    color: #fff;
    background: linear-gradient(135deg, #c1121f, #ef4444);
    box-shadow: 0 18px 36px rgba(193, 18, 31, 0.28);
}

.secondary-action {
    color: #111827;
    background: rgba(255, 255, 255, 0.86);
}

.visual-panel {
    min-height: 560px;
    padding: 26px;
    border-radius: 34px;
    display: flex;
    flex-direction: column;
    gap: 22px;
    color: #fff;
    background: rgba(15, 23, 42, 0.9);
    box-shadow: 0 32px 90px rgba(15, 23, 42, 0.24);
    animation: cardIn 0.86s ease 0.1s both;
}

.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.panel-head span {
    display: block;
    color: #f87171;
    font-size: 0.78rem;
    font-weight: 950;
    letter-spacing: 0.14em;
}

.panel-head strong {
    display: block;
    margin-top: 5px;
    font-size: 1.55rem;
}

.panel-head i {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    color: #fff;
    background: #c1121f;
    font-size: 1.7rem;
}

.route-board {
    padding: 22px;
    border-radius: 24px;
    display: grid;
    gap: 14px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stop {
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255, 255, 255, 0.76);
    font-weight: 900;
}

.stop span {
    width: 15px;
    height: 15px;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 0 0 8px rgba(255, 255, 255, 0.08);
}

.stop.active span {
    background: #ef4444;
    box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.18);
}

.dash {
    width: 2px;
    height: 40px;
    margin-left: 7px;
    background: linear-gradient(#fff, transparent);
    opacity: 0.4;
}

.cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.metric-card {
    min-height: 126px;
    padding: 16px;
    border-radius: 22px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background: rgba(255, 255, 255, 0.09);
    border: 1px solid rgba(255, 255, 255, 0.11);
    animation: cardFloat 4s ease-in-out infinite;
}

.metric-card:nth-child(2) { animation-delay: 0.35s; }
.metric-card:nth-child(3) { animation-delay: 0.7s; }

.metric-card i {
    font-size: 1.65rem;
}

.metric-card strong,
.metric-card span {
    display: block;
}

.metric-card span {
    color: rgba(255, 255, 255, 0.56);
    font-weight: 800;
}

.red i { color: #fca5a5; }
.blue i { color: #93c5fd; }
.dark i { color: #cbd5e1; }

.dispatch-row {
    margin-top: auto;
    padding: 18px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.08);
}

.vehicle-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 950;
}

.progress-track {
    height: 10px;
    margin-top: 18px;
    overflow: hidden;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.12);
}

.progress-track span {
    display: block;
    width: 42%;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, #ef4444, #f97316);
    animation: progressMove 3.8s ease-in-out infinite;
}

@keyframes gridMove { to { background-position: 56px 56px; } }
@keyframes routeSweep {
    0%, 100% { opacity: 0.34; transform: translateX(0) rotate(var(--r)); }
    50% { opacity: 1; transform: translateX(8vw) rotate(var(--r)); }
}
@keyframes pulse { 50% { transform: scale(1.55); opacity: 0.55; } }
@keyframes riseIn { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
@keyframes cardIn { from { opacity: 0; transform: translateX(24px) scale(0.98); } to { opacity: 1; transform: none; } }
@keyframes floatLogo { 50% { transform: translateY(-10px); } }
@keyframes cardFloat { 50% { transform: translateY(-8px); } }
@keyframes progressMove { 50% { width: 82%; } }

@media (max-width: 1020px) {
    .hero {
        grid-template-columns: 1fr;
    }

    .visual-panel {
        min-height: auto;
    }
}

@media (max-width: 620px) {
    .welcome-page {
        padding: 18px;
    }

    .topbar {
        align-items: flex-start;
    }

    .brand-link {
        width: 132px;
    }

    .nav-actions {
        flex-direction: column;
        align-items: stretch;
    }

    h1 {
        font-size: 3.1rem;
    }

    .cards-grid {
        grid-template-columns: 1fr;
    }

    .hero-actions {
        align-items: stretch;
        flex-direction: column;
    }
}
</style>
