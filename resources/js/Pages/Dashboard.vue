<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    items: Array,
    itemsPrecedent: { type: Array, default: () => [] },
    societe: Object,
    exerciceActif: [String, Number],
    exercicePrecedent: [String, Number],
    exercicesImportes: { type: Array, default: () => [] },
    sourceDocumentsCount: { type: Number, default: 0 },
    liasseDataCount: { type: Number, default: 0 },
    controlStatus: { type: String, default: null },
    hasGeneratedEdi: { type: Boolean, default: false },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const nImporte = computed(() => props.exercicesImportes.includes(Number(props.exerciceActif)));
const n1Importe = computed(() => props.exercicesImportes.includes(Number(props.exercicePrecedent)));
const importProgress = computed(() => Number(nImporte.value) + Number(n1Importe.value));
const exerciceDu = computed(() => `01/01/${props.exerciceActif}`);
const exerciceAu = computed(() => `31/12/${props.exerciceActif}`);
const balanceSelection = ref('n');
const balances = computed(() => [
    { key: 'n', label: 'Balance N', year: props.exerciceActif, imported: nImporte.value, rows: props.items || [] },
    { key: 'n1', label: 'Balance N-1', year: props.exercicePrecedent, imported: n1Importe.value, rows: props.itemsPrecedent || [] },
]);
const selectedBalance = computed(() => balances.value.find(balance => balance.key === balanceSelection.value) || balances.value[0]);
const selectedItems = computed(() => selectedBalance.value?.rows || []);

const form = useForm({ annee: props.exerciceActif || '', balance: null });
const handleSubmit = () => form.post(route('balance.import'), { forceFormData: true });

const editingSociete = ref(!props.societe);
const societeForm = useForm({
    nom_societe: props.societe?.nom_societe || '',
    if: props.societe?.if || '',
    ice: props.societe?.ice || '',
    rc: props.societe?.rc || '',
    cnss: props.societe?.cnss || '',
    patente: props.societe?.patente || '',
    adresse: props.societe?.adresse || '',
});
const saveSociete = () => societeForm.post(route('societe.save'), {
    preserveScroll: true,
    onSuccess: () => { editingSociete.value = false; },
});

const liasseTableaux = [
    ['T01-A', 'Bilan Actif', 'liasse.bilan_actif'], ['T01-B', 'Bilan Passif', 'liasse.bilan_passif'],
    ['T02', 'CPC', 'liasse.cpc'], ['T03', 'Passage fiscal', 'liasse.passage_fiscal'],
    ['T04', 'Immobilisations', 'liasse.immobilisations'], ['T05', 'ESG', 'liasse.esg'],
    ['T06', 'Detail CPC', 'liasse.detail_cpc'], ['T07', 'Credit-bail', 'liasse.credit_bail'],
    ['T08', 'Amortissements', 'liasse.amortissements'], ['T09', 'Provisions', 'liasse.provisions'],
    ['T10', 'Plus/moins-values', 'liasse.plus_values'], ['T11', 'Titres de participation', 'liasse.titres_participation'],
    ['T12', 'Detail TVA', 'liasse.tva'], ['T13', 'Repartition du capital', 'liasse.repartition_capital'],
    ['T14', 'Affectation des resultats', 'liasse.affectation_resultats'], ['T15', 'Calcul impot encouragement', 'liasse.calcul_impot_encouragement'],
    ['T16', 'Dotations aux amortissements', 'liasse.dotations_amortissements'], ['T17', 'Plus-values de fusion', 'liasse.plus_values_fusion'],
    ['T18', 'Interets des emprunts', 'liasse.interets_emprunts'], ['T19', 'Locations et baux', 'liasse.locations_baux'],
    ['T20', 'Etat detaille des stocks', 'liasse.detail_stocks'], ['T21', 'Operations en devises', 'liasse.operations_devises'],
    ['T22', 'TFT / Tableau de financement', 'liasse.tableau_financement'], ['T23', "Methodes d'evaluation", 'liasse.methodes_evaluation'],
    ['T24', 'Derogations', 'liasse.derogations'], ['T25', 'Changements de methodes', 'liasse.changements_methodes'],
    ['T26', 'Calcul IS entreprises encouragees', 'liasse.calcul_is_encouragees'], ['OK', 'Controle de coherence', 'liasse.controle'],
].map(([code, name, route]) => ({ code, name, route }));

