<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route("logout"));
};

const toggleSidebar = () => {
    document.body.classList.toggle("toggled");

    if (!document.body.classList.contains("toggled")) {
        document.body.classList.remove("sidebar-hovered");
    }
};

const page = usePage();

const user = computed(() => page.props?.auth?.user || {});
const userName = computed(() => user.value?.name || "User");
const userEmail = computed(() => user.value?.email || "account@example.com");

const userInitials = computed(() => {
    const name = userName.value?.trim() || "U";
    const parts = name.split(" ").filter(Boolean);

    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();

    return (parts[0][0] + parts[1][0]).toUpperCase();
});

const supportPhone = "212624984302";

const whatsappMessage = computed(() =>
    encodeURIComponent(
        "Hello Jaouad, I need help using the MD TOURS platform. I faced an issue, got blocked, saw an error, or need clarification about something.",
    ),
);

const whatsappLink = computed(() => {
    return `https://wa.me/${supportPhone}?text=${whatsappMessage.value}`;
});
</script>

<template>
    <header class="top-header">
        <nav class="navbar navbar-expand align-items-center gap-1 topbar-shell">
            <div class="topbar-left d-flex align-items-center gap-3">
                <div class="btn-toggle modern-toggle" @click="toggleSidebar">
                    <a href="javascript:;">
                        <i class="material-icons-outlined">menu</i>
                    </a>
                </div>
            </div>

            <div class="search-bar flex-grow-1">
                <div class="position-relative search-modern-wrap">
                    <input
                        class="form-control search-control-modern d-lg-block d-none"
                        type="text"
                        placeholder="Search planning, client, supplier..."
                    />

                    <span
                        class="material-icons-outlined position-absolute search-icon-start d-lg-block d-none"
                    >
                        search
                    </span>

                    <span
                        class="material-icons-outlined position-absolute search-icon-end d-lg-block d-none"
                    >
                        tune
                    </span>

                    <div class="search-popup p-3">
                        <div class="card search-popup-card overflow-hidden">
                            <div class="card-body search-content pt-3">
                                <p class="search-title">Quick Access</p>

                                <div
                                    class="d-flex align-items-start flex-wrap gap-2 keywords-wrapper"
                                >
                                    <a href="#" class="keywords-chip">
                                        <span>Dashboard</span>
                                        <i class="material-icons-outlined fs-6"
                                            >search</i
                                        >
                                    </a>

                                    <a href="#" class="keywords-chip">
                                        <span>Plannings</span>
                                        <i class="material-icons-outlined fs-6"
                                            >search</i
                                        >
                                    </a>

                                    <a href="#" class="keywords-chip">
                                        <span>Clients</span>
                                        <i class="material-icons-outlined fs-6"
                                            >search</i
                                        >
                                    </a>
                                </div>

                                <hr />

                                <p class="search-title">Suggestions</p>

                                <div
                                    class="search-list d-flex flex-column gap-2"
                                >
                                    <div class="search-list-item">
                                        <div
                                            class="list-icon gradient-red-soft"
                                        >
                                            <i
                                                class="material-icons-outlined fs-5"
                                                >event_note</i
                                            >
                                        </div>
                                        <div>
                                            <h5 class="mb-0 search-list-title">
                                                Planning Management
                                            </h5>
                                            <p class="mb-0 search-list-sub">
                                                Trips and assignments tracking
                                            </p>
                                        </div>
                                    </div>

                                    <div class="search-list-item">
                                        <div
                                            class="list-icon gradient-blue-soft"
                                        >
                                            <i
                                                class="material-icons-outlined fs-5"
                                                >groups</i
                                            >
                                        </div>
                                        <div>
                                            <h5 class="mb-0 search-list-title">
                                                Client Database
                                            </h5>
                                            <p class="mb-0 search-list-sub">
                                                Quick search by file
                                            </p>
                                        </div>
                                    </div>

                                    <div class="search-list-item">
                                        <div
                                            class="list-icon gradient-purple-soft"
                                        >
                                            <i
                                                class="material-icons-outlined fs-5"
                                                >analytics</i
                                            >
                                        </div>
                                        <div>
                                            <h5 class="mb-0 search-list-title">
                                                Reports & Statistics
                                            </h5>
                                            <p class="mb-0 search-list-sub">
                                                Global performance overview
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="card-footer text-center bg-transparent border-0 pt-0"
                            >
                                <a href="#" class="btn search-footer-btn w-100">
                                    View all results
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="navbar-nav gap-2 nav-right-links align-items-center">
                <li class="nav-item d-lg-none mobile-search-btn">
                    <a class="nav-link action-circle-btn" href="javascript:;">
                        <i class="material-icons-outlined">search</i>
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle dropdown-toggle-nocaret action-circle-btn help-btn"
                        data-bs-auto-close="outside"
                        data-bs-toggle="dropdown"
                        href="javascript:;"
                    >
                        <i class="material-icons-outlined">help_outline</i>
                    </a>

                    <div
                        class="dropdown-menu dropdown-menu-end shadow modern-dropdown help-dropdown"
                    >
                        <div class="help-head">
                            <div class="help-icon">
                                <i class="material-icons-outlined"
                                    >support_agent</i
                                >
                            </div>
                            <div>
                                <h5 class="help-title mb-1">Need Help?</h5>
                                <p class="help-subtitle mb-0">
                                    Support & assistance
                                </p>
                            </div>
                        </div>

                        <div class="help-message">
                            Dear user, we hope your experience on this platform
                            is comfortable, smooth, and helpful.
                            <br /><br />
                            If you face any
                            issue, get blocked, see an error, or need to ask
                            about anything, I am here to support you.
                            <br /><br /> 
                            If you also have an idea that could make your work easier,
                            improve the platform, or enhance the design and user
                            experience, feel free to share it with me.
                            <br /><br />
                            I will be
                            happy to listen, help, and do my best to meet your
                            needs.
                            <br /><br />
                            Your feedback matters, and this platform is
                            here to make your daily work easier. Contact me
                            directly on WhatsApp whenever you need assistance.
                        </div>

                        <div class="help-info">
                            <div><strong>Name:</strong> Jaouad Braouz</div>
                            <div><strong>Email:</strong> {{ userEmail }}</div>
                            <div><strong>WhatsApp:</strong> 0624984302</div>
                        </div>

                        <a
                            :href="whatsappLink"
                            target="_blank"
                            class="btn whatsapp-btn w-100"
                        >
                            <i class="bx bxl-whatsapp me-2"></i>
                            Contact on WhatsApp
                        </a>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative action-circle-btn"
                        data-bs-auto-close="outside"
                        data-bs-toggle="dropdown"
                        href="javascript:;"
                    >
                        <i class="material-icons-outlined">notifications</i>
                        <span class="badge-notify modern-badge">5</span>
                    </a>

                    <div
                        class="dropdown-menu dropdown-notify dropdown-menu-end shadow modern-dropdown"
                    >
                        <div class="dropdown-header-modern">
                            <div>
                                <h5 class="notify-title-main mb-0">
                                    Notifications
                                </h5>
                                <p class="notify-subtitle mb-0">
                                    Real-time tracking
                                </p>
                            </div>
                        </div>

                        <div class="notify-list modern-notify-list">
                            <a
                                class="dropdown-item notify-item-modern"
                                href="javascript:;"
                            >
                                <div class="notify-avatar gradient-red">PL</div>
                                <div class="notify-content">
                                    <h6 class="notify-item-title">
                                        New planning validated
                                    </h6>
                                    <p class="notify-item-desc">
                                        A planning has been added successfully
                                        today.
                                    </p>
                                    <span class="notify-item-time"
                                        >5 min ago</span
                                    >
                                </div>
                            </a>

                            <a
                                class="dropdown-item notify-item-modern"
                                href="javascript:;"
                            >
                                <div class="notify-avatar gradient-blue">
                                    CL
                                </div>
                                <div class="notify-content">
                                    <h6 class="notify-item-title">
                                        New client assigned
                                    </h6>
                                    <p class="notify-item-desc">
                                        A client has just been linked to an
                                        active planning.
                                    </p>
                                    <span class="notify-item-time">Today</span>
                                </div>
                            </a>

                            <a
                                class="dropdown-item notify-item-modern"
                                href="javascript:;"
                            >
                                <div class="notify-avatar gradient-purple">
                                    RP
                                </div>
                                <div class="notify-content">
                                    <h6 class="notify-item-title">
                                        Report available
                                    </h6>
                                    <p class="notify-item-desc">
                                        Period statistics have been generated.
                                    </p>
                                    <span class="notify-item-time"
                                        >Yesterday</span
                                    >
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        href="javascript:;"
                        class="dropdown-toggle dropdown-toggle-nocaret user-trigger"
                        data-bs-toggle="dropdown"
                    >
                        <div class="user-avatar-modern">
                            <span>{{ userInitials }}</span>
                        </div>

                        <div class="user-meta d-none d-xl-flex">
                            <div class="user-meta-name">{{ userName }}</div>
                            <div class="user-meta-role">Administrator</div>
                        </div>

                        <i
                            class="material-icons-outlined user-arrow d-none d-xl-inline-flex"
                        >
                            expand_more
                        </i>
                    </a>

                    <div
                        class="dropdown-menu dropdown-user dropdown-menu-end shadow modern-dropdown user-dropdown-modern"
                    >
                        <div class="user-dropdown-head">
                            <div class="user-dropdown-avatar">
                                {{ userInitials }}
                            </div>

                            <div>
                                <h5 class="user-name mb-1 fw-bold">
                                    {{ userName }}
                                </h5>
                                <p class="user-email mb-0">{{ userEmail }}</p>
                            </div>
                        </div>

                        <hr class="dropdown-divider" />

                        <Link
                            class="dropdown-item modern-item"
                            :href="route('profile.edit')"
                        >
                            <i class="material-icons-outlined"
                                >person_outline</i
                            >
                            Profile
                        </Link>

                        <Link
                            class="dropdown-item modern-item"
                            :href="route('dashboard')"
                        >
                            <i class="material-icons-outlined">dashboard</i>
                            Dashboard
                        </Link>

                        <Link
                            class="dropdown-item modern-item"
                            :href="route('historique.index')"
                        >
                            <i class="material-icons-outlined">history</i>
                            History
                        </Link>

                        <hr class="dropdown-divider" />

                        <button
                            type="button"
                            class="dropdown-item modern-item logout-item"
                            @click="logout"
                            :disabled="logoutForm.processing"
                        >
                            <i class="material-icons-outlined"
                                >power_settings_new</i
                            >
                            Logout
                        </button>
                    </div>
                </li>
            </ul>
        </nav>
    </header>
