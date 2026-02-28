<?php

namespace App\Models;

use App\Traits\BelongsToTenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasArquivos;
use Spatie\MediaLibrary\HasMedia;

class Cliente extends Model implements HasMedia, \OwenIt\Auditing\Contracts\Auditable
{
    use HasFactory, SoftDeletes, HasArquivos, \OwenIt\Auditing\Auditable;
    use BelongsToTenant;

    protected $fillable = [
        'uuid',
        'nome',
        'email',
        'telefone',
        'celular',
        'cpf_cnpj',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'observacoes',
        'arquivos',
        'registrado_por',
        'chave_pix',
        'comissao_percentual',
        'dados_bancarios',
    ];

    protected $casts = [
        'arquivos' => 'array',
        'comissao_percentual' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documentos_cadastro')
            ->useDisk('public');
    }

    /**
     * Check if a feature flag is enabled for this client.
     */
    public function hasFeature(string $key): bool
    {
        return (bool) data_get($this->features ?? [], $key, false);
    }

    // UUID routing: use uuid for route-model binding and generate on create
    protected static function booted(): void
    {
        static::creating(function (\App\Models\Cliente $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Enable a feature flag for this client and persist it.
     */
    public function enableFeature(string $key): void
    {
        $features = $this->features ?? [];
        data_set($features, $key, true);
        $this->features = $features;
        $this->save();
    }

    /**
     * Disable a feature flag for this client and persist it.
     */
    public function disableFeature(string $key): void
    {
        $features = $this->features ?? [];
        data_set($features, $key, false);
        $this->features = $features;
        $this->save();
    }



    /**
     * Retorna endereço completo formatado
     */
    public function getEnderecoCompletoAttribute(): string
    {
        $partes = array_filter([
            $this->logradouro,
            $this->numero ? "nº {$this->numero}" : null,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->estado,
            $this->cep ? "CEP: {$this->cep}" : null,
        ]);

        return implode(', ', $partes) ?: 'Endereço não informado';
    }

    /**
     * Retorna link do Google Maps/Waze
     */
    public function getLinkMapsAttribute(): string
    {
        if (!$this->logradouro || !$this->cidade) {
            return '#';
        }

        $endereco = urlencode($this->endereco_completo);

        return "https://www.google.com/maps/search/?api=1&query={$endereco}";
    }

    /**
     * Retorna link do WhatsApp
     */
    public function getLinkWhatsappAttribute(): ?string
    {
        if (!$this->celular) {
            return null;
        }

        $numero = preg_replace('/[^0-9]/', '', $this->celular);

        return "https://wa.me/55{$numero}";
    }

    /**
     * Relacionamento com Ordens de Serviço
     */
    public function ordensServico(): HasMany
    {
        return $this->hasMany(\App\Models\OrdemServico::class, 'cliente_id');
    }

    /**
     * Relacionamento para vincular um vendedor a sua loja (parent)
     */
    public function loja()
    {
        return $this->belongsTo(\App\Models\Cliente::class, 'parent_id')->where('tipo', 'loja');
    }

    /**
     * Uma Loja tem muitos Vendedores
     */
    public function vendedores()
    {
        return $this->hasMany(\App\Models\Cliente::class, 'parent_id')->where('tipo', 'vendedor');
    }

    /**
     * Escopo: Filtra apenas Parceiros (Lojas, Vendedores, Arquitetos)
     */
    public function scopeParceiros($query)
    {
        return $query->whereIn('tipo', ['loja', 'vendedor', 'arquiteto']);
    }

    /**
     * Escopo: Filtra apenas Clientes Finais
     */
    public function scopeClientes($query)
    {
        return $query->where('tipo', 'cliente');
    }
}
