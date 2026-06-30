<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    roles: Array,
    permissions: Array,
    permissionGroups: Array,
    users: Array,
});

const page = usePage();

const roleSearch = ref("");
const userSearch = ref("");
const permissionSearch = ref("");
const selectedRoleId = ref(props.roles?.[0]?.id || null);

const canManage = computed(
    () =>
        page.props?.auth?.isSuperAdmin ||
        page.props?.auth?.can?.["roles-permissions.manage"],
);

const selectedRole = computed(() =>
    (props.roles || []).find((role) => role.id === selectedRoleId.value),
);

const filteredRoles = computed(() => {
    const term = roleSearch.value.toLowerCase().trim();
    if (!term) return props.roles || [];

    return (props.roles || []).filter((role) =>
        role.name.toLowerCase().includes(term),
    );
});

const filteredUsers = computed(() => {
    const term = userSearch.value.toLowerCase().trim();
    if (!term) return props.users || [];

    return (props.users || []).filter(
        (user) =>
            user.name.toLowerCase().includes(term) ||
            user.email.toLowerCase().includes(term),
    );
});

const filteredPermissionGroups = computed(() => {
    const term = permissionSearch.value.toLowerCase().trim();
    if (!term) return props.permissionGroups || [];

    return (props.permissionGroups || [])
        .map((group) => ({
            ...group,
            permissions: group.permissions.filter(
                (permission) =>
                    permission.name.toLowerCase().includes(term) ||
                    permission.label.toLowerCase().includes(term) ||
                    group.label.toLowerCase().includes(term),
            ),
        }))
        .filter((group) => group.permissions.length);
});

const roleForm = useForm({
    name: "",
    permissions: [],
});

const permissionForm = useForm({
    name: "",
});

watch(
    selectedRole,
    (role) => {
        if (!role) return;

        roleForm.name = role.name;
        roleForm.permissions = [...(role.permissions || [])];
        roleForm.clearErrors();
    },
    { immediate: true },
);

const roleLabel = (name) =>
    name
        ? name
              .replaceAll("_", " ")
              .replaceAll("-", " ")
              .replace(/\b\w/g, (letter) => letter.toUpperCase())
        : "-";

const notifyError = (fallback = "Une erreur est survenue.") => {
    Swal.fire({
        icon: "error",
        title: "Erreur",
        text: page.props.flash?.error || fallback,
        confirmButtonColor: "#dc2626",
    });
};

const togglePermission = (permission) => {
    if (!canManage.value || selectedRole.value?.locked) return;

    const permissions = new Set(roleForm.permissions);

    if (permissions.has(permission)) {
        permissions.delete(permission);
    } else {
        permissions.add(permission);
    }

    roleForm.permissions = [...permissions];
};

const selectAllGroup = (group) => {
    if (!canManage.value || selectedRole.value?.locked) return;

    roleForm.permissions = [
        ...new Set([
            ...roleForm.permissions,
            ...group.permissions.map((permission) => permission.name),
        ]),
    ];
};

const clearGroup = (group) => {
    if (!canManage.value || selectedRole.value?.locked) return;

    const names = new Set(group.permissions.map((permission) => permission.name));
    roleForm.permissions = roleForm.permissions.filter((name) => !names.has(name));
};

const saveSelectedRole = () => {
    if (!selectedRole.value) return;

    roleForm.put(route("roles-permissions.roles.update", selectedRole.value.id), {
        preserveScroll: true,
        onError: () => notifyError("Impossible de modifier ce rôle."),
    });
};

const createRole = () => {
    Swal.fire({
        title: "Nouveau rôle",
        input: "text",
        inputPlaceholder: "ex: operations_manager",
        showCancelButton: true,
        confirmButtonText: "Créer",
        confirmButtonColor: "#c1121f",
        inputValidator: (value) => {
            if (!value) return "Nom obligatoire";
            if (!/^[a-z0-9_-]+$/.test(value)) {
                return "Utilisez seulement lettres minuscules, chiffres, - ou _";
            }
            return null;
        },
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.post(
            route("roles-permissions.roles.store"),
            { name: result.value, permissions: [] },
            {
                preserveScroll: true,
                onError: () => notifyError("Impossible de créer le rôle."),
            },
        );
    });
};

