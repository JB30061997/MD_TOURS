<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from "vue";
import { loaderState, useGlobalLoader } from "@/Composables/useGlobalLoader";

const { visible } = useGlobalLoader();
const overlay = ref(null);
let previousFocus = null;

const blockKeyboard = (event) => {
    if (!visible.value) return;
    if (["Tab", "Enter", " ", "Escape"].includes(event.key)) {
        event.preventDefault();
        event.stopImmediatePropagation();
        overlay.value?.focus({ preventScroll: true });
    }
};

watch(visible, async (isVisible) => {
    const appShell = document.getElementById("__app-shell");
    document.documentElement.setAttribute("aria-busy", String(isVisible));

    if (isVisible) {
        previousFocus = document.activeElement;
        appShell?.setAttribute("inert", "");
        document.body.classList.add("global-loader-active");
        window.addEventListener("keydown", blockKeyboard, true);
        await nextTick();
        overlay.value?.focus({ preventScroll: true });
    } else {
        appShell?.removeAttribute("inert");
        document.body.classList.remove("global-loader-active");
        window.removeEventListener("keydown", blockKeyboard, true);
        previousFocus?.focus?.({ preventScroll: true });
        previousFocus = null;
    }
});

onBeforeUnmount(() => window.removeEventListener("keydown", blockKeyboard, true));
</script>

<template>
    <Transition name="global-loader-fade">
        <div
            v-if="visible"
            ref="overlay"
            class="global-loader-overlay"
            role="status"
            aria-live="assertive"
            aria-atomic="true"
            :aria-label="loaderState.message"
            tabindex="-1"
        >
            <div class="global-loader-content">
                <div class="loader-road" aria-hidden="true">
                    <span class="speed-line line-one"></span>
                    <span class="speed-line line-two"></span>
                    <span class="speed-line line-three"></span>
                    <span class="smoke smoke-one"></span>
                    <span class="smoke smoke-two"></span>
                    <span class="smoke smoke-three"></span>
                    <div class="minibus-wrap">
                        <img src="/assets/images/loaders/md-tours-minibus.png" alt="" class="loader-minibus" />
                    </div>
                    <span class="minibus-shadow"></span>
                </div>
                <div class="loader-dots" aria-hidden="true"><span></span><span></span><span></span></div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
