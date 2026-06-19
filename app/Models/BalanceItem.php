<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BalanceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'societe_id', // INDISPENSABLE : Ajoute cette ligne ici
        'compte',
        'libelle',
        'solde_debiteur',
        'solde_crediteur',
        'exercice',
    ];

    /**
     * Relation avec la société
     */
    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }
}