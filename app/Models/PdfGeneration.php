<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'orcamento_id',
        'user_id',
        'include_pix',
        'url',
    ];

    public function orcamento()
    {
        return $this->belongsTo(Orcamento::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