:global(body.global-loader-active) { overflow: hidden !important; cursor: wait; }
.global-loader-overlay { position: fixed; inset: 0; z-index: 20000; display: grid; place-items: center; padding: 20px; background: rgba(248, 250, 252, .74); backdrop-filter: blur(9px) saturate(115%); -webkit-backdrop-filter: blur(9px) saturate(115%); outline: 0; cursor: wait; }
.global-loader-content { width: min(390px, calc(100vw - 32px)); text-align: center; }
.loader-road { position: relative; width: 100%; height: 152px; overflow: hidden; }
.loader-road::after { content: ""; position: absolute; right: 4%; bottom: 23px; left: 4%; height: 2px; border-radius: 99px; background: linear-gradient(90deg, transparent, rgba(100,116,139,.26) 18%, rgba(100,116,139,.26) 82%, transparent); }
.minibus-wrap { position: absolute; z-index: 4; bottom: 20px; left: 50%; width: 236px; transform: translateX(-50%); animation: minibusDrive 1.55s ease-in-out infinite; }
.loader-minibus { position: relative; z-index: 1; display: block; width: 100%; height: auto; filter: drop-shadow(0 8px 8px rgba(15,23,42,.12)); }
.minibus-wrap::before,.minibus-wrap::after { content: ""; position: absolute; z-index: 2; bottom: 7%; width: 14%; aspect-ratio: 1; border-radius: 50%; background: conic-gradient(from 0deg, rgba(255,255,255,.72), transparent 16%, rgba(255,255,255,.55) 34%, transparent 50%, rgba(255,255,255,.68) 72%, transparent 88%); opacity: .34; animation: wheelSpin .72s linear infinite; pointer-events: none; }
.minibus-wrap::before { left: 36.5%; }.minibus-wrap::after { right: 5.8%; }
.minibus-shadow { position: absolute; z-index: 2; bottom: 17px; left: 50%; width: 185px; height: 13px; border-radius: 50%; background: rgba(15,23,42,.18); filter: blur(5px); transform: translateX(-50%); animation: minibusShadow 1.55s ease-in-out infinite; }
.speed-line { position: absolute; z-index: 1; left: 15px; height: 2px; border-radius: 99px; background: linear-gradient(90deg, transparent, rgba(193,18,31,.3)); animation: speedLine 1.2s ease-in-out infinite; }
.line-one { top: 66px; width: 72px; }.line-two { top: 84px; width: 48px; animation-delay: -.35s; }.line-three { top: 101px; width: 62px; animation-delay: -.7s; }
.smoke { position: absolute; z-index: 3; bottom: 34px; left: 38px; width: 12px; height: 12px; border-radius: 50%; background: rgba(148,163,184,.27); filter: blur(1px); animation: loaderSmoke 1.5s ease-out infinite; }
.smoke-two { animation-delay: -.5s; }.smoke-three { animation-delay: -1s; }
.loader-dots { display: flex; justify-content: center; gap: 6px; height: 18px; margin-top: 2px; }.loader-dots span { width: 7px; height: 7px; border-radius: 50%; background: #c1121f; animation: loaderDot 1.1s ease-in-out infinite; }.loader-dots span:nth-child(2) { animation-delay: .14s; }.loader-dots span:nth-child(3) { animation-delay: .28s; }
.global-loader-fade-enter-active,.global-loader-fade-leave-active { transition: opacity .2s ease; }.global-loader-fade-enter-active .global-loader-content,.global-loader-fade-leave-active .global-loader-content { transition: transform .22s ease, opacity .18s ease; }.global-loader-fade-enter-from,.global-loader-fade-leave-to { opacity: 0; }.global-loader-fade-enter-from .global-loader-content,.global-loader-fade-leave-to .global-loader-content { opacity: 0; transform: scale(.965) translateY(6px); }
@keyframes minibusDrive { 0%,100% { transform: translateX(calc(-50% - 7px)) translateY(0) rotate(-.35deg); } 42% { transform: translateX(calc(-50% + 8px)) translateY(-3px) rotate(.4deg); } 62% { transform: translateX(calc(-50% + 4px)) translateY(0); } }
@keyframes minibusShadow { 0%,100% { opacity: .55; transform: translateX(-50%) scaleX(.92); } 45% { opacity: .34; transform: translateX(-50%) scaleX(1.05); } }
@keyframes speedLine { 0% { opacity: 0; transform: translateX(-20px) scaleX(.55); } 35% { opacity: 1; } 100% { opacity: 0; transform: translateX(65px) scaleX(1.15); } }
@keyframes loaderSmoke { 0% { opacity: 0; transform: translate(75px,0) scale(.45); } 22% { opacity: .6; } 100% { opacity: 0; transform: translate(4px,-18px) scale(1.6); } }
@keyframes loaderDot { 0%,60%,100% { opacity: .28; transform: translateY(0) scale(.8); } 30% { opacity: 1; transform: translateY(-4px) scale(1); } }
@keyframes wheelSpin { to { transform: rotate(360deg); } }
@media (max-width: 575px) { .global-loader-content { width: min(320px, calc(100vw - 24px)); }.loader-road { height: 128px; }.minibus-wrap { width: 205px; } }
@media (prefers-reduced-motion: reduce) { .minibus-wrap,.minibus-shadow,.speed-line,.smoke,.loader-dots span,.minibus-wrap::before,.minibus-wrap::after { animation-duration: 3s; animation-timing-function: linear; } }
</style>
