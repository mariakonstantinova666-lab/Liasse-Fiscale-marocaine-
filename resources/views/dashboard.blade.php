<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tableau de bord - Gestion de la Liasse Fiscale') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <h3 class="text-lg font-medium text-gray-900 mb-4">🏢 Entreprise Active</h3>
                @if($societe)
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 block">Raison Sociale :</span>
                            <strong class="text-gray-800 text-base">{{ $societe->nom_societe }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Identifiant Fiscal (IF) :</span>
                            <strong class="text-gray-800">{{ $societe->if ?? 'Non renseigné' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">ICE :</span>
                            <strong class="text-gray-800">{{ $societe->ice ?? 'Non renseigné' }}</strong>
                        </div>
                        <div>
                            <span class="text-gray-500 block">Registre du Commerce (RC) :</span>
                            <strong class="text-gray-800">{{ $societe->rc ?? 'Non renseigné' }}</strong>
                        </div>
                    </div>
                @else
                    <p class="text-red-500 font-medium">⚠️ Aucune société n'est configurée pour ce compte. Veuillez exécuter les seeders.</p>
                @endif
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <h3 class="text-lg font-medium text-gray-900 mb-4">📥 Importer une Balance Comptable</h3>

                @if(session('success'))
                    <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                        <ul class="list-disc pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('balance.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
                    @csrf
                    
                    <div class="w-full md:w-1/4">
                        <label for="annee" class="block text-sm font-medium text-gray-700 mb-1">Année d'exercice</label>
                        <select name="annee" id="annee" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Choisir une année --</option>
                            <option value="2024" {{ session('annee_exercice') == 2024 ? 'selected' : '' }}>2024</option>
                            <option value="2025" {{ session('annee_exercice') == 2025 ? 'selected' : '' }}>2025</option>
                            <option value="2026" {{ session('annee_exercice') == 2026 ? 'selected' : '' }}>2026</option>
                            <option value="2027" {{ session('annee_exercice') == 2027 ? 'selected' : '' }}>2027</option>
                        </select>
                    </div>

                    <div class="w-full md:w-2/4">
                        <label for="balance" class="block text-sm font-medium text-gray-700 mb-1">Fichier de la balance (Excel / CSV)</label>
                        <input type="file" name="balance" id="balance" required class="w-full text-sm text-gray-500 border border-gray-300 rounded-md p-1 bg-white">
                    </div>

                    <div class="w-full md:w-1/4">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Lancer l'importation
                        </button>
                    </div>
                </form>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <h3 class="text-lg font-medium text-gray-900 mb-2">📊 Données de la balance comptable</h3>
                @if(session('annee_exercice'))
                    <p class="text-sm text-gray-500 mb-4">Exercice actif : <span class="font-semibold text-indigo-600">{{ session('annee_exercice') }}</span></p>
                @endif

                @if(count($items) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Compte</th>
                                    <th class="px-6 py-3 text-left font-medium text-gray-500 uppercase">Libellé</th>
                                    <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase">Débiteur</th>
                                    <th class="px-6 py-3 text-right font-medium text-gray-500 uppercase">Créditeur</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-mono font-bold">{{ $item->compte }}</td>
                                        <td class="px-6 py-4 text-gray-700">{{ $item->libelle }}</td>
                                        <td class="px-6 py-4 text-right">{{ number_format($item->solde_debiteur, 2, ',', ' ') }} DH</td>
                                        <td class="px-6 py-4 text-right">{{ number_format($item->solde_crediteur, 2, ',', ' ') }} DH</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        📭 Aucune donnée importée pour cet exercice. Utilisez le formulaire ci-dessus pour charger votre fichier Excel.
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>