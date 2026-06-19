<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    items: Array,
    societe: Object,
    exerciceActif: [String, Number]
});

const page = usePage();
const flash = computed(() => page.props.flash);

const form = useForm({
    annee: props.exerciceActif || '',
    balance: null,
});

const handleSubmit = () => {
    form.post(route('balance.import'), {
        forceFormData: true,
    });
};

// Liste de tes fichiers Blade de la liasse pour générer le menu
const liasseTableaux = [
    { name: 'Bilan Actif', route: 'liasse.bilan_actif', icon: '📊' },
    { name: 'Bilan Passif', route: 'liasse.bilan_passif', icon: '📉' },
    { name: 'CPC (Compte de Produits & Charges)', route: 'liasse.cpc', icon: '💰' },
    { name: 'Passage Fiscal', route: 'liasse.passage_fiscal', icon: '🧮' },
    { name: 'Amortissements', route: 'liasse.amortissements', icon: '📉' },
    { name: 'Provisions', route: 'liasse.provisions', icon: '🛡️' },
    { name: 'TVA', route: 'liasse.tva', icon: '📝' },
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
                    <h3 class="text-lg font-medium text-gray-900 mb-4">🏢 Entreprise Active</h3>
                    <div v-if="societe" class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
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
                    <h3 class="text-lg font-medium text-gray-900 mb-4">📥 Importer une Balance Comptable</h3>

                    <div v-if="flash?.success" class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg">
                        {{ flash.success }}
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