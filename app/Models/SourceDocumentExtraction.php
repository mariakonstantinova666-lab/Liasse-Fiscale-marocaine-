<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceDocumentExtraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_document_id',
        'raw_data',
        'mapped_data',
        'errors',
        'status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'mapped_data' => 'array',
            'errors' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function sourceDocument()
    {
        return $this->belongsTo(SourceDocument::class);
    }
}
