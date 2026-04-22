<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    users: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
        }),
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const deleteUser = (id) => {
    if (!confirm("Voulez-vous vraiment supprimer cet utilisateur ?")) return;

    router.delete(route("users.destroy", id), {
        preserveScroll: true,
    });
};

const toggleStatus = (id) => {
    router.patch(
        route("users.toggle-status", id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const roleBadgeClass = (role) => {
    if (role === "admin") return "role-admin";
    if (role === "administrateur") return "role-administrateur";
    if (role === "driver") return "role-driver";
    if (role === "supplier") return "role-supplier";
    if (role === "guide") return "role-guide";
    return "role-default";
};
</script>

<template>
    <Head title="Utilisateurs" />

    <div class="page-content">
        <div class="container-fluid">
            <!-- Flash -->
            <div
                v-if="$page.props.flash?.success"
                class="alert alert-success rounded-4 shadow-sm mb-4"
            >
                <i class="bx bx-check-circle me-1"></i>
                {{ $page.props.flash.success }}
            </div>

            <div
                v-if="$page.props.flash?.error"
                class="alert alert-danger rounded-4 shadow-sm mb-4"
            >
                <i class="bx bx-error-circle me-1"></i>
                {{ $page.props.flash.error }}
            </div>

            <!-- Hero -->
            <div
                class="users-hero card border-0 shadow-lg mb-4 overflow-hidden"
            >
                <div class="card-body p-4 p-lg-5">
                    <div
                        class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3"
                    >
                        <div>
                            <h1 class="users-title mb-2">
                                Gestion des utilisateurs
                            </h1>
                            <p class="users-subtitle mb-0">
                                Gérez les comptes, les rôles et l’activation des
                                utilisateurs.
                            </p>
                        </div>

                        <div class="hero-actions">
                            <Link
                                :href="route('users.create')"
                                class="btn btn-add-user"
                            >
                                <i class="bx bx-user-plus me-2"></i>
                                Nouvel utilisateur
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-4 users-main-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table align-middle table-hover mb-0 custom-users-table"
                        >
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th>Profil lié</th>
                                    <th>Date création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="user in users.data" :key="user.id">
                                    <td>
                                        <span class="id-badge"
                                            >#{{ user.id }}</span
                                        >
                                    </td>

                                    <td>
                                        <div class="user-name-cell">
                                            <div class="user-avatar-sm">
                                                {{
                                                    String(user.name || "U")
                                                        .slice(0, 2)
                                                        .toUpperCase()
                                                }}
                                            </div>
                                            <div>
                                                <div class="user-name">
                                                    {{ user.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="user-email">{{
                                            user.email
                                        }}</span>
                                    </td>

                                    <td>
                                        <span
                                            class="role-badge"
                                            :class="roleBadgeClass(user.role)"
                                        >
                                            {{ user.role || "-" }}
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="
                                                user.active
                                                    ? 'status-active'
                                                    : 'status-inactive'
                                            "
                                        >
                                            <i
                                                :class="
                                                    user.active
                                                        ? 'bx bx-check-circle'
                                                        : 'bx bx-x-circle'
                                                "
                                                class="me-1"
                                            ></i>
                                            {{
                                                user.active
                                                    ? "Actif"
                                                    : "Inactif"
                                            }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="linked-profile-badge">
                                            {{ user.linked_profile || "-" }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="date-badge">
                                            {{ user.created_at || "-" }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="actions-wrap">
                                            <Link
                                                :href="
                                                    route('users.edit', user.id)
                                                "
                                                class="btn btn-action-edit btn-sm"
                                            >
                                                <i
                                                    class="bx bx-edit-alt me-1"
                                                ></i>
                                                Éditer
                                            </Link>

                                            <button
                                                type="button"
                                                class="btn btn-action-toggle btn-sm"
                                                @click="toggleStatus(user.id)"
                                            >
                                                <i
                                                    :class="
                                                        user.active
                                                            ? 'bx bx-block me-1'
                                                            : 'bx bx-check me-1'
                                                    "
                                                ></i>
                                                {{
                                                    user.active
                                                        ? "Désactiver"
                                                        : "Activer"
                                                }}
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-action-delete btn-sm"
                                                @click="deleteUser(user.id)"
                                            >
                                                <i class="bx bx-trash me-1"></i>
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="users.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="text-center py-5 text-muted"
                                    >
                                        Aucun utilisateur trouvé.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="users.links?.length"
                        class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top"
                    >
                        <div class="text-muted small">
                            Page {{ users.current_page }} /
                            {{ users.last_page }}
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <Link
                                v-for="(link, index) in users.links"
                                :key="index"
                                :href="link.url || ''"
                                v-html="link.label"
                                class="btn btn-sm"
                                :class="
                                    link.active
                                        ? 'btn-danger-red'
                                        : 'btn-outline-secondary'
                                "
                                :disabled="!link.url"
                                preserve-scroll
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-content {
    background:
        radial-gradient(
            circle at top left,
            rgba(193, 18, 31, 0.06),
            transparent 18%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(29, 78, 216, 0.06),
            transparent 18%
        ),
        #f4f6fb;
    min-height: 100vh;
}

.users-hero {
    border-radius: 28px;
    background: linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
    color: white;
    box-shadow: 0 20px 45px rgba(127, 16, 36, 0.18);
}

.users-title {
    font-size: 2.1rem;
    font-weight: 900;
    margin: 0;
    color: #fff;
    letter-spacing: -0.03em;
}

.users-subtitle {
    color: rgba(255, 255, 255, 0.86);
    font-size: 1rem;
}

.btn-add-user {
    min-height: 50px;
    border-radius: 16px;
    padding: 0 22px;
    border: none;
    color: #fff;
    font-weight: 800;
    background: rgba(255, 255, 255, 0.16);
    backdrop-filter: blur(10px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}

.btn-add-user:hover {
    color: #fff;
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.22);
}

.users-main-card {
    overflow: hidden;
    border-radius: 28px !important;
    background: rgba(255, 255, 255, 0.94);
    backdrop-filter: blur(14px);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
}

.custom-users-table thead th {
    background: #f8fafc;
    font-size: 0.86rem;
    font-weight: 800;
    color: #64748b;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    padding: 16px 14px;
}

.custom-users-table tbody td {
    vertical-align: middle;
    padding: 16px 14px;
    font-size: 0.92rem;
    border-color: #eef2f7;
}

.custom-users-table tbody tr {
    transition: background 0.22s ease;
}

.custom-users-table tbody tr:hover {
    background: rgba(248, 250, 252, 0.8);
}

.id-badge,
.linked-profile-badge,
.date-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 14px;
    font-weight: 700;
    background: #f8fafc;
    color: #334155;
    border: 1px solid #e2e8f0;
}

.user-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar-sm {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #d51024 0%, #8f1230 55%, #2a56d9 100%);
    color: #fff;
    font-size: 0.85rem;
    font-weight: 900;
    box-shadow: 0 10px 20px rgba(42, 86, 217, 0.15);
}

.user-name {
    font-weight: 900;
    color: #0f172a;
}

.user-email {
    color: #475569;
    font-weight: 600;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 118px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 800;
    text-transform: uppercase;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
}

.role-admin {
    background: rgba(220, 38, 38, 0.12);
    color: #b91c1c;
}

.role-administrateur {
    background: rgba(37, 99, 235, 0.12);
    color: #1d4ed8;
}

.role-driver {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.role-supplier {
    background: rgba(249, 115, 22, 0.12);
    color: #c2410c;
}

.role-guide {
    background: rgba(124, 58, 237, 0.12);
    color: #6d28d9;
}

.role-default {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 800;
    font-size: 0.82rem;
}

.status-active {
    background: rgba(22, 163, 74, 0.12);
    color: #15803d;
}

.status-inactive {
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
}

.actions-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.btn-action-edit,
.btn-action-toggle,
.btn-action-delete {
    min-height: 38px;
    border-radius: 12px;
    font-weight: 800;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition:
        transform 0.22s ease,
        box-shadow 0.22s ease,
        opacity 0.22s ease;
}

.btn-action-edit:hover,
.btn-action-toggle:hover,
.btn-action-delete:hover {
    transform: translateY(-2px);
}

.btn-action-edit {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    box-shadow: 0 12px 24px rgba(29, 78, 216, 0.22);
}

.btn-action-edit:hover {
    color: #fff;
}

.btn-action-toggle {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
    box-shadow: 0 12px 24px rgba(217, 119, 6, 0.22);
}

.btn-action-toggle:hover {
    color: #fff;
}

.btn-action-delete {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    box-shadow: 0 12px 24px rgba(220, 38, 38, 0.22);
}

.btn-action-delete:hover {
    color: #fff;
}

.btn-danger-red {
    background: linear-gradient(135deg, #d51024 0%, #8f1230 52%, #2a56d9 100%);
    color: #fff;
    border: none;
    box-shadow: 0 14px 26px rgba(143, 18, 48, 0.18);
}

.btn-danger-red:hover {
    color: #fff;
}

@media (max-width: 991.98px) {
    .actions-wrap {
        min-width: 200px;
    }
}

@media (max-width: 768px) {
    .users-title {
        font-size: 1.6rem;
    }

    .user-name-cell {
        min-width: 180px;
    }

    .role-badge {
        min-width: 95px;
    }
}
</style>
