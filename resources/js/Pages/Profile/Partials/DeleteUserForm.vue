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

    <!-- HEADER -->
    <!-- <header class="form-header">
        <h3 class="form-title danger-title">Delete Account</h3>
        <p class="form-description">
            This action will permanently delete all your data.
        </p>
    </header> -->

    <!-- ALERT -->
    <div class="danger-alert glow-danger">
        <i class="bx bx-error danger-icon"></i>

        <div>
            <div class="danger-alert-title">Permanent Action</div>
            <div class="danger-alert-text">
                This cannot be undone.
            </div>
        </div>
    </div>

    <!-- BUTTON -->
    <div class="mt-4">
        <DangerButton @click="confirmUserDeletion" class="danger-btn pulse-btn">
            <i class="bx bx-trash"></i>
            Delete Account
        </DangerButton>
    </div>

    <!-- MODAL -->
    <Modal :show="confirmingUserDeletion" @close="closeModal">
        <div class="delete-modal-wrap animated-modal">

            <div class="delete-modal-icon pulse-danger">
                <i class="bx bx-trash-alt"></i>
            </div>

            <h2 class="delete-modal-title">
                Confirm Deletion
            </h2>

            <p class="delete-modal-text">
                Enter your password to permanently delete your account.
            </p>

            <div class="mt-6">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="modern-input mt-2"
                    placeholder="Password"
                    @keyup.enter="deleteUser"
                />

                <InputError :message="form.errors.password" />
            </div>

            <div class="delete-actions mt-6">
                <SecondaryButton @click="closeModal" class="cancel-btn">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="danger-btn"
                    :disabled="form.processing"
                    @click="deleteUser"
                >
                    Delete
                </DangerButton>
            </div>

        </div>
    </Modal>

</section>
</template>

<style scoped>

/* BACKGROUND */
.profile-section {
    padding: 20px;
}

/* ALERT */
.danger-alert {
    display: flex;
    gap: 12px;
    padding: 18px;
    border-radius: 18px;
    background: rgba(239,68,68,0.08);
    border: 1px solid rgba(239,68,68,0.2);
}

.glow-danger {
    box-shadow: 0 0 30px rgba(239,68,68,0.2);
}

.danger-icon {
    font-size: 26px;
    color: #dc2626;
}

/* BUTTON */
:deep(.danger-btn) {
    background: linear-gradient(135deg,#dc2626,#7f1d1d) !important;
    border-radius: 14px;
    padding: 12px 22px;
    font-weight: 900;
    transition: 0.3s;
}

/* PULSE EFFECT 🔥 */
.pulse-btn {
    animation: pulse 1.8s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(220,38,38,0.4); }
    70% { box-shadow: 0 0 0 20px rgba(220,38,38,0); }
    100% { box-shadow: 0 0 0 0 rgba(220,38,38,0); }
}

/* MODAL */
.delete-modal-wrap {
    padding: 30px;
    border-radius: 22px;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(14px);
}

/* ANIMATION */
.animated-modal {
    animation: scaleIn 0.3s ease;
}

@keyframes scaleIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

/* ICON */
.delete-modal-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 18px;
    background: rgba(239,68,68,0.1);
    font-size: 28px;
    color: #dc2626;
}

.pulse-danger {
    animation: pulse 2s infinite;
}

/* INPUT */
:deep(.modern-input) {
    border-radius: 14px !important;
    min-height: 50px;
}

:deep(.modern-input:focus) {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 4px rgba(220,38,38,0.12);
}

/* ACTIONS */
.delete-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

:deep(.cancel-btn) {
    border-radius: 14px;
}

/* TEXT */
.delete-modal-title {
    font-weight: 900;
}

.delete-modal-text {
    color: #6b7280;
}

</style>