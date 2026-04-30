<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import logo from "@/assets/images/logo_md_tours.png";

const page = usePage();

// Kandiro active 3la component dyal Inertia bach ytbedel direct bla refresh
const currentComponent = computed(() => page.component);

const isActive = (component) => currentComponent.value === component;

const isAnyActive = (components = []) =>
    components.some((component) => currentComponent.value.startsWith(component));
</script>

<template>
    <aside class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header">
            <div class="logo-icon">
                <img :src="logo" class="logo-img w-100" alt="logo" />
            </div>

            <!-- <div class="sidebar-close">
                <span class="material-icons-outlined">close</span>
            </div> -->
        </div>

        <div class="sidebar-nav fw-bold fs-6 w-100">
            <ul class="metismenu" id="sidenav">
                <li>
                    <Link
                        :href="route('dashboard')"
                        :class="{ active: isActive('Dashboard') }"
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
                        :class="{ active: isAnyActive(['Plannings/']) }"
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
                        :class="{ active: isAnyActive(['Clients/']) }"
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
                        :class="{ active: isAnyActive(['SupplierClients/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">business_center</i>
                        </div>
                        <div class="menu-title">Fournisseurs clients</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('supplier-vehicules.index')"
                        :class="{ active: isAnyActive(['SupplierVehicules/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">airport_shuttle</i>
                        </div>
                        <div class="menu-title">Fournisseurs véhicules</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('vehicules.index')"
                        :class="{ active: isAnyActive(['Vehicules/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">directions_bus</i>
                        </div>
                        <div class="menu-title">Véhicules</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('drivers.index')"
                        :class="{ active: isAnyActive(['Drivers/']) }"
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
                        :class="{ active: isAnyActive(['Guides/']) }"
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
                        :class="{ active: isAnyActive(['Services/']) }"
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
                        :class="{ active: isAnyActive(['Destinations/']) }"
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
                        :class="{ active: isAnyActive(['DriverFuelInvoices/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">local_gas_station</i>
                        </div>
                        <div class="menu-title">Factures carburant</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('all-users.index')"
                        :class="{ active: isAnyActive(['Users/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">manage_accounts</i>
                        </div>
                        <div class="menu-title">Utilisateurs</div>
                    </Link>
                </li>

                <li class="menu-label">Paramétrage</li>

                <li>
                    <Link
                        :href="route('type-services.index')"
                        :class="{ active: isAnyActive(['TypeServices/']) }"
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
                        :class="{ active: isAnyActive(['Profile/']) }"
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

.sidebar-header {
    border-bottom: 1px solid #f0f1f3;
}

.logo-img {
    max-height: 50px;
    object-fit: contain;
}

.metismenu {
    padding: 10px 12px;
}

.metismenu li {
    margin-bottom: 4px;
}

.metismenu li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 12px;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.2s ease;
}

.metismenu li a:hover {
    background: rgba(193, 18, 31, 0.07);
    color: #c1121f !important;
    transform: translateX(2px);
}

.parent-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    flex-shrink: 0;
}

.parent-icon i {
    font-size: 21px;
    color: #6b7280;
}

.menu-title {
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
}

.metismenu li a.active {
    background: linear-gradient(
        90deg,
        rgba(193, 18, 31, 0.14) 0%,
        rgba(193, 18, 31, 0.04) 100%
    );
    color: #c1121f !important;
    border-right: 4px solid #c1121f;
    border-radius: 12px;
}

.metismenu li a.active .parent-icon {
    background: #c1121f;
}

.metismenu li a.active .parent-icon i {
    color: #fff !important;
}

.metismenu li a.active .menu-title {
    color: #c1121f !important;
    font-weight: 800;
}
</style>