const money = value => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Tableau de bord" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700 dark:text-blue-300">Pilotage fiscal</p><h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100">Tableau de bord</h1></div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Exercice actif <strong class="text-slate-800 dark:text-slate-100">{{ exerciceActif }}</strong></p>
            </div>
        </template>

        <div class="min-h-screen bg-slate-100/80 py-6 dark:bg-slate-950 sm:py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash?.success" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm"><span class="font-bold">OK</span><span>{{ flash.success }}</span></div>
                <div v-if="flash?.error" class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm"><span class="font-bold">!</span><span>{{ flash.error }}</span></div>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="ui-card border-l-4 border-l-blue-600 p-4"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Exercice courant</p><div class="mt-2 flex items-end justify-between gap-3"><p class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ exerciceActif }}</p><span class="rounded-md bg-blue-50 px-2 py-1 text-[10px] font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">N</span></div><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Balance de référence</p></div>
                    <div class="ui-card border-l-4 border-l-slate-400 p-4"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Historique</p><div class="mt-2 flex items-end justify-between gap-3"><p class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ exercicePrecedent }}</p><span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">N-1</span></div><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Exercice précédent</p></div>
                    <div class="ui-card border-l-4 p-4" :class="importProgress === 2 ? 'border-l-emerald-500' : 'border-l-amber-500'"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Importations</p><div class="mt-2 flex items-end justify-between gap-3"><p class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ importProgress }}/2</p><span class="mb-1 rounded-full px-2 py-1 text-[10px] font-bold" :class="importProgress === 2 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'">{{ importProgress === 2 ? 'Complet' : 'À compléter' }}</span></div><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Balances N et N-1</p></div>
                    <div class="ui-card border-l-4 border-l-blue-600 p-4"><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-400">Comptes importés</p><p class="mt-2 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ items?.length || 0 }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lignes de l'exercice actif</p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.75fr)]">
                    <div class="ui-card overflow-hidden">
                        <div class="border-b border-slate-200 bg-slate-50/80 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50 sm:px-6">
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Parcours de production</p>
                            <div class="mt-1 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between"><h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100">État du dossier fiscal</h2><p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Exercice {{ exerciceActif }}</p></div>
                        </div>
                        <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3">
                            <div v-for="(balance, index) in balances" :key="`step-${balance.key}`" class="rounded-xl border p-4" :class="balance.imported ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-500/10' : 'border-amber-200 bg-amber-50/70 dark:border-amber-800 dark:bg-amber-500/10'">
                                <div class="flex items-start justify-between gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-900 text-xs font-black text-white dark:bg-slate-700">{{ index + 1 }}</span><span class="rounded-full px-2 py-1 text-[10px] font-bold" :class="balance.imported ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white'">{{ balance.imported ? 'Importée' : 'À importer' }}</span></div>
                                <p class="mt-4 font-black text-slate-900 dark:text-slate-100">{{ balance.label }}</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Exercice {{ balance.year }}</p>
                            </div>
                            <a :href="route('source-documents.create')" class="group rounded-xl border p-4 transition hover:border-blue-300 hover:bg-blue-50 dark:hover:border-blue-500 dark:hover:bg-blue-500/10" :class="sourceDocumentsCount > 0 ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-500/10' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'">
                                <div class="flex items-start justify-between gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-600 group-hover:bg-blue-700 group-hover:text-white dark:bg-slate-800 dark:text-slate-300">3</span><span v-if="sourceDocumentsCount > 0" class="rounded-full bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white">{{ sourceDocumentsCount === 1 ? 'Importé' : 'Importés' }}</span></div>
                                <p class="mt-4 font-black text-slate-900 dark:text-slate-100">Documents source</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ sourceDocumentsCount > 0 ? 'Importer ou analyser un autre document' : 'Importer et analyser' }}</p>
                            </a>
                            <a :href="route('liasse.bilan_actif')" class="group rounded-xl border p-4 transition hover:border-blue-300 hover:bg-blue-50 dark:hover:border-blue-500 dark:hover:bg-blue-500/10" :class="liasseDataCount > 0 ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-500/10' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'">
                                <div class="flex items-start justify-between gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-600 group-hover:bg-blue-700 group-hover:text-white dark:bg-slate-800 dark:text-slate-300">4</span><span v-if="liasseDataCount > 0" class="rounded-full bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white">Données présentes</span></div><p class="mt-4 font-black text-slate-900 dark:text-slate-100">Liasse fiscale</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Consulter les tableaux</p>
                            </a>
                            <a :href="route('liasse.controle')" class="group rounded-xl border p-4 transition hover:border-blue-300 hover:bg-blue-50 dark:hover:border-blue-500 dark:hover:bg-blue-500/10" :class="controlStatus === 'compliant' ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-500/10' : controlStatus === 'warning' ? 'border-amber-200 bg-amber-50/70 dark:border-amber-800 dark:bg-amber-500/10' : controlStatus === 'blocking' ? 'border-red-200 bg-red-50/70 dark:border-red-800 dark:bg-red-500/10' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'">
                                <div class="flex items-start justify-between gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-600 group-hover:bg-blue-700 group-hover:text-white dark:bg-slate-800 dark:text-slate-300">5</span><span v-if="controlStatus" class="rounded-full px-2 py-1 text-[10px] font-bold text-white" :class="controlStatus === 'compliant' ? 'bg-emerald-600' : controlStatus === 'warning' ? 'bg-amber-500' : 'bg-red-600'">{{ controlStatus === 'compliant' ? 'Conforme' : controlStatus === 'warning' ? 'Avertissement' : 'Bloquant' }}</span></div><p class="mt-4 font-black text-slate-900 dark:text-slate-100">Contrôle de cohérence</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Accéder aux contrôles</p>
                            </a>
                            <a :href="route('liasse.edi.index')" class="group rounded-xl border p-4 transition hover:border-blue-300 hover:bg-blue-50 dark:hover:border-blue-500 dark:hover:bg-blue-500/10" :class="hasGeneratedEdi ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800 dark:bg-emerald-500/10' : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'">
                                <div class="flex items-start justify-between gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-600 group-hover:bg-blue-700 group-hover:text-white dark:bg-slate-800 dark:text-slate-300">6</span><span v-if="hasGeneratedEdi" class="rounded-full bg-emerald-600 px-2 py-1 text-[10px] font-bold text-white">Généré</span></div><p class="mt-4 font-black text-slate-900 dark:text-slate-100">EDI / XML</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ hasGeneratedEdi ? 'Générer un nouvel export' : 'Préparer l\'export' }}</p>
                            </a>
                        </div>
                    </div>

                    <div id="balance-import" class="ui-card p-5 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Actions rapides</p>
                        <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-slate-100">Alimenter le dossier</h2>
                        <form @submit.prevent="handleSubmit" class="mt-5 space-y-4">
                            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">Année d'exercice</label><select v-model="form.annee" required class="ui-input"><option value="">Sélectionner</option><option value="2024">2024</option><option value="2025">2025</option><option value="2026">2026</option></select></div>
                            <div><label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">Fichier de la balance</label><input type="file" required @input="form.balance = $event.target.files[0]" class="ui-input file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-700 dark:file:bg-slate-700 dark:file:text-slate-100" /></div>
                            <button type="submit" :disabled="form.processing" class="ui-button-primary w-full">{{ form.processing ? 'Importation...' : 'Importer la balance' }}</button>
                        </form>
                        <div class="my-5 flex items-center gap-3"><span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span><span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ou</span><span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span></div>
                        <a :href="route('source-documents.create')" class="ui-button-secondary w-full border-blue-200 text-blue-800 dark:border-blue-500/40 dark:text-blue-200">Importer un document source</a>
                        <div class="mt-3 grid grid-cols-2 gap-2"><a :href="route('liasse.controle')" class="ui-button-secondary px-3 text-xs">Contrôle</a><a :href="route('liasse.edi.index')" class="ui-button-secondary px-3 text-xs">EDI / XML</a></div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-3">
                    <div class="ui-card p-5 sm:p-6 xl:col-span-2">
                        <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Entreprise</p><h2 class="mt-1 text-lg font-black text-slate-900 dark:text-slate-100">{{ societe?.nom_societe || 'Société à configurer' }}</h2></div><button v-if="societe && !editingSociete" type="button" @click="editingSociete = true" class="ui-button-secondary">Modifier</button></div>
                        <div v-if="societe && !editingSociete" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div v-for="([label, value]) in [['Identifiant fiscal', societe.if], ['ICE', societe.ice], ['Registre du commerce', societe.rc], ['CNSS', societe.cnss], ['Patente (TP)', societe.patente], ['Exercice du', exerciceDu], ['Exercice au', exerciceAu]]" :key="label" class="rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800"><p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ label }}</p><p class="mt-0.5 break-all text-sm font-bold text-slate-800 dark:text-slate-100">{{ value || 'Non renseigné' }}</p></div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800 sm:col-span-2 lg:col-span-3"><p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Adresse</p><p class="mt-0.5 text-sm font-bold leading-5 text-slate-800 dark:text-slate-100">{{ societe.adresse || 'Non renseignée' }}</p></div>
                        </div>
                        <form v-else @submit.prevent="saveSociete" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="lg:col-span-3"><label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">Raison sociale *</label><input v-model="societeForm.nom_societe" required class="ui-input" /><p v-if="societeForm.errors.nom_societe" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ societeForm.errors.nom_societe }}</p></div>
                            <div v-for="field in [{k:'if',l:'Identifiant fiscal'}, {k:'ice',l:'ICE'}, {k:'rc',l:'Registre du commerce'}, {k:'cnss',l:'CNSS'}, {k:'patente',l:'Patente (TP)'}]" :key="field.k"><label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">{{ field.l }}</label><input v-model="societeForm[field.k]" class="ui-input" /><p v-if="societeForm.errors[field.k]" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ societeForm.errors[field.k] }}</p></div>
                            <div class="lg:col-span-3"><label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">Adresse</label><textarea v-model="societeForm.adresse" rows="3" class="ui-input resize-y"></textarea><p v-if="societeForm.errors.adresse" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ societeForm.errors.adresse }}</p></div>
                            <div class="flex items-end gap-3"><button type="submit" :disabled="societeForm.processing" class="ui-button-primary">{{ societeForm.processing ? 'Enregistrement...' : 'Enregistrer' }}</button><button v-if="societe" type="button" @click="editingSociete = false" class="ui-button-secondary">Annuler</button></div>
                        </form>
                    </div>

                    <div class="ui-card p-5 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Balances</p>
                        <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-slate-100">Exercices disponibles</h2>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div v-for="exercise in [{label:'Exercice N',year:exerciceActif},{label:'Exercice N-1',year:exercicePrecedent}]" :key="exercise.label" class="rounded-xl border border-slate-200 bg-slate-50/70 p-3 dark:border-slate-700 dark:bg-slate-800">
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ exercise.label }}</p>
                                <p class="mt-1 text-lg font-black text-slate-900 dark:text-slate-100">{{ exercise.year }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ui-card p-5 sm:p-6"><div class="flex items-end justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Liasse fiscale</p><h2 class="mt-1 text-lg font-black text-slate-900 dark:text-slate-100">Accès aux tableaux</h2></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-300">T01–T26</span></div><div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"><a v-for="tableau in liasseTableaux" :key="tableau.route" :href="route(tableau.route)" class="group flex min-h-12 items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 transition hover:border-blue-300 hover:bg-blue-50 dark:border-slate-700 dark:hover:border-blue-500 dark:hover:bg-blue-500/10"><span class="flex h-8 min-w-10 items-center justify-center rounded-md bg-slate-100 px-1.5 text-[10px] font-black text-slate-600 transition group-hover:bg-blue-700 group-hover:text-white dark:bg-slate-800 dark:text-slate-300">{{ tableau.code }}</span><span class="text-sm font-semibold leading-tight text-slate-700 group-hover:text-blue-800 dark:text-slate-300 dark:group-hover:text-blue-300">{{ tableau.name }}</span><span class="ml-auto text-slate-300 group-hover:text-blue-500 dark:text-slate-600">›</span></a></div></section>

                <section class="ui-card overflow-hidden">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-700 sm:px-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Balance comptable</p>
                                <h2 class="mt-1 text-lg font-black text-slate-900 dark:text-slate-100">Lignes importées</h2>
                            </div>
                            <span class="w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ selectedItems.length }} lignes</span>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <button v-for="balance in balances" :key="balance.key" type="button" @click="balanceSelection = balance.key" class="rounded-xl border p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-blue-500" :class="balanceSelection === balance.key ? 'border-blue-300 bg-blue-50 shadow-sm dark:border-blue-500 dark:bg-blue-500/10' : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600'">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-black text-slate-900 dark:text-slate-100">{{ balance.label }}</p>
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="balance.imported ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300'">{{ balance.imported ? 'Importée' : 'À importer' }}</span>
                                </div>
                                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Exercice {{ balance.year }}</p>
                            </button>
                        </div>
                    </div>
                    <div v-if="selectedItems.length" class="max-h-[520px] overflow-auto">
                        <table class="min-w-full text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-800 text-xs uppercase tracking-wide text-white">
                                <tr><th class="px-5 py-3 text-left">Compte</th><th class="px-5 py-3 text-left">Libelle</th><th class="px-5 py-3 text-right">Debit</th><th class="px-5 py-3 text-right">Credit</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-for="item in selectedItems" :key="`${selectedBalance.key}-${item.id}`" class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50 dark:odd:bg-slate-900 dark:even:bg-slate-800/60 dark:hover:bg-indigo-500/10">
                                    <td class="px-5 py-3 font-mono font-bold text-slate-900 dark:text-slate-100">{{ item.compte }}</td>
                                    <td class="px-5 py-3 text-slate-600 dark:text-slate-300">{{ item.libelle }}</td>
                                    <td class="px-5 py-3 text-right font-mono tabular-nums">{{ money(item.solde_debiteur) }} DH</td>
                                    <td class="px-5 py-3 text-right font-mono tabular-nums">{{ money(item.solde_crediteur) }} DH</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">-</div>
                        <p class="mt-3 font-semibold text-slate-700 dark:text-slate-300">{{ selectedBalance.label }} non importee</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Importez la balance de l'exercice {{ selectedBalance.year }} pour consulter ses lignes.</p>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
