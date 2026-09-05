<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 px-4 py-10 sm:px-6">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="absolute -bottom-40 -right-24 h-96 w-96 rounded-full bg-cyan-400/10 blur-3xl"></div>
        </div>

        <div class="relative text-center">
            <Link href="/">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black text-white">LX</span>
            </Link>
            <p class="mt-4 text-xs font-bold uppercase tracking-[0.28em] text-indigo-300">LIASSE EXPERT</p>
            <p class="mt-2 text-sm font-bold uppercase text-slate-400">GESTION &amp; AUTOMATISATION FISCALE</p>
        </div>

        <div class="relative mt-8 w-full overflow-hidden rounded-2xl border border-white/10 bg-white px-6 py-7 shadow-2xl shadow-black/30 sm:max-w-md sm:px-8">
            <slot />
        </div>
    </div>
</template>
