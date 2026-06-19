@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Vérification de Cohérence (Modèle Marocain)</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow border-l-4 {{ $equilibreBilan ? 'border-green-500' : 'border-red-500' }}">
            <h3 class="font-bold text-slate-700">Équilibre Actif / Passif</h3>
            <p class="text-2xl {{ $equilibreBilan ? 'text-green-600' : 'text-red-600' }}">
                {{ $equilibreBilan ? 'Équilibré ✅' : 'Erreur ❌' }}
            </p>
            <p class="text-sm text-slate-500">Écart : {{ number_format($ecartBilan, 2) }} DH</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow border-l-4 {{ $equilibreResultat ? 'border-green-500' : 'border-red-500' }}">
            <h3 class="font-bold text-slate-700">Cohérence Résultat CPC / Bilan</h3>
            <p class="text-2xl {{ $equilibreResultat ? 'text-green-600' : 'text-red-600' }}">
                {{ $equilibreResultat ? 'Coherent ✅' : 'Incohérent ❌' }}
            </p>
            <p class="text-sm text-slate-500">Le résultat du T3 doit être au T2 (Passif).</p>
        </div>
    </div>
</div>
@endsection