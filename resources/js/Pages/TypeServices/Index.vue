<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { reactive, computed } from "vue";
import Swal from "sweetalert2";
import AppShell from "@/Layouts/AppShell.vue";

defineOptions({
    layout: AppShell,
});

const props = defineProps({
    typeServices: {
        type: Array,
        default: () => [],
    },
});

const filters = reactive({
    search: "",
});

const filteredTypeServices = computed(() => {
    let rows = [...props.typeServices];

    if (filters.search) {
        const q = filters.search.toLowerCase();
        rows = rows.filter(
            (item) =>
                String(item.designation || "")
                    .toLowerCase()
                    .includes(q) ||
                String(item.description || "")
                    .toLowerCase()
                    .includes(q),
        );
    }

    return rows;
});

// ✅ SweetAlert delete
const destroyTypeService = (id) => {
    Swal.fire({
        title: "Delete this type?",
        text: "This action cannot be undone",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#c1121f",
        cancelButtonColor: "#64748b",
        confirmButtonText: "Yes, delete",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/type-services/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        icon: "success",
                        title: "Deleted successfully",
                        showConfirmButton: false,
                        timer: 2500,
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Service Types" />

    <div class="page-content">
        <div class="container-fluid py-4">
            <!-- HERO -->
            <div class="hero-card mb-4">
                <div class="hero-overlay"></div>

                <div class="hero-content">
                    <div class="hero-left">
                        <div class="hero-icon">
                            <i class="bx bx-category-alt"></i>
                        </div>

                        <div>
                            <h1 class="hero-title">Service Types</h1>
                            <p class="hero-subtitle mb-0">
                                Manage categories of services.
                            </p>
                        </div>
                    </div>

                    <Link href="/type-services/create" class="btn btn-add">
                        <i class="bx bx-plus-circle me-2"></i>
                        New Type
                    </Link>
                </div>
            </div>

            <!-- SEARCH -->
            <div class="toolbar-card mb-4">
                <div class="search-wrapper">
                    <i class="bx bx-search search-icon"></i>
                    <input
                        v-model="filters.search"
                        type="text"
                        class="form-control search-input"
                        placeholder="Search designation or description..."
                    />
                </div>
            </div>

            <!-- TABLE -->
            <div class="main-card">
                <div class="table-header">
                    <h5 class="table-title">Types List</h5>
                </div>

                <div class="table-responsive">
                    <table class="table custom-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Designation</th>
                                <th>Description</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="item in filteredTypeServices"
                                :key="item.id"
                            >
                                <td class="fw-bold">
                                    {{ item.designation }}
                                </td>

                                <td>
                                    {{ item.description || "-" }}
                                </td>

                                <td>
                                    <div class="actions">
                                        <Link
                                            :href="`/type-services/${item.id}/edit`"
                                            class="btn btn-edit"
                                        >
                                            <i class="bx bx-edit"></i>
                                            <span>Edit</span>
                                        </Link>

                                        <button
                                            class="btn btn-delete"
                                            @click="destroyTypeService(item.id)"
                                        >
                                            <i class="bx bx-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="filteredTypeServices.length === 0">
                                <td
                                    colspan="3"
                                    class="text-center py-5 text-muted"
                                >
                                    No types found
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
            rgba(225, 29, 72, 0.1),
            transparent 24%
        ),
        radial-gradient(
            circle at top right,
            rgba(249, 115, 22, 0.08),
            transparent 22%
        ),
        linear-gradient(180deg, #f8fafc, #f1f5f9);
}

/* HERO */
.hero-card {
    position: relative;
    border-radius: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #991b1b, #be123c, #ea580c);
    color: #fff;
}

.hero-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.hero-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.hero-icon {
    width: 65px;
    height: 65px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.15);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 28px;
}

.hero-title {
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 6px;
}

.btn-add {
    background: #fff;
    color: #991b1b;
    border-radius: 14px;
    padding: 10px 18px;
    font-weight: 800;
}

/* SEARCH */
.toolbar-card {
    background: #fff;
    padding: 18px;
    border-radius: 20px;
}

.search-wrapper {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-input {
    padding-left: 40px;
    border-radius: 14px;
    min-height: 50px;
}

/* TABLE */
.main-card {
    background: #fff;
    border-radius: 24px;
    overflow: hidden;
}

.table-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.custom-table thead th {
    background: #fff4f5;
    color: #92111b;
    font-weight: 800;
}

.custom-table tbody td {
    padding: 14px;
}

/* ACTIONS */
.actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

.btn-edit {
    background: linear-gradient(135deg, #ff8a00, #ff6b00);
    color: #fff;
    border-radius: 10px;
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .hero-card {
        padding: 20px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .search-wrapper {
        flex-direction: column;
    }

    .btn-search {
        width: 100%;
        justify-content: center;
    }

    .notes-box {
        max-width: 170px;
    }

    .btn-action-edit span,
    .btn-action-delete span {
        display: none;
    }
}
</style>
