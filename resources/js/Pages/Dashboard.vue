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

// Statut d'historisation : la balance N et la balance N-1 sont-elles importées ?
const nImporte = computed(() => props.exercicesImportes.includes(Number(props.exerciceActif)));
const n1Importe = computed(() => props.exercicesImportes.includes(Number(props.exercicePrecedent)));

const form = useForm({
    annee: props.exerciceActif || '',
    balance: null,
});

const handleSubmit = () => {
    form.post(route('balance.import'), {
        forceFormData: true,
    });
};

// --- Ma société : création (si absente) ou modification ---
// On affiche le formulaire d'emblée tant qu'aucune société n'est configurée,
// car l'import de balance en dépend.
const editingSociete = ref(!props.societe);

const societeForm = useForm({
    nom_societe: props.societe?.nom_societe || '',
    if: props.societe?.if || '',
    ice: props.societe?.ice || '',
    rc: props.societe?.rc || '',
    cnss: props.societe?.cnss || '',
});

const saveSociete = () => {
    societeForm.post(route('societe.save'), {
        preserveScroll: true,
        onSuccess: () => { editingSociete.value = false; },
    });
};

// Liste des tableaux Blade de la liasse pour générer la grille d'accès
const liasseTableaux = [
    { name: 'Bilan Actif', route: 'liasse.bilan_actif', icon: '📊' },
    { name: 'Bilan Passif', route: 'liasse.bilan_passif', icon: '📉' },
    { name: 'CPC (Compte de Produits & Charges)', route: 'liasse.cpc', icon: '💰' },
    { name: 'Passage Fiscal', route: 'liasse.passage_fiscal', icon: '🧮' },
    { name: 'Immobilisations', route: 'liasse.immobilisations', icon: '🏗️' },
    { name: 'Amortissements', route: 'liasse.amortissements', icon: '📉' },
    { name: 'Provisions', route: 'liasse.provisions', icon: '🛡️' },
    { name: 'TVA', route: 'liasse.tva', icon: '📝' },
    { name: 'E.S.G', route: 'liasse.esg', icon: '📈' },
    { name: 'Détail des postes CPC', route: 'liasse.detail_cpc', icon: '🧾' },
    { name: 'Biens en crédit-bail', route: 'liasse.credit_bail', icon: '🚗' },
    { name: 'Plus/moins-values', route: 'liasse.plus_values', icon: '💹' },
    { name: 'Titres de participation', route: 'liasse.titres_participation', icon: '📑' },
    { name: 'Répartition du capital', route: 'liasse.repartition_capital', icon: '🏦' },
    { name: 'Affectation des résultats', route: 'liasse.affectation_resultats', icon: '🪙' },
    { name: 'Calcul impôt (encour.)', route: 'liasse.calcul_impot_encouragement', icon: '🧮' },
    { name: 'Dotations amortissements', route: 'liasse.dotations_amortissements', icon: '📐' },
    { name: 'Plus-values de fusion', route: 'liasse.plus_values_fusion', icon: '🔗' },
    { name: 'Intérêts des emprunts', route: 'liasse.interets_emprunts', icon: '💸' },
    { name: 'Locations et baux', route: 'liasse.locations_baux', icon: '🏢' },
    { name: 'État détaillé des stocks', route: 'liasse.detail_stocks', icon: '📦' },
    { name: 'Opérations en devises', route: 'liasse.operations_devises', icon: '💱' },
    { name: 'Tableau de financement', route: 'liasse.tableau_financement', icon: '🔄' },
    { name: "Méthodes d'évaluation", route: 'liasse.methodes_evaluation', icon: '⚖️' },
    { name: 'État des dérogations', route: 'liasse.derogations', icon: '📋' },
    { name: 'Changements de méthodes', route: 'liasse.changements_methodes', icon: '🔧' },
    { name: 'Calcul IS (encouragées)', route: 'liasse.calcul_is_encouragees', icon: '🏛️' },
    { name: 'Contrôle de cohérence', route: 'liasse.controle', icon: '✅' },
];
</script>

