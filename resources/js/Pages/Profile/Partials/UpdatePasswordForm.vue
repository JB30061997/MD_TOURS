<script setup>
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const passwordInput = ref(null)
const currentPasswordInput = ref(null)

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation')
                passwordInput.value.focus()
            }
            if (form.errors.current_password) {
                form.reset('current_password')
                currentPasswordInput.value.focus()
            }
        },
    })
}
</script>

<template>
<section class="profile-section">

    <!-- HEADER -->
    <!-- <header class="form-header">
        <h3 class="form-title">Security Settings</h3>
        <p class="form-description">
            Update your password and keep your account protected.
        </p>
    </header> -->

    <!-- FORM -->
    <form @submit.prevent="updatePassword" class="profile-form">

        <div class="field-stack">

            <!-- CURRENT PASSWORD -->
            <div class="field-card floating-card">
                <InputLabel value="Current Password" />
                <TextInput
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="modern-input mt-2"
                />
                <InputError :message="form.errors.current_password" />
            </div>

            <!-- NEW PASSWORD -->
            <div class="field-card floating-card delay">
                <InputLabel value="New Password" />
                <TextInput
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="modern-input mt-2"
                />
                <InputError :message="form.errors.password" />
            </div>

            <!-- CONFIRM -->
            <div class="field-card floating-card delay-2">
                <InputLabel value="Confirm Password" />
                <TextInput
                    v-model="form.password_confirmation"
                    type="password"
                    class="modern-input mt-2"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

        </div>

        <!-- ACTION -->
        <div class="actions-row mt-4">

            <PrimaryButton class="save-btn">
                <i class="bx bx-lock"></i>
                Update Password
            </PrimaryButton>

            <Transition name="fade">
                <p v-if="form.recentlySuccessful" class="saved-text">
                    Password Updated 🔥
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
        radial-gradient(circle at top left, rgba(59,130,246,0.08), transparent 30%),
        radial-gradient(circle at bottom right, rgba(220,38,38,0.08), transparent 30%),
        #f4f6fb;
}

/* HEADER */
.form-title {
    font-weight: 900;
    font-size: 1.4rem;
}

.form-description {
    color: #6b7280;
}

/* STACK */
.field-stack {
    display: grid;
    gap: 18px;
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

/* FLOAT ANIMATION 🔥 */
.floating-card {
    animation: float 4s ease-in-out infinite;
}

.delay { animation-delay: .2s; }
.delay-2 { animation-delay: .4s; }

.field-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

/* INPUT */
:deep(.modern-input) {
    min-height: 52px;
    border-radius: 14px !important;
    transition: all 0.25s ease;
}

/* FOCUS EFFECT 🔥 */
:deep(.modern-input:focus) {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 4px rgba(37,99,235,0.12);
    transform: scale(1.02);
}

/* BUTTON 🔥 */
:deep(.save-btn) {
    background: linear-gradient(135deg,#2563eb,#1e40af) !important;
    border-radius: 14px;
    padding: 12px 22px;
    font-weight: 900;
    transition: 0.3s;
}

:deep(.save-btn:hover) {
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 20px 30px rgba(37,99,235,0.25);
}

/* TEXT */
.saved-text {
    color: #16a34a;
    font-weight: 800;
}

/* ANIMATIONS */
@keyframes float {
    0%,100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* FADE */
.fade-enter-active { transition: .3s }
.fade-enter-from { opacity: 0; transform: translateY(10px); }

</style>