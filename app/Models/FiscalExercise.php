<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalExercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'societe_id',
        'exercice',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }
}