<template>
    <Head title="Tableau de bord" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tableau de bord - Gestion de la Liasse Fiscale
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">🏢 Entreprise Active</h3>
                        <button
                            v-if="societe && !editingSociete"
                            type="button"
                            @click="editingSociete = true"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            ✏️ Modifier
                        </button>
                    </div>

                    <!-- Affichage des infos quand la société existe et qu'on n'édite pas -->
                    <div v-if="societe && !editingSociete" class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 block">Raison Sociale :</span>
                            <strong class="text-gray-800 text-base">{{ societe.nom_societe }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Identifiant Fiscal (IF) :</span>
                            <strong class="text-gray-800">{{ societe.if || 'Non renseigné' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">ICE :</span>
                            <strong class="text-gray-800">{{ societe.ice || 'Non renseigné' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Registre du Commerce (RC) :</span>
                            <strong class="text-gray-800">{{ societe.rc || 'Non renseigné' }}</strong>
                        </div>
                    </div>

                    <!-- Formulaire création / édition de la société -->
                    <div v-else>
                        <p v-if="!societe" class="mb-4 p-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg">
                            Aucune société configurée. Renseignez votre société pour pouvoir importer une balance.
                        </p>
                        <form @submit.prevent="saveSociete" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="lg:col-span-3">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Raison sociale <span class="text-red-500">*</span></label>
                                <input v-model="societeForm.nom_societe" type="text" required
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <p v-if="societeForm.errors.nom_societe" class="text-xs text-red-600 mt-1">{{ societeForm.errors.nom_societe }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Identifiant Fiscal (IF)</label>
                                <input v-model="societeForm.if" type="text"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <p v-if="societeForm.errors.if" class="text-xs text-red-600 mt-1">{{ societeForm.errors.if }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ICE</label>
                                <input v-model="societeForm.ice" type="text"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <p v-if="societeForm.errors.ice" class="text-xs text-red-600 mt-1">{{ societeForm.errors.ice }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Registre du Commerce (RC)</label>
                                <input v-model="societeForm.rc" type="text"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <p v-if="societeForm.errors.rc" class="text-xs text-red-600 mt-1">{{ societeForm.errors.rc }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CNSS</label>
                                <input v-model="societeForm.cnss" type="text"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                <p v-if="societeForm.errors.cnss" class="text-xs text-red-600 mt-1">{{ societeForm.errors.cnss }}</p>
                            </div>
                            <div class="md:col-span-2 lg:col-span-3 flex items-center space-x-3">
                                <button type="submit" :disabled="societeForm.processing"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none disabled:opacity-50">
                                    {{ societeForm.processing ? 'Enregistrement...' : 'Enregistrer la société' }}
                                </button>
                                <button v-if="societe" type="button" @click="editingSociete = false"
                                        class="text-sm font-medium text-gray-500 hover:text-gray-700">
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">📂 Tableaux de la Liasse Fiscale Marocaine</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <a 
                            v-for="tableau in liasseTableaux" 
                            :key="tableau.route"
                            :href="route(tableau.route)"
                            class="p-4 border border-gray-200 rounded-xl hover:border-indigo-500 hover:bg-indigo-50/50 transition flex items-center space-x-3 group text-left"
                        >
                            <span class="text-2xl bg-gray-100 p-2 rounded-lg group-hover:bg-indigo-100 transition">{{ tableau.icon }}</span>
                            <div>
                                <span class="block font-semibold text-gray-800 text-sm group-hover:text-indigo-600 transition">{{ tableau.name }}</span>
                                <span class="text-xs text-gray-400">Consulter le tableau</span>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">📥 Importer une Balance Comptable (N et N-1)</h3>

                    <div v-if="flash?.success" class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                        {{ flash.success }}
                    </div>
                    <div v-if="flash?.error" class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                        {{ flash.error }}
                    </div>

                    <!-- Statut d'historisation : pour alimenter la colonne "Exercice Précédent",
                         il faut importer la balance N puis la balance N-1. -->
                    <div class="mb-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="flex items-center justify-between p-3 rounded-lg border"
                             :class="nImporte ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50'">
                            <span class="text-sm font-medium text-gray-700">
                                Exercice N — <strong>{{ exerciceActif }}</strong>
                            </span>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                  :class="nImporte ? 'bg-green-600 text-white' : 'bg-amber-500 text-white'">
                                {{ nImporte ? '✓ Importé' : 'À importer' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg border"
                             :class="n1Importe ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50'">
                            <span class="text-sm font-medium text-gray-700">
                                Exercice N-1 — <strong>{{ exercicePrecedent }}</strong>
                            </span>
                            <span class="text-xs font-semibold px-2 py-1 rounded-full"
                                  :class="n1Importe ? 'bg-green-600 text-white' : 'bg-amber-500 text-white'">
                                {{ n1Importe ? '✓ Importé' : 'À importer' }}
                            </span>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
                        <div class="w-full md:w-1/4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Année d'exercice</label>
                            <select v-model="form.annee" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">-- Exercice --</option>
                                <option value="2024">2024</option>
                                <option value="2025">2025</option>
                                <option value="2026">2026</option>
                            </select>
                        </div>

                        <div class="w-full md:w-2/4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fichier de la balance (Excel / CSV)</label>
                            <input type="file" @input="form.balance = $event.target.files[0]" required class="w-full text-sm text-gray-500 border border-gray-300 rounded-md p-1 bg-white" />
                        </div>

                        <div class="w-full md:w-1/4">
                            <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none font-semibold">
                                {{ form.processing ? 'Importation...' : "Lancer l'importation" }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">📊 Lignes de la balance comptable</h3>
                    <p v-if="exerciceActif" class="text-sm text-gray-500 mb-4">
                        Exercice affiché : <span class="font-semibold text-indigo-600">{{ exerciceActif }}</span>
                    </p>

                    <div v-if="items && items.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-gray-500 uppercase font-medium">Compte</th>
                                    <th class="px-6 py-3 text-left text-gray-500 uppercase font-medium">Libellé</th>
                                    <th class="px-6 py-3 text-right text-gray-500 uppercase font-medium">Solde Débiteur</th>
                                    <th class="px-6 py-3 text-right text-gray-500 uppercase font-medium">Solde Créditeur</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in items" :key="item.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-mono font-bold text-gray-900">{{ item.compte }}</td>
                                    <td class="px-6 py-4 text-gray-700">{{ item.libelle }}</td>
                                    <td class="px-6 py-4 text-right text-gray-900">
                                        {{ Number(item.solde_debiteur).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} DH
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900">
                                        {{ Number(item.solde_crediteur).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} DH
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-8 text-gray-500">
                        📭 Aucune donnée importée pour cet exercice.
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>