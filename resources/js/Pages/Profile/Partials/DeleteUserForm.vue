<script setup>
import DangerButton from '@/Components/DangerButton.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)

const form = useForm({
    password: '',
})

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true

    nextTick(() => passwordInput.value.focus())
}

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    })
}

const closeModal = () => {
    confirmingUserDeletion.value = false
    form.clearErrors()
    form.reset()
}
</script>

<template>
    <section class="profile-section">
        <header class="form-header">
            <h3 class="form-title danger-title">Delete Account</h3>

            <p class="form-description">
                Once your account is deleted, all of its resources and data will
                be permanently deleted. Before deleting your account, please
                download any data or information that you wish to retain.
            </p>
        </header>

        <div class="danger-alert">
            <div class="danger-alert-icon">
                <i class="bx bx-error"></i>
            </div>

            <div>
                <div class="danger-alert-title">Action irréversible</div>
                <div class="danger-alert-text">
                    Cette suppression est définitive et ne pourra pas être annulée.
                </div>
            </div>
        </div>

        <div class="mt-4">
            <DangerButton @click="confirmUserDeletion" class="danger-btn">
                <i class="bx bx-trash me-1"></i>
                Delete Account
            </DangerButton>
        </div>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="delete-modal-wrap p-6">
                <div class="delete-modal-icon">
                    <i class="bx bx-trash-alt"></i>
                </div>

                <h2 class="delete-modal-title">
                    Are you sure you want to delete your account?
                </h2>

                <p class="delete-modal-text">
                    Once your account is deleted, all of its resources and data
                    will be permanently deleted. Please enter your password to
                    confirm you would like to permanently delete your account.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Password"
                        class="modern-label"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="modern-input mt-2 block w-full"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="delete-actions mt-6">
                    <SecondaryButton @click="closeModal" class="cancel-btn">
                        Cancel
                    </SecondaryButton>

                    <DangerButton
                        class="danger-btn ms-2"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Delete Account
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>

<style scoped>
.profile-section {
    width: 100%;
}

.form-header {
    margin-bottom: 22px;
}

.form-title {
    font-size: 1.15rem;
    font-weight: 900;
    color: #111827;
    margin: 0;
}

.danger-title {
    color: #b91c1c;
}

.form-description {
    margin: 8px 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.danger-alert {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08), rgba(255,255,255,1));
    border: 1px solid rgba(239, 68, 68, 0.14);
    border-radius: 18px;
    padding: 18px;
}

.danger-alert-icon {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: rgba(239, 68, 68, 0.14);
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    flex-shrink: 0;
}

.danger-alert-title {
    color: #991b1b;
    font-weight: 900;
    margin-bottom: 4px;
}

.danger-alert-text {
    color: #7f1d1d;
    font-size: 0.93rem;
}

:deep(.danger-btn) {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important;
    border: none !important;
    color: #fff !important;
    border-radius: 14px !important;
    padding: 12px 20px !important;
    font-weight: 800 !important;
    box-shadow: 0 14px 28px rgba(239, 68, 68, 0.18);
}

.delete-modal-wrap {
    background: #fff;
    border-radius: 22px;
}

.delete-modal-icon {
    width: 60px;
    height: 60px;
    border-radius: 18px;
    background: rgba(239, 68, 68, 0.12);
    color: #dc2626;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.7rem;
    margin-bottom: 18px;
}

.delete-modal-title {
    font-size: 1.2rem;
    font-weight: 900;
    color: #111827;
    margin: 0 0 8px;
}

.delete-modal-text {
    color: #6b7280;
    font-size: 0.95rem;
    line-height: 1.7;
    margin: 0;
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
    border-color: rgba(239, 68, 68, 0.35) !important;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.08) !important;
}

.delete-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

:deep(.cancel-btn) {
    border-radius: 14px !important;
    padding: 12px 18px !important;
    font-weight: 700 !important;
}
</style>