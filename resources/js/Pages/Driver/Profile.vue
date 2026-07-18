<script setup>
import DriverLayout from "@/Layouts/DriverLayout.vue";
import UpdatePasswordForm from "@/Pages/Profile/Partials/UpdatePasswordForm.vue";
import UpdateProfileInformationForm from "@/Pages/Profile/Partials/UpdateProfileInformationForm.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Profil Driver" />
    <DriverLayout>
        <template #title>Profil</template>
        <section class="profile-hero">
            <div class="hero-icon">
                <span class="material-icons-outlined">manage_accounts</span>
            </div>
            <div>
                <p>Mon compte</p>
                <h1>Profil Driver</h1>
                <span
                    >Gérez vos informations et la sécurité de votre
                    compte.</span
                >
            </div>
        </section>
        <div class="profile-grid">
            <section class="profile-card">
                <header>
                    <span class="icon red"
                        ><span class="material-icons-outlined"
                            >person</span
                        ></span
                    >
                    <div>
                        <h2>Informations personnelles</h2>
                        <p>Nom et adresse e-mail</p>
                    </div>
                </header>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </section>
            <section class="profile-card">
                <header>
                    <span class="icon blue"
                        ><span class="material-icons-outlined">lock</span></span
                    >
                    <div>
                        <h2>Mot de passe</h2>
                        <p>Sécurisez votre accès</p>
                    </div>
                </header>
                <UpdatePasswordForm />
            </section>
        </div>
    </DriverLayout>
</template>

<style scoped>
.profile-hero,
.profile-hero :deep(*) {
    color: #ffffff !important;
}

.profile-hero {
    min-height: 170px;
    padding: clamp(22px, 4vw, 40px);
    border-radius: 28px;
    background: linear-gradient(135deg, #450a0a, #b91c1c 65%, #ef4444);
    color: #fff;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 20px 45px #991b1b2c;
}
.hero-icon {
    width: 68px;
    height: 68px;
    flex: 0 0 68px;
    border-radius: 21px;
    background: #ffffff1f;
    border: 1px solid #ffffff35;
    display: grid;
    place-items: center;
}
.hero-icon span {
    font-size: 34px;
}
.profile-hero p {
    margin: 0 0 3px;
    color: #fecaca;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}
.profile-hero h1 {
    margin: 0 0 7px;
    font-size: clamp(24px, 4vw, 36px);
}
.profile-hero > div > span {
    color: #fee2e2;
    font-size: 14px;
}
.profile-grid {
    margin-top: 20px;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}
.profile-card {
    min-width: 0;
    padding: clamp(18px, 3vw, 28px);
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 10px 30px #0f172a0b;
}
.profile-card header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}
.profile-card h2,
.profile-card p {
    margin: 0;
}
.profile-card h2 {
    font-size: 18px;
    color: #0f172a;
}
.profile-card p {
    margin-top: 3px;
    color: #64748b;
    font-size: 12px;
}
.icon {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    border-radius: 14px;
    display: grid;
    place-items: center;
}
.icon.red {
    color: #b91c1c;
    background: #fee2e2;
}
.icon.blue {
    color: #1d4ed8;
    background: #dbeafe;
}
@media (max-width: 800px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    .profile-hero {
        min-height: 140px;
        border-radius: 22px;
    }
    .profile-card {
        border-radius: 20px;
    }
}
@media (max-width: 480px) {
    .profile-hero {
        padding: 20px 16px;
        gap: 13px;
    }
    .hero-icon {
        width: 50px;
        height: 50px;
        flex-basis: 50px;
        border-radius: 16px;
    }
    .hero-icon span {
        font-size: 26px;
    }
    .profile-grid {
        gap: 12px;
        margin-top: 12px;
    }
    .profile-card {
        padding: 16px;
    }
    .profile-card header {
        margin-bottom: 18px;
    }
}
</style>
