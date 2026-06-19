@extends('layouts.app') {{-- Modifiez par layouts.master ou votre layout principal si nécessaire --}}

@section('content')
<div class="container-fluid py-4" style="background-color: #f8f9fc;">
    
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 bg-white shadow-sm rounded">
        <div>
            <h1 class="h4 mb-1 text-gray-800 font-weight-bold">TABLEAU DES IMMOBILISATIONS AUTRES QUE LES IMMOBILISATIONS EN NON-VALEURS</h1>
            <p class="text-muted mb-0 small">EXERCICE : <strong>{{ $exercice ?? session('annee_exercice', 2025) }}</strong></p>
        </div>
        <div class="text-right">
            <span class="badge badge-primary p-2" style="font-size: 0.9rem;">TABLEAU N° 2</span>
        </div>
    </div>

    @php
        $globalDebut = 0;
        $globalAcquisition = 0;
        $globalVirementAug = 0;
        $globalCession = 0;
        $globalVirementDim = 0;
        $globalFin = 0;

        if (isset($totauxImmo) && is_array($totauxImmo)) {
            foreach ($totauxImmo as $sub) {
                $globalDebut += $sub->debut ?? 0;
                $globalAcquisition += $sub->acquisition ?? 0;
                $globalVirementAug += $sub->virement_aug ?? 0;
                $globalCession += $sub->cession ?? 0;
                $globalVirementDim += $sub->virement_dim ?? 0;
                $globalFin += $sub->fin ?? 0;
            }
        }
    @endphp

    <div class="card shadow mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered m-0 align-middle text-dark" style="min-width: 1100px;">
                    <thead class="bg-secondary text-white text-center small font-weight-bold">
                        <tr>
                            <th rowspan="2" class="align-middle" style="width: 32%; background-color: #4e73df;">NATURE DES IMMOBILISATIONS</th>
                            <th rowspan="2" class="align-middle" style="background-color: #4e73df;">MONTANT BRUT AU DEBUT<br>DE L'EXERCICE</th>
                            <th colspan="2" class="bg-success text-white py-1" style="border-bottom: 1px solid rgba(255,255,255,0.2);">AUGMENTATIONS</th>
                            <th colspan="2" class="bg-danger text-white py-1" style="border-bottom: 1px solid rgba(255,255,255,0.2);">DIMINUTIONS</th>
                            <th rowspan="2" class="align-middle" style="background-color: #4e73df;">MONTANT BRUT A LA FIN<br>DE L'EXERCICE</th>
                        </tr>
                        <tr>
                            <th class="bg-success text-white font-weight-normal py-1" style="font-size: 0.8rem;">Acquisitions</th>
                            <th class="bg-success text-white font-weight-normal py-1" style="font-size: 0.8rem;">Virements poste à poste</th>
                            <th class="bg-danger text-white font-weight-normal py-1" style="font-size: 0.8rem;">Cessions ou retraits</th>
                            <th class="bg-danger text-white font-weight-normal py-1" style="font-size: 0.8rem;">Virements poste à poste</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($immoData) && (is_array($immoData) || is_object($immoData)))
                            @foreach($immoData as $rubrique => $lignes)
                                
                                <tr style="background-color: #eaecf4;" class="font-weight-bold text-dark">
                                    <td colspan="7" class="pl-3 py-2 text-uppercase font-weight-bold" style="letter-spacing: 0.3px;">
                                        {{ $rubrique }}
                                    </td>
                                </tr>

                                @foreach($lignes as $nomLigne => $donnees)
                                    <tr>
                                        <td class="pl-4 text-secondary font-italic py-2">{{ $nomLigne }}</td>
                                        
                                        <td class="text-right pr-3 font-weight-bold font-monospace">
                                            {{ number_format($donnees->debut ?? 0, 2, ',', ' ') }}
                                        </td>
                                        
                                        <td class="text-right text-success pr-3 font-monospace">
                                            {{ number_format($donnees->acquisition ?? 0, 2, ',', ' ') }}
                                        </td>
                                        
                                        <td class="text-right text-muted pr-3 font-monospace">
                                            {{ number_format($donnees->virement_aug ?? 0, 2, ',', ' ') }}
                                        </td>
                                        
                                        <td class="text-right text-danger pr-3 font-monospace">
                                            {{ number_format($donnees->cession ?? 0, 2, ',', ' ') }}
                                        </td>
                                        
                                        <td class="text-right text-muted pr-3 font-monospace">
                                            {{ number_format($donnees->virement_dim ?? 0, 2, ',', ' ') }}
                                        </td>
                                        
                                        <td class="text-right font-weight-bold pr-3 font-monospace" style="background-color: #f8f9fc;">
                                            {{ number_format($donnees->fin ?? 0, 2, ',', ' ') }}
                                        </td>
                                    </tr>
                                @endforeach

                                @if(isset($totauxImmo[$rubrique]))
                                    @php $subTotal = $totauxImmo[$rubrique]; @endphp
                                    <tr class="font-weight-bold table-light" style="border-top: 1px dashed #6c757d; border-bottom: 2px solid #6c757d;">
                                        <td class="pl-3 text-uppercase text-primary font-weight-bold">TOTAL {{ $rubrique }}</td>
                                        <td class="text-right pr-3 font-monospace">{{ number_format($subTotal->debut ?? 0, 2, ',', ' ') }}</td>
                                        <td class="text-right text-success pr-3 font-monospace">{{ number_format($subTotal->acquisition ?? 0, 2, ',', ' ') }}</td>
                                        <td class="text-right pr-3 font-monospace">{{ number_format($subTotal->virement_aug ?? 0, 2, ',', ' ') }}</td>
                                        <td class="text-right text-danger pr-3 font-monospace">{{ number_format($subTotal->cession ?? 0, 2, ',', ' ') }}</td>
                                        <td class="text-right pr-3 font-monospace">{{ number_format($subTotal->virement_dim ?? 0, 2, ',', ' ') }}</td>
                                        <td class="text-right pr-3 text-primary font-monospace">{{ number_format($subTotal->fin ?? 0, 2, ',', ' ') }}</td>
                                    </tr>
                                @endif

                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted font-italic">
                                    Aucune ligne d'immobilisation trouvée pour cet exercice.
                                </td>
                            </tr>
                        @endif
                    </tbody>

                    <tfoot class="bg-dark text-white font-weight-bold" style="border-top: 3px double #2e2f37;">
                        <tr>
                            <td class="pl-3 py-3 align-middle uppercase" style="font-size: 0.95rem;">TOTAL GÉNÉRAL</td>
                            
                            <td class="text-right pr-3 align-middle font-monospace" style="font-size: 1rem;">
                                {{ number_format($totalGeneral->debut ?? $totalGeneral->col1 ?? $globalDebut, 2, ',', ' ') }}
                            </td>
                            
                            <td class="text-right pr-3 align-middle text-success font-monospace" style="font-size: 1rem;">
                                {{ number_format($totalGeneral->acquisition ?? $totalGeneral->col2 ?? $globalAcquisition, 2, ',', ' ') }}
                            </td>
                            
                            <td class="text-right pr-3 align-middle font-monospace" style="font-size: 1rem;">
                                {{ number_format($totalGeneral->virement_aug ?? $globalVirementAug, 2, ',', ' ') }}
                            </td>
                            
                            <td class="text-right pr-3 align-middle text-danger font-monospace" style="font-size: 1rem;">
                                {{ number_format($totalGeneral->cession ?? $totalGeneral->col3 ?? $globalCession, 2, ',', ' ') }}
                            </td>
                            
                            <td class="text-right pr-3 align-middle font-monospace" style="font-size: 1rem;">
                                {{ number_format($totalGeneral->virement_dim ?? $globalVirementDim, 2, ',', ' ') }}
                            </td>
                            
                            <td class="text-right pr-3 align-middle text-warning font-monospace" style="font-size: 1rem; background-color: #23242a;">
                                {{ number_format($totalGeneral->fin ?? $totalGeneral->col4 ?? $globalFin, 2, ',', ' ') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection