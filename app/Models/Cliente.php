<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasArquivos;

class Cliente extends Model
{
    use HasFactory, SoftDeletes, HasArquivos;

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
    ];

    protected $casts = [
        'arquivos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
        if (! $this->logradouro || ! $this->cidade) {
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
        if (! $this->celular) {
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
}
