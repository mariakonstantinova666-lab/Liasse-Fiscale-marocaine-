<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompteMapping extends Model
{
    use HasFactory;

    /**
     * On force le modèle à utiliser la nouvelle table.
     */
    protected $table = 'compte_mappings';

    /**
     * Les attributs que l'on peut remplir.
     * J'ai inclus les nouvelles colonnes nécessaires à la liasse fiscale.
     */
    protected $fillable = [
        'racine_compte',
        'libelle',
        'code_edi_brut',
        'code_edi_amort',
        'code_edi_net',
        'code_edi_net_prec',
        'tableau_liasse', // Pour distinguer BILAN_ACTIF, BILAN_PASSIF, CPC
    ];

    /**
     * Par défaut, Laravel cherche created_at et updated_at.
     */
    public $timestamps = true;

    /**
     * Scope pour récupérer facilement les comptes d'un tableau spécifique.
     */
    public function scopeDuTableau($query, $tableau)
    {
        return $query->where('tableau_liasse', $tableau);
    }
}