</template>

<style scoped>
.top-header {
    position: sticky;
    top: 0;
    z-index: 1030;
    padding: 14px 18px 0 18px;
    background: transparent;
}

.topbar-shell {
    min-height: 78px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 0;
    padding: 14px 18px;
    backdrop-filter: blur(16px);
    box-shadow:
        0 12px 30px rgba(15, 23, 42, 0.06),
        0 3px 10px rgba(15, 23, 42, 0.03);
}

.topbar-left {
    min-width: fit-content;
}

.modern-toggle {
    cursor: pointer;
}

.modern-toggle a,
.action-circle-btn {
    width: 48px;
    height: 48px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e5e7eb;
    color: #111827 !important;
    transition: all 0.2s ease;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.modern-toggle a:hover,
.action-circle-btn:hover {
    transform: translateY(-2px);
    color: #be123c !important;
    border-color: rgba(225, 29, 72, 0.18);
}

.search-modern-wrap {
    position: relative;
}

.search-control-modern {
    height: 52px;
    border-radius: 18px;
    border: 1px solid #dbe2ea;
    background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    padding-left: 52px;
    padding-right: 48px;
    color: #111827;
    font-weight: 600;
}

.search-icon-start {
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 22px;
}

.search-icon-end {
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 21px;
}

.modern-badge {
    position: absolute;
    top: 2px;
    right: 1px;
    min-width: 20px;
    height: 20px;
    border-radius: 999px;
    padding: 0 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    font-weight: 900;
    color: #fff;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: 2px solid #fff;
}

.user-trigger {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px;
    padding-right: 10px;
    border-radius: 18px;
    text-decoration: none;
    background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%);
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.05);
}

