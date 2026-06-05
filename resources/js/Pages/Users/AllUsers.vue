<script setup>
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";

import { computed, ref, watch, onMounted } from "vue";

import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
    drivers: Array,
    guides: Array,
    supplierClients: Array,
    supplierVehicules: Array,
});

const page = usePage();

onMounted(() => {
    if (page.props.flash?.success) {
        Swal.fire({
            icon: "success",
            title: "Success",
            text: page.props.flash.success,
            confirmButtonColor: "#16a34a",
        });
    }

    if (page.props.flash?.error) {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: page.props.flash.error,
            confirmButtonColor: "#dc2626",
        });
    }
});

const search = ref(props.filters?.search || "");
const selectedRole = ref(props.filters?.role || "");
const selectedStatus = ref(props.filters?.status || "");

const rows = computed(() => props.users?.data || []);

const totalUsers = computed(() => props.users?.total || 0);

const activeUsers = computed(() => rows.value.filter((u) => u.active).length);

const inactiveUsers = computed(
    () => rows.value.filter((u) => !u.active).length,
);

const adminUsers = computed(
    () => rows.value.filter((u) => u.role === "admin").length,
);

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

const showNewRow = ref(false);
const editingId = ref(null);

const applyFilters = () => {
    router.get(
        route("all-users.index"),
        {
            search: search.value,
            role: selectedRole.value,
            status: selectedStatus.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

let filterTimer = null;

watch([search, selectedRole, selectedStatus], () => {
    clearTimeout(filterTimer);

    filterTimer = setTimeout(() => {
        applyFilters();
    }, 350);
});

const clearFilters = () => {
    search.value = "";
    selectedRole.value = "";
    selectedStatus.value = "";

    applyFilters();
};

const roleLabel = (role) => {
    const labels = {
        admin: "Administrator",
        administrateur: "Manager",
        guide: "Guide",
        driver: "Driver",
        supplier_client: "Client Supplier",
        supplier_vehicule: "Vehicle Supplier",
    };

    return labels[role] || role;
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

const roleIcon = (role) => {
    if (role === "admin") return "bx bx-crown";
    if (role === "administrateur") return "bx bx-shield-quarter";
    if (role === "driver") return "bx bx-car";
    if (role === "guide") return "bx bx-map";
    if (role === "supplier_client") return "bx bx-briefcase";
    if (role === "supplier_vehicule") return "bx bx-bus";

    return "bx bx-user";
};

const resetProfileFields = (targetForm) => {
    targetForm.driver_id = "";
    targetForm.guide_id = "";
    targetForm.supplier_client_id = "";
    targetForm.supplier_vehicule_id = "";
};

const needsProfile = (role) => {
    return ["driver", "guide", "supplier_client", "supplier_vehicule"].includes(
        role,
    );
};

const profileFieldByRole = (role) => {
    if (role === "driver") return "driver_id";
    if (role === "guide") return "guide_id";
    if (role === "supplier_client") return "supplier_client_id";
    if (role === "supplier_vehicule") return "supplier_vehicule_id";

    return null;
};

const profileOptionsByRole = (role) => {
    if (role === "driver") return props.drivers;
    if (role === "guide") return props.guides;
    if (role === "supplier_client") return props.supplierClients;
    if (role === "supplier_vehicule") return props.supplierVehicules;

    return [];
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
        },

        onError: () => {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: page.props.flash?.error || "Unexpected error occurred.",
                confirmButtonColor: "#dc2626",
            });
        },
    });
};

const startEdit = (user) => {
    showNewRow.value = false;

    editingId.value = user.id;

    editForm.name = user.name || "";
    editForm.email = user.email || "";
    editForm.password = "";
    editForm.role = user.role || "";
    editForm.active = !!user.active;

    editForm.driver_id = user.driver_id || "";
    editForm.guide_id = user.guide_id || "";
    editForm.supplier_client_id = user.supplier_client_id || "";

    editForm.supplier_vehicule_id = user.supplier_vehicule_id || "";
};

