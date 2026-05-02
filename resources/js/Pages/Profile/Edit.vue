<script setup>
import AppShell from "@/Layouts/AppShell.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Profile" />

    <AppShell>
        <div class="profile-wrapper">
            <div class="profile-hero">
                <div class="hero-glow hero-glow-one"></div>
                <div class="hero-glow hero-glow-two"></div>
                <div class="hero-grid"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-badge">
                            <i class="bx bx-cog"></i>
                            Account Center
                        </div>

                        <h1 class="hero-title">Profile Settings</h1>

                        <p class="hero-subtitle">
                            Manage your personal information, update your password,
                            and keep your account secure from one modern dashboard.
                        </p>

                        <div class="hero-pills">
                            <div class="hero-pill">
                                <i class="bx bx-user"></i>
                                Personal Info
                            </div>
                            <div class="hero-pill">
                                <i class="bx bx-lock-alt"></i>
                                Password
                            </div>
                            <div class="hero-pill danger-pill">
                                <i class="bx bx-shield-quarter"></i>
                                Security
                            </div>
                        </div>
                    </div>

                    <div class="hero-panel">
                        <div class="panel-icon">
                            <i class="bx bx-check-shield"></i>
                        </div>

                        <div class="panel-title">Account Status</div>

                        <div class="panel-row">
                            <span>Profile</span>
                            <strong>Active</strong>
                        </div>

                        <div class="panel-row">
                            <span>Personal Data</span>
                            <strong>Editable</strong>
                        </div>

                        <div class="panel-row">
                            <span>Security</span>
                            <strong>Available</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-grid">
                <div class="profile-card main-card animated-card">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Information</div>
                            <div class="section-title">Profile Information</div>
                            <div class="section-subtitle">
                                Update your name and email address.
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

                <div class="profile-card animated-card delay-one">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Security</div>
                            <div class="section-title">Password</div>
                            <div class="section-subtitle">
                                Keep your account protected with a strong password.
                            </div>
                        </div>

                        <div class="section-icon blue-soft">
                            <i class="bx bx-lock-alt"></i>
                        </div>
                    </div>

                    <UpdatePasswordForm class="max-w-none" />
                </div>

                <div class="profile-card danger-card animated-card delay-two">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker danger-text">
                                Critical Zone
                            </div>
                            <div class="section-title">Delete Account</div>
                            <div class="section-subtitle">
                                This action is permanent. Delete your account only if needed.
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
    </AppShell>
</template>

<style scoped>
.profile-wrapper {
    min-height: calc(100vh - 90px);
    padding: 26px;
    background:
        radial-gradient(circle at top left, rgba(225, 29, 72, 0.1), transparent 24%),
        radial-gradient(circle at bottom right, rgba(249, 115, 22, 0.09), transparent 24%),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.profile-hero {
    position: relative;
    overflow: hidden;
    border-radius: 34px;
    padding: 42px;
    margin-bottom: 28px;
    color: #fff;
    background: linear-gradient(135deg, #7f1d1d 0%, #be123c 45%, #ea580c 100%);
    box-shadow: 0 28px 60px rgba(190, 24, 93, 0.24);
    animation: heroEnter 0.75s ease both;
}

.hero-grid {
    position: absolute;
    inset: 0;
    opacity: 0.14;
    background-image:
        linear-gradient(rgba(255,255,255,0.25) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.25) 1px, transparent 1px);
    background-size: 42px 42px;
}

.hero-glow {
    position: absolute;
    border-radius: 999px;
    filter: blur(10px);
    animation: floatGlow 6s ease-in-out infinite;
}

.hero-glow-one {
    width: 260px;
    height: 260px;
    right: -70px;
    top: -70px;
    background: rgba(255, 255, 255, 0.22);
}

.hero-glow-two {
    width: 180px;
    height: 180px;
    left: 40%;
    bottom: -80px;
    background: rgba(255, 214, 165, 0.25);
    animation-delay: 1.5s;
}

.hero-content {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1fr 330px;
    gap: 28px;
    align-items: center;
}

.hero-badge,
.hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #fff;
    font-weight: 900;
}

.hero-badge {
    padding: 9px 15px;
    margin-bottom: 18px;
}

.hero-title {
    font-size: 2rem;
    font-weight: 950;
    margin: 0 0 12px;
    letter-spacing: -0.04em;
    color: #fff;
}

.hero-subtitle {
    max-width: 720px;
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.05rem;
    line-height: 1.8;
    margin-bottom: 22px;
}

.hero-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-pill {
    padding: 10px 15px;
}

.danger-pill {
    background: rgba(255, 255, 255, 0.18);
}

.hero-panel {
    border-radius: 26px;
    padding: 22px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(14px);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
    animation: panelFloat 4s ease-in-out infinite;
}

.panel-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.18);
    font-size: 30px;
    margin-bottom: 14px;
}

.panel-title {
    font-size: 1.15rem;
    font-weight: 950;
    margin-bottom: 14px;
}

.panel-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 14px;
    margin-bottom: 10px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.1);
}

.panel-row span {
    color: rgba(255, 255, 255, 0.82);
    font-weight: 700;
}

.panel-row strong {
    color: #fff;
}

.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 22px;
}

.main-card {
    grid-column: 1 / -1;
}

.profile-card {
    position: relative;
    overflow: hidden;
    border-radius: 28px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.75);
    padding: 34px;
    box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
    transition: transform 0.28s ease, box-shadow 0.28s ease;
}

.profile-card::before {
    content: "";
    position: absolute;
    inset: 0;
    height: 5px;
    background: linear-gradient(90deg, #be123c, #ea580c);
}

.profile-card:hover {
    transform: translateY(-6px) scale(1.005);
    box-shadow: 0 28px 60px rgba(15, 23, 42, 0.12);
}

.danger-card::before {
    background: linear-gradient(90deg, #ef4444, #be123c);
}

.section-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 18px;
    margin-bottom: 28px;
}

.section-kicker {
    color: #64748b;
    font-weight: 900;
    font-size: 0.86rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 6px;
}

.section-title {
    color: #0f172a;
    font-weight: 950;
    font-size: 1.35rem;
}

.section-subtitle {
    color: #64748b;
    margin-top: 6px;
    line-height: 1.6;
}

.section-icon {
    width: 58px;
    height: 58px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    flex-shrink: 0;
}

.red-soft {
    background: #fff1f2;
    color: #be123c;
}

.blue-soft {
    background: #eff6ff;
    color: #1d4ed8;
}

.danger-soft {
    background: #fef2f2;
    color: #dc2626;
}

.danger-text {
    color: #dc2626;
}

.animated-card {
    animation: cardEnter 0.7s ease both;
}

.delay-one {
    animation-delay: 0.12s;
}

.delay-two {
    animation-delay: 0.22s;
}

@keyframes heroEnter {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes cardEnter {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes floatGlow {
    0%, 100% {
        transform: translateY(0) translateX(0);
    }
    50% {
        transform: translateY(22px) translateX(-16px);
    }
}

@keyframes panelFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

@media (max-width: 992px) {
    .profile-wrapper {
        padding: 16px;
    }

    .profile-hero {
        padding: 28px;
    }

    .hero-content,
    .profile-grid {
        grid-template-columns: 1fr;
    }

    .hero-title {
        font-size: 2.1rem;
    }
}
</style>