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
        <header class="form-header">
            <h3 class="form-title">Update Password</h3>
            <p class="form-description">
                Ensure your account is using a long, random password to stay secure.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="profile-form">
            <div class="field-stack">
                <div class="field-card">
                    <InputLabel
                        for="current_password"
                        value="Current Password"
                        class="modern-label"
                    />

                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="modern-input mt-2 block w-full"
                        autocomplete="current-password"
                    />

                    <InputError
                        :message="form.errors.current_password"
                        class="mt-2"
                    />
                </div>

                <div class="field-card">
                    <InputLabel
                        for="password"
                        value="New Password"
                        class="modern-label"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="modern-input mt-2 block w-full"
                        autocomplete="new-password"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="field-card">
                    <InputLabel
                        for="password_confirmation"
                        value="Confirm Password"
                        class="modern-label"
                    />

                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="modern-input mt-2 block w-full"
                        autocomplete="new-password"
                    />

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-2"
                    />
                </div>
            </div>

            <div class="actions-row mt-4">
                <PrimaryButton :disabled="form.processing" class="save-btn">
                    <i class="bx bx-lock-alt me-1"></i>
                    Update Password
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="saved-text"
                    >
                        Password updated.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>

<style scoped>
.profile-section {
    width: 100%;
}

.form-header {
    margin-bottom: 24px;
}

.form-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #111827;
    margin: 0;
}

.form-description {
    margin: 8px 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.field-stack {
    display: grid;
    gap: 16px;
}

.field-card {
    background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
    border: 1px solid #edf0f6;
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.03);
}

:deep(.modern-label),
.modern-label {
    color: #374151 !important;
    font-weight: 800 !important;
    font-size: 0.92rem !important;
}

:deep(.modern-input) {
    min-height: 50px;
    border-radius: 14px !important;
    border: 1px solid #dbe2ea !important;
    background: #fff !important;
    box-shadow: none !important;
    transition: all 0.2s ease;
}

:deep(.modern-input:focus) {
    border-color: rgba(29, 78, 216, 0.45) !important;
    box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.08) !important;
}

.actions-row {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

:deep(.save-btn) {
    background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%) !important;
    border: none !important;
    color: #fff !important;
    border-radius: 14px !important;
    padding: 12px 20px !important;
    font-weight: 800 !important;
    box-shadow: 0 14px 28px rgba(37, 99, 235, 0.18);
}

.saved-text {
    color: #15803d;
    font-weight: 700;
    margin: 0;
}
</style>