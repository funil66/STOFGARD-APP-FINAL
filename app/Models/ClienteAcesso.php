<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ClienteAcesso — Token de magic link para o portal do cliente final.
 * O cliente não tem login/senha — recebe um link temporário via WhatsApp.
 */
class ClienteAcesso extends Model
{
    protected $table = 'cliente_acessos';

    protected $fillable = [
        'cadastro_id',
        'token',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
        'motivo',
        'resource_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    // =========================================================================
    // Relacionamentos
    // =========================================================================

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cadastro::class, 'cadastro_id');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Gera um novo token seguro de 48 caracteres.
     */
    public static function gerarToken(): string
    {
        return Str::random(48);
    }

    /**
     * Verifica se o token ainda é válido.
     */
    public function estaValido(): bool
    {
        return $this->expires_at->isFuture() && is_null($this->used_at);
    }

    /**
     * Marca o token como usado.
     */
    public function marcarComoUsado(string $ip, string $userAgent): void
    {
        $this->update([
            'used_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Cria um magic link para um cliente com validade de 48h.
     */
    public static function criarParaCliente(
        int $cadastroId,
        string $motivo = 'portal',
        ?int $resourceId = null,
        int $horasValidade = 48
    ): self {
        return static::create([
            'cadastro_id' => $cadastroId,
            'token' => static::gerarToken(),
            'expires_at' => now()->addHours($horasValidade),
            'motivo' => $motivo,
            'resource_id' => $resourceId,
        ]);
    }
}