.user-avatar-modern,
.user-dropdown-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    font-weight: 900;
}

.user-avatar-modern {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    font-size: 0.95rem;
}

.user-dropdown-avatar {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    font-size: 1.05rem;
}

.user-meta {
    flex-direction: column;
    line-height: 1.15;
}

.user-meta-name {
    color: #111827;
    font-weight: 900;
    font-size: 0.92rem;
}

.user-meta-role,
.user-email {
    color: #6b7280;
    font-size: 0.8rem;
    font-weight: 700;
}

.user-arrow {
    color: #6b7280;
    font-size: 18px;
}

.modern-dropdown {
    border: 1px solid #edf0f6;
    border-radius: 20px;
    padding: 10px;
    min-width: 320px;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(12px);
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.12);
}

.dropdown-notify {
    width: 430px !important;
    max-width: calc(100vw - 30px);
    max-height: 520px;
    overflow: hidden;
}

.modern-notify-list {
    max-height: 390px;
    overflow-y: auto;
    padding: 8px;
}

.dropdown-header-modern,
.user-dropdown-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 8px;
}

.dropdown-header-modern {
    justify-content: space-between;
    border-bottom: 1px solid #edf0f6;
}

.notify-title-main,
.search-title,
.notify-item-title,
.search-list-title {
    color: #111827;
    font-weight: 900;
}

