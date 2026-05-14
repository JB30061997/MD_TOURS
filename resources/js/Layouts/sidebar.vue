<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import logo from "@/assets/images/logo_md_tours.png";

const page = usePage();

const currentComponent = computed(() => page.component);

const isActive = (component) => currentComponent.value === component;

const isAnyActive = (components = []) =>
    components.some((component) =>
        currentComponent.value.startsWith(component),
    );
</script>

<template>
    <aside class="sidebar-wrapper" data-simplebar="true">
        <div class="sidebar-header">
            <div class="logo-icon">
                <img :src="logo" class="logo-img w-100" alt="logo" />
            </div>
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
                        <div class="menu-title">Dashboard</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('mailbox.index')"
                        :class="{ active: isAnyActive(['Mailbox/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">mail</i>
                        </div>
                        <div class="menu-title">Mailbox</div>
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
                        <div class="menu-title">Client Suppliers</div>
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
                        <div class="menu-title">Vehicle Suppliers</div>
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
                        <div class="menu-title">Vehicles</div>
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
                        <div class="menu-title">Drivers</div>
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
                        <div class="menu-title">Fuel Invoices</div>
                    </Link>
                </li>

                <li>
                    <Link
                        :href="route('supplier-vehicule-invoices.index')"
                        :class="{ active: isAnyActive(['SupplierVehiculeInvoices/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">receipt_long</i>
                        </div>
                        <div class="menu-title">Vehicle Supplier Invoices</div>
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
                        <div class="menu-title">Users</div>
                    </Link>
                </li>

                <li class="menu-label">Settings</li>

                <li>
                    <Link
                        :href="route('type-services.index')"
                        :class="{ active: isAnyActive(['TypeServices/']) }"
                    >
                        <div class="parent-icon">
                            <i class="material-icons-outlined">tune</i>
                        </div>
                        <div class="menu-title">Service Types</div>
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

.metismenu li a {
    --icon-bg: #f3f4f6;
    --icon-color: #6b7280;
    --active-color: #c1121f;
}

.metismenu li a:hover .parent-icon {
    transform: rotate(-6deg) scale(1.08);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.parent-icon {
    background: var(--icon-bg);
    transition: all 0.25s ease;
}

.parent-icon i {
    color: var(--icon-color);
    transition: all 0.25s ease;
}

.metismenu li a:hover {
    color: var(--active-color) !important;
}

.metismenu li a:hover .parent-icon i {
    transform: scale(1.08);
}

.metismenu li a.active {
    color: var(--active-color) !important;
    border-right-color: var(--active-color);
}

.metismenu li a.active .parent-icon {
    background: var(--active-color);
}

.metismenu li a.active .menu-title {
    color: var(--active-color) !important;
}

/* Colors per menu item */
.metismenu li:nth-child(1) a { --icon-bg: #eef2ff; --icon-color: #4f46e5; --active-color: #4f46e5; }
.metismenu li:nth-child(2) a { --icon-bg: #eff6ff; --icon-color: #2563eb; --active-color: #2563eb; }
.metismenu li:nth-child(3) a { --icon-bg: #ecfeff; --icon-color: #0891b2; --active-color: #0891b2; }
.metismenu li:nth-child(4) a { --icon-bg: #f5f3ff; --icon-color: #7c3aed; --active-color: #7c3aed; }
.metismenu li:nth-child(5) a { --icon-bg: #fff7ed; --icon-color: #ea580c; --active-color: #ea580c; }
.metismenu li:nth-child(6) a { --icon-bg: #eff6ff; --icon-color: #2563eb; --active-color: #2563eb; }
.metismenu li:nth-child(7) a { --icon-bg: #f0fdf4; --icon-color: #16a34a; --active-color: #16a34a; }
.metismenu li:nth-child(8) a { --icon-bg: #fef3c7; --icon-color: #d97706; --active-color: #d97706; }
.metismenu li:nth-child(9) a { --icon-bg: #faf5ff; --icon-color: #9333ea; --active-color: #9333ea; }
.metismenu li:nth-child(10) a { --icon-bg: #fff1f2; --icon-color: #e11d48; --active-color: #e11d48; }
.metismenu li:nth-child(11) a { --icon-bg: #ecfdf5; --icon-color: #059669; --active-color: #059669; }
.metismenu li:nth-child(12) a { --icon-bg: #fff7ed; --icon-color: #c2410c; --active-color: #c2410c; }
.metismenu li:nth-child(13) a { --icon-bg: #f8fafc; --icon-color: #475569; --active-color: #475569; }
.metismenu li:nth-child(14) a { --icon-bg: #fef2f2; --icon-color: #dc2626; --active-color: #dc2626; }

.metismenu li:nth-child(16) a { --icon-bg: #f1f5f9; --icon-color: #64748b; --active-color: #64748b; }
.metismenu li:nth-child(17) a { --icon-bg: #fff1f2; --icon-color: #c1121f; --active-color: #c1121f; }
</style>