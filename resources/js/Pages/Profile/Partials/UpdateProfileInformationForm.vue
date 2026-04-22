<script setup>
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
})

const user = usePage().props.auth.user

const form = useForm({
    name: user.name,
    email: user.email,
})
</script>

<template>
    <section class="profile-section">
        <header class="form-header">
            <h3 class="form-title">Profile Information</h3>
            <p class="form-description">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="profile-form"
        >
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="field-card">
                        <InputLabel for="name" value="Name" class="modern-label" />

                        <TextInput
                            id="name"
                            type="text"
                            class="modern-input mt-2 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="field-card">
                        <InputLabel for="email" value="Email" class="modern-label" />

                        <TextInput
                            id="email"
                            type="email"
                            class="modern-input mt-2 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                </div>
            </div>

            <div
                v-if="mustVerifyEmail && user.email_verified_at === null"
                class="verify-box mt-4"
            >
                <p class="verify-text">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="verify-link"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="verify-success"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="actions-row mt-4">
                <PrimaryButton
                    :disabled="form.processing"
                    class="save-btn"
                >
                    <i class="bx bx-save me-1"></i>
                    Save Changes
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="saved-text">
                        Saved successfully.
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

.profile-form {
    width: 100%;
}

.field-card {
    background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
    border: 1px solid #edf0f6;
    border-radius: 18px;
    padding: 18px;
    height: 100%;
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

.verify-box {
    background: linear-gradient(135deg, rgba(37, 99, 235, 0.06), rgba(255, 255, 255, 1));
    border: 1px solid rgba(37, 99, 235, 0.12);
    border-radius: 18px;
    padding: 16px 18px;
}

.verify-text {
    margin: 0;
    color: #374151;
    font-size: 0.95rem;
}

.verify-link {
    margin-left: 6px;
    color: #1d4ed8;
    text-decoration: underline;
    font-weight: 700;
}

.verify-success {
    margin-top: 10px;
    color: #15803d;
    font-weight: 700;
    font-size: 0.92rem;
}

.actions-row {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

:deep(.save-btn) {
    background: linear-gradient(135deg, #d51024 0%, #b60f24 100%) !important;
    border: none !important;
    color: #fff !important;
    border-radius: 14px !important;
    padding: 12px 20px !important;
    font-weight: 800 !important;
    box-shadow: 0 14px 28px rgba(213, 16, 36, 0.18);
}

.saved-text {
    color: #15803d;
    font-weight: 700;
    margin: 0;
}
</style>