<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceDocument extends Model
{
    use HasFactory;

    public const STATUS_IMPORTED = 'imported';
    public const STATUS_ANALYZING = 'analyzing';
    public const STATUS_EXTRACTED = 'extracted';
    public const STATUS_NEEDS_VALIDATION = 'needs_validation';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'societe_id',
        'exercice',
        'document_type',
        'tableau_code',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
        'status',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }

    public function extraction()
    {
        return $this->hasOne(SourceDocumentExtraction::class);
    }

    public function fieldSources()
    {
        return $this->hasMany(LiasseFieldSource::class);
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_IMPORTED => 'Importe',
            self::STATUS_ANALYZING => 'Analyse en cours',
            self::STATUS_EXTRACTED => 'Donnees extraites',
            self::STATUS_NEEDS_VALIDATION => 'Validation necessaire',
            self::STATUS_VALIDATED => 'Valide',
            self::STATUS_ERROR => 'Erreur',
        ];
    }
}
