<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiasseFieldSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'societe_id',
        'source_document_id',
        'exercice',
        'tableau_code',
        'cle',
        'valeur',
        'source_type',
        'status',
        'modified_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    public function sourceDocument()
    {
        return $this->belongsTo(SourceDocument::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
