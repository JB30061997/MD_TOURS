<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    message: Object,
});
</script>

<template>
    <Head :title="message.subject || 'Email details'" />

    <div class="mail-show-page">
        <div class="container-fluid py-4">
            <div class="top-actions mb-4">
                <Link :href="route('mailbox.index')" class="btn btn-back">
                    <i class="bx bx-arrow-back"></i>
                    Back to mailbox
                </Link>

                <button class="btn btn-reply" type="button">
                    <i class="bx bx-reply"></i>
                    Reply
                </button>
            </div>

            <div class="message-card">
                <div class="message-header">
                    <div class="avatar">
                        {{
                            (message.from_name || message.from_email || "?")
                                .charAt(0)
                                .toUpperCase()
                        }}
                    </div>

                    <div class="header-content">
                        <h1 class="subject">
                            {{ message.subject || "(No subject)" }}
                        </h1>

                        <div class="meta-grid">
                            <div>
                                <span>From</span>
                                <strong>{{ message.from_name || "-" }}</strong>
                                <small>{{ message.from_email || "-" }}</small>
                            </div>

                            <div>
                                <span>To</span>
                                <strong>{{ message.to_email || "-" }}</strong>
                            </div>

                            <div>
                                <span>Date</span>
                                <strong>{{
                                    message.received_at || message.created_at
                                }}</strong>
                            </div>

                            <div>
                                <span>Mailbox</span>
                                <strong>{{
                                    message.account?.email || "-"
                                }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="message-body">
                    <div
                        v-if="message.body_html"
                        v-html="message.body_html"
                    ></div>
                    <p v-else>
                        {{ message.body_text || "No content." }}
                    </p>
                </div>

                <div
                    v-if="message.attachments?.length"
                    class="attachments-area"
                >
                    <h5>
                        <i class="bx bx-paperclip"></i>
                        Attachments
                    </h5>

                    <div class="attachments-list">
                        <div
                            v-for="attachment in message.attachments"
                            :key="attachment.id"
                            class="attachment-item"
                        >
                            <div class="file-icon">
                                <i class="bx bx-file"></i>
                            </div>

                            <div>
                                <strong>{{ attachment.file_name }}</strong>
                                <small>{{
                                    attachment.mime_type || "file"
                                }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="reply-box">
                    <h5>Quick Reply</h5>

                    <textarea
                        class="form-control reply-textarea"
                        rows="6"
                        placeholder="Write your reply here..."
                    ></textarea>

                    <div class="reply-actions">
                        <button class="btn btn-send" type="button">
                            <i class="bx bx-send"></i>
                            Send Reply
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mail-show-page {
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
.btn-reply,
.btn-send {
    border: 0;
    border-radius: 14px;
    padding: 11px 18px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-back {
    background: #fff;
    color: #334155;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
}

.btn-reply,
.btn-send {
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.message-card {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.75);
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.07);
}

.message-header {
    padding: 28px;
    display: flex;
    gap: 20px;
    border-bottom: 1px solid #eef2f7;
    background: linear-gradient(180deg, #fff1f2 0%, #fff7ed 100%);
}

.avatar {
    width: 68px;
    height: 68px;
    min-width: 68px;
    border-radius: 22px;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 900;
}

.header-content {
    width: 100%;
}

.subject {
    font-size: 1.6rem;
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 18px;
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(160px, 1fr));
    gap: 14px;
}

.meta-grid div {
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid #ffe4e6;
    border-radius: 16px;
    padding: 12px 14px;
}

.meta-grid span {
    display: block;
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.meta-grid strong {
    display: block;
    color: #334155;
    font-weight: 900;
}

.meta-grid small {
    color: #64748b;
}

.message-body {
    padding: 30px;
    color: #334155;
    line-height: 1.8;
    font-size: 1rem;
    white-space: pre-line;
}

.attachments-area {
    padding: 0 30px 30px;
}

.attachments-area h5 {
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 14px;
}

.attachments-list {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.attachment-item {
    min-width: 240px;
    padding: 14px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex;
    gap: 12px;
    align-items: center;
}

.file-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #fff7ed;
    color: #ea580c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.attachment-item strong {
    display: block;
    color: #334155;
}

.attachment-item small {
    color: #64748b;
}

.reply-box {
    padding: 30px;
    border-top: 1px solid #eef2f7;
    background: #f8fafc;
}

.reply-box h5 {
    font-weight: 900;
    color: #0f172a;
    margin-bottom: 14px;
}

.reply-textarea {
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    resize: vertical;
    padding: 16px;
}

.reply-textarea:focus {
    border-color: #e11d48;
    box-shadow: 0 0 0 0.2rem rgba(225, 29, 72, 0.1);
}

.reply-actions {
    margin-top: 16px;
    display: flex;
    justify-content: flex-end;
}

@media (max-width: 992px) {
    .message-header {
        flex-direction: column;
    }

    .meta-grid {
        grid-template-columns: 1fr;
    }
}
</style>
