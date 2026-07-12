<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    items: Array,
    societe: Object,
    exerciceActif: [String, Number],
    exercicePrecedent: [String, Number],
    exercicesImportes: { type: Array, default: () => [] },
});

const page = usePage();
const flash = computed(() => page.props.flash);
const nImporte = computed(() => props.exercicesImportes.includes(Number(props.exerciceActif)));
const n1Importe = computed(() => props.exercicesImportes.includes(Number(props.exercicePrecedent)));
const importProgress = computed(() => Number(nImporte.value) + Number(n1Importe.value));

const form = useForm({ annee: props.exerciceActif || '', balance: null });
const handleSubmit = () => form.post(route('balance.import'), { forceFormData: true });

const editingSociete = ref(!props.societe);
const societeForm = useForm({
    nom_societe: props.societe?.nom_societe || '', if: props.societe?.if || '',
    ice: props.societe?.ice || '', rc: props.societe?.rc || '', cnss: props.societe?.cnss || '',
});
const saveSociete = () => societeForm.post(route('societe.save'), {
    preserveScroll: true, onSuccess: () => { editingSociete.value = false; },
});

const liasseTableaux = [
    ['T01', 'Bilan Actif', 'liasse.bilan_actif'], ['T01', 'Bilan Passif', 'liasse.bilan_passif'],
    ['T02', 'Compte de produits et charges', 'liasse.cpc'], ['T03', 'Passage fiscal', 'liasse.passage_fiscal'],
    ['T04', 'Immobilisations', 'liasse.immobilisations'], ['T08', 'Amortissements', 'liasse.amortissements'],
    ['T09', 'Provisions', 'liasse.provisions'], ['T12', 'Détail de la TVA', 'liasse.tva'],
    ['T05', 'Soldes intermédiaires de gestion', 'liasse.esg'], ['T06', 'Détail des postes CPC', 'liasse.detail_cpc'],
    ['T07', 'Biens en crédit-bail', 'liasse.credit_bail'], ['T10', 'Plus ou moins-values', 'liasse.plus_values'],
    ['T11', 'Titres de participation', 'liasse.titres_participation'], ['T13', 'Répartition du capital', 'liasse.repartition_capital'],
    ['T14', 'Affectation des résultats', 'liasse.affectation_resultats'], ['T15', 'Calcul impôt encouragement', 'liasse.calcul_impot_encouragement'],
    ['T16', 'Dotations aux amortissements', 'liasse.dotations_amortissements'], ['T17', 'Plus-values de fusion', 'liasse.plus_values_fusion'],
    ['T18', 'Intérêts des emprunts', 'liasse.interets_emprunts'], ['T19', 'Locations et baux', 'liasse.locations_baux'],
    ['T20', 'État détaillé des stocks', 'liasse.detail_stocks'], ['T21', 'Opérations en devises', 'liasse.operations_devises'],
    ['T22', 'Tableau de financement', 'liasse.tableau_financement'], ['T23', "Méthodes d'évaluation", 'liasse.methodes_evaluation'],
    ['T24', 'État des dérogations', 'liasse.derogations'], ['T25', 'Changements de méthodes', 'liasse.changements_methodes'],
    ['T26', 'Calcul IS entreprises encouragées', 'liasse.calcul_is_encouragees'], ['✓', 'Contrôle de cohérence', 'liasse.controle'],
].map(([code, name, route]) => ({ code, name, route }));

