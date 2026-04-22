<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { reactive } from 'vue'
import AppShell from '@/Layouts/AppShell.vue'

defineOptions({
    layout: AppShell
})

const props = defineProps({
    guides: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            from: null,
            to: null,
        })
    },
    filters: {
        type: Object,
        default: () => ({
            search: ''
        })
    }
})

const query = reactive({
    search: props.filters?.search || ''
})

const applyFilters = () => {
    router.get(
        '/guides',
        { search: query.search },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    )
}

const resetFilters = () => {
    query.search = ''
    applyFilters()
}

const destroyGuide = (id) => {
    if (!confirm('Voulez-vous vraiment supprimer ce guide ?')) return

    router.delete(`/guides/${id}`, {
        preserveScroll: true
    })
}
</script>

<template>
    <Head title="Guides" />

    <div class="page-content">
        <div class="container-fluid">
            <!-- HERO -->
            <div class="guides-hero card border-0 shadow-lg mb-4 overflow-hidden">
                <div class="hero-overlay"></div>

                <div class="card-body p-4 p-lg-5 position-relative">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <div>
                            <div class="hero-chip mb-3">
                                <i class="bx bx-id-card"></i>
                                Gestion équipe terrain
                            </div>

                            <h1 class="hero-title mb-2">Gestion des guides</h1>
                            <p class="hero-subtitle mb-0">
                                Gérez vos guides, suivez leurs informations et gardez une vue claire sur vos ressources.
                            </p>
                        </div>

                        <div class="hero-stat-badge">
                            <i class="bx bx-user-voice me-1"></i>
                            {{ guides?.total || 0 }} guide(s)
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOOLBAR -->
            <div class="toolbar-card card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="toolbar-grid">
                        <div class="search-box">
                            <label class="form-label toolbar-label">Recherche</label>
                            <input
                                v-model="query.search"
                                type="text"
                                class="form-control form-control-modern"
                                placeholder="Nom, téléphone, email, status..."
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <div class="toolbar-actions">
                            <button
                                type="button"
                                class="btn btn-outline-dark action-btn-soft"
                                @click="applyFilters"
                            >
                                <i class="bx bx-filter-alt me-1"></i>
                                Filtrer
                            </button>

                            <button
                                type="button"
                                class="btn btn-soft-secondary action-btn-soft"
                                @click="resetFilters"
                            >
                                <i class="bx bx-refresh me-1"></i>
                                Reset
                            </button>

                            <Link href="/guides/create" class="btn btn-danger-red action-btn-main">
                                <i class="bx bx-plus me-1"></i>
                                Nouveau guide
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="guides-table-card card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 custom-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Téléphone</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="guide in guides.data" :key="guide.id">
                                    <td>
                                        <div class="guide-name-cell">
                                            <div class="guide-avatar">
                                                {{ String(guide.name || 'G').charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="guide-name">{{ guide.name || '-' }}</div>
                                                <div class="guide-id">#{{ guide.id }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>{{ guide.phone || '-' }}</td>
                                    <td>{{ guide.email || '-' }}</td>

                                    <td>
                                        <span
                                            class="status-pill"
                                            :class="{
                                                'status-disponible': guide.status === 'Disponible',
                                                'status-occupe': guide.status === 'Occupé',
                                                'status-indisponible': guide.status === 'Indisponible',
                                            }"
                                        >
                                            {{ guide.status || '-' }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="notes-text">
                                            {{ guide.notes || '-' }}
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                            <Link
                                                :href="`/guides/${guide.id}/edit`"
                                                class="btn btn-edit-action btn-sm"
                                            >
                                                <i class="bx bx-edit-alt me-1"></i>
                                                Éditer
                                            </Link>

                                            <button
                                                class="btn btn-delete-action btn-sm"
                                                @click="destroyGuide(guide.id)"
                                            >
                                                <i class="bx bx-trash me-1"></i>
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="guides.data.length === 0">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        Aucun guide trouvé.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <div
                        v-if="guides.links?.length"
                        class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-3 border-top pagination-wrap"
                    >
                        <div class="pagination-info text-muted small">
                            Affichage
                            <span class="fw-bold">{{ guides.from || 0 }}</span>
                            à
                            <span class="fw-bold">{{ guides.to || 0 }}</span>
                            sur
                            <span class="fw-bold">{{ guides.total || 0 }}</span>
                            guide(s)
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <Link
                                v-for="(link, index) in guides.links"
                                :key="index"
                                :href="link.url || ''"
                                v-html="link.label"
                                class="btn btn-sm pagination-btn"
                                :class="link.active ? 'btn-danger-red' : 'btn-outline-secondary'"
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
        radial-gradient(circle at top left, rgba(193, 18, 31, 0.05), transparent 18%),
        radial-gradient(circle at bottom right, rgba(29, 78, 216, 0.05), transparent 18%),
        #f4f6fb;
    min-height: 100vh;
}

.guides-hero {
    position: relative;
    border-radius: 28px;
    background:
        radial-gradient(circle at 85% 15%, rgba(255,255,255,.22), transparent 18%),
        radial-gradient(circle at 20% 120%, rgba(255,255,255,.12), transparent 28%),
        linear-gradient(135deg, #c1121f 0%, #7f1024 45%, #1d4ed8 100%);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(255,255,255,.03), rgba(255,255,255,.01));
    pointer-events: none;
}

.hero-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,.14);
    color: #fff;
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 999px;
    padding: 8px 14px;
    font-weight: 700;
    font-size: .9rem;
}