const deleteRole = (role) => {
    Swal.fire({
        title: `Supprimer ${roleLabel(role.name)} ?`,
        text: "Le rôle doit être vide avant suppression.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Supprimer",
        confirmButtonColor: "#dc2626",
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(route("roles-permissions.roles.destroy", role.id), {
            preserveScroll: true,
            onError: () => notifyError("Impossible de supprimer le rôle."),
        });
    });
};

const savePermission = () => {
    permissionForm.post(route("roles-permissions.permissions.store"), {
        preserveScroll: true,
        onSuccess: () => permissionForm.reset(),
        onError: () => notifyError("Impossible de créer la permission."),
    });
};

const deletePermission = (permission) => {
    Swal.fire({
        title: `Supprimer ${permission.name} ?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Supprimer",
        confirmButtonColor: "#dc2626",
    }).then((result) => {
        if (!result.isConfirmed) return;

        router.delete(
            route("roles-permissions.permissions.destroy", permission.id),
            {
                preserveScroll: true,
                onError: () =>
                    notifyError("Impossible de supprimer la permission."),
            },
        );
    });
};

const assignUserRole = (user, roleName) => {
    router.patch(
        route("roles-permissions.users.assign-role", user.id),
        { role: roleName },
        {
            preserveScroll: true,
            onError: () => notifyError("Impossible de modifier le rôle utilisateur."),
        },
    );
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <div class="permissions-page">
        <section class="hero">
            <div>
                <span class="eyebrow">Administration sécurisée</span>
                <h1>Roles & Permissions</h1>
                <p>
                    Gérez les rôles, les permissions par module et les accès de
                    chaque utilisateur depuis une seule interface.
                </p>
            </div>
            <button v-if="canManage" class="hero-action" @click="createRole">
                <i class="bx bx-plus"></i>
                Nouveau rôle
            </button>
        </section>

        <section class="layout-grid">
            <aside class="roles-panel">
                <div class="panel-head">
                    <div>
                        <span>Roles</span>
                        <strong>{{ roles.length }}</strong>
                    </div>
                    <input v-model="roleSearch" placeholder="Rechercher..." />
                </div>

                <button
                    v-for="role in filteredRoles"
                    :key="role.id"
                    class="role-item"
                    :class="{ active: selectedRoleId === role.id }"
                    @click="selectedRoleId = role.id"
                >
                    <span>
                        <i class="bx bx-shield-quarter"></i>
                        {{ roleLabel(role.name) }}
                    </span>
                    <small>{{ role.users_count }} user(s)</small>
                </button>
            </aside>

            <main class="permissions-panel">
                <div class="editor-head">
                    <div>
                        <span>Rôle sélectionné</span>
                        <h2>{{ roleLabel(selectedRole?.name) }}</h2>
                        <p v-if="selectedRole?.locked">
                            Rôle protégé: tous les accès sont accordés
                            automatiquement.
                        </p>
                    </div>

                    <div class="editor-actions">
                        <button
                            v-if="canManage && selectedRole && !selectedRole.locked"
                            class="btn-danger-soft"
                            @click="deleteRole(selectedRole)"
                        >
                            Supprimer
                        </button>
                        <button
                            v-if="canManage"
                            class="btn-primary"
                            :disabled="roleForm.processing || selectedRole?.locked"
                            @click="saveSelectedRole"
                        >
                            Enregistrer
                        </button>
                    </div>
                </div>

                <div class="role-name-row">
                    <label>Nom du rôle</label>
                    <input
                        v-model="roleForm.name"
                        :disabled="!canManage || selectedRole?.locked"
                    />
                    <small>{{ roleForm.errors.name }}</small>
                </div>

                <div class="permission-search">
                    <i class="bx bx-search"></i>
                    <input
                        v-model="permissionSearch"
                        placeholder="Rechercher module, action ou permission..."
                    />
                </div>

                <div class="permission-groups">
                    <article
                        v-for="group in filteredPermissionGroups"
                        :key="group.key"
                        class="permission-group"
                    >
                        <header>
                            <div>
                                <span>{{ group.label }}</span>
                                <strong>{{ group.key }}</strong>
                            </div>
                            <div class="mini-actions">
                                <button @click="selectAllGroup(group)">Tout</button>
                                <button @click="clearGroup(group)">Aucun</button>
                            </div>
                        </header>

                        <div class="permission-checks">
                            <button
                                v-for="permission in group.permissions"
                                :key="permission.name"
                                type="button"
                                class="permission-pill"
                                :class="{
                                    checked: roleForm.permissions.includes(
                                        permission.name,
                                    ),
                                }"
                                @click="togglePermission(permission.name)"
                            >
                                <i
                                    :class="
                                        roleForm.permissions.includes(
                                            permission.name,
                                        )
                                            ? 'bx bx-check-circle'
                                            : 'bx bx-circle'
                                    "
                                ></i>
                                <span>{{ permission.label }}</span>
                            </button>
                        </div>
                    </article>
                </div>
            </main>
        </section>

        <section class="bottom-grid">
            <article class="users-panel">
                <div class="section-title">
                    <div>
                        <span>Affectation utilisateurs</span>
                        <h2>Rôle par utilisateur</h2>
                    </div>
                    <input v-model="userSearch" placeholder="Nom ou email..." />
                </div>

                <div class="users-list">
                    <div
                        v-for="user in filteredUsers"
                        :key="user.id"
                        class="user-row"
                    >
                        <div>
                            <strong>{{ user.name }}</strong>
                            <span>{{ user.email }}</span>
                        </div>
                        <select
                            :value="user.roles?.[0] || ''"
                            :disabled="!canManage"
                            @change="assignUserRole(user, $event.target.value)"
                        >
                            <option value="">Sans rôle</option>
                            <option
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.name"
                            >
                                {{ roleLabel(role.name) }}
                            </option>
                        </select>
                    </div>
                </div>
            </article>

            <article class="custom-permissions">
                <div class="section-title">
                    <div>
                        <span>Permissions</span>
                        <h2>Permissions personnalisées</h2>
                    </div>
                </div>

                <form class="permission-form" @submit.prevent="savePermission">
                    <input
                        v-model="permissionForm.name"
                        :disabled="!canManage"
                        placeholder="module.action"
                    />
                    <button :disabled="!canManage || permissionForm.processing">
                        Ajouter
                    </button>
                    <small>{{ permissionForm.errors.name }}</small>
                </form>

                <div class="permission-list">
                    <div
                        v-for="permission in permissions"
                        :key="permission.id"
                        class="permission-row"
                    >
                        <span>{{ permission.name }}</span>
                        <button
                            v-if="canManage"
                            type="button"
                            @click="deletePermission(permission)"
                        >
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>

<style scoped>
.permissions-page {
    display: grid;
    gap: 22px;
}

.hero {
    align-items: center;
    background: linear-gradient(135deg, #101827 0%, #981b28 52%, #ef4444 100%);
    border-radius: 8px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    overflow: hidden;
    padding: 28px;
    position: relative;
}

.hero::after {
    background: rgba(255, 255, 255, 0.16);
    border-radius: 999px;
    content: "";
    height: 180px;
    position: absolute;
    right: 110px;
    top: -70px;
    width: 180px;
}

.eyebrow,
.section-title span,
.editor-head span,
.role-name-row label,
.panel-head span {
    color: #7b8798;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.hero .eyebrow,
.hero p {
    color: rgba(255, 255, 255, 0.78);
}

.hero h1 {
    font-size: 36px;
    font-weight: 950;
    margin: 8px 0 4px;
}

.hero-action,
.btn-primary {
    background: #101827;
    border: 0;
    border-radius: 8px;
    color: #fff;
    font-weight: 900;
    padding: 14px 18px;
}

.layout-grid {
    display: grid;
    gap: 18px;
    grid-template-columns: 310px minmax(0, 1fr);
}

.roles-panel,
.permissions-panel,
.users-panel,
.custom-permissions {
    background: #fff;
    border: 1px solid #e6ebf2;
    border-radius: 8px;
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
}

.roles-panel {
    padding: 16px;
}

.panel-head,
.section-title,
.editor-head {
    align-items: center;
    display: flex;
    gap: 14px;
    justify-content: space-between;
}

input,
select {
    background: #f8fafc;
    border: 1px solid #dbe3ee;
    border-radius: 8px;
    color: #111827;
    font-weight: 750;
    min-height: 44px;
    padding: 0 14px;
    width: 100%;
}

.panel-head input {
    max-width: 160px;
}

.role-item {
    align-items: center;
    background: #f8fafc;
    border: 1px solid transparent;
    border-radius: 8px;
    color: #111827;
    display: flex;
    justify-content: space-between;
    margin-top: 10px;
    padding: 14px;
    text-align: left;
    width: 100%;
}

.role-item span {
    align-items: center;
    display: flex;
    font-weight: 900;
    gap: 10px;
}

.role-item small {
    color: #7b8798;
    font-weight: 800;
}

.role-item.active {
    background: #fff1f2;
    border-color: #fecdd3;
    color: #be123c;
}

.permissions-panel {
    padding: 22px;
}

.editor-head h2,
.section-title h2 {
    font-size: 24px;
    font-weight: 950;
    margin: 2px 0;
}

.editor-head p {
    color: #64748b;
    font-weight: 750;
    margin: 0;
}

.editor-actions {
    display: flex;
    gap: 10px;
}

.btn-danger-soft {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    border-radius: 8px;
    color: #be123c;
    font-weight: 900;
    padding: 12px 16px;
}

.role-name-row {
    display: grid;
    gap: 8px;
    margin: 18px 0;
}

.role-name-row small,
.permission-form small {
    color: #dc2626;
    font-weight: 800;
}

.permission-search {
    align-items: center;
    display: flex;
    gap: 10px;
    margin-bottom: 16px;
}

.permission-search i {
    color: #94a3b8;
    font-size: 20px;
}

.permission-groups {
    display: grid;
    gap: 14px;
}

.permission-group {
    background: #fbfdff;
    border: 1px solid #e6ebf2;
    border-radius: 8px;
    padding: 16px;
}

.permission-group header {
    align-items: center;
    display: flex;
    justify-content: space-between;
}

.permission-group header span {
    display: block;
    font-size: 18px;
    font-weight: 950;
}

.permission-group header strong {
    color: #94a3b8;
    font-size: 12px;
}

.mini-actions {
    display: flex;
    gap: 8px;
}

.mini-actions button {
    background: #fff;
    border: 1px solid #dbe3ee;
    border-radius: 8px;
    color: #475569;
    font-weight: 900;
    padding: 8px 11px;
}

.permission-checks {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.permission-pill {
    align-items: center;
    background: #fff;
    border: 1px solid #dbe3ee;
    border-radius: 999px;
    color: #475569;
    display: flex;
    font-weight: 850;
    gap: 8px;
    padding: 9px 12px;
}

.permission-pill.checked {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}

.bottom-grid {
    display: grid;
    gap: 18px;
    grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.6fr);
}

.users-panel,
.custom-permissions {
    padding: 20px;
}

.section-title input {
    max-width: 300px;
}

.users-list,
.permission-list {
    display: grid;
    gap: 10px;
    margin-top: 16px;
    max-height: 520px;
    overflow: auto;
}

.user-row,
.permission-row {
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e6ebf2;
    border-radius: 8px;
    display: grid;
    gap: 14px;
    grid-template-columns: minmax(0, 1fr) 240px;
    padding: 12px;
}

.user-row strong,
.permission-row span {
    color: #111827;
    display: block;
    font-weight: 950;
}

.user-row span {
    color: #64748b;
    font-weight: 750;
}

.permission-form {
    display: grid;
    gap: 10px;
    grid-template-columns: minmax(0, 1fr) 110px;
    margin-top: 16px;
}

.permission-form button {
    background: #c1121f;
    border: 0;
    border-radius: 8px;
    color: #fff;
    font-weight: 900;
}

.permission-row {
    grid-template-columns: minmax(0, 1fr) 44px;
}

.permission-row button {
    background: #fff1f2;
    border: 0;
    border-radius: 8px;
    color: #dc2626;
    height: 40px;
}

@media (max-width: 1180px) {
    .layout-grid,
    .bottom-grid {
        grid-template-columns: 1fr;
    }
}
</style>
