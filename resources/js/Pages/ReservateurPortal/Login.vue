<script setup>
import { Head, router, usePage } from "@inertiajs/vue3";
import { reactive, watch } from "vue";
import Swal from "sweetalert2";

const page = usePage();
const form = reactive({ reference: "" });

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.error) {
            Swal.fire({
                icon: "error",
                title: "Erreur",
                text: flash.error,
                confirmButtonColor: "#c1121f",
            });
        }
    },
    { immediate: true },
);

const submit = () => {
    router.post(route("reservateur.authenticate"), form, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Portail Réservateur" />

    <main class="portal-login">
        <section class="login-card">
            <div class="brand-mark">MD</div>
            <span>MD TOURS</span>
            <h1>Portail Réservateur</h1>
            <p>Saisissez votre référence pour accéder à votre espace.</p>

            <form @submit.prevent="submit">
                <label>
                    <span>Référence Réservateur</span>
                    <input
                        v-model="form.reference"
                        type="text"
                        placeholder="Exemple : RS0928618266"
                        autocomplete="off"
                    />
                </label>

                <button type="submit">Authentification</button>
            </form>
        </section>
    </main>
</template>

<style scoped>
.portal-login {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    background:
        radial-gradient(circle at 20% 20%, rgba(193, 18, 31, 0.14), transparent 28%),
        linear-gradient(135deg, #f8fafc 0%, #fff7ed 100%);
}

.login-card {
    width: min(460px, 100%);
    border: 1px solid #e5e7eb;
    border-radius: 28px;
    padding: 34px;
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.14);
}

.brand-mark {
    width: 62px;
    height: 62px;
    border-radius: 20px;
    display: grid;
    place-items: center;
    color: #fff;
    font-weight: 950;
    background: linear-gradient(135deg, #9f101d 0%, #f97316 100%);
}

.login-card > span,
label span {
    display: block;
    color: #9f101d;
    font-size: 0.76rem;
    font-weight: 950;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-top: 18px;
}

h1 {
    margin: 8px 0;
    color: #111827;
    font-size: 2.2rem;
    font-weight: 950;
}

p {
    margin: 0 0 24px;
    color: #64748b;
    font-weight: 750;
}

input {
    width: 100%;
    height: 56px;
    border: 1px solid #dbe2ea;
    border-radius: 16px;
    padding: 0 16px;
    color: #111827;
    font-size: 1rem;
    font-weight: 850;
    outline: 0;
    text-transform: uppercase;
}

input:focus {
    border-color: #c1121f;
    box-shadow: 0 0 0 4px rgba(193, 18, 31, 0.08);
}

button {
    width: 100%;
    height: 56px;
    border: 0;
    border-radius: 16px;
    margin-top: 18px;
    color: #fff;
    background: linear-gradient(135deg, #9f101d 0%, #c1121f 55%, #f97316 100%);
    font-weight: 950;
    box-shadow: 0 16px 32px rgba(193, 18, 31, 0.22);
}
</style>