const cancelEdit = () => {
    editingId.value = null;

    editForm.reset();
};

const updateUser = (id) => {
    editForm.put(route("all-users.update", id), {
        preserveScroll: true,

        onSuccess: () => {
            editingId.value = null;
        },

        onError: () => {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: page.props.flash?.error || "Unexpected error occurred.",
                confirmButtonColor: "#dc2626",
            });
        },
    });
};

const destroyUser = (id) => {
    Swal.fire({
        title: "Delete user?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes delete",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("all-users.destroy", id), {
                preserveScroll: true,

                onError: () => {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text:
                            page.props.flash?.error ||
                            "Unexpected error occurred.",
                        confirmButtonColor: "#dc2626",
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

            onError: () => {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text:
                        page.props.flash?.error || "Unexpected error occurred.",
                    confirmButtonColor: "#dc2626",
                });
            },
        },
    );
};
</script>
<template>
    <Head title="Users Management" />

    <div class="users-page">
        <div class="container-fluid">
            <div class="hero-card">
                <div class="hero-left">
                    <div class="hero-icon">
                        <i class="bx bx-group"></i>
                    </div>

                    <div>
                        <div class="hero-chip">Administration</div>
                        <h1>Users Management</h1>
                        <p>
                            Manage accounts, permissions, status, and linked
                            operational profiles.
                        </p>
                    </div>
                </div>

                <button class="btn-new-user" @click="openNewRow">
                    <i class="bx bx-plus"></i>
                    New User
                </button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon stat-blue">
                        <i class="bx bx-user"></i>
                    </div>
                    <div>
                        <span>Total Users</span>
                        <strong>{{ totalUsers }}</strong>
                        <small>All accounts</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-green">
                        <i class="bx bx-check-circle"></i>
                    </div>
                    <div>
                        <span>Active Users</span>
                        <strong>{{ activeUsers }}</strong>
                        <small>Active this page</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-red">
                        <i class="bx bx-x-circle"></i>
                    </div>
                    <div>
                        <span>Inactive Users</span>
                        <strong>{{ inactiveUsers }}</strong>
                        <small>Inactive this page</small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-purple">
                        <i class="bx bx-crown"></i>
                    </div>
                    <div>
                        <span>Administrators</span>
                        <strong>{{ adminUsers }}</strong>
                        <small>Admin this page</small>
                    </div>
                </div>
            </div>

            <div class="users-panel">
                <div class="filters-bar">
                    <div class="filter-search">
                        <i class="bx bx-search"></i>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name or email..."
                        />
                    </div>

                    <div class="filter-select">
                        <i class="bx bx-user-check"></i>
                        <select v-model="selectedRole">
                            <option value="">Filter by role</option>
                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ roleLabel(role) }}
                            </option>
                        </select>
                    </div>

                    <div class="filter-select">
                        <i class="bx bx-slider-alt"></i>
                        <select v-model="selectedStatus">
                            <option value="">Filter by status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button class="btn-clear" @click="clearFilters">
                        <i class="bx bx-refresh"></i>
                        Clear
                    </button>

                    <button class="btn-apply" @click="applyFilters">
                        <i class="bx bx-search"></i>
                        Apply Filters
                    </button>
                </div>

                <div class="table-wrap">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Password</th>
                                <th>Role</th>
                                <th>Mailbox</th>
                                <th>Linked Profile</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-if="showNewRow" class="edit-row">
                                <td>
                                    <input
                                        v-model="form.name"
                                        class="table-input"
                                        placeholder="Full name"
                                    />
                                    <small>{{ form.errors.name }}</small>
                                </td>

                                <td>
                                    <input
                                        v-model="form.email"
                                        class="table-input input-email"
                                        placeholder="email@example.com"
                                    />
                                    <small>{{ form.errors.email }}</small>
                                </td>

                                <td>
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        class="table-input"
                                        placeholder="Password"
                                    />
                                    <small>{{ form.errors.password }}</small>
                                </td>

                                <td>
                                    <select
                                        v-model="form.role"
                                        class="table-input"
                                        @change="resetProfileFields(form)"
                                    >
                                        <option value="">Select role...</option>
                                        <option
                                            v-for="role in roles"
                                            :key="role"
                                            :value="role"
                                        >
                                            {{ roleLabel(role) }}
                                        </option>
                                    </select>
                                    <small>{{ form.errors.role }}</small>
                                </td>

                                <td>
                                    <span class="empty-profile">Profile only</span>
                                </td>

                                <td>
                                    <select
                                        v-if="needsProfile(form.role)"
                                        v-model="
                                            form[profileFieldByRole(form.role)]
                                        "
                                        class="table-input input-email"
                                    >
                                        <option value="">
                                            Select profile...
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

                                    <span v-else class="empty-profile"
                                        >No profile</span
                                    >

                                    <small>
                                        {{
                                            form.errors.driver_id ||
                                            form.errors.guide_id ||
                                            form.errors.supplier_client_id ||
                                            form.errors.supplier_vehicule_id
                                        }}
                                    </small>
                                </td>

                                <td>
                                    <select
                                        v-model="form.active"
                                        class="table-input input-status"
                                    >
                                        <option :value="true">Active</option>
                                        <option :value="false">Inactive</option>
                                    </select>
                                </td>

                                <td>-</td>

                                <td>
                                    <div class="actions">
                                        <button
                                            class="btn-save"
                                            :disabled="form.processing"
                                            @click="saveUser"
                                        >
                                            Save
                                        </button>

                                        <button
                                            class="btn-cancel"
                                            @click="cancelNewRow"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="user in rows" :key="user.id">
                                <template v-if="editingId === user.id">
                                    <td>
                                        <input
                                            v-model="editForm.name"
                                            class="table-input"
                                        />
                                        <small>{{
                                            editForm.errors.name
                                        }}</small>
                                    </td>

                                    <td>
                                        <input
                                            v-model="editForm.email"
                                            class="table-input input-email"
                                        />
                                        <small>{{
                                            editForm.errors.email
                                        }}</small>
                                    </td>

                                    <td>
                                        <input
                                            v-model="editForm.password"
                                            type="password"
                                            class="table-input"
                                            placeholder="Leave empty"
                                        />
                                        <small>{{
                                            editForm.errors.password
                                        }}</small>
                                    </td>

                                    <td>
                                        <select
                                            v-model="editForm.role"
                                            class="table-input"
                                            @change="
                                                resetProfileFields(editForm)
                                            "
                                        >
                                            <option value="">
                                                Select role...
                                            </option>
                                            <option
                                                v-for="role in roles"
                                                :key="role"
                                                :value="role"
                                            >
                                                {{ roleLabel(role) }}
                                            </option>
                                        </select>
                                        <small>{{
                                            editForm.errors.role
                                        }}</small>
                                    </td>

                                    <td>
                                        <span class="empty-profile">Profile only</span>
                                    </td>

                                    <td>
                                        <select
                                            v-if="needsProfile(editForm.role)"
                                            v-model="
                                                editForm[
                                                    profileFieldByRole(
                                                        editForm.role,
                                                    )
                                                ]
                                            "
                                            class="table-input input-email"
                                        >
                                            <option value="">
                                                Select profile...
                                            </option>

                                            <option
                                                v-if="
                                                    user.linked_profile &&
                                                    user.linked_profile !== '-'
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

                                        <span v-else class="empty-profile"
                                            >No profile</span
                                        >

                                        <small>
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
                                            class="table-input input-status"
                                        >
                                            <option :value="true">
                                                Active
                                            </option>
                                            <option :value="false">
                                                Inactive
                                            </option>
                                        </select>
                                    </td>

                                    <td>{{ user.created_at || "-" }}</td>

                                    <td>
                                        <div class="actions">
                                            <button
                                                class="btn-save"
                                                @click="updateUser(user.id)"
                                            >
                                                Update
                                            </button>

                                            <button
                                                class="btn-cancel"
                                                @click="cancelEdit"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </td>
                                </template>

                                <template v-else>
                                    <td>
                                        <div class="user-cell">
                                            <div class="avatar">
                                                {{
                                                    String(user.name || "U")
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </div>

                                            <div>
                                                <strong>{{ user.name }}</strong>
                                                <span>#{{ user.id }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div
                                            class="email-pill"
                                            :title="user.email"
                                        >
                                            <i class="bx bx-envelope"></i>
                                            <span>{{ user.email }}</span>
                                            <i class="bx bx-copy copy-icon"></i>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="password-cell">
                                            <span>••••••••</span>
                                            <i class="bx bx-low-vision"></i>
                                        </div>
                                    </td>

                                    <td>
                                        <span
                                            class="role-badge"
                                            :class="roleClass(user.role)"
                                        >
                                            <i :class="roleIcon(user.role)"></i>
                                            {{ roleLabel(user.role) }}
                                        </span>
                                    </td>

                                    <td>
                                        <div
                                            class="mail-status"
                                            :class="{
                                                enabled: user.mail_integrate,
                                            }"
                                        >
                                            <i
                                                :class="
                                                    user.mail_integrate
                                                        ? 'bx bx-envelope'
                                                        : 'bx bx-envelope-open'
                                                "
                                            ></i>
                                            <div>
                                                <strong>
                                                    {{
                                                        user.mail_integrate
                                                            ? 'Integrated'
                                                            : 'Not integrated'
                                                    }}
                                                </strong>
                                                <span>
                                                    {{
                                                        user.mail_integration_login ||
                                                        '-'
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="profile-pill">
                                            <i class="bx bx-user"></i>
                                            {{ user.linked_profile || "-" }}
                                        </span>
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="status-pill"
                                            :class="
                                                user.active
                                                    ? 'active'
                                                    : 'inactive'
                                            "
                                            @click="toggleStatus(user)"
                                        >
                                            <span></span>
                                            {{
                                                user.active
                                                    ? "Active"
                                                    : "Inactive"
                                            }}
                                        </button>
                                    </td>

                                    <td>
                                        <div class="date-cell">
                                            <i class="bx bx-calendar"></i>
                                            <span>{{
                                                user.created_at || "-"
                                            }}</span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="actions">
                                            <button
                                                class="btn-edit"
                                                @click="startEdit(user)"
                                            >
                                                <i class="bx bx-edit"></i>
                                                Edit
                                            </button>

                                            <button
                                                class="btn-delete"
                                                @click="destroyUser(user.id)"
                                            >
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </template>
                            </tr>

                            <tr v-if="rows.length === 0 && !showNewRow">
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bx bx-user-x"></i>
                                        <h3>No users found</h3>
                                        <p>
                                            Try changing the filters or create a
                                            new user.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="users?.links?.length" class="pagination-footer">
                    <div class="result-text">
                        Showing {{ users.from || 0 }} to {{ users.to || 0 }} of
                        {{ users.total || 0 }} results
                    </div>

                    <div class="pagination-buttons">
                        <Link
                            v-for="(link, index) in users.links"
                            :key="index"
                            :href="link.url || ''"
                            v-html="link.label"
                            class="page-btn"
                            :class="{
                                active: link.active,
                                disabled: !link.url,
                            }"
                            preserve-scroll
                        />
                    </div>

                    <div class="per-page">
                        <span>Rows per page:</span>
                        <button>10 <i class="bx bx-chevron-down"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.users-page {
    min-height: 100vh;
    padding: 28px 0 38px;
    background:
        radial-gradient(
            circle at 7% 5%,
            rgba(225, 29, 72, 0.08),
            transparent 28%
        ),
        radial-gradient(
            circle at 96% 12%,
            rgba(37, 99, 235, 0.12),
            transparent 30%
        ),
        linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
}

.hero-card {
    min-height: 150px;
    border-radius: 22px;
    padding: 30px 36px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    overflow: hidden;
    position: relative;
    color: #fff;
    background:
        radial-gradient(
            circle at 88% 20%,
            rgba(255, 255, 255, 0.18),
            transparent 24%
        ),
        linear-gradient(135deg, #d90429 0%, #8f1538 42%, #075eee 100%);
    box-shadow: 0 24px 55px rgba(15, 23, 42, 0.14);
}

.hero-card::before {
    content: "";
    position: absolute;
    left: 24px;
    top: 22px;
    width: 92px;
    height: 92px;
    border-radius: 30px;
    background: rgba(255, 255, 255, 0.13);
}

.hero-card::after {
    content: "";
    position: absolute;
    right: 150px;
    bottom: -130px;
    width: 300px;
    height: 300px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.09);
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 28px;
    position: relative;
    z-index: 2;
}

.hero-icon {
    width: 82px;
    height: 82px;
    border-radius: 26px;
    background: rgba(255, 255, 255, 0.92);
    color: #e11d48;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    box-shadow: 0 18px 35px rgba(0, 0, 0, 0.18);
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.16);
    font-size: 13px;
    font-weight: 900;
    margin-bottom: 8px;
}

.hero-card h1 {
    margin: 0;
    color: #fff;
    font-size: 34px;
    font-weight: 950;
    letter-spacing: -0.8px;
}

.hero-card p {
    margin: 8px 0 0;
    color: rgba(255, 255, 255, 0.92);
    font-size: 15px;
    font-weight: 650;
}

.btn-new-user {
    position: relative;
    z-index: 3;
    height: 56px;
    padding: 0 24px;
    border: none;
    border-radius: 16px;
    background: #fff;
    color: #dc2626;
    font-size: 15px;
    font-weight: 950;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    box-shadow: 0 18px 32px rgba(0, 0, 0, 0.16);
    transition: 0.18s ease;
}

.btn-new-user:hover {
    transform: translateY(-2px);
    background: #fff1f2;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin: 24px 0;
}

.stat-card {
    min-height: 120px;
    padding: 24px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e6ebf2;
    display: flex;
    align-items: center;
    gap: 18px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.07);
}

