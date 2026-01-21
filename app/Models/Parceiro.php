<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasArquivos;

class Parceiro extends Model
{
    use HasFactory, SoftDeletes, HasArquivos;

    protected $table = 'parceiros';

    protected $fillable = [
        'uuid',
        'tipo',
        'nome',
        'razao_social',
        'cnpj_cpf',
        'email',
        'telefone',
        'celular',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'percentual_comissao',
        'loja_id',
        'ativo',
        'total_vendas',
        'total_comissoes',
        'observacoes',
        'arquivos',
        'registrado_por',
    ];

    protected $casts = [
        'percentual_comissao' => 'decimal:2',
        'total_comissoes' => 'decimal:2',
        'ativo' => 'boolean',
        'arquivos' => 'array',
    ];

    // Relationships
    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }

    // If this is a vendor, it belongs to a Loja (parceiro)
    public function loja()
    {
        return $this->belongsTo(Parceiro::class, 'loja_id');
    }

    // If this is a store (loja), this returns its vendors
    public function vendedores(): HasMany
    {
        return $this->hasMany(Parceiro::class, 'loja_id');
    }

    // Accessors
    public function getEnderecoCompletoAttribute(): string
    {
        $endereco = collect([
            $this->logradouro,
            $this->numero,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->estado,
        ])->filter()->implode(', ');

        return $endereco ?: 'Endereço não cadastrado';
    }

    public function getTipoLabelAttribute(): string
    {
        return $this->tipo === 'loja' ? 'Loja Parceira' : 'Vendedor';
    }

    // UUID routing: use uuid for route-model binding and generate on create
    protected static function booted(): void
    {
        static::creating(function (\App\Models\Parceiro $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function getLinkWhatsappAttribute(): ?string
    {
        if (! $this->celular) {
            return null;
        }

        $celular = preg_replace('/\D/', '', $this->celular);

        return "https://wa.me/55{$celular}";
    }

    public function getLinkMapsAttribute(): string
    {
        if (! $this->logradouro || ! $this->cidade) {
            return '#';
        }

        $endereco = urlencode($this->endereco_completo);

        return "https://www.google.com/maps/search/?api=1&query={$endereco}";
    }
}
