<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const showPwd1 = ref(false)
const showPwd2 = ref(false)
const submitting = computed(() => form.processing)

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Reset Password" />

    <div class="reset-page">
        <!-- Background -->
        <div class="reset-bg">
            <div class="bg-orb bg-orb-red"></div>
            <div class="bg-orb bg-orb-blue"></div>
            <div class="bg-grid"></div>
            <div class="bg-overlay"></div>
        </div>

        <div class="reset-wrapper container-fluid px-4 px-lg-5 py-4 py-lg-5">
            <!-- Topbar -->
            <div class="reset-topbar mb-4">
                <Link href="/" class="brand-wrap text-decoration-none">
                    <div class="brand-logo"><span>MD</span></div>
                    <div>
                        <div class="brand-name">MD TOURS</div>
                        <div class="brand-subtitle">transport touristique</div>
                    </div>
                </Link>

                <Link :href="route('login')" class="btn topbar-btn-outline">
                    <i class="bx bx-arrow-back me-1"></i>
                    Login
                </Link>
            </div>

            <div class="row g-4 justify-content-center align-items-stretch">
                <!-- LEFT -->
                <div class="col-12 col-xl-5 col-lg-6">
                    <div class="reset-card h-100">
                        <div class="reset-badge">
                            <i class="bx bx-key"></i>
                            Réinitialisation
                        </div>

                        <h1 class="reset-title">
                            Nouveau
                            <span class="gradient-text">mot de passe</span>
                        </h1>

                        <p class="reset-subtitle">
                            Entrez votre nouveau mot de passe pour sécuriser votre compte.
                        </p>

                        <form class="reset-form" @submit.prevent="submit">
                            <!-- Email -->
                            <div class="form-group-modern">
                                <label class="form-label-modern">Email</label>
                                <div class="input-icon-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-envelope"></i>
                                    </span>
                                    <input
                                        type="email"
                                        class="form-control input-modern"
                                        v-model="form.email"
                                        required
                                    />
                                </div>
                                <div v-if="form.errors.email" class="error-text">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group-modern">
                                <label class="form-label-modern">New Password</label>
                                <div class="input-icon-wrap password-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-lock"></i>
                                    </span>

                                    <input
                                        :type="showPwd1 ? 'text' : 'password'"
                                        class="form-control input-modern input-password"
                                        v-model="form.password"
                                        placeholder="New password"
                                        required
                                    />

                                    <button
                                        type="button"
                                        class="password-toggle-btn"
                                        @click="showPwd1 = !showPwd1"
                                    >
                                        <i :class="showPwd1 ? 'bx bx-show' : 'bx bx-hide'"></i>
                                    </button>
                                </div>

                                <div v-if="form.errors.password" class="error-text">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <!-- Confirm -->
                            <div class="form-group-modern">
                                <label class="form-label-modern">Confirm Password</label>
                                <div class="input-icon-wrap password-wrap">
                                    <span class="input-left-icon">
                                        <i class="bx bx-check"></i>
                                    </span>

                                    <input
                                        :type="showPwd2 ? 'text' : 'password'"
                                        class="form-control input-modern input-password"
                                        v-model="form.password_confirmation"
                                        placeholder="Confirm password"
                                        required
                                    />

                                    <button
                                        type="button"
                                        class="password-toggle-btn"
                                        @click="showPwd2 = !showPwd2"
                                    >
                                        <i :class="showPwd2 ? 'bx bx-show' : 'bx bx-hide'"></i>
                                    </button>
                                </div>

                                <div v-if="form.errors.password_confirmation" class="error-text">
                                    {{ form.errors.password_confirmation }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="action-stack">
                                <button
                                    type="submit"
                                    class="btn reset-btn"
                                    :disabled="submitting"
                                >
                                    <i v-if="!submitting" class="bx bx-check-circle me-2"></i>
                                    <i v-else class="bx bx-loader-alt bx-spin me-2"></i>

                                    <span v-if="!submitting">Change Password</span>
                                    <span v-else>Processing...</span>
                                </button>

                                <Link :href="route('login')" class="btn back-btn">
                                    Back to Login
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="col-12 col-xl-5 col-lg-6 d-none d-lg-block">
                    <div class="reset-showcase-card h-100">
                        <h2 class="showcase-title">
                            Sécurisez votre compte
                        </h2>

                        <p class="showcase-subtitle">
                            Une étape simple pour protéger vos données et continuer
                            à gérer votre activité touristique en toute sécurité.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.reset-page {
    min-height: 100vh;
    background: linear-gradient(180deg,#f8fafc,#eef2f8);
    position: relative;
}

.reset-card {
    background: white;
    border-radius: 24px;
    padding: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.reset-title {
    font-size: 2.2rem;
    font-weight: 900;
}

.gradient-text {
    background: linear-gradient(135deg,#d51024,#2a56d9);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.input-modern {
    height: 52px;
    border-radius: 14px;
    padding-left: 45px;
}

.reset-btn {
    height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg,#d51024,#2a56d9);
    color: white;
    font-weight: bold;
}

.back-btn {
    border-radius: 14px;
    border: 1px solid #ddd;
}
</style>