<script setup>
import { Link } from "@inertiajs/vue3";
import logo from "@/assets/images/logo_md_tours.png";

const isActive = (name) => route().current(name);
const isAnyActive = (names = []) => names.some((name) => route().current(name));
</script>

<template>
    <aside class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header">
            <div class="logo-icon">
                <img :src="logo" class="logo-img w-100" alt="logo" />
            </div>

            <div class="sidebar-close">
                <span class="material-icons-outlined">close</span>
            </div>
        </div>

        <div class="sidebar-nav fw-bold fs-6 w-100">
            <ul class="metismenu" id="sidenav">
                <li>
                    <Link
                        :href="route('dashboard')"
                        :class="{ active: isActive('dashboard') }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">dashboard</i>
                        </div>
                        <div class="menu-title">Tableau de bord</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('plannings.index')"
                        :class="{
                            active: isAnyActive([
                                'plannings.index',
                                'plannings.create',
                                'plannings.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">event_note</i>
                        </div>
                        <div class="menu-title">Plannings</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('clients.index')"
                        :class="{
                            active: isAnyActive([
                                'clients.index',
                                'clients.create',
                                'clients.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">groups</i>
                        </div>
                        <div class="menu-title">Clients</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('supplier-clients.index')"
                        :class="{
                            active: isAnyActive([
                                'supplier-clients.index',
                                'supplier-clients.create',
                                'supplier-clients.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined"
                                >business_center</i
                            >
                        </div>
                        <div class="menu-title">Fournisseurs clients</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('supplier-vehicules.index')"
                        :class="{
                            active: isAnyActive([
                                'supplier-vehicules.index',
                                'supplier-vehicules.create',
                                'supplier-vehicules.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined"
                                >airport_shuttle</i
                            >
                        </div>
                        <div class="menu-title">Fournisseurs véhicules</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('drivers.index')"
                        :class="{
                            active: isAnyActive([
                                'drivers.index',
                                'drivers.create',
                                'drivers.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">local_taxi</i>
                        </div>
                        <div class="menu-title">Chauffeurs</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('guides.index')"
                        :class="{
                            active: isAnyActive([
                                'guides.index',
                                'guides.create',
                                'guides.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">badge</i>
                        </div>
                        <div class="menu-title">Guides</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('services.index')"
                        :class="{
                            active: isAnyActive([
                                'services.index',
                                'services.create',
                                'services.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">room_service</i>
                        </div>
                        <div class="menu-title">Services</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('destinations.index')"
                        :class="{
                            active: isAnyActive([
                                'destinations.index',
                                'destinations.create',
                                'destinations.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">place</i>
                        </div>
                        <div class="menu-title">Destinations</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('driver-fuel-invoices.index')"
                        :class="{
                            active: isAnyActive([
                                'driver-fuel-invoices.index',
                                'driver-fuel-invoices.create',
                                'driver-fuel-invoices.edit',
                                'driver-fuel-invoices.show',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined"
                                >local_gas_station</i
                            >
                        </div>
                        <div class="menu-title">Factures carburant</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('users.index')"
                        :class="{
                            active: isAnyActive([
                                'users.index',
                                'users.create',
                                'users.edit',
                                'users.show',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined"
                                >manage_accounts</i
                            >
                        </div>
                        <div class="menu-title">Utilisateurs</div>
                    </Link>
                </li>

                <li class="menu-label">Paramétrage</li>

                <!-- <li>
                    <Link
                        :href="route('type-suppliers.index')"
                        :class="{
                            active: isAnyActive([
                                'type-suppliers.index',
                                'type-suppliers.create',
                                'type-suppliers.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">category</i>
                        </div>
                        <div class="menu-title">Types fournisseurs</div>
                    </Link>
                </li> -->

                <li>
                    <Link
                        :href="route('type-services.index')"
                        :class="{
                            active: isAnyActive([
                                'type-services.index',
                                'type-services.create',
                                'type-services.edit',
                            ]),
                        }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">tune</i>
                        </div>
                        <div class="menu-title">Types services</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('profile.edit')"
                        :class="{ active: isActive('profile.edit') }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">person</i>
                        </div>
                        <div class="menu-title">Profil</div>
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
    letter-spacing: 0.08em;
    padding: 12px 18px 6px 18px;
    font-weight: 700;
}

.metismenu li a.active {
    background: linear-gradient(
        90deg,
        rgba(193, 18, 31, 0.12) 0%,
        rgba(193, 18, 31, 0.04) 100%
    );
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
