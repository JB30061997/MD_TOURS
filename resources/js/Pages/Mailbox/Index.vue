<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { reactive, watch, computed } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    messages: Object,
    filters: Object,
    counts: Object,
});

const page = usePage();

const query = reactive({
    search: props.filters?.search || "",
    folder: props.filters?.folder || "inbox",
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
                title: "Erreur",
                text: flash.error,
                confirmButtonColor: "#c1121f",
            });
        }
    },
    { immediate: true },
);

const folderTitle = computed(() => {
    if (query.folder === "sent") return "Messages envoyés";
    if (query.folder === "draft") return "Brouillons";
    return "Boîte de réception";
});

const goFolder = (folder) => {
    query.folder = folder;

    router.get(route("mailbox.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const applySearch = () => {
    router.get(route("mailbox.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const syncMailbox = () => {
    router.post(
        route("mailbox.sync"),
        {},
        {
            preserveScroll: true,
            onStart: () => {
                Swal.fire({
                    title: "Synchronisation...",
                    text: "Récupération des emails en cours",
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading(),
                });
            },
            onFinish: () => Swal.close(),
        },
    );
};

const shortText = (text) => {
    if (!text) return "";
    return text.length > 140 ? text.substring(0, 140) + "..." : text;
};

const formatDate = (date) => {
    if (!date) return "-";

    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return date;

    const today = new Date();
    const sameDay = d.toDateString() === today.toDateString();

    if (sameDay) {
        return d.toLocaleTimeString("fr-FR", {
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    return d.toLocaleDateString("fr-FR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};

const senderInitial = (message) => {
    return (message.from_name || message.from_email || "?")
        .charAt(0)
        .toUpperCase();
};
</script>

<template>
    <Head title="Mailbox" />

    <div class="mailbox-page">
        <div class="container-fluid py-4">
            <div class="mail-app">
                <aside class="mail-left">
                    <div class="compose-box">
                        <button
    class="btn compose-btn"
>
                            <i class="bx bx-pencil"></i>
                            Nouveau message
                        </button>
                    </div>

                    <div class="folders-box">
                        <button
                            class="folder-btn"
                            :class="{ active: query.folder === 'inbox' }"
                            @click="goFolder('inbox')"
                        >
                            <span>
                                <i class="bx bx-inbox"></i>
                                Boîte de réception
                            </span>
                            <b>{{ counts?.inbox || 0 }}</b>
                        </button>

                        <button
                            class="folder-btn"
                            :class="{ active: query.folder === 'sent' }"
                            @click="goFolder('sent')"
                        >
                            <span>
                                <i class="bx bx-send"></i>
                                Messages envoyés
                            </span>
                            <b>{{ counts?.sent || 0 }}</b>
                        </button>

                        <button
                            class="folder-btn"
                            :class="{ active: query.folder === 'draft' }"
                            @click="goFolder('draft')"
                        >
                            <span>
                                <i class="bx bx-file-blank"></i>
                                Brouillons
                            </span>
                            <b>{{ counts?.draft || 0 }}</b>
                        </button>

                        <button class="folder-btn">
                            <span>
                                <i class="bx bx-star"></i>
                                Messages suivis
                            </span>
                            <b>0</b>
                        </button>

                        <button class="folder-btn">
                            <span>
                                <i class="bx bx-time-five"></i>
                                En attente
                            </span>
                            <b>0</b>
                        </button>
                    </div>

                    <div class="mail-stat">
                        <div class="stat-icon">
                            <i class="bx bx-bell"></i>
                        </div>

                        <div>
                            <strong>{{ counts?.unread || 0 }}</strong>
                            <span>Messages non lus</span>
                        </div>
                    </div>
                </aside>

                <main class="mail-main">
                    <div class="mail-hero">
                        <div>
                            <div class="hero-kicker">MD TOURS MAIL CENTER</div>
                            <h1 style="color: #fff;">{{ folderTitle }}</h1>
                            <p>
                                Gérez les emails clients directement depuis votre application.
                            </p>
                        </div>

                        <div class="hero-actions">
                            <Link :href="route('mailbox.demo')" class="btn btn-light-mail">
                                <i class="bx bx-data"></i>
                                Demo
                            </Link>

                            <button class="btn btn-sync" @click="syncMailbox">
                                <i class="bx bx-refresh"></i>
                                Sync Mail
                            </button>
                        </div>
                    </div>

                    <div class="mail-toolbar">
                        <div class="toolbar-left">
                            <button class="icon-btn">
                                <i class="bx bx-checkbox-square"></i>
                            </button>

                            <button class="icon-btn" @click="syncMailbox">
                                <i class="bx bx-refresh"></i>
                            </button>

                            <button class="icon-btn">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>

                            <span class="result-text">
                                {{ messages.from || 0 }}-{{ messages.to || 0 }}
                                sur {{ messages.total || 0 }}
                            </span>
                        </div>

                        <div class="search-box">
                            <i class="bx bx-search"></i>
                            <input
                                v-model="query.search"
                                type="text"
                                placeholder="Rechercher dans les messages..."
                                @keyup.enter="applySearch"
                            />
                            <button @click="applySearch">
                                <i class="bx bx-slider-alt"></i>
                            </button>
                        </div>
                    </div>

                    <div class="category-tabs">
                        <button
                            class="category-tab"
                            :class="{ active: query.folder === 'inbox' }"
                            @click="goFolder('inbox')"
                        >
                            <i class="bx bx-inbox"></i>
                            Principale
                        </button>

                        <button class="category-tab">
                            <i class="bx bx-purchase-tag"></i>
                            Promotions
                            <span>New</span>
                        </button>

                        <button class="category-tab">
                            <i class="bx bx-group"></i>
                            Réseaux sociaux
                        </button>

                        <button class="category-tab">
                            <i class="bx bx-info-circle"></i>
                            Notifications
                        </button>
                    </div>

                    <div v-if="messages.data && messages.data.length" class="gmail-list">
                        <Link
                            v-for="message in messages.data"
                            :key="message.id"
                            :href="route('mailbox.show', message.id)"
                            class="gmail-row"
                            :class="{ unread: !message.is_read && message.folder === 'inbox' }"
                        >
                            <div class="row-check">
                                <span class="fake-check"></span>
                                <i class="bx bx-star star-icon"></i>
                            </div>

                            <div class="sender-block">
                                <div class="avatar">
                                    {{ senderInitial(message) }}
                                </div>

                                <div>
                                    <strong>
                                        {{ message.from_name || message.from_email || "-" }}
                                    </strong>
                                    <small>
                                        {{ message.from_email || message.to_email || "-" }}
                                    </small>
                                </div>
                            </div>

                            <div class="message-block">
                                <div class="subject-line">
                                    <strong>{{ message.subject || "(No subject)" }}</strong>
                                    <span>-</span>
                                    <p>{{ shortText(message.body_text) }}</p>
                                </div>

                                <div class="chips">
                                    <span class="mail-chip" :class="message.folder">
                                        {{ message.folder }}
                                    </span>

                                    <span
                                        v-if="message.attachments?.length"
                                        class="mail-chip attachment"
                                    >
                                        <i class="bx bx-paperclip"></i>
                                        Pièce jointe
                                    </span>
                                </div>
                            </div>

                            <div class="date-block">
                                {{ formatDate(message.received_at || message.created_at) }}
                            </div>
                        </Link>
                    </div>

                    <div v-else class="empty-mail">
                        <div class="empty-icon">
                            <i class="bx bx-envelope-open"></i>
                        </div>

                        <h4>Aucun email trouvé</h4>
                        <p>Synchronisez votre boîte mail ou changez de dossier.</p>

                        <button class="btn btn-sync" @click="syncMailbox">
                            <i class="bx bx-refresh"></i>
                            Synchroniser maintenant
                        </button>
                    </div>

                    <div v-if="messages.links?.length" class="pagination-area">
                        <div class="pagination-info">
                            Page {{ messages.current_page }} /
                            {{ messages.last_page }}
                        </div>

                        <div class="pagination-list">
                            <Link
                                v-for="(link, index) in messages.links"
                                :key="index"
                                :href="link.url || ''"
                                v-html="link.label"
                                class="page-btn"
                                :class="{
                                    active: link.active,
                                    disabled: !link.url,
                                }"
                                preserve-scroll
                                preserve-state
                            />
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mailbox-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(225, 29, 72, 0.12), transparent 24%),
        radial-gradient(circle at top right, rgba(249, 115, 22, 0.1), transparent 22%),
        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

.mail-app {
    display: grid;
    grid-template-columns: 280px minmax(0, 1fr);
    gap: 24px;
}

.mail-left,
.mail-main {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.75);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    backdrop-filter: blur(12px);
}

.mail-left {
    border-radius: 28px;
    padding: 20px;
    height: calc(100vh - 130px);
    position: sticky;
    top: 95px;
}

.mail-main {
    border-radius: 30px;
    overflow: hidden;
    min-height: calc(100vh - 130px);
}

.compose-box {
    margin-bottom: 20px;
}

.compose-btn {
    width: 100%;
    height: 58px;
    border: 0;
    border-radius: 20px;
    background: linear-gradient(135deg, #fff 0%, #fff7ed 100%);
    color: #991b1b;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 14px 28px rgba(190, 24, 93, 0.12);
}

.compose-btn i {
    font-size: 22px;
}

.folders-box {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.folder-btn {
    border: 0;
    background: transparent;
    width: 100%;
    min-height: 48px;
    border-radius: 16px;
    padding: 0 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #475569;
    font-weight: 800;
    transition: 0.2s ease;
}

.folder-btn span {
    display: flex;
    align-items: center;
    gap: 12px;
}

.folder-btn i {
    font-size: 20px;
}

.folder-btn:hover {
    background: #f8fafc;
    transform: translateX(2px);
}

.folder-btn.active {
    color: #fff;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
    box-shadow: 0 12px 24px rgba(190, 24, 93, 0.18);
}

.mail-stat {
    margin-top: 24px;
    padding: 18px;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff1f2 0%, #fff7ed 100%);
    display: flex;
    align-items: center;
    gap: 14px;
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.mail-stat strong {
    display: block;
    font-size: 1.6rem;
    font-weight: 950;
    color: #0f172a;
    line-height: 1;
}

.mail-stat span {
    color: #64748b;
    font-weight: 800;
    font-size: 0.86rem;
}

.mail-hero {
    padding: 26px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    background: linear-gradient(135deg, #991b1b 0%, #be123c 45%, #ea580c 100%);
    color: #fff;
}

.hero-kicker {
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 0.12em;
    opacity: 0.75;
    margin-bottom: 6px;
}

.mail-hero h1 {
    font-size: 1.9rem;
    font-weight: 950;
    margin-bottom: 4px;
}

.mail-hero p {
    margin: 0;
    opacity: 0.8;
    font-weight: 600;
}

.hero-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-light-mail,
.btn-sync {
    border: 0;
    border-radius: 16px;
    padding: 12px 18px;
    font-weight: 900;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-light-mail {
    background: #fff;
    color: #991b1b;
}

.btn-sync {
    color: #fff;
    background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22);
}

.btn-sync:hover,
.btn-light-mail:hover,
.compose-btn:hover {
    transform: translateY(-2px);
}

.mail-toolbar {
    padding: 16px 22px;
    border-bottom: 1px solid #eef2f7;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: rgba(248, 250, 252, 0.85);
}

.toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.icon-btn {
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 14px;
    background: transparent;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.icon-btn:hover {
    background: #fff;
    color: #be123c;
}

.result-text {
    color: #64748b;
    font-weight: 800;
    margin-left: 8px;
    font-size: 0.84rem;
}

.search-box {
    width: min(520px, 100%);
    min-height: 48px;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.search-box i {
    color: #94a3b8;
    font-size: 22px;
    margin-left: 16px;
}

.search-box input {
    flex: 1;
    border: 0;
    outline: 0;
    height: 48px;
    padding: 0 12px;
    color: #334155;
    font-weight: 700;
}

.search-box button {
    width: 52px;
    height: 48px;
    border: 0;
    background: transparent;
    color: #64748b;
}

.category-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    background: #fff;
    border-bottom: 1px solid #eef2f7;
}

.category-tab {
    border: 0;
    background: #fff;
    min-height: 62px;
    padding: 0 20px;
    color: #64748b;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.category-tab i {
    font-size: 22px;
}

.category-tab.active {
    color: #be123c;
}

.category-tab.active::after {
    content: "";
    position: absolute;
    height: 4px;
    border-radius: 999px 999px 0 0;
    left: 18px;
    right: 18px;
    bottom: 0;
    background: linear-gradient(90deg, #be123c, #ea580c);
}

.category-tab span {
    font-size: 0.72rem;
    color: #fff;
    background: #16a34a;
    border-radius: 999px;
    padding: 3px 8px;
}

.gmail-list {
    background: #fff;
}

.gmail-row {
    min-height: 74px;
    display: grid;
    grid-template-columns: 72px 260px minmax(0, 1fr) 96px;
    align-items: center;
    gap: 12px;
    padding: 0 22px;
    color: inherit;
    text-decoration: none;
    border-bottom: 1px solid #eef2f7;
    transition: 0.18s ease;
}

.gmail-row:hover {
    background: #f8fafc;
    box-shadow: inset 4px 0 0 #be123c;
}

.gmail-row.unread {
    background: #fff7ed;
}

.row-check {
    display: flex;
    align-items: center;
    gap: 12px;
}

.fake-check {
    width: 17px;
    height: 17px;
    border-radius: 5px;
    border: 2px solid #cbd5e1;
    background: #fff;
}

.star-icon {
    color: #cbd5e1;
    font-size: 20px;
}

.gmail-row:hover .star-icon {
    color: #f59e0b;
}

.sender-block {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: linear-gradient(135deg, #be123c 0%, #f97316 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 950;
}

.sender-block strong {
    display: block;
    color: #0f172a;
    font-weight: 950;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sender-block small {
    display: block;
    color: #64748b;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.message-block {
    min-width: 0;
}

.subject-line {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}

.subject-line strong {
    color: #111827;
    font-weight: 950;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 320px;
}

.subject-line span {
    color: #94a3b8;
}

.subject-line p {
    margin: 0;
    color: #64748b;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.chips {
    margin-top: 6px;
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}

.mail-chip {
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 950;
    text-transform: uppercase;
}

.mail-chip.inbox {
    background: #eff6ff;
    color: #1d4ed8;
}

.mail-chip.sent {
    background: #ecfdf5;
    color: #047857;
}

.mail-chip.draft {
    background: #fff7ed;
    color: #c2410c;
}

.mail-chip.attachment {
    background: #f5f3ff;
    color: #7c3aed;
}

.date-block {
    color: #475569;
    font-weight: 900;
    text-align: right;
    font-size: 0.84rem;
}

.empty-mail {
    padding: 80px 20px;
    text-align: center;
    background: #fff;
}

.empty-icon {
    width: 84px;
    height: 84px;
    border-radius: 28px;
    margin: 0 auto 18px;
    background: linear-gradient(135deg, #fda4af 0%, #fdba74 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
}

.empty-mail h4 {
    font-weight: 950;
    color: #0f172a;
}

.empty-mail p {
    color: #64748b;
    font-weight: 700;
}

.pagination-area {
    padding: 18px 24px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    border-top: 1px solid #eef2f7;
    background: #fff;
}

.pagination-info {
    color: #64748b;
    font-weight: 800;
}

.pagination-list {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.page-btn {
    min-width: 42px;
    height: 42px;
    padding: 0 14px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-weight: 800;
}

.page-btn.active {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #be123c 0%, #ea580c 100%);
}

.page-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
}

@media (max-width: 1200px) {
    .mail-app {
        grid-template-columns: 1fr;
    }

    .mail-left {
        height: auto;
        position: static;
    }

    .gmail-row {
        grid-template-columns: 60px 210px minmax(0, 1fr) 80px;
    }
}

@media (max-width: 768px) {
    .mail-hero,
    .mail-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .category-tabs {
        grid-template-columns: 1fr 1fr;
    }

    .gmail-row {
        grid-template-columns: 1fr;
        gap: 8px;
        padding: 16px;
    }

    .row-check {
        display: none;
    }

    .date-block {
        text-align: left;
    }

    .subject-line {
        align-items: flex-start;
        flex-direction: column;
    }

    .subject-line strong {
        max-width: 100%;
    }
}
</style>