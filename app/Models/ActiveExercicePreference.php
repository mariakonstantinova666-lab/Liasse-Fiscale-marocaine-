<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActiveExercicePreference extends Model
{
    protected $fillable = ['user_id', 'societe_id', 'exercice'];

    protected $casts = ['exercice' => 'integer'];
}
