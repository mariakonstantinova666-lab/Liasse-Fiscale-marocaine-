<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Societe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom_societe',
        'if',
        'ice',
        'rc',
        'cnss',
    ];

    /**
     * Propriétaire (utilisateur) de la société.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lignes de balance rattachées à la société (toutes années confondues).
     */
    public function balanceItems()
    {
        return $this->hasMany(BalanceItem::class);
    }
}
