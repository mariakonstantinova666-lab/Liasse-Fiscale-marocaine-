<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
defineProps(['exercise', 'previousExercise', 'accountsCount', 'modelVersion', 'unavailable', 'analysis']);
const processing = ref(false);
const launch = () => {
    if (processing.value) return;
    processing.value = true;
    router.post(route('accounting-anomaly.analyze'), {}, { preserveScroll: true, onFinish: () => { processing.value = false; } });
};
const number = (value) => Number(value).toFixed(4);
const versionLabel = (value) => value === 'phase3b_v1' ? 'Version expérimentale 1' : value;
</script>

<template>
    <Head title="Analyse IA expérimentale" />
    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Analyse IA expérimentale</h1>
            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">Analyse de l’évolution des comptes entre N-1 et N à l’aide du modèle Isolation Forest.</p>
        </template>
        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
            <aside class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-4 text-sm leading-relaxed text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 11v6M12 7h.01" /></svg>
                <p>
                Analyse expérimentale. Les scores indiquent des atypies statistiques et ne constituent ni une erreur comptable certaine, ni une erreur fiscale, ni une preuve de fraude ou de non-conformité. Cette analyse ne participe pas aux contrôles fiscaux et ne bloque pas l’EDI/XML.
                </p>
            </aside>
            <section class="rounded-lg border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-4 text-sm font-semibold text-slate-900 dark:text-white">Contexte de l’analyse</h2>
                <dl class="grid gap-4 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2 lg:grid-cols-5 [&_dd]:break-words [&_dd]:text-slate-900 dark:[&_dd]:text-slate-100">
                    <div><dt>Exercice analysé</dt><dd class="mt-1 font-bold">{{ exercise ?? 'Indisponible' }}</dd></div>
                    <div><dt>Comparaison</dt><dd class="mt-1 font-bold">{{ previousExercise ?? '—' }} → {{ exercise ?? '—' }}</dd></div>
                    <div><dt>Modèle</dt><dd class="mt-1 font-bold">Isolation Forest</dd></div>
                    <div><dt>Version</dt><dd class="mt-1 font-mono">{{ versionLabel(modelVersion) }}</dd></div>
                    <div><dt>Comptes disponibles</dt><dd class="mt-1 font-bold tabular-nums">{{ accountsCount ?? '—' }}</dd></div>
                </dl>
                <p v-if="unavailable" role="status" class="mt-5 rounded-lg border border-slate-300 bg-slate-50 p-4 text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">{{ unavailable }}</p>
                <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <button type="button" :disabled="processing || !!unavailable" :aria-busy="processing" class="ui-button-primary w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto" @click="launch">{{ processing ? 'Analyse en cours…' : 'Lancer l’analyse expérimentale' }}</button>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">L’analyse est lancée uniquement à votre demande.</p>
                </div>
            </section>
            <section v-if="analysis" class="space-y-5">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in [['Comptes analysés', analysis.observations_count], ['Comptes signalés', analysis.flagged_count], ['Seuil expérimental', number(analysis.threshold)], ['Modèle / version', 'Isolation Forest / ' + versionLabel(analysis.model_version)]]" :key="item[0]" class="flex min-w-0 flex-col rounded-lg border border-slate-200 bg-white p-5 text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white"><p class="text-sm text-slate-600 dark:text-slate-300">{{ item[0] }}</p><p class="mt-3 break-words text-lg font-semibold tabular-nums">{{ item[1] }}</p></div>
                </div>
                <p v-if="analysis.flagged_count === 0" role="status" class="rounded-lg border border-slate-200 bg-slate-100 p-4 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">Aucun compte n’a été signalé au seuil expérimental.</p>
                <p class="text-sm text-slate-600 dark:text-slate-300">Score statistique : plus la valeur est basse, plus le compte est atypique selon le modèle.</p>
                <div class="max-h-[36rem] overflow-auto rounded-lg border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" tabindex="0" aria-label="Classement des comptes par score statistique">
                    <table class="w-full min-w-[850px] text-left text-sm text-slate-800 dark:text-slate-100">
                        <thead class="sticky top-0 z-10 bg-slate-100 dark:bg-slate-800"><tr><th v-for="title in ['Rang', 'Compte', 'Libellé', 'Score statistique', 'Statut']" :key="title" scope="col" class="px-4 py-3 text-xs font-semibold uppercase tracking-wide" :class="{ 'text-right': title === 'Score statistique', 'text-center': title === 'Rang' }">{{ title }}</th></tr></thead>
                        <tbody><tr v-for="row in analysis.results" :key="row.account" class="border-t border-slate-200 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60"><td class="px-4 py-3 text-center tabular-nums text-slate-500 dark:text-slate-400">{{ row.rank }}</td><td class="px-4 py-3 font-mono tabular-nums">{{ row.account }}</td><td class="px-4 py-3">{{ row.label }}</td><td class="px-4 py-3 text-right font-mono tabular-nums">{{ number(row.score_samples) }}</td><td class="px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-xs" :class="row.flagged ? 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'">{{ row.flagged ? 'Signalé au seuil expérimental' : 'Non signalé au seuil expérimental' }}</span></td></tr></tbody>
                    </table>
                </div>
            </section>
            <section class="rounded-lg border border-slate-200 bg-white p-5 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                <h2 class="font-semibold text-slate-900 dark:text-white">À propos de l’analyse IA</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-3 leading-relaxed sm:grid-cols-[9rem_1fr]">
                    <dt class="text-slate-500 dark:text-slate-400">Modèle</dt><dd>Isolation Forest</dd>
                    <dt class="text-slate-500 dark:text-slate-400">Type</dt><dd>Apprentissage automatique non supervisé</dd>
                    <dt class="text-slate-500 dark:text-slate-400">Analyse</dt><dd>Évolution des comptes entre N-1 et N</dd>
                    <dt class="text-slate-500 dark:text-slate-400">Objectif</dt><dd>Identifier les évolutions comptables statistiquement atypiques afin d’attirer l’attention de l’utilisateur sur certains comptes.</dd>
                    <dt class="text-slate-500 dark:text-slate-400">Rôle</dt><dd>Outil d’aide à l’analyse, indépendant des calculs et contrôles fiscaux.</dd>
                </dl>
                <p class="mt-4 border-t border-slate-200 pt-3 text-slate-600 dark:border-slate-800 dark:text-slate-300">Un score plus faible indique une observation statistiquement plus atypique. Ce score n’est pas une probabilité.</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
