<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
    replyTo: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const form = reactive({
    mail_account_id: props.accounts?.[0]?.id || "",
    to_email: props.replyTo?.from_email || "",
    subject: props.replyTo?.subject
        ? `Re: ${props.replyTo.subject.replace(/^Re:\s*/i, "")}`
        : "",
    body_text: "",
});

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: flash.success,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
            });
        }

        if (flash?.error) {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: flash.error,
                confirmButtonColor: "#c1121f",
            });
        }
    },
    { immediate: true },
);

const sendMail = () => {
    router.post(route("mailbox.send"), form, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Compose Email" />

    <div class="compose-page">
        <div class="container-fluid py-4">
            <div class="top-actions mb-4">
                <Link :href="route('mailbox.index')" class="btn btn-back">
                    <i class="bx bx-arrow-back"></i>
                    Back to mailbox
                </Link>
            </div>

            <div class="compose-card">
                <div class="compose-header">
                    <div class="compose-icon">
                        <i class="bx bx-edit-alt"></i>
                    </div>

                    <div>
                        <h1>Compose Email</h1>
                        <p>
                            Write and send an email from your mailbox account.
                        </p>
                    </div>
                </div>

                <div class="compose-body">
                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Mailbox Account</label>
                            <select
                                v-model="form.mail_account_id"
                                class="form-control custom-input"
                            >
                                <option value="">Choose mailbox account</option>
                                <option
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }} - {{ account.email }}
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">To</label>
                            <input
                                v-model="form.to_email"
                                type="email"
                                class="form-control custom-input"
                                placeholder="client@example.com"
                            />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Subject</label>
                            <input
                                v-model="form.subject"
                                type="text"
                                class="form-control custom-input"
                                placeholder="Email subject"
                            />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea
                                v-model="form.body_text"
                                rows="10"
                                class="form-control custom-textarea"
                                placeholder="Write your message here..."
                            ></textarea>
                        </div>
                    </div>

                    <div v-if="replyTo" class="original-box">
                        <h5>
                            <i class="bx bx-reply"></i>
                            Original Message
                        </h5>

                        <div class="original-meta">
                            <strong>{{
                                replyTo.from_name || replyTo.from_email
                            }}</strong>
                            <span>{{ replyTo.subject }}</span>
                        </div>

                        <p>{{ replyTo.body_text }}</p>
                    </div>

                    <div class="compose-actions">
                        <Link
                            :href="route('mailbox.index')"
                            class="btn btn-cancel"
                        >
                            Cancel
                        </Link>

                        <button
                            type="button"
                            class="btn btn-send"
                            @click="sendMail"
                        >
                            <i class="bx bx-send"></i>
                            Send Email
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.compose-page {
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(225, 29, 72, 0.1),
            transparent 24%
        ),
        radial-gradient(
            circle at top right,
            rgba(249, 115, 22, 0.08),
            transparent 22%
        ),
        linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.top-actions {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
}

.btn-back,
.btn-send,
.btn-cancel {
    border: 0;
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-back,
.btn-cancel {
    background: #fff;
    color: #334155;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.btn-send {
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.compose-card {
    background: rgba(255, 255, 255, 0.92);
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.75);
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.07);
}

.compose-header {
    padding: 28px;
    display: flex;
    gap: 18px;
    align-items: center;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
}

.compose-icon {
    width: 68px;
    height: 68px;
    min-width: 68px;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
}

.compose-header h1 {
    color: #fff;
    font-weight: 900;
    margin-bottom: 6px;
}

.compose-header p {
    color: rgba(255, 255, 255, 0.82);
    margin-bottom: 0;
}

.compose-body {
    padding: 30px;
}

.form-label {
    font-weight: 900;
    color: #334155;
    margin-bottom: 8px;
}

.custom-input {
    min-height: 54px;
}

.custom-input,
.custom-textarea {
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 14px 16px;
}

.custom-input:focus,
.custom-textarea:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.custom-textarea {
    resize: vertical;
}

.original-box {
    margin-top: 28px;
    padding: 20px;
    border-radius: 20px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.original-box h5 {
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 14px;
}

.original-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 12px;
}

.original-meta strong {
    color: #0f172a;
}

.original-meta span {
    color: #64748b;
    font-weight: 700;
}

.original-box p {
    color: #475569;
    line-height: 1.7;
    margin-bottom: 0;
    white-space: pre-line;
}

.compose-actions {
    margin-top: 28px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .compose-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .compose-body {
        padding: 20px;
    }

    .compose-actions {
        justify-content: stretch;
    }

    .btn-send,
    .btn-cancel {
        width: 100%;
        justify-content: center;
    }
}
</style>
