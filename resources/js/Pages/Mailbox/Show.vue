<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    message: Object,
});

const replyBody = ref("");
const showReplyBox = ref(false);

const openReplyBox = () => {
    showReplyBox.value = true;
};

const senderInitial = computed(() => {
    return (props.message.from_name || props.message.from_email || "?")
        .charAt(0)
        .toUpperCase();
});

const formattedDate = computed(() => {
    const date = props.message.received_at || props.message.created_at;
    if (!date) return "-";

    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return date;

    return d.toLocaleString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
});

const isImage = (attachment) => {
    return attachment.mime_type?.startsWith("image/");
};

const isPdf = (attachment) => {
    return attachment.mime_type === "application/pdf";
};

const isExcel = (attachment) => {
    return (
        attachment.mime_type?.includes("spreadsheet") ||
        attachment.mime_type?.includes("excel") ||
        attachment.file_name?.endsWith(".xlsx") ||
        attachment.file_name?.endsWith(".xls")
    );
};
</script>

<template>
    <Head :title="message.subject || 'Email details'" />

    <div class="mail-show-page">
        <div class="container-fluid py-4">
            <div class="mail-layout">
                <aside class="mail-side">
                    <Link
                        :href="route('mailbox.index')"
                        class="side-btn active"
                    >
                        <i class="bx bx-inbox"></i>
                        Boîte de réception
                    </Link>

                    <button class="side-btn disabled-feature" disabled>
                        <i class="bx bx-send"></i>
                        Messages envoyés
                    </button>

                    <button class="side-btn disabled-feature" disabled>
                        <i class="bx bx-star"></i>
                        Messages suivis
                    </button>

                    <button class="side-btn disabled-feature" disabled>
                        <i class="bx bx-file-blank"></i>
                        Brouillons
                    </button>
                </aside>

                <main class="mail-content">
                    <div class="top-bar">
                        <Link
                            :href="route('mailbox.index')"
                            class="icon-action"
                        >
                            <i class="bx bx-arrow-back"></i>
                        </Link>

                        <button class="icon-action disabled-feature" disabled>
                            <i class="bx bx-archive"></i>
                        </button>

                        <button class="icon-action disabled-feature" disabled>
                            <i class="bx bx-error-circle"></i>
                        </button>

                        <button class="icon-action disabled-feature" disabled>
                            <i class="bx bx-trash"></i>
                        </button>

                        <button class="icon-action disabled-feature" disabled>
                            <i class="bx bx-envelope"></i>
                        </button>

                        <button class="icon-action disabled-feature" disabled>
                            <i class="bx bx-dots-vertical-rounded"></i>
                        </button>
                    </div>

                    <section class="message-panel">
                        <div class="subject-area">
                            <h1>{{ message.subject || "(No subject)" }}</h1>
                            <span class="label-badge">Boîte de réception</span>
                        </div>

                        <div class="sender-area">
                            <div class="avatar">
                                {{ senderInitial }}
                            </div>

                            <div class="sender-info">
                                <div class="sender-line">
                                    <div>
                                        <strong>
                                            {{
                                                message.from_name ||
                                                message.from_email ||
                                                "-"
                                            }}
                                        </strong>

                                        <span>
                                            &lt;{{
                                                message.from_email || "-"
                                            }}&gt;
                                        </span>
                                    </div>

                                    <div class="date-actions">
                                        <span>{{ formattedDate }}</span>

                                        <button
                                            class="mini-icon disabled-feature"
                                            disabled
                                        >
                                            <i class="bx bx-star"></i>
                                        </button>

                                        <button
                                            class="mini-icon"
                                            @click="openReplyBox"
                                        >
                                            <i class="bx bx-reply"></i>
                                        </button>

                                        <button
                                            class="mini-icon disabled-feature"
                                            disabled
                                        >
                                            <i
                                                class="bx bx-dots-vertical-rounded"
                                            ></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="to-line">
                                    À moi / {{ message.to_email || "-" }}
                                </div>
                            </div>
                        </div>

                        <div class="body-area">
                            <div
                                v-if="message.body_html"
                                class="email-html"
                                v-html="message.body_html"
                            ></div>

                            <div
                                v-else-if="message.body_text"
                                class="email-text"
                            >
                                {{ message.body_text }}
                            </div>

                            <div v-else class="empty-body">
                                <i class="bx bx-envelope-open"></i>
                                <strong>Aucun contenu disponible</strong>
                                <span>Le contenu de cet email est vide.</span>
                            </div>
                        </div>

                        <div
                            v-if="
                                message.attachments &&
                                message.attachments.length
                            "
                            class="attachments-area"
                        >
                            <h5>
                                <i class="bx bx-paperclip"></i>
                                Pièces jointes
                            </h5>

                            <div class="attachments-list">
                                <div
                                    v-for="attachment in message.attachments"
                                    :key="attachment.id"
                                    class="attachment-card"
                                >
                                    <a
                                        v-if="isImage(attachment)"
                                        :href="attachment.url"
                                        target="_blank"
                                        class="image-preview"
                                    >
                                        <img
                                            :src="attachment.url"
                                            :alt="attachment.file_name"
                                        />
                                    </a>

                                    <div v-else class="file-icon">
                                        <i
                                            v-if="isPdf(attachment)"
                                            class="bx bxs-file-pdf"
                                        ></i>
                                        <i
                                            v-else-if="isExcel(attachment)"
                                            class="bx bxs-file-doc"
                                        ></i>
                                        <i v-else class="bx bx-file"></i>
                                    </div>

                                    <div class="attachment-info">
                                        <strong>{{
                                            attachment.file_name
                                        }}</strong>
                                        <span>{{
                                            attachment.mime_type || "Fichier"
                                        }}</span>

                                        <div class="attachment-actions">
                                            <a
                                                :href="attachment.url"
                                                target="_blank"
                                                class="open-link"
                                            >
                                                Ouvrir
                                            </a>

                                            <a
                                                :href="attachment.url"
                                                download
                                                class="download-link"
                                            >
                                                Télécharger
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="quick-actions">
                            <button class="pill-btn" @click="openReplyBox">
                                <i class="bx bx-reply"></i>
                                Répondre
                            </button>

                            <button class="pill-btn disabled-feature" disabled>
                                <i class="bx bx-share"></i>
                                Transférer
                            </button>
                        </div>

                        <transition name="fade-slide">
                            <div v-if="showReplyBox" class="reply-box">
                                <div class="reply-title">
                                    <i class="bx bx-reply"></i>
                                    Réponse rapide
                                </div>

                                <textarea
                                    v-model="replyBody"
                                    class="form-control reply-textarea"
                                    rows="7"
                                    placeholder="Écrire votre réponse ici..."
                                ></textarea>

                                <div class="reply-actions">
                                    <button class="btn btn-send" type="button">
                                        <i class="bx bx-send"></i>
                                        Envoyer la réponse
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </section>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.25s ease;
}

