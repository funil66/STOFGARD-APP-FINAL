<?php

namespace App\Events;

use App\Models\Financeiro;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PagamentoConfirmado
{
    use Dispatchable, SerializesModels;

    public $financeiro;

    public function __construct(Financeiro $financeiro)
    {
        $this->financeiro = $financeiro;
    }
}
