<script setup>
import { Head, Link } from '@inertiajs/vue3';
defineProps({ canLogin: Boolean, canRegister: Boolean });
</script>

<template>
    <Head title="Accueil" />
    <div class="relative min-h-screen overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0" aria-hidden="true"><div class="absolute -left-40 -top-40 h-[34rem] w-[34rem] rounded-full bg-indigo-600/25 blur-3xl"></div><div class="absolute -bottom-48 right-0 h-[34rem] w-[34rem] rounded-full bg-cyan-500/15 blur-3xl"></div></div>
        <header class="relative mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
            <div class="flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black shadow-lg shadow-indigo-950">LX</span><div><p class="font-extrabold tracking-tight">LIASSE EXPERT</p><p class="text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-300">Fiscalité marocaine</p></div></div>
            <nav v-if="canLogin" class="flex items-center gap-2"><Link v-if="$page.props.auth.user" :href="route('dashboard')" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-sm">Tableau de bord</Link><template v-else><Link :href="route('login')" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/10">Connexion</Link><Link v-if="canRegister" :href="route('register')" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-950/40 transition hover:bg-indigo-500">Créer un compte</Link></template></nav>
        </header>
        <main class="relative mx-auto grid min-h-[calc(100vh-92px)] max-w-7xl items-center gap-14 px-6 py-16 lg:grid-cols-2 lg:px-8">
            <div><span class="inline-flex rounded-full border border-indigo-400/30 bg-indigo-400/10 px-3 py-1 text-xs font-bold uppercase tracking-wider text-indigo-200">Gestion fiscale intégrée</span><h1 class="mt-6 text-4xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">Votre liasse fiscale,<br><span class="text-indigo-400">claire et maîtrisée.</span></h1><p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">Importez vos balances N et N-1, consultez vos états fiscaux et centralisez les informations de votre entreprise dans un espace professionnel.</p><div v-if="canLogin" class="mt-8 flex flex-wrap gap-3"><Link :href="$page.props.auth.user ? route('dashboard') : route('login')" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-xl shadow-indigo-950/50 transition hover:-translate-y-0.5 hover:bg-indigo-500">Accéder à l'application</Link></div></div>
            <div class="grid gap-4 sm:grid-cols-2"><div v-for="item in [{n:'N / N-1',t:'Historisation',d:'Comparaison immédiate des exercices.'},{n:'26',t:'Tableaux fiscaux',d:'Tous vos états dans un même espace.'},{n:'100 %',t:'Centralisé',d:'Société, balances et liasse réunies.'},{n:'✓',t:'Contrôles',d:'Une lecture claire des cohérences.'}]" :key="item.t" class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 shadow-xl backdrop-blur transition hover:-translate-y-1 hover:bg-white/[0.09]"><p class="text-3xl font-black text-indigo-300">{{ item.n }}</p><h2 class="mt-4 font-bold text-white">{{ item.t }}</h2><p class="mt-2 text-sm leading-6 text-slate-400">{{ item.d }}</p></div></div>
        </main>
    </div>
</template>