.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.disabled-feature {
    opacity: 0.45;
    cursor: not-allowed !important;
    pointer-events: none;
}

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
        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

.mail-layout {
    display: grid;
    grid-template-columns: 260px minmax(0, 1fr);
    gap: 24px;
}

.mail-side,
.mail-content {
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.75);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(12px);
}

.mail-side {
    border-radius: 28px;
    padding: 18px;
    height: fit-content;
    position: sticky;
    top: 95px;
}

.side-btn {
    width: 100%;
    min-height: 50px;
    border: 0;
    background: transparent;
    border-radius: 16px;
    padding: 0 16px;
    color: #475569;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    margin-bottom: 6px;
}

.side-btn i {
    font-size: 21px;
}

.side-btn:hover,
.side-btn.active {
    color: #fff;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
}

.mail-content {
    border-radius: 30px;
    overflow: hidden;
    min-height: calc(100vh - 130px);
}

.top-bar {
    height: 66px;
    padding: 0 24px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border-bottom: 1px solid #eef2f7;
}

.icon-action,
.mini-icon {
    border: 0;
    background: transparent;
    color: #475569;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}

.icon-action {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    font-size: 22px;
}

.icon-action:hover,
.mini-icon:hover {
    background: #f8fafc;
    color: #be123c;
}

.message-panel {
    padding: 34px 42px 42px;
    background: #fff;
}

.subject-area {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}

.subject-area h1 {
    margin: 0;
    color: #111827;
    font-size: 1.8rem;
    font-weight: 950;
}

.label-badge {
    padding: 6px 10px;
    border-radius: 8px;
    background: #e5e7eb;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 900;
}

.sender-area {
    display: flex;
    gap: 16px;
    margin-bottom: 22px;
}

.avatar {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    font-weight: 950;
}

.sender-info {
    width: 100%;
    min-width: 0;
}

.sender-line {
    display: flex;
    justify-content: space-between;
    gap: 18px;
    align-items: flex-start;
}

.sender-line strong {
    color: #111827;
    font-size: 1rem;
    font-weight: 950;
}

.sender-line span,
.to-line {
    color: #64748b;
    font-weight: 700;
}

.date-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.mini-icon {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    font-size: 20px;
}

.to-line {
    margin-top: 4px;
    font-size: 0.88rem;
}

.body-area {
    padding: 22px 0 34px 68px;
    color: #111827;
    line-height: 1.9;
    font-size: 1rem;
}

.email-text {
    white-space: pre-line;
}

.email-html {
    max-width: 100%;
    overflow-x: auto;
}

.empty-body {
    padding: 30px;
    border-radius: 22px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    color: #64748b;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    text-align: center;
}

.empty-body i {
    font-size: 36px;
    color: #be123c;
}

.empty-body strong {
    color: #111827;
}

.attachments-area {
    padding: 0 0 28px 68px;
}

.attachments-area h5 {
    color: #111827;
    font-weight: 950;
    margin-bottom: 14px;
}

.attachments-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 14px;
}

