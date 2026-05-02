<script setup>
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
})

const user = usePage().props.auth.user

const form = useForm({
    name: user.name,
    email: user.email,
})
</script>

<template>
<section class="profile-section">

    <!-- HEADER -->
    <!-- <div class="form-header">
        <h3 class="form-title">Profile Information</h3>
        <p class="form-description">
            Update your account information with a modern experience.
        </p>
    </div> -->

    <!-- FORM -->
    <form @submit.prevent="form.patch(route('profile.update'))" class="profile-form">

        <div class="row g-4">

            <!-- NAME -->
            <div class="col-md-6">
                <div class="field-card floating-card">
                    <InputLabel for="name" value="Name" class="modern-label" />

                    <TextInput
                        id="name"
                        type="text"
                        class="modern-input mt-2"
                        v-model="form.name"
                    />

                    <InputError :message="form.errors.name" />
                </div>
            </div>

            <!-- EMAIL -->
            <div class="col-md-6">
                <div class="field-card floating-card">
                    <InputLabel for="email" value="Email" class="modern-label" />

                    <TextInput
                        id="email"
                        type="email"
                        class="modern-input mt-2"
                        v-model="form.email"
                    />

                    <InputError :message="form.errors.email" />
                </div>
            </div>

        </div>

        <!-- VERIFY -->
        <div v-if="mustVerifyEmail && user.email_verified_at === null" class="verify-box mt-4">
            <p>
                Email not verified.
                <Link
                    :href="route('verification.send')"
                    method="post"
                    as="button"
                    class="verify-link"
                >
                    Resend verification
                </Link>
            </p>
        </div>

        <!-- ACTION -->
        <div class="actions-row mt-4">

            <PrimaryButton :disabled="form.processing" class="save-btn">
                <i class="bx bx-save"></i>
                Save
            </PrimaryButton>

            <Transition name="fade">
                <p v-if="form.recentlySuccessful" class="saved-text">
                    Saved ✅
                </p>
            </Transition>

        </div>

    </form>

</section>
</template>

<style scoped>

/* BACKGROUND */
.profile-section {
    padding: 20px;
    background:
        radial-gradient(circle at top left, rgba(220,38,38,0.08), transparent 30%),
        radial-gradient(circle at bottom right, rgba(59,130,246,0.08), transparent 30%),
        #f4f6fb;
}

/* HEADER */
.form-title {
    font-weight: 900;
    font-size: 1.3rem;
}

.form-description {
    color: #6b7280;
}

/* CARD */
.field-card {
    border-radius: 20px;
    padding: 20px;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.6);
    transition: all 0.3s ease;
}

/* FLOAT EFFECT 🔥 */
.floating-card {
    animation: float 4s ease-in-out infinite;
}

.floating-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

/* INPUT */
:deep(.modern-input) {
    border-radius: 14px !important;
    min-height: 50px;
    transition: all 0.25s ease;
}

/* FOCUS EFFECT 🔥 */
:deep(.modern-input:focus) {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 4px rgba(220,38,38,0.12);
    transform: scale(1.02);
}

/* VERIFY */
.verify-box {
    padding: 15px;
    border-radius: 16px;
    background: rgba(37,99,235,0.08);
}

.verify-link {
    color: #1d4ed8;
    font-weight: 700;
}

/* BUTTON 🔥 */
:deep(.save-btn) {
    background: linear-gradient(135deg,#dc2626,#7f1d1d) !important;
    border-radius: 14px;
    padding: 12px 22px;
    font-weight: 900;
    transition: all 0.3s ease;
}

:deep(.save-btn:hover) {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 20px 30px rgba(220,38,38,0.25);
}

/* TEXT */
.saved-text {
    color: #16a34a;
    font-weight: 800;
}

/* ANIMATIONS 🔥 */
@keyframes float {
    0%,100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* FADE */
.fade-enter-active { transition: 0.3s; }
.fade-enter-from { opacity: 0; transform: translateY(10px); }

</style>