.notify-subtitle,
.notify-item-desc,
.notify-item-time,
.search-list-sub {
    color: #6b7280;
}

.notify-item-modern,
.search-list-item,
.modern-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border-radius: 16px;
    padding: 12px;
    transition: all 0.2s ease;
    width: 100%;
    white-space: normal !important;
}

.notify-item-modern:hover,
.search-list-item:hover,
.modern-item:hover {
    background: #f8fafc;
    color: #be123c;
}

.notify-avatar,
.list-icon {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 900;
    flex-shrink: 0;
}

.notify-content {
    min-width: 0;
    flex: 1;
}

.notify-item-title {
    font-size: 0.95rem;
    line-height: 1.25;
    margin-bottom: 4px;
}

.notify-item-desc {
    font-size: 0.88rem;
    line-height: 1.45;
    margin-bottom: 4px;
    word-break: break-word;
}

.notify-item-time {
    font-size: 0.78rem;
    font-weight: 700;
}

.gradient-red {
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
}

.gradient-blue {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.gradient-purple {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
}

.gradient-red-soft {
    background: rgba(225, 29, 72, 0.12);
    color: #be123c;
}

.gradient-blue-soft {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.gradient-purple-soft {
    background: rgba(124, 58, 237, 0.12);
    color: #6d28d9;
}

.keywords-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 999px;
    padding: 8px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    color: #374151;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.85rem;
}

.search-popup-card {
    border-radius: 22px;
    border: 1px solid #edf0f6;
    box-shadow: 0 24px 44px rgba(15, 23, 42, 0.12);
}

.search-footer-btn {
    min-height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    font-weight: 800;
    border: none;
}

.help-btn {
    color: #2563eb !important;
}

.help-dropdown {
    width: 600px;
    max-width: calc(100vw - 30px);
}

.help-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 10px;
    border-bottom: 1px solid #edf0f6;
}

.help-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    font-size: 26px;
}

.help-title {
    color: #111827;
    font-weight: 900;
}

.help-subtitle {
    color: #6b7280;
    font-weight: 700;
    font-size: 0.85rem;
}

.help-message {
    margin: 12px 8px;
    padding: 14px;
    border-radius: 16px;
    background: linear-gradient(135deg, #eff6ff, #fff);
    color: #475569;
    font-size: 0.9rem;
    line-height: 1.6;
    font-weight: 650;
}

.help-info {
    margin: 8px;
    padding: 12px 14px;
    border-radius: 16px;
    background: #f8fafc;
    color: #334155;
    font-size: 0.88rem;
    line-height: 1.8;
}

.whatsapp-btn {
    min-height: 46px;
    border-radius: 14px;
    border: 0;
    color: #fff;
    font-weight: 900;
    background: linear-gradient(135deg, #22c55e, #16a34a);
}

.whatsapp-btn:hover {
    color: #fff;
    transform: translateY(-2px);
}

.logout-item {
    width: 100%;
    background: transparent;
    border: 0;
    text-align: left;
}

@media (max-width: 1199.98px) {
    .top-header {
        padding: 12px 12px 0 12px;
    }

    .topbar-shell {
        padding: 0;
        border-radius: 10px;
    }
}

@media (max-width: 991.98px) {
    .modern-dropdown {
        min-width: 290px;
    }

    .search-control-modern {
        display: none !important;
    }
}

@media (max-width: 767.98px) {
    .topbar-shell {
        min-height: 70px;
    }

    .modern-toggle a,
    .action-circle-btn {
        width: 44px;
        height: 44px;
        border-radius: 14px;
    }

    .user-avatar-modern {
        width: 42px;
        height: 42px;
        border-radius: 14px;
    }
}
</style>
