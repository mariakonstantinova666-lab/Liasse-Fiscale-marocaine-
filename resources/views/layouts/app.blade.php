<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liasse Expert - Maria Data</title>
    <script>
        (() => {
            let theme = 'system';
            try {
                const stored = localStorage.getItem('theme');
                theme = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
            } catch (error) {}
            const systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const dark = theme === 'dark' || (theme === 'system' && systemDark);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.dataset.theme = theme;
            document.documentElement.dataset.resolvedTheme = dark ? 'dark' : 'light';
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>
    @vite('resources/js/app.js')
    <style>
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: #0f172a; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="app-shell lg:h-screen lg:overflow-hidden">
        <div id="sidebar-overlay" class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>
        
        <aside id="app-sidebar" class="app-sidebar">
            <div class="flex h-[72px] items-center gap-3 border-b border-slate-800 bg-slate-900/40 px-5">
                <div class="shell-brand-mark"><span class="text-sm font-black tracking-tight">LX</span></div>
                <div>
                    <h1 class="text-[15px] font-black tracking-tight text-white">LIASSE FISCALE</h1>
                    <p class="mt-0.5 text-[9px] font-bold uppercase tracking-[0.2em] text-slate-400">Gestion fiscale</p>
                </div>
                <button id="sidebar-close" type="button" class="ml-auto rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden" aria-label="Fermer le menu">✕</button>
            </div>
            
            <div id="sidebar-scroll" class="sidebar-scroll flex-grow space-y-5 overflow-y-auto px-3 py-4">
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link-active' : '' }}">
                    <span class="sidebar-code" aria-hidden="true">DB</span> <span>Tableau de bord</span>
                </a>

                <div>
                    <p class="sidebar-section-title mb-3">Tableaux de la liasse</p>
                    <div class="space-y-2">
                        @foreach ([
                            ['label' => 'Synthèse financière', 'items' => [
                                ['bilan_actif', 'T01-A', 'Bilan Actif'],
                                ['bilan_passif', 'T01-B', 'Bilan Passif'],
                                ['cpc', 'T02', 'CPC'],
                                ['passage_fiscal', 'T03', 'Passage fiscal'],
                                ['immobilisations', 'T04', 'Immobilisations'],
                                ['esg', 'T05', 'ESG'],
                                ['detail_cpc', 'T06', 'Détail CPC'],
                            ]],
                            ['label' => 'Immobilisations et risques', 'items' => [
                                ['credit_bail', 'T07', 'Crédit-bail'],
                                ['amortissements', 'T08', 'Amortissements'],
                                ['provisions', 'T09', 'Provisions'],
                                ['plus_values', 'T10', 'Plus/moins-values'],
                                ['titres_participation', 'T11', 'Titres de participation'],
                            ]],
                            ['label' => 'Fiscalité et capital', 'items' => [
                                ['tva', 'T12', 'Détail TVA'],
                                ['repartition_capital', 'T13', 'Répartition du capital'],
                                ['affectation_resultats', 'T14', 'Affectation des résultats'],
                                ['calcul_impot_encouragement', 'T15', 'Calcul impôt encouragement'],
                                ['dotations_amortissements', 'T16', 'Dotations aux amortissements'],
                                ['plus_values_fusion', 'T17', 'Plus-values de fusion'],
                            ]],
                            ['label' => 'Engagements et annexes', 'items' => [
                                ['interets_emprunts', 'T18', 'Intérêts des emprunts'],
                                ['locations_baux', 'T19', 'Locations et baux'],
                                ['detail_stocks', 'T20', 'État détaillé des stocks'],
                                ['operations_devises', 'T21', 'Opérations en devises'],
                                ['tableau_financement', 'T22', 'TFT / Tableau de financement'],
                            ]],
                            ['label' => 'Méthodes et déclarations', 'items' => [
                                ['methodes_evaluation', 'T23', "Méthodes d'évaluation"],
                                ['derogations', 'T24', 'Dérogations'],
                                ['changements_methodes', 'T25', 'Changements de méthodes'],
                                ['calcul_is_encouragees', 'T26', 'Calcul IS entreprises encouragées'],
                            ]],
                        ] as $group)
                            @php
                                $groupIsActive = collect($group['items'])->contains(
                                    fn ($item) => request()->routeIs('liasse.'.$item[0])
                                );
                            @endphp
                            <details class="sidebar-group" {{ $groupIsActive ? 'open' : '' }}>
                                <summary class="sidebar-group-summary {{ $groupIsActive ? 'sidebar-group-summary-active' : '' }}">
                                    <span>{{ $group['label'] }}</span>
                                    <span class="sidebar-group-chevron" aria-hidden="true">›</span>
                                </summary>
                                <div class="space-y-1 pb-1 pt-1.5">
                                    @foreach ($group['items'] as [$r, $t, $lbl])
                                        <a href="{{ route('liasse.'.$r) }}" class="sidebar-link {{ request()->routeIs('liasse.'.$r) ? 'sidebar-link-active' : '' }}">
                                            <span class="sidebar-code">{{ $t }}</span> <span>{{ $lbl }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="sidebar-user-panel">
                <div class="sidebar-user-card">
                    <div class="mb-3 flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-700 text-xs font-bold text-white shadow-sm">
                            {{ strtoupper(mb_substr(auth()->user()?->name ?? 'M', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">Compte utilisateur</p>
                            <p class="truncate text-sm font-bold text-slate-100">{{ auth()->user()?->name ?? 'Maria Konstantinova' }}</p>
                        </div>
                    </div>
                    <a href="{{ route('settings.index') }}" class="mb-2 flex w-full items-center justify-center rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-slate-300 transition hover:border-blue-400/60 hover:bg-blue-500/10 hover:text-blue-100">
                        Paramètres
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-xs font-bold text-slate-300 transition hover:border-red-400/60 hover:bg-red-500/10 hover:text-red-200">
                            <span aria-hidden="true">↪</span>
                            <span>Déconnexion</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="flex min-h-screen min-w-0 flex-grow flex-col lg:min-h-0">
            <header class="shell-header sticky top-0 z-30 flex h-[72px] items-center justify-between px-4 sm:px-6 lg:static lg:px-8">
                <div class="flex items-center space-x-3">
                    <button id="sidebar-open" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-800 dark:border-slate-700 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:bg-slate-800 lg:hidden" aria-label="Ouvrir le menu">☰</button>
                    <div class="min-w-0">
                        <span class="block max-w-[55vw] truncate text-sm font-bold tracking-tight text-slate-900 dark:text-slate-100">Liasse fiscale marocaine</span>
                        <span class="hidden text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400 sm:block">Espace de production fiscale</span>
                    </div>
                    <span class="status-pill border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-200">Exercice {{ session('annee_exercice', 2026) }}</span>
                </div>
            </header>

            <div class="shell-content">
                @if(session('success'))
                    <div class="max-w-7xl mx-auto mb-4 p-3 rounded-lg bg-green-100 text-green-800 text-sm border border-green-200 dark:border-green-800 dark:bg-green-500/10 dark:text-green-300">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="liasse-ui">
                    @yield('content')
                </div>
            </div>
        </main>
    </div>
    <script>
        (() => {
            const sidebar = document.getElementById('app-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const scrollContainer = document.getElementById('sidebar-scroll');
            const openButton = document.getElementById('sidebar-open');
            const closeButton = document.getElementById('sidebar-close');
            const scrollKey = 'liasse.sidebar.scrollTop';
            const open = () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); };
            const close = () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); };
            const saveSidebarScroll = () => {
                if (scrollContainer) sessionStorage.setItem(scrollKey, String(scrollContainer.scrollTop));
            };
            const restoreSidebarScroll = () => {
                const value = sessionStorage.getItem(scrollKey);
                if (scrollContainer && value !== null) scrollContainer.scrollTop = Number(value) || 0;
            };
            restoreSidebarScroll();
            requestAnimationFrame(restoreSidebarScroll);
            scrollContainer?.addEventListener('scroll', saveSidebarScroll, { passive: true });
            scrollContainer?.querySelectorAll('a[href]')?.forEach(link => link.addEventListener('click', saveSidebarScroll));
            openButton?.addEventListener('click', open);
            closeButton?.addEventListener('click', close);
            overlay?.addEventListener('click', close);
            document.addEventListener('keydown', event => { if (event.key === 'Escape') close(); });
        })();
    </script>
</body>
</html>