.stat-icon {
    width: 62px;
    height: 62px;
    border-radius: 19px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 31px;
}

.stat-blue {
    background: #eef4ff;
    color: #2563eb;
}

.stat-green {
    background: #dcfce7;
    color: #16a34a;
}

.stat-red {
    background: #fff1f2;
    color: #e11d48;
}

.stat-purple {
    background: #f3e8ff;
    color: #7c3aed;
}

.stat-card span {
    color: #475569;
    font-size: 13px;
    font-weight: 900;
}

.stat-card strong {
    display: block;
    margin-top: 4px;
    color: #0f172a;
    font-size: 28px;
    font-weight: 950;
    line-height: 1;
}

.stat-card small {
    display: block;
    margin-top: 7px;
    color: #64748b;
    font-size: 13px;
    font-weight: 650;
}

.users-panel {
    border-radius: 18px;
    background: #fff;
    border: 1px solid #e6ebf2;
    overflow: hidden;
    box-shadow: 0 22px 55px rgba(15, 23, 42, 0.08);
}

.filters-bar {
    padding: 18px 24px;
    display: grid;
    grid-template-columns:
        minmax(300px, 1.3fr) minmax(220px, 0.75fr) minmax(220px, 0.75fr)
        130px 180px;
    gap: 14px;
    align-items: center;
    background: #fff;
}