.attachment-card {
    padding: 14px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    gap: 12px;
    color: inherit;
    transition: all 0.2s ease;
}

.attachment-card:hover {
    border-color: #be123c;
    background: #fff7ed;
    transform: translateY(-2px);
}

.image-preview {
    width: 120px;
    height: 86px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #fff;
    flex-shrink: 0;
    display: block;
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.file-icon {
    width: 54px;
    height: 54px;
    min-width: 54px;
    border-radius: 16px;
    background: #fff7ed;
    color: #ea580c;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.attachment-info {
    min-width: 0;
}

.attachment-info strong {
    display: block;
    color: #111827;
    font-weight: 900;
    word-break: break-word;
}

.attachment-info span {
    color: #64748b;
    font-size: 0.82rem;
    font-weight: 700;
}

.attachment-actions {
    margin-top: 8px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.open-link,
.download-link {
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 900;
    text-decoration: none;
}

.open-link {
    background: #eff6ff;
    color: #1d4ed8;
}

.download-link {
    background: #ecfdf5;
    color: #047857;
}

.quick-actions {
    padding-left: 68px;
    display: flex;
    gap: 12px;
    margin-bottom: 28px;
}

.pill-btn {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
    border-radius: 999px;
    padding: 11px 18px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.pill-btn:hover {
    border-color: #be123c;
    color: #be123c;
}

.reply-box {
    margin-left: 68px;
    padding: 22px;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
}

.reply-title {
    font-weight: 950;
    color: #111827;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.reply-textarea {
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    resize: vertical;
    padding: 16px;
}

.reply-textarea:focus {
    border-color: #be123c;
    box-shadow: 0 0 0 0.2rem rgba(190, 18, 60, 0.1);
}

.reply-actions {
    margin-top: 16px;
    display: flex;
    justify-content: flex-end;
}

.btn-send {
    border: 0;
    color: #fff;
    border-radius: 16px;
    padding: 12px 20px;
    font-weight: 950;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

@media (max-width: 1100px) {
    .mail-layout {
        grid-template-columns: 1fr;
    }

    .mail-side {
        position: static;
    }
}

@media (max-width: 768px) {
    .message-panel {
        padding: 24px 18px;
    }

    .sender-line,
    .date-actions {
        align-items: flex-start;
        flex-direction: column;
    }

    .body-area,
    .attachments-area,
    .quick-actions,
    .reply-box {
        padding-left: 0;
        margin-left: 0;
    }
}
</style>
