<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-50 transition-colors dark:bg-slate-950">
        <nav class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur transition-colors dark:border-slate-800 dark:bg-slate-950/95">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex min-w-0 items-center gap-8">
                        <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-3 rounded-lg">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-700 shadow-sm">
                                <ApplicationLogo class="h-7 w-7 fill-current text-white" />
                            </span>
                            <span class="hidden sm:block">
                                <span class="block text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">LIASSE EXPERT</span>
                                <span class="block text-[10px] font-semibold uppercase tracking-[0.18em] text-indigo-600">Système marocain</span>
                            </span>
                        </Link>

                        <Link
                            :href="route('dashboard')"
                            class="hidden rounded-lg px-3 py-2 text-sm font-semibold transition sm:block"
                            :class="route().current('dashboard') ? 'bg-blue-50 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'"
                        >
                            Tableau de bord
                        </Link>
                    </div>

                    <div class="hidden items-center sm:flex">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:bg-slate-800">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-100 text-xs font-bold uppercase text-blue-800 dark:bg-blue-500/20 dark:text-blue-100">
                                        {{ $page.props.auth.user.name.charAt(0) }}
                                    </span>
                                    <span class="max-w-36 truncate">{{ $page.props.auth.user.name }}</span>
                                    <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Mon profil</DropdownLink>
                                <DropdownLink :href="route('settings.index')">Paramètres</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Déconnexion</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white sm:hidden"
                        :aria-expanded="showingNavigationDropdown"
                        aria-label="Ouvrir le menu"
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                    >
                        <svg v-if="!showingNavigationDropdown" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <div v-show="showingNavigationDropdown" class="border-t border-slate-200 bg-white sm:hidden dark:border-slate-800 dark:bg-slate-950">
                <div class="space-y-1 px-3 py-3">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Tableau de bord</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('profile.edit')">Mon profil</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('settings.index')" :active="route().current('settings.index')">Paramètres</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">Déconnexion</ResponsiveNavLink>
                </div>
                <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                    <div class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $page.props.auth.user.name }}</div>
                    <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $page.props.auth.user.email }}</div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="border-b border-slate-200 bg-white transition-colors dark:border-slate-800 dark:bg-slate-950">
            <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8"><slot name="header" /></div>
        </header>

        <main><slot /></main>
    </div>
</template>
