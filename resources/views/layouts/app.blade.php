<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liasse Expert - Maria Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #0f172a; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans">
    <div class="flex h-screen overflow-hidden">
        
        <aside class="w-72 bg-slate-950 text-white flex-shrink-0 flex flex-col shadow-2xl">
            <div class="p-6 border-b border-slate-800 flex items-center space-x-3">
                <div class="bg-blue-600 p-2 rounded-lg shadow-lg"><span class="text-xl font-bold text-white">LX</span></div>
                <div>
                    <h1 class="text-lg font-bold tracking-tight text-white">LIASSE EXPERT</h1>
                    <p class="text-[10px] text-blue-400 font-bold uppercase tracking-widest">Système Marocain</p>
                </div>
            </div>
            
            <div class="flex-grow overflow-y-auto sidebar-scroll p-4 space-y-6">
                <a href="{{ route('dashboard') }}" class="flex items-center p-3 rounded-lg transition {{ request()->routeIs('dashboard') ? 'bg-blue-700 text-white shadow-lg shadow-blue-900/20' : 'text-slate-400 hover:bg-slate-900' }}">
                    <span class="mr-3 text-lg">📊</span> <span class="font-medium">Tableau de bord</span>
                </a>

                <div>
                    <p class="text-[10px] font-black text-slate-500 uppercase px-3 mb-3 tracking-[0.2em]">Etats de Synthèse</p>
                    <div class="space-y-1 text-slate-400">
                        <a href="{{ route('liasse.bilan_actif') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.bilan_actif') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.bilan_actif') ? 'text-blue-400' : 'text-slate-600' }}">T1</span> Bilan (Actif)
                        </a>
                        <a href="{{ route('liasse.bilan_passif') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.bilan_passif') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.bilan_passif') ? 'text-blue-400' : 'text-slate-600' }}">T2</span> Bilan (Passif)
                        </a>
                        <a href="{{ route('liasse.cpc') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.cpc') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.cpc') ? 'text-blue-400' : 'text-slate-600' }}">T3</span> CPC (Hors Taxes)
                        </a>
                        <a href="{{ route('liasse.passage_fiscal') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.passage_fiscal') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.passage_fiscal') ? 'text-blue-400' : 'text-slate-600' }}">T4</span> Passage Fiscal (IS)
                        </a>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-black text-slate-500 uppercase px-3 mb-3 tracking-[0.2em]">Annexes principales</p>
                    <div class="space-y-1 text-slate-400">
                        <a href="{{ route('liasse.immobilisations') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.immobilisations') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.immobilisations') ? 'text-blue-400' : 'text-slate-600' }}">T5</span> Immobilisations
                        </a>
                        <a href="{{ route('liasse.amortissements') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.amortissements') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.amortissements') ? 'text-blue-400' : 'text-slate-600' }}">T7</span> Amortissements
                        </a>
                        <a href="{{ route('liasse.provisions') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.provisions') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.provisions') ? 'text-blue-400' : 'text-slate-600' }}">T9</span> Provisions
                        </a>
                        <a href="{{ route('liasse.tva') }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.tva') ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                            <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.tva') ? 'text-blue-400' : 'text-slate-600' }}">T12</span> Détail de la TVA
                        </a>
                    </div>
                </div>

                <div>
                    <p class="text-[10px] font-black text-slate-500 uppercase px-3 mb-3 tracking-[0.2em]">Annexes complémentaires</p>
                    <div class="space-y-1 text-slate-400">
                        @foreach ([
                            ['esg', 'T5', 'E.S.G'],
                            ['detail_cpc', 'T6', 'Détail des postes CPC'],
                            ['credit_bail', 'T7', 'Biens en crédit-bail'],
                            ['plus_values', 'T10', 'Plus/moins-values'],
                            ['titres_participation', 'T11', 'Titres de participation'],
                            ['repartition_capital', 'T13', 'Répartition du capital'],
                            ['affectation_resultats', 'T14', 'Affectation des résultats'],
                            ['calcul_impot_encouragement', 'T15', 'Calcul impôt (encour.)'],
                            ['dotations_amortissements', 'T16', 'Dotations amortissements'],
                            ['plus_values_fusion', 'T17', 'Plus-values de fusion'],
                            ['interets_emprunts', 'T18', 'Intérêts des emprunts'],
                            ['locations_baux', 'T19', 'Locations et baux'],
                            ['detail_stocks', 'T20', 'État détaillé des stocks'],
                            ['operations_devises', 'T21', 'Opérations en devises'],
                            ['tableau_financement', 'T22', 'Tableau de financement'],
                            ['methodes_evaluation', 'T23', "Méthodes d'évaluation"],
                            ['derogations', 'T24', 'État des dérogations'],
                            ['changements_methodes', 'T25', 'Changements de méthodes'],
                            ['calcul_is_encouragees', 'T26', 'Calcul IS (encouragées)'],
                        ] as [$r, $t, $lbl])
                            <a href="{{ route('liasse.'.$r) }}" class="flex items-center p-2 text-sm rounded-md transition {{ request()->routeIs('liasse.'.$r) ? 'text-blue-400 font-bold bg-slate-900' : 'hover:bg-slate-900' }}">
                                <span class="w-8 font-mono text-[10px] {{ request()->routeIs('liasse.'.$r) ? 'text-blue-400' : 'text-slate-600' }}">{{ $t }}</span> {{ $lbl }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-800 bg-slate-900/50">
                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-xs font-bold text-white shadow-md">M</div>
                        <span class="text-xs font-bold text-slate-300 truncate w-24">Maria</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-slate-500 hover:text-red-400 transition">🚪</button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="flex-grow flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 shadow-sm">
                <div class="flex items-center space-x-3">
                    <span class="font-black text-slate-800 uppercase tracking-tight">MARIA DATA SARL AU</span>
                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded-full">2026</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="text-slate-400 hover:text-blue-600 transition">🔔</button>
                </div>
            </header>

            <div class="flex-grow overflow-y-auto bg-slate-50 p-8">
                @if(session('success'))
                    <div class="max-w-7xl mx-auto mb-4 p-3 rounded-lg bg-green-100 text-green-800 text-sm border border-green-200">
                        {{ session('success') }}
                    </div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>