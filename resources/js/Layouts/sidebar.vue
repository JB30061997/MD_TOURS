<script setup>
import { Link } from '@inertiajs/vue3'
import logo from '@/assets/images/logo_md_tours.png'

const isActive = (name) => route().current(name)
const isAnyActive = (names = []) => names.some(name => route().current(name))
</script>

<template>
    <aside class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header">
            <div class="logo-icon">
                <img :src="logo" class="logo-img w-100" alt="logo">
            </div>

            <div class="sidebar-close">
                <span class="material-icons-outlined">close</span>
            </div>
        </div>

        <div class="sidebar-nav fw-bold fs-6 w-100">
            <ul class="metismenu" id="sidenav">

                <!-- Dashboard -->
                <li>
                    <Link :href="route('dashboard')" :class="{ active: isActive('dashboard') }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">dashboard</i>
                        </div>
                        <div class="menu-title">Dashboard</div>
                    </Link>
                </li>

                <!-- Plannings -->
                <li>
                    <Link :href="route('plannings.index')" :class="{ active: isAnyActive(['plannings.index', 'plannings.create', 'plannings.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">event_note</i>
                        </div>
                        <div class="menu-title">Plannings</div>
                    </Link>
                </li>

                <!-- Clients -->
                <li>
                    <Link :href="route('clients.index')" :class="{ active: isAnyActive(['clients.index', 'clients.create', 'clients.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">groups</i>
                        </div>
                        <div class="menu-title">Clients</div>
                    </Link>
                </li>

                <!-- Suppliers -->
                <li>
                    <Link :href="route('suppliers.index')" :class="{ active: isAnyActive(['suppliers.index', 'suppliers.create', 'suppliers.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">apartment</i>
                        </div>
                        <div class="menu-title">Suppliers</div>
                    </Link>
                </li>

                <!-- Drivers -->
                <li>
                    <Link :href="route('drivers.index')" :class="{ active: isAnyActive(['drivers.index', 'drivers.create', 'drivers.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">local_taxi</i>
                        </div>
                        <div class="menu-title">Drivers</div>
                    </Link>
                </li>

                <!-- Guides -->
                <li>
                    <Link :href="route('guides.index')" :class="{ active: isAnyActive(['guides.index', 'guides.create', 'guides.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">badge</i>
                        </div>
                        <div class="menu-title">Guides</div>
                    </Link>
                </li>

                <!-- Services -->
                <li>
                    <Link :href="route('services.index')" :class="{ active: isAnyActive(['services.index', 'services.create', 'services.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">room_service</i>
                        </div>
                        <div class="menu-title">Services</div>
                    </Link>
                </li>

                <!-- Factures Carburant -->
                <li>
                    <Link
                        :href="route('driver-fuel-invoices.index')"
                        :class="{ active: isAnyActive(['driver-fuel-invoices.index', 'driver-fuel-invoices.create', 'driver-fuel-invoices.edit', 'driver-fuel-invoices.show']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">local_gas_station</i>
                        </div>
                        <div class="menu-title">Factures Carburant</div>
                    </Link>
                </li>

                <!-- Planning Clients -->
                <!-- <li>
                    <Link :href="route('planning-clients.index')" :class="{ active: isAnyActive(['planning-clients.index', 'planning-clients.create', 'planning-clients.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">group_add</i>
                        </div>
                        <div class="menu-title">Planning Clients</div>
                    </Link>
                </li> -->

                <!-- Types -->
                <li class="menu-label">Paramétrage</li>

                <li>
                    <Link :href="route('type-suppliers.index')" :class="{ active: isAnyActive(['type-suppliers.index', 'type-suppliers.create', 'type-suppliers.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">category</i>
                        </div>
                        <div class="menu-title">Type Suppliers</div>
                    </Link>
                </li>

                <li>
                    <Link :href="route('type-services.index')" :class="{ active: isAnyActive(['type-services.index', 'type-services.create', 'type-services.edit']) }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">tune</i>
                        </div>
                        <div class="menu-title">Type Services</div>
                    </Link>
                </li>

                <!-- Profile -->
                <li>
                    <Link :href="route('profile.edit')" :class="{ active: isActive('profile.edit') }">
                        <div class="parent-icon">
                            <i class="material-icons-outlined">person</i>
                        </div>
                        <div class="menu-title">Profile</div>
                    </Link>
                </li>

            </ul>
        </div>
    </aside>
</template>

<style scoped>
.menu-label {
    color: #9aa4b2;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .08em;
    padding: 12px 18px 6px 18px;
    font-weight: 700;
}

.metismenu li a.active {
    background: linear-gradient(90deg, rgba(193,18,31,.12) 0%, rgba(193,18,31,.04) 100%);
    color: #c1121f !important;
    border-right: 4px solid #c1121f;
    border-radius: 10px;
}

.metismenu li a.active .parent-icon i,
.metismenu li a.active .menu-title {
    color: #c1121f !important;
    font-weight: 700;
}

.sidebar-header {
    border-bottom: 1px solid #f0f1f3;
}

.logo-img {
    max-height: 42px;
    object-fit: contain;
}
</style>