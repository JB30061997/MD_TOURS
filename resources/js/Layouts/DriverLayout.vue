<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const open = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const items = [
    ['driver.dashboard', 'dashboard', 'Dashboard'],
    ['driver.plannings.index', 'event_note', 'My Planning'],
    ['driver.road-sheets.index', 'edit_note', 'Fiches de route'],
    ['driver.road-sheets.saved', 'task_alt', 'Mes fiches'],
    ['driver.profile', 'person', 'Profil'],
];
const logout = () => router.post(route('logout'));
</script>

<template>
    <div class="driver-shell">
        <aside :class="['driver-sidebar', { open }]">
            <div class="brand"><div class="brand-mark">MD</div><div><strong>MD TOURS</strong><small>Espace Driver</small></div></div>
            <div class="user"><div class="avatar">{{ (user.name || 'D').slice(0, 1).toUpperCase() }}</div><div><strong>{{ user.name }}</strong><small>Chauffeur</small></div></div>
            <nav>
                <Link v-for="item in items" :key="item[0]" :href="route(item[0])" :class="{ active: route().current(item[0]) }" @click="open=false">
                    <span class="material-icons-outlined">{{ item[1] }}</span><span>{{ item[2] }}</span>
                </Link>
            </nav>
            <button class="logout" type="button" @click="logout"><span class="material-icons-outlined">logout</span>Déconnexion</button>
        </aside>
        <div v-if="open" class="backdrop" @click="open=false"></div>
        <main>
            <header class="driver-topbar">
                <button class="menu" type="button" @click="open=!open"><span class="material-icons-outlined">menu</span></button>
                <div><strong><slot name="title">Espace Driver</slot></strong><small>MD TOURS</small></div>
                <Link :href="route('driver.profile')" class="profile"><span class="material-icons-outlined">person</span></Link>
            </header>
            <div class="driver-content"><slot /></div>
        </main>
    </div>
</template>

<style scoped>
.driver-shell{--red:#d71920;--deep:#450a0a;--ink:#0f172a;--muted:#64748b;--line:#e2e8f0;min-height:100vh;background:#f4f7fb;color:var(--ink);display:grid;grid-template-columns:264px minmax(0,1fr)}
.driver-sidebar{position:sticky;top:0;height:100vh;padding:22px 16px;background:linear-gradient(165deg,#450a0a 0%,#7f1d1d 58%,#b91c1c 100%);display:flex;flex-direction:column;color:#fff;z-index:40}.brand{display:flex;align-items:center;gap:11px;padding:0 6px 20px}.brand-mark{width:42px;height:42px;border-radius:14px;background:#fff;color:#991b1b;display:grid;place-items:center;font-weight:900}.brand strong,.brand small,.user strong,.user small{display:block}.brand small,.user small{margin-top:2px;color:#fecaca;font-size:11px;font-weight:700}.user{display:flex;align-items:center;gap:10px;padding:11px;margin-bottom:20px;border:1px solid #ffffff20;border-radius:17px;background:#ffffff12}.avatar{width:40px;height:40px;border-radius:13px;background:#fee2e2;color:#991b1b;display:grid;place-items:center;font-weight:900}.user strong{max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}nav{display:grid;gap:6px}nav a{min-height:46px;padding:0 12px;border-radius:14px;color:#fee2e2;display:flex;align-items:center;gap:11px;font-size:13px;font-weight:800;text-decoration:none}nav a:hover,nav a.active{background:#ffffff18;color:#fff}.material-icons-outlined{font-size:20px}.logout{margin-top:auto;height:45px;border:0;border-radius:14px;background:#ef4444;color:#fff;display:flex;align-items:center;justify-content:center;gap:8px;font-weight:900}.driver-topbar{height:64px;padding:0 clamp(14px,3vw,30px);background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:12px;position:sticky;top:0;z-index:25}.driver-topbar>div{flex:1}.driver-topbar strong,.driver-topbar small{display:block}.driver-topbar small{color:var(--muted);font-size:10px}.menu,.profile{width:40px;height:40px;border:0;border-radius:13px;background:#fef2f2;color:#991b1b;display:grid;place-items:center;text-decoration:none}.menu{display:none}.driver-content{width:min(1380px,100%);margin:auto;padding:clamp(12px,2.5vw,28px)}.backdrop{display:none}
@media(max-width:900px){.driver-shell{display:block}.driver-sidebar{position:fixed;left:0;transform:translateX(-105%);width:276px;transition:.22s ease}.driver-sidebar.open{transform:none}.backdrop{display:block;position:fixed;inset:0;background:#0f172a77;z-index:35}.menu{display:grid}.driver-content{padding:12px}.driver-topbar{height:58px;padding:0 10px}}
</style>
