<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiasseData extends Model
{
    use HasFactory;

    // Nom de la table
    protected $table = 'liasse_data';

    // Champs que l'on peut remplir
    protected $fillable = [
        'user_id',
        'exercice',
        'tableau_code',
        'cle',
        'valeur',
    ];

    // Relation avec l'utilisateur
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}