.hero-title {
    color: #fff;
    font-size: 2.15rem;
    font-weight: 900;
    letter-spacing: .2px;
}

.hero-subtitle {
    color: rgba(255,255,255,.9);
    font-size: 1rem;
    max-width: 720px;
}

.hero-stat-badge {
    background: rgba(255,255,255,.14);
    color: #fff;
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 999px;
    padding: 12px 18px;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(0,0,0,.08);
}

.toolbar-card,
.guides-table-card {
    border-radius: 24px;
    overflow: hidden;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(12px);
}

.toolbar-grid {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) auto;
    gap: 16px;
    align-items: end;
}

.toolbar-label {
    font-size: .82rem;
    font-weight: 800;
    color: #6b7280;
    margin-bottom: 8px;
}

.toolbar-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.form-control-modern {
    border-radius: 16px;
    min-height: 50px;
    border: 1px solid #dfe3ec;
    box-shadow: none;
    background: #fff;
    transition: all .22s ease;
}

.form-control-modern:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 .18rem rgba(193,18,31,.12);
}

.btn-danger-red {
    background: linear-gradient(135deg, #d11a2a 0%, #a20e19 100%);
    color: #fff;
    border: 0;
    box-shadow: 0 12px 24px rgba(193,18,31,.18);
}

.btn-danger-red:hover {
    color: #fff;
    background: linear-gradient(135deg, #b91422 0%, #8f0a14 100%);
    transform: translateY(-1px);
}

.action-btn-main,
.action-btn-soft {
    min-height: 50px;
    border-radius: 16px;
    padding-inline: 18px;
    font-weight: 800;
}

.btn-soft-secondary {
    background: #eef1f7;
    border: 1px solid #dfe4ee;
    color: #5d6574;
}

.btn-soft-secondary:hover {
    background: #e6ebf3;
    color: #374151;
}

.custom-table thead th {
    background: linear-gradient(180deg, #fff7f8 0%, #fff1f2 100%);
    color: #92111b;
    font-weight: 900;
    font-size: .86rem;
    padding: 16px 14px;
    border-bottom: 1px solid #f0d7da;
    white-space: nowrap;
}

.custom-table tbody td {
    padding: 14px;
    border-color: #edf0f5;
    vertical-align: middle;
    color: #2f3747;
    background: #fff;
}

.custom-table tbody tr:hover td {
    background: #fffafb;
}

.guide-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 220px;
}

.guide-avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #c1121f 0%, #1d4ed8 100%);
    color: #fff;
    font-weight: 900;
    box-shadow: 0 10px 20px rgba(29, 78, 216, .18);
    flex-shrink: 0;
}

.guide-name {
    font-weight: 800;
    color: #111827;
}

.guide-id {
    font-size: .76rem;
    color: #9ca3af;
    margin-top: 2px;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 110px;
    padding: 7px 12px;
    border-radius: 999px;
    font-size: .8rem;
    font-weight: 800;
    border: 1px solid transparent;
}

.status-disponible {
    background: rgba(34, 197, 94, .10);
    color: #15803d;
    border-color: rgba(34, 197, 94, .14);
}

.status-occupe {
    background: rgba(245, 158, 11, .12);
    color: #b45309;
    border-color: rgba(245, 158, 11, .16);
}

.status-indisponible {
    background: rgba(239, 68, 68, .10);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, .14);
}

.notes-text {
    max-width: 260px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-edit-action {
    background: linear-gradient(135deg, #ff8a00 0%, #ff6b00 100%);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 700;
    box-shadow: 0 10px 18px rgba(255, 107, 0, .15);
}

.btn-edit-action:hover {
    color: #fff;
    transform: translateY(-1px);
}

.btn-delete-action {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    border: 0;
    border-radius: 12px;
    font-weight: 700;
    box-shadow: 0 10px 18px rgba(239, 68, 68, .14);
}

.btn-delete-action:hover {
    color: #fff;
    transform: translateY(-1px);
}

.pagination-wrap {
    background: linear-gradient(180deg, rgba(248,250,252,.9), rgba(255,255,255,1));
}

.pagination-btn {
    border-radius: 12px;
    min-width: 40px;
    font-weight: 700;
}

@media (max-width: 991.98px) {
    .hero-title {
        font-size: 1.7rem;
    }

    .toolbar-grid {
        grid-template-columns: 1fr;
    }

    .toolbar-actions {
        justify-content: stretch;
    }

    .toolbar-actions > * {
        flex: 1 1 auto;
    }

    .notes-text {
        max-width: 180px;
    }
}

@media (max-width: 767.98px) {
    .hero-title {
        font-size: 1.45rem;
    }

    .hero-subtitle {
        font-size: .92rem;
    }

    .guide-name-cell {
        min-width: 180px;
    }

    .notes-text {
        max-width: 120px;
    }
}
</style>