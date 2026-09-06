<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { applyTheme, getStoredTheme, setTheme, systemPrefersDark } from '@/theme';

const currentTheme = ref('system');
const resolvedTheme = ref('light');
const page = usePage();
const props = defineProps({
    success: { type: String, default: null },
});
const availableExercices = computed(() => page.props.availableExercices || []);
const activeExercice = computed(() => page.props.activeExercice);
const maxExerciseYear = new Date().getFullYear() + 10;
const exerciseForm = useForm({ exercice: new Date().getFullYear() });

const createExercise = () => exerciseForm.post(route('fiscal-exercises.store'), {
    preserveScroll: true,
});

const options = [
    {
        value: 'light',
        icon: '☀',
        title: 'Clair',
        description: 'Interface lumineuse pour les environnements de bureau.',
    },
    {
        value: 'dark',
        icon: '◐',
        title: 'Sombre',
        description: 'Interface assombrie pour réduire la fatigue visuelle.',
    },
    {
        value: 'system',
        icon: '▣',
        title: 'Système',
        description: 'Suit automatiquement le thème de votre appareil.',
    },
];

const resolvedLabel = computed(() => (resolvedTheme.value === 'dark' ? 'Sombre' : 'Clair'));

const refreshThemeState = () => {
    const stored = getStoredTheme();
    currentTheme.value = stored;
    resolvedTheme.value = stored === 'system'
        ? (systemPrefersDark() ? 'dark' : 'light')
        : stored;
};

const chooseTheme = (theme) => {
    const result = setTheme(theme);
    currentTheme.value = result.theme;
    resolvedTheme.value = result.resolvedTheme;
};

const handleThemeChanged = (event) => {
    currentTheme.value = event.detail?.theme || getStoredTheme();
    resolvedTheme.value = event.detail?.resolvedTheme || applyTheme(currentTheme.value).resolvedTheme;
};

onMounted(() => {
    refreshThemeState();
    window.addEventListener('theme:changed', handleThemeChanged);
});

onUnmounted(() => {
    window.removeEventListener('theme:changed', handleThemeChanged);
});
</script>

<template>
    <Head title="Paramètres" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">Configuration</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Paramètres</h1>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Apparence actuelle :
                    <strong class="text-slate-800 dark:text-slate-100">{{ resolvedLabel }}</strong>
                </p>
            </div>
        </template>

        <div class="min-h-screen bg-slate-50 py-6 transition-colors dark:bg-slate-950 sm:py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="props.success" class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ props.success }}
                </div>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="border-b border-slate-200 pb-5 dark:border-slate-800">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">Dossiers fiscaux</p>
                        <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white">Gestion des exercices</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Créez un exercice fiscal, même avant l’import de sa balance.</p>
                    </div>

                    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.55fr)]">
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Exercices disponibles</p>
                            <div v-if="availableExercices.length" class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                <div v-for="exercice in availableExercices" :key="exercice" class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800">
                                    <span class="font-black text-slate-900 dark:text-white">{{ exercice }}</span>
                                    <span v-if="Number(exercice) === Number(activeExercice)" class="rounded-full bg-blue-700 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white dark:bg-blue-400 dark:text-slate-950">Actif</span>
                                </div>
                            </div>
                            <p v-else class="mt-3 rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Aucun exercice fiscal n’est encore disponible.</p>
                        </div>

                        <form class="rounded-lg border border-slate-200 p-4 dark:border-slate-700" @submit.prevent="createExercise">
                            <label for="fiscal-exercise-year" class="block text-sm font-bold text-slate-800 dark:text-slate-100">Nouvel exercice</label>
                            <input id="fiscal-exercise-year" v-model="exerciseForm.exercice" type="number" min="1900" :max="maxExerciseYear" required class="ui-input mt-2" />
                            <p v-if="exerciseForm.errors.exercice" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ exerciseForm.errors.exercice }}</p>
                            <button type="submit" :disabled="exerciseForm.processing" class="ui-button-primary mt-4 w-full">
                                {{ exerciseForm.processing ? 'Ajout en cours…' : 'Ajouter un exercice' }}
                            </button>
                        </form>
                    </div>
                </section>

                <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="border-b border-slate-200 pb-5 dark:border-slate-800">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-300">Apparence</p>
                        <h2 class="mt-2 text-xl font-black tracking-tight text-slate-950 dark:text-white">Thème de l'application</h2>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Choisissez l'apparence de l'interface.</p>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <button
                            v-for="option in options"
                            :key="option.value"
                            type="button"
                            class="group flex min-h-40 flex-col rounded-lg border p-5 text-left shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-slate-950"
                            :class="currentTheme === option.value
                                ? 'border-blue-600 bg-blue-50 text-blue-950 ring-1 ring-blue-600 dark:border-blue-400 dark:bg-blue-500/10 dark:text-white dark:ring-blue-400'
                                : 'border-slate-200 bg-white text-slate-800 hover:border-blue-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-blue-500 dark:hover:bg-slate-800'"
                            :aria-pressed="currentTheme === option.value"
                            @click="chooseTheme(option.value)"
                        >
                            <span class="flex items-start justify-between gap-4">
                                <span
                                    class="flex h-11 w-11 items-center justify-center rounded-lg text-lg font-black"
                                    :class="currentTheme === option.value ? 'bg-blue-700 text-white dark:bg-blue-400 dark:text-slate-950' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                                    aria-hidden="true"
                                >
                                    {{ option.icon }}
                                </span>
                                <span
                                    v-if="currentTheme === option.value"
                                    class="rounded-full bg-blue-700 px-2.5 py-1 text-[10px] font-black uppercase tracking-wide text-white dark:bg-blue-400 dark:text-slate-950"
                                >
                                    Actif
                                </span>
                            </span>
                            <span class="mt-5 text-base font-black">{{ option.title }}</span>
                            <span class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ option.description }}</span>
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