const money = value => Number(value || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
</script>

<template>
    <Head title="Tableau de bord" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">Pilotage fiscal</p><h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">Tableau de bord</h1></div>
                <p class="text-sm text-slate-500">Exercice actif <strong class="text-slate-800">{{ exerciceActif }}</strong></p>
            </div>
        </template>

        <div class="min-h-screen bg-slate-50 py-6 sm:py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="flash?.success" class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 shadow-sm"><span class="font-bold">✓</span><span>{{ flash.success }}</span></div>
                <div v-if="flash?.error" class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm"><span class="font-bold">!</span><span>{{ flash.error }}</span></div>

                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="ui-card p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Exercice courant</p><p class="mt-3 text-3xl font-black text-slate-900">{{ exerciceActif }}</p><p class="mt-1 text-xs text-slate-500">Balance N</p></div>
                    <div class="ui-card p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Historique</p><p class="mt-3 text-3xl font-black text-slate-900">{{ exercicePrecedent }}</p><p class="mt-1 text-xs text-slate-500">Balance N-1</p></div>
                    <div class="ui-card p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Importations</p><div class="mt-3 flex items-end gap-2"><p class="text-3xl font-black text-slate-900">{{ importProgress }}/2</p><span class="mb-1 rounded-full px-2 py-1 text-[10px] font-bold" :class="importProgress === 2 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ importProgress === 2 ? 'Complet' : 'À compléter' }}</span></div></div>
                    <div class="ui-card p-5"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Comptes importés</p><p class="mt-3 text-3xl font-black text-slate-900">{{ items?.length || 0 }}</p><p class="mt-1 text-xs text-slate-500">Lignes pour l'exercice actif</p></div>
                </section>

                <section class="grid gap-6 xl:grid-cols-3">
                    <div class="ui-card p-5 sm:p-6 xl:col-span-2">
                        <div class="flex items-center justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Entreprise</p><h2 class="mt-1 text-lg font-bold text-slate-900">{{ societe?.nom_societe || 'Société à configurer' }}</h2></div><button v-if="societe && !editingSociete" type="button" @click="editingSociete = true" class="ui-button-secondary">Modifier</button></div>
                        <div v-if="societe && !editingSociete" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div v-for="([label, value]) in [['Identifiant fiscal', societe.if], ['ICE', societe.ice], ['Registre du commerce', societe.rc], ['CNSS', societe.cnss]]" :key="label" class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium text-slate-500">{{ label }}</p><p class="mt-1 break-all text-sm font-bold text-slate-800">{{ value || 'Non renseigné' }}</p></div>
                        </div>
                        <form v-else @submit.prevent="saveSociete" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="lg:col-span-3"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Raison sociale *</label><input v-model="societeForm.nom_societe" required class="ui-input" /><p v-if="societeForm.errors.nom_societe" class="mt-1 text-xs text-rose-600">{{ societeForm.errors.nom_societe }}</p></div>
                            <div v-for="field in [{k:'if',l:'Identifiant fiscal'}, {k:'ice',l:'ICE'}, {k:'rc',l:'Registre du commerce'}, {k:'cnss',l:'CNSS'}]" :key="field.k"><label class="mb-1.5 block text-sm font-semibold text-slate-700">{{ field.l }}</label><input v-model="societeForm[field.k]" class="ui-input" /><p v-if="societeForm.errors[field.k]" class="mt-1 text-xs text-rose-600">{{ societeForm.errors[field.k] }}</p></div>
                            <div class="flex items-end gap-3"><button type="submit" :disabled="societeForm.processing" class="ui-button-primary">{{ societeForm.processing ? 'Enregistrement…' : 'Enregistrer' }}</button><button v-if="societe" type="button" @click="editingSociete = false" class="ui-button-secondary">Annuler</button></div>
                        </form>
                    </div>

                    <div class="ui-card p-5 sm:p-6"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Balances</p><h2 class="mt-1 text-lg font-bold text-slate-900">Statut des exercices</h2><div class="mt-5 space-y-3"><div v-for="status in [{label:'Exercice N',year:exerciceActif,ok:nImporte},{label:'Exercice N-1',year:exercicePrecedent,ok:n1Importe}]" :key="status.label" class="flex items-center justify-between rounded-xl border p-3" :class="status.ok ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'"><div><p class="text-xs text-slate-500">{{ status.label }}</p><p class="font-bold text-slate-800">{{ status.year }}</p></div><span class="rounded-full px-2.5 py-1 text-[10px] font-bold" :class="status.ok ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white'">{{ status.ok ? 'Importée' : 'À importer' }}</span></div></div></div>
                </section>

                <section class="ui-card p-5 sm:p-6"><div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Importation</p><h2 class="mt-1 text-lg font-bold text-slate-900">Charger une balance comptable</h2></div><p class="text-xs text-slate-500">Formats acceptés : Excel et CSV</p></div><form @submit.prevent="handleSubmit" class="mt-5 grid gap-4 md:grid-cols-4 md:items-end"><div><label class="mb-1.5 block text-sm font-semibold text-slate-700">Année d'exercice</label><select v-model="form.annee" required class="ui-input"><option value="">Sélectionner</option><option value="2024">2024</option><option value="2025">2025</option><option value="2026">2026</option></select></div><div class="md:col-span-2"><label class="mb-1.5 block text-sm font-semibold text-slate-700">Fichier de la balance</label><input type="file" required @input="form.balance = $event.target.files[0]" class="ui-input file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700" /></div><button type="submit" :disabled="form.processing" class="ui-button-primary h-[42px]">{{ form.processing ? 'Importation…' : 'Lancer l’importation' }}</button></form></section>

                <section class="ui-card p-5 sm:p-6"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Liasse fiscale</p><h2 class="mt-1 text-lg font-bold text-slate-900">Accès aux tableaux</h2></div><div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"><a v-for="tableau in liasseTableaux" :key="tableau.route" :href="route(tableau.route)" class="group flex items-center gap-3 rounded-xl border border-slate-200 p-3.5 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-indigo-50/60 hover:shadow-sm"><span class="flex h-10 min-w-10 items-center justify-center rounded-lg bg-slate-100 px-2 text-[10px] font-black text-slate-600 transition group-hover:bg-indigo-600 group-hover:text-white">{{ tableau.code }}</span><span class="text-sm font-semibold leading-tight text-slate-700 group-hover:text-indigo-800">{{ tableau.name }}</span><span class="ml-auto text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-indigo-500">→</span></a></div></section>

                <section class="ui-card overflow-hidden"><div class="flex items-center justify-between border-b border-slate-200 p-5 sm:px-6"><div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Balance comptable</p><h2 class="mt-1 text-lg font-bold text-slate-900">Lignes importées</h2></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ items?.length || 0 }} lignes</span></div><div v-if="items?.length" class="max-h-[520px] overflow-auto"><table class="min-w-full text-sm"><thead class="sticky top-0 z-10 bg-slate-800 text-xs uppercase tracking-wide text-white"><tr><th class="px-5 py-3 text-left">Compte</th><th class="px-5 py-3 text-left">Libellé</th><th class="px-5 py-3 text-right">Débit</th><th class="px-5 py-3 text-right">Crédit</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="item in items" :key="item.id" class="odd:bg-white even:bg-slate-50/60 hover:bg-indigo-50"><td class="px-5 py-3 font-mono font-bold text-slate-900">{{ item.compte }}</td><td class="px-5 py-3 text-slate-600">{{ item.libelle }}</td><td class="px-5 py-3 text-right font-mono tabular-nums">{{ money(item.solde_debiteur) }} DH</td><td class="px-5 py-3 text-right font-mono tabular-nums">{{ money(item.solde_crediteur) }} DH</td></tr></tbody></table></div><div v-else class="px-6 py-14 text-center"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">∅</div><p class="mt-3 font-semibold text-slate-700">Aucune balance importée</p><p class="mt-1 text-sm text-slate-500">Utilisez le formulaire ci-dessus pour commencer.</p></div></section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