.filter-search,
.filter-select {
    position: relative;
}

.filter-search i,
.filter-select i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 21px;
    color: #64748b;
    z-index: 2;
}

.filter-search input,
.filter-select select {
    width: 100%;
    height: 54px;
    border-radius: 15px;
    border: 1px solid #dfe7f1;
    background: #fff;
    padding: 0 18px 0 52px;
    outline: none;
    color: #334155;
    font-size: 15px;
    font-weight: 750;
    transition: 0.18s ease;
}

.filter-search input:focus,
.filter-select select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 5px rgba(37, 99, 235, 0.08);
}

.btn-clear,
.btn-apply {
    height: 54px;
    border: none;
    border-radius: 15px;
    font-size: 15px;
    font-weight: 950;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.18s ease;
}

.btn-clear {
    background: #f1f5f9;
    color: #475569;
}

.btn-apply {
    background: linear-gradient(135deg, #ef233c, #dc2626);
    color: #fff;
    box-shadow: 0 16px 28px rgba(220, 38, 38, 0.18);
}

.btn-clear:hover,
.btn-apply:hover {
    transform: translateY(-1px);
}

.table-wrap {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    min-width: 1640px;
    border-collapse: separate;
    border-spacing: 0;
}

.users-table thead th {
    background: #fff4f5;
    color: #9f1239;
    padding: 18px 24px;
    font-size: 13px;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: 0.035em;
    border-top: 1px solid #fee2e2;
    border-bottom: 1px solid #fee2e2;
    white-space: nowrap;
}

.users-table tbody td {
    padding: 18px 24px;
    background: #fff;
    border-bottom: 1px solid #edf2f7;
    color: #334155;
    font-weight: 700;
    vertical-align: middle;
}

.users-table tbody tr:hover td {
    background: #fcfdff;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 175px;
}

.avatar {
    width: 44px;
    height: 44px;
    border-radius: 15px;
    background: linear-gradient(135deg, #ef233c, #b91c1c);
    color: #fff;
    font-size: 15px;
    font-weight: 950;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 12px 24px rgba(220, 38, 38, 0.2);
}

.user-cell strong {
    display: block;
    color: #111827;
    font-size: 14px;
    font-weight: 950;
    white-space: nowrap;
}

.user-cell span {
    display: block;
    color: #94a3b8;
    font-size: 12px;
    font-weight: 900;
}

.email-pill {
    width: 285px;
    height: 40px;
    padding: 0 12px;
    border-radius: 13px;
    background: #f8fafc;
    border: 1px solid #dfe7f1;
    display: flex;
    align-items: center;
    gap: 9px;
    color: #334155;
}

.email-pill i {
    flex: 0 0 auto;
    color: #2563eb;
    font-size: 18px;
}

.email-pill span {
    display: block;
    min-width: 0;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 800;
}

.email-pill .copy-icon {
    color: #64748b;
    font-size: 16px;
}

.password-cell {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: #334155;
}

.password-cell span {
    letter-spacing: 4px;
    font-size: 15px;
    font-weight: 950;
}

.password-cell i {
    color: #64748b;
    font-size: 18px;
}

.mail-status {
    width: 250px;
    min-height: 48px;
    padding: 8px 12px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #64748b;
}

.mail-status.enabled {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
}

.mail-status i {
    flex: 0 0 auto;
    font-size: 20px;
}

.mail-status strong,
.mail-status span {
    display: block;
    max-width: 190px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mail-status strong {
    color: #0f172a;
    font-size: 12px;
    font-weight: 950;
}

.mail-status span {
    color: #64748b;
    font-size: 12px;
    font-weight: 750;
}

.role-badge,
.profile-pill,
.status-pill {
    min-height: 38px;
    padding: 0 13px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 950;
}

.role-badge i,
.profile-pill i {
    font-size: 17px;
}

.role-admin {
    background: #111827;
    color: #fff;
}

.role-manager {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.role-driver {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.role-guide {
    background: #faf5ff;
    color: #9333ea;
    border: 1px solid #e9d5ff;
}

.role-client {
    background: #fff7ed;
    color: #ea580c;
    border: 1px solid #fed7aa;
}

.role-vehicule {
    background: #fff1f2;
    color: #e11d48;
    border: 1px solid #fecdd3;
}

.role-neutral {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.profile-pill {
    background: #eff6ff;
    color: #2563eb;
    border: 1px solid #dbeafe;
}

.status-pill {
    border: 1px solid transparent;
    cursor: pointer;
    background: transparent;
}

.status-pill span {
    width: 8px;
    height: 8px;
    border-radius: 999px;
}

.status-pill.active {
    background: #ecfdf5;
    color: #059669;
    border-color: #bbf7d0;
}

.status-pill.active span {
    background: #10b981;
}

.status-pill.inactive {
    background: #fff1f2;
    color: #e11d48;
    border-color: #fecdd3;
}

.status-pill.inactive span {
    background: #f43f5e;
}

.date-cell {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #334155;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 800;
}

.date-cell i {
    color: #64748b;
    font-size: 17px;
}

.actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    white-space: nowrap;
}

.btn-edit,
.btn-delete,
.btn-save,
.btn-cancel {
    height: 40px;
    border-radius: 12px;
    border: none;
    padding: 0 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 950;
    transition: 0.18s ease;
}

.btn-edit {
    color: #ea580c;
    background: #fff7ed;
    border: 1px solid #fed7aa;
}

.btn-delete {
    width: 44px;
    padding: 0;
    color: #fff;
    background: linear-gradient(135deg, #ef233c, #dc2626);
    box-shadow: 0 12px 22px rgba(220, 38, 38, 0.2);
}

.btn-save {
    color: #fff;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.btn-cancel {
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
}

.btn-edit:hover,
.btn-delete:hover,
.btn-save:hover,
.btn-cancel:hover {
    transform: translateY(-1px);
}

.edit-row td {
    background: #fffafa !important;
    vertical-align: top;
}

.table-input {
    height: 42px;
    min-width: 150px;
    border-radius: 12px;
    border: 1px solid #dfe7f1;
    background: #fff;
    padding: 0 12px;
    outline: none;
    color: #334155;
    font-size: 13px;
    font-weight: 750;
}

.input-email {
    min-width: 260px;
}

.input-status {
    min-width: 120px;
}

.table-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
}

small {
    display: block;
    margin-top: 5px;
    color: #dc2626;
    font-size: 12px;
    font-weight: 750;
}

.empty-profile {
    display: inline-flex;
    align-items: center;
    height: 38px;
    padding: 0 13px;
    border-radius: 12px;
    color: #94a3b8;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
    font-weight: 850;
}

.empty-state {
    text-align: center;
    padding: 70px 20px;
    color: #64748b;
}

.empty-state i {
    font-size: 58px;
    color: #cbd5e1;
}

.empty-state h3 {
    margin: 12px 0 4px;
    color: #0f172a;
    font-size: 20px;
    font-weight: 950;
}

.empty-state p {
    margin: 0;
    font-weight: 650;
}

.pagination-footer {
    padding: 18px 24px;
    border-top: 1px solid #edf2f7;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 18px;
    align-items: center;
}

.result-text {
    color: #475569;
    font-size: 14px;
    font-weight: 750;
}

.pagination-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.page-btn {
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 12px;
    border: 1px solid #dfe7f1;
    background: #fff;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 14px;
    font-weight: 900;
}

.page-btn.active {
    background: linear-gradient(135deg, #ef233c, #dc2626);
    color: #fff;
    border-color: #dc2626;
    box-shadow: 0 12px 22px rgba(220, 38, 38, 0.18);
}

.page-btn.disabled {
    opacity: 0.45;
    pointer-events: none;
}

.per-page {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #475569;
    font-size: 14px;
    font-weight: 750;
}

.per-page button {
    height: 40px;
    padding: 0 15px;
    border-radius: 12px;
    border: 1px solid #dfe7f1;
    background: #fff;
    color: #334155;
    font-weight: 850;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.text-end {
    text-align: right;
}

@media (max-width: 1400px) {
    .filters-bar {
        grid-template-columns: 1fr 1fr;
    }

    .btn-clear,
    .btn-apply {
        width: 100%;
    }

    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 992px) {
    .hero-card {
        flex-direction: column;
        align-items: stretch;
    }

    .hero-left {
        flex-direction: column;
        align-items: flex-start;
    }

    .filters-bar {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .pagination-footer {
        grid-template-columns: 1fr;
    }

    .pagination-buttons,
    .per-page {
        justify-self: start;
    }
}
</style>
