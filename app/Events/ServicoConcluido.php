<?php

namespace App\Events;

use App\Models\OrdemServico;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServicoConcluido
{
    use Dispatchable, SerializesModels;

    public $ordemServico;

    public function __construct(OrdemServico $ordemServico)
    {
        $this->ordemServico = $ordemServico;
    }
}
