<script setup>
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({ layout: AppShell });

const props = defineProps({
    users: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: "" }) },
    roles: { type: Array, default: () => [] },
    drivers: { type: Array, default: () => [] },
    guides: { type: Array, default: () => [] },
    supplierClients: { type: Array, default: () => [] },
    supplierVehicules: { type: Array, default: () => [] },
});

const search = ref(props.filters.search || "");
const showNewRow = ref(false);
const editingId = ref(null);

const rows = computed(() => props.users?.data || []);

const emptyForm = () => ({
    name: "",
    email: "",
    password: "",
    role: "",
    active: true,
    driver_id: "",
    guide_id: "",
    supplier_client_id: "",
    supplier_vehicule_id: "",
});

const form = useForm(emptyForm());
const editForm = useForm(emptyForm());

let searchTimer = null;

watch(search, (value) => {
    clearTimeout(searchTimer);

    searchTimer = setTimeout(() => {
        router.get(
            route("all-users.index"),
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 350);
});

const resetProfileFields = (targetForm) => {
    targetForm.driver_id = "";
    targetForm.guide_id = "";
    targetForm.supplier_client_id = "";
    targetForm.supplier_vehicule_id = "";
};

const openNewRow = () => {
    form.defaults(emptyForm());
    form.reset();
    form.clearErrors();

    showNewRow.value = true;
    editingId.value = null;
};

const cancelNewRow = () => {
    showNewRow.value = false;
    form.reset();
    form.clearErrors();
};

const saveUser = () => {
    form.post(route("all-users.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showNewRow.value = false;
            form.reset();

            Swal.fire({
                icon: "success",
                title: "Succès",
                text: "Utilisateur ajouté avec succès.",
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const startEdit = (user) => {
    showNewRow.value = false;
    editingId.value = user.id;

    editForm.clearErrors();

    editForm.name = user.name || "";
    editForm.email = user.email || "";
    editForm.password = "";
    editForm.role = user.role || "";
    editForm.active = Boolean(user.active);

    editForm.driver_id = user.driver_id || "";
    editForm.guide_id = user.guide_id || "";
    editForm.supplier_client_id = user.supplier_client_id || "";
    editForm.supplier_vehicule_id = user.supplier_vehicule_id || "";
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.clearErrors();
};

const updateUser = (id) => {
    editForm.put(route("all-users.update", id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;

            Swal.fire({
                icon: "success",
                title: "Modifié",
                text: "Utilisateur modifié avec succès.",
                timer: 1800,
                showConfirmButton: false,
            });
        },
    });
};

const destroyUser = (id) => {
    Swal.fire({
        title: "Supprimer cet utilisateur ?",
        text: "Cette action est irréversible.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Oui, supprimer",
        cancelButtonText: "Annuler",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("all-users.destroy", id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: "success",
                        title: "Supprimé",
                        text: "Utilisateur supprimé avec succès.",
                        timer: 1600,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
};

const toggleStatus = (user) => {
    router.patch(
        route("all-users.toggle-status", user.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: "success",
                    title: "Statut modifié",
                    timer: 1300,
                    showConfirmButton: false,
                });
            },
        },
    );
};

const roleLabel = (role) => {
    const labels = {
        admin: "Admin",
        administrateur: "Administrateur",
        guide: "Guide",
        driver: "Chauffeur",
        supplier_client: "Fournisseur client",
        supplier_vehicule: "Fournisseur véhicule",
    };

    return labels[role] || role || "-";
};

const roleClass = (role) => {
    if (role === "admin") return "role-admin";
    if (role === "administrateur") return "role-manager";
    if (role === "driver") return "role-driver";
    if (role === "guide") return "role-guide";
    if (role === "supplier_client") return "role-client";
    if (role === "supplier_vehicule") return "role-vehicule";
    return "role-neutral";
};

const profileOptionsByRole = (role) => {
    if (role === "driver") return props.drivers;
    if (role === "guide") return props.guides;
    if (role === "supplier_client") return props.supplierClients;
    if (role === "supplier_vehicule") return props.supplierVehicules;
    return [];
};

const profileFieldByRole = (role) => {
    if (role === "driver") return "driver_id";
    if (role === "guide") return "guide_id";
    if (role === "supplier_client") return "supplier_client_id";
    if (role === "supplier_vehicule") return "supplier_vehicule_id";
    return null;
};

const needsProfile = (role) => {
    return ["driver", "guide", "supplier_client", "supplier_vehicule"].includes(
        role,
    );
};
</script>

<template>
    <Head title="Gestion utilisateurs" />

    <div class="page-content">
        <div class="container-fluid">
            <div class="hero-card card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                    >
                        <div>
                            <div class="hero-kicker">Administration</div>
                            <h1 class="hero-title">Gestion utilisateurs</h1>
                            <p class="hero-subtitle mb-0">
                                Créer les comptes, attribuer les rôles et lier
                                chaque utilisateur à son profil.
                            </p>
                        </div>

                        <button class="btn btn-add" @click="openNewRow">
                            <i class="bx bx-plus me-1"></i>
                            Nouvel utilisateur
                        </button>
                    </div>
                </div>
            </div>

            <div class="toolbar-card card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="search-box">
                        <i class="bx bx-search"></i>
                        <input
                            v-model="search"
                            type="text"
                            class="form-control"
                            placeholder="Rechercher par nom ou email..."
                        />
                    </div>
                </div>
            </div>

            <div class="users-table-card card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table
                            class="table align-middle table-hover custom-users-table mb-0"
                        >
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Mot de passe</th>
                                    <th>Rôle</th>
                                    <th>Profil lié</th>
                                    <th>Statut</th>
                                    <th>Créé le</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-if="showNewRow" class="new-row">
                                    <td>
                                        <input
                                            v-model="form.name"
                                            type="text"
                                            class="form-control table-input"
                                            placeholder="Nom complet"
                                        />
                                        <small class="text-danger">
                                            {{ form.errors.name }}
                                        </small>
                                    </td>

                                    <td>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="form-control table-input email-input"
                                            placeholder="email@exemple.com"
                                        />
                                        <small class="text-danger">
                                            {{ form.errors.email }}
                                        </small>
                                    </td>

                                    <td>
                                        <input
                                            v-model="form.password"
                                            type="password"
                                            class="form-control table-input"
                                            placeholder="Mot de passe"
                                        />
                                        <small class="text-danger">
                                            {{ form.errors.password }}
                                        </small>
                                    </td>

                                    <td>
                                        <select
                                            v-model="form.role"
                                            class="form-select table-input"
                                            @change="resetProfileFields(form)"
                                        >
                                            <option value="">
                                                Choisir rôle...
                                            </option>
                                            <option
                                                v-for="role in roles"
                                                :key="role"
                                                :value="role"
                                            >
                                                {{ roleLabel(role) }}
                                            </option>
                                        </select>
                                        <small class="text-danger">
                                            {{ form.errors.role }}
                                        </small>
                                    </td>

                                    <td>
                                        <select
                                            v-if="needsProfile(form.role)"
                                            v-model="
                                                form[
                                                    profileFieldByRole(
                                                        form.role,
                                                    )
                                                ]
                                            "
                                            class="form-select table-input profile-input"
                                        >
                                            <option value="">
                                                Choisir profil...
                                            </option>
                                            <option
                                                v-for="item in profileOptionsByRole(
                                                    form.role,
                                                )"
                                                :key="item.id"
                                                :value="item.id"
                                            >
                                                {{ item.name }}
                                            </option>
                                        </select>

                                        <span v-else class="profile-empty">
                                            Aucun profil requis
                                        </span>

                                        <small class="text-danger d-block">
                                            {{
                                                form.errors.driver_id ||
                                                form.errors.guide_id ||
                                                form.errors
                                                    .supplier_client_id ||
                                                form.errors.supplier_vehicule_id
                                            }}
                                        </small>
                                    </td>

                                    <td>
                                        <select
                                            v-model="form.active"
                                            class="form-select table-input status-input"
                                        >
                                            <option :value="true">Actif</option>
                                            <option :value="false">
                                                Inactif
                                            </option>
                                        </select>
                                    </td>

                                    <td>
                                        <span class="text-muted">-</span>
                                    </td>

                                    <td class="actions-cell">
                                        <div class="row-actions">
                                            <button
                                                class="btn btn-save-action btn-sm"
                                                :disabled="form.processing"
                                                @click="saveUser"
                                            >
                                                <span
                                                    v-if="form.processing"
                                                    class="spinner-border spinner-border-sm me-1"
                                                ></span>
                                                Enregistrer
                                            </button>

                                            <button
                                                class="btn btn-cancel-action btn-sm"
                                                @click="cancelNewRow"
                                            >
                                                Annuler
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-for="user in rows" :key="user.id">
                                    <template v-if="editingId === user.id">
                                        <td>
                                            <input
                                                v-model="editForm.name"
                                                type="text"
                                                class="form-control table-input"
                                            />
                                            <small class="text-danger">
                                                {{ editForm.errors.name }}
                                            </small>
                                        </td>

                                        <td>
                                            <input
                                                v-model="editForm.email"
                                                type="email"
                                                class="form-control table-input email-input"
                                            />
                                            <small class="text-danger">
                                                {{ editForm.errors.email }}
                                            </small>
                                        </td>

                                        <td>
                                            <input
                                                v-model="editForm.password"
                                                type="password"
                                                class="form-control table-input"
                                                placeholder="Laisser vide"
                                            />
                                            <small class="text-danger">
                                                {{ editForm.errors.password }}
                                            </small>
                                        </td>

                                        <td>
                                            <select
                                                v-model="editForm.role"
                                                class="form-select table-input"
                                                @change="
                                                    resetProfileFields(editForm)
                                                "
                                            >
                                                <option value="">
                                                    Choisir rôle...
                                                </option>
                                                <option
                                                    v-for="role in roles"
                                                    :key="role"
                                                    :value="role"
                                                >
                                                    {{ roleLabel(role) }}
                                                </option>
                                            </select>
                                            <small class="text-danger">
                                                {{ editForm.errors.role }}
                                            </small>
                                        </td>

                                        <td>
                                            <select
                                                v-if="
                                                    needsProfile(editForm.role)
                                                "
                                                v-model="
                                                    editForm[
                                                        profileFieldByRole(
                                                            editForm.role,
                                                        )
                                                    ]
                                                "
                                                class="form-select table-input profile-input"
                                            >
                                                <option value="">
                                                    Choisir profil...
                                                </option>

                                                <option
                                                    v-if="
                                                        user.linked_profile &&
                                                        user.linked_profile !==
                                                            '-'
                                                    "
                                                    :value="
                                                        editForm[
                                                            profileFieldByRole(
                                                                editForm.role,
                                                            )
                                                        ]
                                                    "
                                                >
                                                    {{ user.linked_profile }}
                                                </option>

                                                <option
                                                    v-for="item in profileOptionsByRole(
                                                        editForm.role,
                                                    )"
                                                    :key="item.id"
                                                    :value="item.id"
                                                >
                                                    {{ item.name }}
                                                </option>
                                            </select>

                                            <span v-else class="profile-empty">
                                                Aucun profil requis
                                            </span>

                                            <small class="text-danger d-block">
                                                {{
                                                    editForm.errors.driver_id ||
                                                    editForm.errors.guide_id ||
                                                    editForm.errors
                                                        .supplier_client_id ||
                                                    editForm.errors
                                                        .supplier_vehicule_id
                                                }}
                                            </small>
                                        </td>

                                        <td>
                                            <select
                                                v-model="editForm.active"
                                                class="form-select table-input status-input"
                                            >
                                                <option :value="true">
                                                    Actif
                                                </option>
                                                <option :value="false">
                                                    Inactif
                                                </option>
                                            </select>
                                        </td>

                                        <td>
                                            {{ user.created_at || "-" }}
                                        </td>

                                        <td class="actions-cell">
                                            <div class="row-actions">
                                                <button
                                                    class="btn btn-save-action btn-sm"
                                                    :disabled="
                                                        editForm.processing
                                                    "
                                                    @click="updateUser(user.id)"
                                                >
                                                    Modifier
                                                </button>

                                                <button
                                                    class="btn btn-cancel-action btn-sm"
                                                    @click="cancelEdit"
                                                >
                                                    Annuler
                                                </button>
                                            </div>
                                        </td>
                                    </template>

                                    <template v-else>
                                        <td>
                                            <div class="user-cell">
                                                <div class="avatar-circle">
                                                    {{
                                                        String(user.name || "U")
                                                            .charAt(0)
                                                            .toUpperCase()
                                                    }}
                                                </div>
                                                <div>
                                                    <div class="user-name">
                                                        {{ user.name }}
                                                    </div>
                                                    <div class="user-id">
                                                        #{{ user.id }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="email-pill">
                                                {{ user.email }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="password-hidden">
                                                ********
                                            </span>
                                        </td>

                                        <td>
                                            <span
                                                class="role-badge"
                                                :class="roleClass(user.role)"
                                            >
                                                {{ roleLabel(user.role) }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="profile-badge">
                                                {{ user.linked_profile || "-" }}
                                            </span>
                                        </td>

                                        <td>
                                            <button
                                                type="button"
                                                class="status-badge"
                                                :class="
                                                    user.active
                                                        ? 'status-active'
                                                        : 'status-inactive'
                                                "
                                                @click="toggleStatus(user)"
                                            >
                                                {{
                                                    user.active
                                                        ? "Actif"
                                                        : "Inactif"
                                                }}
                                            </button>
                                        </td>

                                        <td>
                                            {{ user.created_at || "-" }}
                                        </td>

                                        <td class="actions-cell" style="min-width: 300px;">
                                            <div class="row-actions">
                                                <button
                                                    class="btn btn-edit-action btn-sm"
                                                    @click="startEdit(user)"
                                                >
                                                    <i
                                                        class="bx bx-edit me-1"
                                                    ></i>
                                                    Modifier
                                                </button>

                                                <button
                                                    class="btn btn-delete-action btn-sm"
                                                    @click="
                                                        destroyUser(user.id)
                                                    "
                                                >
                                                    <i
                                                        class="bx bx-trash me-1"
                                                    ></i>
                                                    Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </template>
                                </tr>

                                <tr v-if="rows.length === 0 && !showNewRow">
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

                    <div
                        v-if="users?.links?.length"
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
    min-height: 100vh;
    background:
        radial-gradient(
            circle at top left,
            rgba(220, 38, 38, 0.05),
            transparent 25%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(37, 99, 235, 0.06),
            transparent 25%
        ),
        #f4f6fb;
}

.hero-card {
    border-radius: 26px;
    background:
        radial-gradient(
            circle at 90% 20%,
            rgba(255, 255, 255, 0.2),
            transparent 22%
        ),
        linear-gradient(135deg, #c1121f, #7f1024 48%, #1d4ed8);
    color: #fff;
}

.hero-kicker {
    display: inline-flex;
    padding: 7px 13px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    font-weight: 900;
    font-size: 13px;
    margin-bottom: 10px;
}

.hero-title {
    font-size: 32px;
    font-weight: 950;
    margin: 0;
    color: #fff;
}

.hero-subtitle {
    color: rgba(255, 255, 255, 0.88);
    font-weight: 600;
}

.btn-add {
    min-height: 46px;
    border-radius: 15px;
    border: 0;
    padding: 10px 18px;
    background: #fff;
    color: #b91c1c;
    font-weight: 950;
    box-shadow: 0 16px 30px rgba(0, 0, 0, 0.18);
}

.btn-add:hover {
    background: #dc2626;
    color: #fff;
}

.toolbar-card,
.users-table-card {
    border-radius: 24px;
}

.search-box {
    position: relative;
}

.search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 20px;
}

.search-box input {
    height: 50px;
    border-radius: 16px;
    padding-left: 46px;
    border: 1px solid #e2e8f0;
    font-weight: 700;
}

.table-responsive {
    overflow-x: auto;
}

.custom-users-table {
    min-width: 1450px;
    border-collapse: separate;
    border-spacing: 0;
}

.custom-users-table thead th {
    background: #fff3f4;
    color: #9f101d;
    font-size: 14px;
    font-weight: 950;
    padding: 18px 16px;
    border-bottom: 1px solid #f3d4d7;
    white-space: nowrap;
}

.custom-users-table tbody td {
    padding: 15px 14px;
    border-bottom: 1px solid #eef1f5;
    background: #fff;
    vertical-align: middle;
    min-width: 150px;
    font-weight: 650;
    color: #334155;
}

.new-row td {
    background: #fffafa !important;
    vertical-align: top;
}

.table-input {
    min-width: 160px;
    height: 44px;
    border-radius: 14px;
    border: 1px solid #d9e1ec;
    background: #fff;
    box-shadow: none;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
}

.email-input {
    min-width: 240px;
}

.profile-input {
    min-width: 240px;
}

.status-input {
    min-width: 120px;
}

.table-input:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.avatar-circle {
    width: 44px;
    height: 44px;
    border-radius: 16px;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 950;
    box-shadow: 0 12px 24px rgba(220, 38, 38, 0.18);
}

.user-name {
    font-weight: 950;
    color: #111827;
}

.user-id {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 800;
}

.email-pill {
    display: inline-flex;
    align-items: center;
    padding: 8px 13px;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-weight: 850;
}

.password-hidden {
    color: #94a3b8;
    font-weight: 950;
    letter-spacing: 2px;
}

.role-badge,
.profile-badge,
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 13px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 950;
    white-space: nowrap;
}

.profile-badge {
    background: linear-gradient(135deg, #f8fbff, #eef4ff);
    border: 1px solid #dbe7ff;
    color: #1d4ed8;
}

.profile-empty {
    display: inline-flex;
    padding: 10px 12px;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    color: #94a3b8;
    font-weight: 850;
    white-space: nowrap;
}

.role-admin {
    background: #111827;
    color: #fff;
    border: 1px solid #111827;
}

.role-manager {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.role-driver {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.role-guide {
    background: #faf5ff;
    color: #7e22ce;
    border: 1px solid #e9d5ff;
}

.role-client {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
}

.role-vehicule {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.role-neutral {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.status-badge {
    border: 0;
    cursor: pointer;
}

.status-active {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #bbf7d0;
}

.status-inactive {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}

.actions-cell {
    min-width: 400px;
}

.row-actions {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
}

.btn-save-action {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 950;
    padding: 10px 14px;
}

.btn-edit-action {
    background: #fff7ed;
    color: #c2410c;
    border: 1px solid #fed7aa;
    border-radius: 12px;
    font-weight: 950;
    padding: 10px 14px;
}

.btn-cancel-action {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-weight: 950;
    padding: 10px 14px;
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 950;
    padding: 10px 14px;
}

.btn-danger-red {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}

.btn-delete-action:hover,
.btn-save-action:hover {
    color: #fff;
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 24px;
    }
}
</style>
